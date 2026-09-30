# Retención de snapshots `run-*.json`

## El problema, inspeccionado antes de tocar nada

Durante la fase anterior (Q01+Q04), `Planner::build()` pasó de tardar segundos a colgarse
varios minutos. Diagnóstico confirmado con evidencia directa (no supuesto): el directorio
privado (`psindustrial-importer-private/`, fuera del repositorio y del webroot) había
acumulado **447 archivos `run-*.json` (≈808 MB de los ≈953 MB totales del directorio)** en
una única sesión larga de pruebas repetidas.

### Quién los crea, quién los lee

- **Escritor único**: `Planner::build()` — cada llamada genera un UUID de ejecución y
  escribe `Storage::write('run-' . $run . '.json', $plan)` con el plan completo (DRY RUN),
  al final del método, siempre.
- **Lectores**: `Runner::batch($run, ...)` lee **un** `run-<id>.json` — el de la ejecución
  concreta que se le pide continuar, identificado por su propio `run_id`, nunca "el más
  reciente" ni "todos". Ningún otro método del plugin, en ningún punto, enumera o relee
  snapshots antiguos.
- **Conclusión verificada**: un `run-*.json` sólo importa mientras su propio `run_id` está
  en uso activo (una ejecución `subset` en curso, reanudable). Pasado ese uso, es un DRY
  RUN reproducible — `Planner::build()` genera un plan nuevo, byte a byte determinista
  frente a los mismos datos fuente, en cualquier momento — y no hay ninguna ruta de código
  que dependa de uno viejo para auditoría, identidad o rollback.

### Qué NO se confundió con snapshots descartables

El mismo directorio contiene, además: `identity-*.json` (15 archivos — estado real de los
objetos WordPress ya aplicados en el ensayo, imprescindible para que
`Identity::prediction()` siga detectando `UNCHANGED`/`CONFLICT` correctamente),
`backup-*.json` y 22 directorios `backup-uploads-*` (seguridad de rollback de ejecuciones
reales), `log-*.jsonl` (245 archivos, auditoría append-only, 653 KB en total — trivial),
`asset-*.bin` (229 archivos, copias en staging de binarios, 65 MB). Ninguno de estos se
toca: la política de retención implementada actúa **exclusivamente** sobre archivos cuyo
nombre coincide, de forma exacta, con el patrón que sólo `Planner::build()` genera.

## Política implementada

`Storage::retain_recent_runs(int $keep = 30, ?string $dir = null, ?callable $unlink =
null): array`, en `migration/Storage.php`. Conserva los 30 `run-*.json` más recientes
(por fecha de modificación); elimina el resto.

### Seguridad de la limpieza

- Patrón estricto: `^run-[a-zA-Z0-9-]+\.json$`, exacto, aplicado sobre el nombre de
  archivo — nunca una coincidencia parcial. `run-decisions-abc.json` sería técnicamente
  válido para el patrón (es un nombre, no una lista de excepciones por significado), pero
  **ningún código del plugin genera jamás un nombre así**: sólo `Planner::build()` escribe
  a este patrón, siempre con un UUID v4 real.
- Cada candidato se resuelve con `realpath()` y debe quedar **directamente dentro** del
  directorio base, ser un archivo plano (nunca un directorio con el mismo nombre) y nunca
  un symlink/reparse point — rechazados explícitamente antes de considerarse candidato.
- Nunca opera fuera del directorio recibido: no hay comodín destructivo (`rm -rf`) ni
  construcción de ruta a partir de datos externos.
- Orden determinista: fecha de modificación descendente; en caso de empate exacto, nombre
  de archivo descendente como desempate — nunca depende del orden, no garantizado por el
  sistema operativo, en que `scandir()` devuelve los archivos.

### Cuándo se ejecuta

Inmediatamente **después** de que `Storage::write()` confirma haber persistido el snapshot
nuevo — nunca antes. Si la escritura del snapshot actual fallara, `Storage::write()` ya
lanza su propia excepción y el método completo aborta antes de llegar a la limpieza: nunca
se puede perder historial por limpiar antes de saber que el nuevo snapshot quedó guardado.

### Nunca hace fallar un DRY RUN válido

`Planner::build()` envuelve la llamada en su propio `try`/`catch`:

```php
try { $plan['retention'] = Storage::retain_recent_runs(); }
catch ( \Throwable $error ) { $plan['retention'] = array( 'kept' => null, 'deleted' => array(), 'failed' => array(), 'error' => $error->getMessage() ); }
```

Y dentro de `retain_recent_runs()` mismo, cada `unlink()` individual que falla (permiso
denegado, archivo bloqueado, etc.) se recoge en `failed` sin lanzar — la limpieza sigue con
el resto de candidatos. Un fallo de mantenimiento se reporta (`error_log()` más el propio
array de retorno, visible en `$plan['retention']`); nunca interrumpe la construcción del
plan ni invalida el DRY RUN.

## Resultado en producción

Verificado en la primera ejecución real tras el despliegue: el directorio ya tenía más de
30 snapshots acumulados por las pruebas de esta misma fase; la primera llamada a
`Planner::build()` reportó `{"kept":30,"deleted":["run-1297ed36-..."],"failed":[]}` —
la política actuó de inmediato, sin intervención manual.

## Trazabilidad

Ningún CSV de auditoría nuevo: el comportamiento es completamente determinista y se
verifica por test ([18-tests-small-decisions.md](18-tests-small-decisions.md)), no por
inspección de datos de catálogo.

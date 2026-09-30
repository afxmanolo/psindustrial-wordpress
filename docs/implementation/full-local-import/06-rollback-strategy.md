# Estrategia de recuperación

## Lo que ya existía, y dónde vivía realmente

Antes de esta fase, un backup completo de base de datos (esquema + filas de cada tabla con
prefijo del sitio, como JSON privado) y de `uploads/` (copia recursiva) **ya existía como
código** — pero sólo dentro de `tests/importer.php`, escrito inline, nunca expuesto como un
método reutilizable ni invocado automáticamente por `Runner`. El ensayo subset lo ejecuta
manualmente antes de su primer lote porque el propio test lo hace explícito; nada obligaba a
que ocurriera.

## El análisis pedido: ¿es suficiente para ~500 CREATE?

**Sí, para esta instalación concreta, con una condición: que deje de ser opcional.** Razones:

- Es una base de desarrollo local. Incluso después de una importación completa (526 filas
  mutables: productos, categorías, marcas, medios, páginas), el volumen total de filas
  (posts + postmeta + term_relationships + etc.) sigue siendo del orden de unas pocas
  decenas de miles de filas — un `SELECT *` por tabla, volcado a JSON, no es distinto en
  naturaleza de lo que el ensayo subset ya demostró que funciona, sólo mayor en cantidad.
- Es una recuperación **completa y exacta**: un dump de esquema + filas permite reconstruir
  el estado exacto de cada tabla, no una aproximación. No hay pérdida de fidelidad frente a
  `mysqldump`.
- Añadir `mysqldump` (shell-out a un binario externo) introduciría una dependencia y una
  superficie nueva (ruta del binario, permisos de ejecución de procesos, parseo de su
  salida) que este proyecto no necesita para resolver un problema que el mecanismo ya
  existente resuelve igual de bien a esta escala. Contradice el principio explícito de este
  proyecto de preferir soluciones nativas/simples sobre dependencias nuevas.

**La condición que faltaba, y que esta fase corrige**: el mecanismo estaba escrito una sola
vez, sólo para el test, nunca reutilizable. Se promovió a `Storage::backup_database(string
$runId)` y `Storage::backup_uploads(string $runId)` — mismos formatos, misma ubicación
privada, ahora invocadas **automáticamente y de forma obligatoria** por
`batch_full_local_resolved_only()` en la primera llamada de cada `run_id` (cuando
`cursor===0` y `status==='VALIDATED'`, es decir, antes de que exista ningún resultado). Una
llamada de reanudación (`cursor>0`) verifica que el backup ya quedó registrado en el propio
plan (`plan['backup']['database']`) — si no está, lanza `BACKUP_MISSING_FOR_IN_PROGRESS_RUN`
en vez de continuar sin red de seguridad o volver a respaldar innecesariamente en cada lote.

Ambos métodos exigen `Storage::guard()` (nunca corren fuera de local) y escriben exclusivamente
bajo el directorio privado (`Storage::root()`), nunca dentro del repositorio ni del webroot —
verificado por la ya existente protección de `Storage::path()`/`Storage::root()` contra rutas
dentro del webroot.

## Qué puede revertirse automáticamente

`Runner::rollback_created()` (existente, ahora también aceptando `scope='full'` además de
`'subset'` — un único carácter de condición ampliado, ninguna lógica nueva): elimina
únicamente creaciones **limpias**, verificadas contra su propio journal
(`identity-<hash>.json`) con el hash exacto de cuando se crearon. Rechaza explícitamente:
términos con objetos/hijos o ya públicos, posts que ya no están en `draft`, cualquier objeto
cuyo hash actual no coincida con el registrado en su journal (evidencia de que algo más lo
tocó después). Esto es correcto y suficiente para "deshacer un ensayo que salió mal antes de
que nadie más tocara nada" — exactamente el caso de uso que ya prueba con los objetos
sintéticos de esta fase.

## Qué necesita el backup, no rollback automático

- **Actualizaciones** (`UPDATE`, un objeto que ya existía y cuyo contenido cambió): el
  método de rollback deliberadamente no las toca — no hay snapshot "antes" suficientemente
  seguro para revertir un `UPDATE` sin el backup completo. Restaurar el `before` de la fila
  concreta desde su journal, o restaurar la tabla completa desde `backup-<run>.json`, son las
  dos vías; la segunda es la más simple y la que este proyecto ya usa.
- **Conflictos** (`CONFLICT`): por definición, el objeto ya tiene una edición humana que el
  sistema se niega a pisar — no hay "deshacer" automático seguro; requiere revisión manual.
- **Una corrida muy grande, ya avanzada**, donde revertir cientos de creaciones limpias una
  por una sería lento y, si algo intermedio falló a medias, arriesgado: el backup completo
  (restaurar las tablas del backup JSON) es la vía realista, no una llamada masiva a
  `rollback_created()`.

## Qué no debe intentarse

Nunca revertir los 15 objetos reales del ensayo subset ya existentes — ninguna prueba de esta
fase los toca, y `rollback_created()` en sí sólo revertiría creaciones marcadas con el
`run_id` que se le pase explícitamente, nunca "todo lo que parezca de la migración". Nunca un
`rm`/`DROP` directo de tablas como mecanismo de "empezar de cero" — si algún día hiciera
falta, es una decisión humana explícita, fuera del alcance de este mecanismo.

## Probado, sólo con fixtures

`Runner::rollback_created()` se probó exclusivamente sobre entidades sintéticas
(`test:<uuid>`) creadas y destruidas por los propios tests de esta fase — nunca sobre los 15
objetos reales del ensayo, confirmado explícitamente por una aserción que revisa que ninguna
clave `sql:productos:`/`category:`/etc. real aparece en el resultado del rollback probado.

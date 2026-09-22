# Capas de protección

Ninguna capa existente se debilitó. Todas las siguientes deben cumplirse, en este orden
efectivo, antes de que `batch_full_local_resolved_only()` toque un solo objeto.

## 1. `Storage::guard()` — sin cambios, incondicional

```php
if ( ! current_user_can('manage_options') || ! current_user_can('psi_manage_migration') ) throw PERMISSION_DENIED;
if ( 'local' !== wp_get_environment_type() || 'psindustrial_wp_dev' !== DB_NAME || !preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/D', DB_HOST) ) throw LOCAL_DATABASE_REQUIRED;
```

Llamado como PRIMERA línea de `batch_full_local_resolved_only()`, y de nuevo dentro de
`Storage::locked()`, y de nuevo dentro de `self::apply()` por cada entidad. **Esta es la única
capa que decide "local sí / producción no"** — nunca se reimplementó una versión "blanda" de
esta comprobación en ningún sitio nuevo. `WP_ENVIRONMENT_TYPE='local'`, `DB_NAME=
'psindustrial_wp_dev'`, `DB_HOST='localhost'` están fijados en `wp-config.php`; no son
alterables desde código de la migración.

## 2. Frase de confirmación exclusiva

`Runner::FULL_CONFIRMATION = 'IMPORTAR FULL LOCAL RESUELTO'` — comparación literal
(`!==`), nunca reutiliza ni deriva de `'IMPORTAR SUBSET EN BORRADOR'`. Ver
[01-execution-model.md](01-execution-model.md) para la prueba de que ninguna frase autoriza
el scope de la otra.

## 3. Forma y sello del plan

- `plan['scope'] === 'full'` (nunca `'subset'`).
- `plan['status'] ∈ {VALIDATED, RUNNING, COMPLETE}`.
- `plan['environment_id']` coincide con `Storage::hash([home_url(), DB_NAME])` del entorno
  actual — un plan generado en otra instalación/DB nunca se ejecuta aquí, sea cual sea la
  frase.
- `plan['plan_hash'] === Planner::digest(plan)` — cualquier edición del archivo tras
  construirlo invalida el sello.
- `Planner::VERSION === plan['transform_version']`.
- `plan['decisions_hash']` coincide con el hash actual de `subset-decisions.json`
  (`Planner::decisions()`).
- Cada ruta en `plan['sources']` re-hashea igual que en el momento de construir el plan —
  **ampliado en esta fase**: `Sources.php` ahora también huella `editorial-decisions.json` y
  `pdf-security-review/pdf-approvals.json` (más `sanitization-audit.json` si existe), que
  antes NUNCA entraban en esta comprobación pese a que las decisiones Q01-Q13 dependen
  literalmente de su contenido. Ver la nota en el propio código de `Sources.php`.

## 4. Techo de cordura (no un límite operativo)

`MAX_FULL_MUTABLE = 5000` — nunca se espera alcanzarlo (el catálogo real tiene 526 filas
mutables); existe sólo para rechazar un plan corrupto/gigantesco por error, el mismo espíritu
que el `SUBSET_LIMIT_EXCEEDED` de 25 ya tiene para subset, escalado para el propósito
distinto de esta ruta.

## 5. Pre-flight — 14 verificaciones, obligatorio, nunca opcional

`batch_full_local_resolved_only()` llama a `preflight_full_local()` **internamente**, antes
de tocar nada; si `ok !== true`, lanza `PREFLIGHT_FAILED:<ids>` y no se ejecuta ni una fila.
Detalle completo en [03-preflight.md](03-preflight.md).

## 6. Backup obligatorio antes del primer cambio

Ver [06-rollback-strategy.md](06-rollback-strategy.md).

## 7. Clasificación fatal vs. por entidad

Un error de una sola entidad (hash de origen cambiado, dependencia no aplicada, escritura de
WordPress rechazada, cualquier fallo inyectado en pruebas) se captura, se marca `FAILED` en
el resultado de esa entidad, y el lote **continúa** con la siguiente — exactamente el
comportamiento que `subset` siempre ha tenido, sin cambios.

Un error de infraestructura — permiso denegado, base de datos incorrecta, almacenamiento
privado inaccesible/dentro del webroot, journal no se pudo escribir, activo copiado corrupto,
escritor ocupado/con lease activa, symlink rechazado — **aborta el lote completo**: se
relanza la excepción, el cursor queda exactamente donde estaba (la entidad que falló nunca
se marca, nunca avanza), y la próxima llamada puede diagnosticar y decidir cómo continuar.
Lista exacta en `Runner::FATAL_ERROR_CODES` (código, no heurística de texto libre) —
deliberadamente una lista de permitidos (nunca "todo lo que no reconozco es fatal"), así que
un fallo inyectado en pruebas con un mensaje no listado — o cualquier fallo legítimo futuro
con un código todavía no catalogado — nunca aborta el lote entero por error; sólo los códigos
explícitamente listados lo hacen. Ver [08-tests.md](08-tests.md) para la prueba de ambos
caminos con fixtures.

Esta distinción **no existía antes de esta fase** en absoluto: `batch()` (subset) sigue sin
ella deliberadamente, para no arriesgar ningún cambio de comportamiento sobre la ruta ya
probada con los 15 objetos reales — sólo el nuevo método la aplica.

## 8. Retención de snapshots nunca borra un run activo

Ver [04-batching-resume.md](04-batching-resume.md), sección de retención.

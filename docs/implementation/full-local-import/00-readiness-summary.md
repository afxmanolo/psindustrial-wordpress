# FULL LOCAL RESOLVED-ONLY import — preparación

Rama `feature/review-resolution`. Esta fase **no ejecuta** ninguna importación real. Prepara,
implementa y prueba la ruta de ejecución que permitirá, con autorización humana explícita
posterior, importar en `psindustrial_wp_dev` únicamente las filas ya resueltas
(CREATE/MERGE/UPDATE/UNCHANGED/SKIP) de un plan `full`, sin tocar ninguna de las 339 filas
REVIEW.

## Qué existía antes de esta fase

`Runner::batch()` sólo aceptaba planes `scope=subset`, confirmados con la frase
`IMPORTAR SUBSET EN BORRADOR`, limitados a 25 objetos mutables — la única ejecución real que
este proyecto ha hecho hasta ahora son los 15 objetos del ensayo. Un plan `full` era, y sigue
siendo, categóricamente imposible de ejecutar por esa vía: verificado explícitamente por un
test ya existente (`tests/importer.php`, "Full real import explicitly blocked").

## Qué se construyó

Una **segunda ruta de ejecución**, `Runner::batch_full_local_resolved_only()`, con su propia
frase de confirmación (`IMPORTAR FULL LOCAL RESUELTO`), su propio pre-flight de 14
verificaciones, su propio backup automático obligatorio, y su propia clasificación de errores
fatal-vs-por-entidad — **sin modificar ni relajar** la ruta subset existente (sus propios
tests, incluidos los 67 que ejercitan la ejecución HTTP real sobre los 15 objetos ya creados,
siguen pasando sin cambios). La lógica de aplicación en sí (`self::apply()`,
`Identity::prediction()`, el manejo de dependencias/conflictos) es literalmente la misma para
ambas rutas — extraída a un método compartido (`process_entry()`) para que "Runner sólo
ejecuta el plan" siga siendo cierto.

## Resultado contra el plan real actual (sección 17 de la tarea)

Pre-flight ejecutado contra el plan `full` real, sin ejecutar nada:

```
TOTAL      2.399
UNCHANGED     15
SKIP       1.534
CREATE       511   (incluye 49 MERGE, ver 01-execution-model.md)
REVIEW       339
ERROR          0
```

Coincide exactamente con el baseline dado, sin forzar ningún número. **Pre-flight: OK, 0
bloqueadores, las 14 verificaciones pasan.** Este plan real, hoy, cumpliría todas las
condiciones para ejecutarse — pero **no se ejecutó**: sólo se le pasó por `preflight_full_local()`
(de sólo lectura), nunca por `batch_full_local_resolved_only()`. Detalle en
[03-preflight.md](03-preflight.md).

## Qué NO se hizo

No se ejecutó ninguna importación real. No se publicó contenido. No se habilitó staging ni
producción — `Storage::guard()` (entorno local + `psindustrial_wp_dev` + host loopback)
sigue siendo la puerta incondicional, nunca reimplementada de forma más débil. No se
incrementó la versión del plugin (permanece `0.4.0`). No se tocaron los 15 objetos reales del
ensayo subset salvo para confirmar (sin modificarlos) que siguen intactos.

## Documentos de esta entrega

1. [01-execution-model.md](01-execution-model.md) — el modo FULL_LOCAL_RESOLVED_ONLY.
2. [02-safety-gates.md](02-safety-gates.md) — todas las capas de protección.
3. [03-preflight.md](03-preflight.md) — las 14 verificaciones, resultado real.
4. [04-batching-resume.md](04-batching-resume.md) — lotes, reanudación, idempotencia.
5. [05-review-preservation.md](05-review-preservation.md) — por qué REVIEW/SKIP nunca mutan.
6. [06-rollback-strategy.md](06-rollback-strategy.md) — backup, qué se puede revertir.
7. [07-admin-workflow.md](07-admin-workflow.md) — la interfaz de administración.
8. [08-tests.md](08-tests.md) — las 20 suites, con detalle de las 2 nuevas.
9. [09-first-import-checklist.md](09-first-import-checklist.md) — checklist corta para la
   futura primera ejecución real, para revisar juntos antes de autorizarla.

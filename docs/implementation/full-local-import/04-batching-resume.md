# Batching, reanudación e idempotencia

## Por qué no se ejecutan ~500 filas en una sola petición

Una petición HTTP/PHP tiene un presupuesto de tiempo finito y un hosting compartido futuro
(Hepsia, sin SSH) no garantiza ejecución en segundo plano. `batch_full_local_resolved_only()`
usa el mismo patrón que `subset` ya prueba: un presupuesto blando de tiempo, un techo de
objetos por llamada, y un cursor persistido en el propio archivo del plan.

```
subset (sin cambios):       min(25, max(1,$limit)) objetos, <5 medios, <5s
full local (nuevo):         min(MAX_FULL_MUTABLE, max(1,$limit)) objetos, <20 medios, <20s
                             $limit por defecto = 50
```

Los límites de `full` son mayores porque el volumen real (526 filas mutables) lo exige, pero
siguen siendo un presupuesto **blando** (una operación de WordPress individual puede tardar
más) — no una garantía de hosting, exactamente la misma advertencia que ya documentaba
`docs/implementation/23-importer-workflow.md` para subset.

## Cursor y reanudación

`plan['cursor']` avanza en 1 por cada entrada procesada (mutada o no — REVIEW/SKIP también
avanzan el cursor, sólo que sin llamar a `apply()`). Se persiste (`Storage::write()`) después
de **cada** entrada individual, no al final del lote — una interrupción a mitad de lote dejará
el cursor exactamente donde se quedó el último resultado escrito. La siguiente llamada, con
el mismo `run_id`, continúa desde ahí. Verificado por test con un `$limit` deliberadamente
pequeño forzando varias llamadas para completar 5 entidades sintéticas: cada llamada procesó
exactamente `$limit`, el cursor persistido coincidió exactamente con el punto de corte, y la
reanudación llegó al final sin volver a crear las primeras.

## Idempotencia

Reejecutar sobre un run ya `COMPLETE` es un no-op puro:
`if ('COMPLETE' === $plan['status']) { return $plan; }`, verificado por test comprobando que
ni el conteo de posts ni un hash de todas las `options` cambian entre la llamada que completa
el run y una llamada posterior sobre el mismo run ya completo.

Reejecutar con un plan **nuevo** (mismas entidades, mismo contenido) sobre entidades que YA se
crearon en una corrida anterior resuelve a `UNCHANGED` vía `Identity::prediction()` — no
duplica nada; esto ya lo prueba `tests/importer.php` para subset y se reutiliza sin cambios
para full.

## Origen de un fallo a mitad de lote: fatal vs. por entidad

Ver [02-safety-gates.md](02-safety-gates.md), punto 7. Un fallo por entidad no interrumpe la
reanudabilidad (el cursor avanza igual, la entidad queda `FAILED`, se puede investigar y
volver a intentar con un plan nuevo más adelante). Un fallo fatal **no avanza el cursor**
para la entidad que lo causó — la siguiente llamada, tras resolver la causa raíz, reintentará
exactamente esa misma entidad, nunca la salta.

## Retención de snapshots nunca borra un run activo

`Storage::retain_recent_runs()` (política existente: conservar los 30 `run-*.json` más
recientes) ahora **excluye por completo** de la posibilidad de borrado cualquier archivo cuyo
propio `status` persistido sea `RUNNING` — sin importar su antigüedad. Esto es necesario
porque una importación full local puede legítimamente abarcar muchas llamadas por lotes a lo
largo de una sesión; mientras tanto, cualquier otra llamada a `Planner::build()` (una
vista previa de DRY RUN, o incluso las propias suites de test de este proyecto, que
construyen decenas de planes por sesión) dispara la misma rutina de retención. Antes de esta
fase, un run activo sólo estaba protegido *indirectamente* por seguir teniendo el mtime más
reciente cada vez que se escribía — protección real, pero no garantizada si ocurrían 30+
builds nuevos entre dos lotes de la misma corrida. Ahora es una garantía explícita, verificada
por test: un run `RUNNING` sobrevive aunque sea, a propósito, el archivo más antiguo del
directorio de prueba, mientras que un run `COMPLETE` (o cualquier otro estado) con la misma
antigüedad sí se elimina normalmente — sólo `RUNNING` está protegido, no "cualquier archivo
válido".

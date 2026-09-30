# El modo FULL LOCAL RESOLVED-ONLY

## Vocabulario de acciones — sin cambios, reutilizado tal cual

Una decisión (Q01-Q13, Policy, o el ensayo manual) produce una de estas `action` en
`plan['entries'][n]['action']`: `MIGRATE`, `MERGE`, `CREATE_FROM_STATIC`, `SKIP`, `REVIEW`.
`Runner` nunca decide cuál de estas le corresponde a una entidad — sólo decide, para las que
NO son `SKIP`/`REVIEW`, qué le pasa a WordPress: `Identity::prediction()` la resuelve en
tiempo real a `CREATE` (no existe todavía), `UPDATE` (existe, fuente cambiada), `UNCHANGED`
(existe, nada cambió) o `CONFLICT` (el destino fue editado a mano, o hay una versión binaria
distinta). Este es el mismo mecanismo que los tests existentes ya prueban para `subset` — no
se reimplementó nada de esto.

## Por qué el conteo de nivel superior muestra "CREATE" y no "MERGE"

`Planner::summary()` tabula por `planned_result` (la predicción, no la acción cruda) — así que
`MERGE`/`MIGRATE`/`CREATE_FROM_STATIC` que predicen `CREATE` se agrupan bajo el mismo
`CREATE` del resumen. Verificado explícitamente contra el plan real:

```
MIGRATE + MERGE + CREATE_FROM_STATIC = 445 + 49 + 32 = 526
CREATE  + UNCHANGED (resumen)         = 511 + 15      = 526
```

Coinciden exactamente porque, con sólo los 15 objetos del ensayo ya existentes en esta base,
todo lo demás mutable predice `CREATE` limpio — nada predice `UPDATE`/`CONFLICT` todavía. Las
**49 filas MERGE no desaparecen**: `Runner::preflight_full_local()` las cuenta por separado
(`counts.MERGE = 49`) precisamente para que este desglose sea auditable sin tener que inferirlo
del resumen. Ver [03-preflight.md](03-preflight.md).

## Dos rutas de ejecución, nunca una que autorice a la otra

| | `Runner::batch()` (existente, sin cambios de comportamiento) | `Runner::batch_full_local_resolved_only()` (nuevo) |
|---|---|---|
| Scope aceptado | `subset` únicamente | `full` únicamente |
| Frase de confirmación | `IMPORTAR SUBSET EN BORRADOR` | `IMPORTAR FULL LOCAL RESUELTO` |
| Límite de objetos mutables | 25 (todo el plan) | sin techo artificial (sólo el lote por request, ver 04) |
| Backup previo | manual, responsabilidad del operador | automático y obligatorio, primera llamada de cada run |
| Pre-flight dedicado | no (sus propias comprobaciones inline) | sí, `preflight_full_local()`, 14 verificaciones |
| Clasificación fatal/por-entidad | no (todo error se trata por entidad, sin cambios) | sí (ver 02-safety-gates.md) |

Ninguna frase autoriza el scope de la otra: `batch()` exige literalmente
`'IMPORTAR SUBSET EN BORRADOR'` y además exige `scope==='subset'`; el nuevo método exige
literalmente `'IMPORTAR FULL LOCAL RESUELTO'` y además exige `scope==='full'`. Verificado por
test: la frase full contra el método subset falla (frase no coincide); la frase subset contra
el método full falla (frase no coincide); una frase correcta contra el scope equivocado falla
igual (verificado con un plan `subset` re-etiquetado a mano — nunca alcanza a comprobar nada
más, la comprobación de scope actúa primero).

## Nada de lógica editorial vive aquí

`Runner` no decide identidad, no fusiona, no resuelve categoría/marca/medios — eso ya está
resuelto, dentro del plan, por `EditorialDecisions.php`/`Policy.php` antes de que `Runner` vea
una sola fila. Esta fase no reimplementa ni una sola de las Q01-Q13 ya construidas; sólo añade
la capacidad de **aplicar** lo que esas decisiones ya resolvieron.

# Q06 — Exclusión de registros legacy vacíos/prueba

Implementa Q06 = Opción A: excluye del catálogo migrable los 15 registros SQL sin valor
comercial real — 151 a 164 (nombre vacío) y 165 (`"Prueba"`) — conservando cualquier medio
que también pertenezca a otra entidad válida. Detalle fila por fila en
[q06-skip-audit.csv](q06-skip-audit.csv).

## Resultado

**15/15 productos → `SKIP`** con `decision_id: Q06`, razón
`INCOMPLETE_OR_TEST_LEGACY_RECORD`. **Ningún producto vacío se crea nunca** (verificado:
ninguna de las 15 filas alcanza `MIGRATE`/`CREATE_FROM_STATIC`/`MERGE`).

**16 medios → `SKIP`**, uno por cada fila de `product-media-relations.csv` cuyo único
propietario documentado es uno de estos 15 IDs. El ID 155 no tiene ningún medio asociado en
PMR (0 filas) — no es un error, simplemente ese registro nunca tuvo un `file` vinculado.

## Algoritmo

Implementado en `EditorialDecisions::q06()`, sin heurística: `editorial-decisions.json`
declara los 15 `legacy_product_ids` literalmente (Opción A, aprobación humana). Para cada
uno se emite `SKIP` directo. Para los medios, se agrupa
`product-media-relations.csv` por `path` y se calcula, por cada ruta con al menos un
propietario en la lista de los 15: **¿tiene también algún propietario fuera de esa lista?**

- **No** (exclusivo de uno o más de los 15) → `SKIP`, misma `decision_id: Q06`.
- **Sí** (compartido con cualquier otra entidad) → **ninguna decisión**; la ruta queda
  exactamente como estaba, a la espera de que se resuelva la propiedad de su otro dueño.

No hay ningún caso en el dataset real donde un medio de los 15 registros esté además
vinculado a un producto válido — se verificó explícitamente (0 de 16 filas PMR
comparten propietario). Esto es una señal positiva sobre la limpieza de los datos legacy en
este punto específico, no una laguna de la implementación: la regla de exclusividad está
codificada de forma genérica (no hay una lista especial de excepciones) y se probó con un
caso real de propiedad compartida (`system/files/images/productos/1bc8dece...`, productos
6/16/20/107, ninguno de los 15) para confirmar que un medio compartido nunca recibe
`decision_id: Q06`.

## El caso de doble bloqueo: `system/files/images/productos/800c352920...`

Este archivo es simultáneamente:

1. Uno de los 32 PDF bloqueados por tipo (Q05, bucket F — falso positivo puro, sin
   `/EmbeddedFile`/`/OpenAction`/`/AA`/`/Names/JavaScript`).
2. El único PDF SQL-vinculado del producto 165 (`"Prueba"`), sin ningún otro propietario en
   `product-media-relations.csv`.

Q05 resuelve su bloqueo de **seguridad** de forma independiente (aprobado como excepción
`SAFE_PDF_FALSE_POSITIVE`, gateado por su hash y ruta exactos — ver
[02-q05-pdf-resolution.md](02-q05-pdf-resolution.md)). Pero como su único propietario es un
registro que Q06 excluye, la exclusividad-de-propiedad de Q06 se aplica igual que a
cualquier otro medio de los 15: su acción final es **`SKIP`**, no `CREATE`. Verificado
directamente en el plan: `action: SKIP`, `decision_id: Q06` (no `Q05`) — es exactamente el
patrón "un bloqueo puede quedar resuelto sin que la fila desaparezca del REVIEW, porque
había otro bloqueo independiente" que el usuario advirtió para Q05, aplicado aquí en la
dirección Q05→Q06 en vez de Q05→propiedad genérica.

## Por qué no "producto SKIP → toda su media SKIP" automáticamente

Se investigó explícitamente si extender el `SKIP` a los duplicados binarios de estas 16
imágenes. 13 de las 16 tienen un gemelo binario en `images/accesorio-N.jpeg` (mismo
contenido exacto, confirmado por `duplicate_paths` en `media-master.csv`), pero esos 13
gemelos **no tienen ninguna fila propia en `product-media-relations.csv`** — no son
propiedad de ningún producto, ni siquiera de los 15 de Q06. Se decidió **no** tocarlos:
pertenecen al conjunto separado de "medios sin referencia" (Q08), fuera del alcance de esta
fase. Extender Q06 hacia ellos habría sido exactamente el error que el usuario pidió
evitar ("no quiero eliminar una imagen útil sólo porque también quedó relacionada con un
registro incompleto") — en este caso, invertido: no asumir que un duplicado binario de una
imagen excluida también debe excluirse, cuando en realidad ni siquiera está vinculado a la
entidad excluida.

## Trazabilidad

[q06-skip-audit.csv](q06-skip-audit.csv) — una fila por producto o por medio vinculado:
ID, vacío/prueba, acción de la entidad, ruta de medio vinculado (si aplica), acción del
medio, si es exclusivo de Q06. [editorial-decisions.json](editorial-decisions.json) — la
aprobación humana (`decision_id: Q06`, los 15 IDs, `reason_code`).

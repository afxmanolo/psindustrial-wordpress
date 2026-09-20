# Q02 — Consolidación de identidad equivalente

Implementa Q02 = Opción B: los 26 grupos verificados en
[03-identity-groups.md](../03-identity-groups.md) se consolidan en un único producto
WordPress cada uno. Detalle fila por fila en
[q02-merge-audit.csv](q02-merge-audit.csv).

## Resultado

**25 de 26 grupos** consolidados (`MERGE` + `SKIP`); **1** (`puertas-industriales-dockman.php`,
IDs 50/59) queda en `KEEP_REVIEW` por una causa ajena a Q02 — ver abajo. Ningún grupo Q03
(fusión editorial, 27 grupos distintos) fue tocado: se verificó explícitamente que no hay
solapamiento entre las claves de ambos conjuntos.

## Algoritmo, definido con precisión

Implementado en `EditorialDecisions::q02()`. Nada de esto depende del orden accidental del
filesystem o del CSV — cada paso usa un campo que ya existe en los maestros de migración o
en el catálogo SQL.

### 1. Integridad del grupo

`editorial-decisions.json` declara, por grupo, el `group_key` (el nombre del PHP candidato,
literalmente `canonical_candidate_group`) y la lista de `legacy_product_ids` aprobada. En
cada ejecución se recalcula esa membresía desde `product-master.csv` y se compara: si algún
ID ya no existe, si su `canonical_candidate_group` cambió, o si el conjunto vivo de IDs con
ese `canonical_candidate_group` ya no coincide exactamente con el aprobado, **el grupo
entero queda sin decisión** (ambos miembros vuelven a REVIEW plano) en vez de improvisar.

### 2. "Registro legacy más antiguo" (ganador)

Se define como el miembro con el valor **mínimo** de `productos.fecha` (el catálogo SQL,
ya poblado para las 165 filas — confirmado sin huecos en los 26 grupos). Único empate en
todo el conjunto: `puertas-industriales-dockman.php` (IDs 50 y 59, ambos `2025-10-23`) —
desempatado por `legacy_product_id` ascendente (la clave primaria legacy, no un orden
accidental). El ganador aporta `name`, `content` (extraído de su propio `legacy_php`),
`categories` y `brand` — verificado que `category_id`/`brand_id` son **idénticos** entre
ambos miembros en los 26 grupos, así que "usar los del ganador" no descarta ninguna
categoría o marca del perdedor.

### 3. "Primera imagen" (imagen destacada)

Es el primer elemento de la columna `images` del **propio registro ganador** en
`product-master.csv` — ese campo ya es la unión página+SQL de esa fila específica,
construida en una fase anterior, no un listado de directorio. No se usó el orden crudo de
`page-source-evidence.json` (que refleja el orden literal en el HTML, y suele poner primero
un ícono genérico "ver ficha técnica" antes que la foto real del producto).

### 4. Unión de medios, deduplicada por contenido real

Para cada miembro (ganador primero, luego los demás en el mismo orden determinista), se
recorren sus columnas `images` y `technical_pdf` (pipe-separadas). Cada ruta se resuelve a
su SHA-256 real vía `Sources::asset()` — **nunca por nombre de archivo**. Se conserva sólo
la primera aparición de cada hash distinto; las repeticiones (incluso bajo un nombre de
archivo completamente distinto) se descartan. Toda ruta de la unión debe tener su propia
fila en `media-master.csv` y pasar exactamente la misma validación
(`asset['valid'] && hash_equals(...)`) que `Planner` aplicará después al construir esa
entidad de medio de forma independiente — si cualquiera falla, **el grupo entero** queda
sin decisión (ver el caso dockman abajo).

Ejemplo real verificado — `cortina-serie-625.php` (ID 4 gana, ID 87 pierde): ambos
miembros referencian literalmente las mismas 3 rutas
(`rolling-service-door-625-wide.jpg`, `verficha.png`,
`system/files/images/productos/0dd525f6...`), pero **dos de esas tres son binariamente
idénticas** (`rolling-service-door-625-wide.jpg` y `0dd525f6...` comparten SHA-256
`c8d461b8...`, verificado independientemente). La unión final tiene 2 imágenes, no 3 y no 6.

Ejemplo de fotos complementarias — `cortina-serie-620.php` (ID 7 gana, ID 91 pierde): el ID
91 aporta `system/files/images/productos/0b26a7b2...` (hash `695b297e...`), genuinamente
distinto de todo lo que el ganador ya tenía. La unión resultante tiene 3 imágenes, no 2:
el aporte del perdedor sí se conserva.

### 5. Aprobación de los medios de la unión (hallazgo durante la implementación)

`Planner::build()` exige que cada imagen/PDF referenciado por un producto tenga **su
propia** decisión de migración antes de aceptar la del producto — exactamente lo que ya
exige a los productos singleton aprobados por `R-P01`. Estos 26 grupos estaban excluidos de
`R-P01` precisamente por ser multi-registro, así que ninguno de sus medios tenía aprobación
previa. Q02 emite además una decisión `MIGRATE` explícita (`decision_id: Q02`) para cada
ruta de la unión — sin esto, el `MERGE` del producto quedaría en REVIEW eternamente por una
dependencia no cumplida, vaciando la decisión de contenido. Algunas de esas rutas (p. ej.
`images/verficha.png`, un ícono genérico "ver ficha" reutilizado en decenas de productos)
son compartidas fuera de los 26 grupos; aprobarlas como dependencia del ganador no publica
ni fuerza ningún otro producto no relacionado con Q02.

### 6. `MERGE` — mecánica de Planner reutilizada, sin cambios de motor

El ganador recibe `action: MERGE`, `source_keys` con las claves de **todos** los miembros,
`field_winners` con los 7 campos (`name, content, categories, brand, images, pdfs, videos`)
apuntando al propio ganador (el chequeo de auto-consistencia que `Planner` ya tenía, sin
modificar), y `editorial_approval`. Los perdedores reciben `action: SKIP` citando al
ganador. Ambos decisiones llevan `origin: editorial_decision`, `decision_id: Q02`.

## El caso que NO se resuelve: `puertas-industriales-dockman.php`

`images/dockmanint.png` (una de las 3 imágenes de este grupo) está declarado como PNG pero
su contenido real es un JPEG (`finfo` detecta `image/jpeg`) — `Sources::asset()` ya
rechazaba este archivo **antes de esta fase**, por un chequeo de seguridad preexistente y
no relacionado con Q02 (mismatch MIME/extensión). Al descubrir esto durante la
implementación se encontró y corrigió una asimetría real: la primera versión del código
sólo verificaba "¿existe la fila en `media-master.csv`?", no "¿es realmente válida?" — así
que el ganador (ID 50) quedaba correctamente en REVIEW por la dependencia no cumplida, pero
el perdedor (ID 59) ya había recibido `SKIP` incondicionalmente, perdiendo esa fila sin que
la fusión se completara nunca. Se corrigió para que la unión de medios valide **cada**
imagen/PDF (existencia + `valid` + hash) antes de decidir nada del grupo: si cualquiera
falla, ni el ganador ni el perdedor reciben decisión — ambos quedan en REVIEW plano,
exactamente como si Q02 no existiera para ese grupo. Verificado: ni `sql:productos:50` ni
`sql:productos:59` llevan `decision_id` alguno.

## Trazabilidad

[q02-merge-audit.csv](q02-merge-audit.csv) — una fila por grupo: IDs, ganador, fecha,
perdedor(es), estado, categoría/marca, tamaño de la unión, imagen destacada.
[editorial-decisions.json](editorial-decisions.json) — la aprobación humana en sí
(`decision_id: Q02`, `editorial_approval`).

## Idempotencia

Verificada en [tests/editorial-decisions.php](../../../../wordpress/wp-content/plugins/psindustrial-core/tests/editorial-decisions.php):
el plan completo se construye dos veces y se comparan byte a byte `action`, `planned_result`
y `decision` de cada fila con `decision_id` Q02/Q06 — 0 diferencias. No depende de estado
oculto: se reconstruye siempre desde `product-master.csv` + `media-master.csv` +
`editorial-decisions.json` + el catálogo SQL.

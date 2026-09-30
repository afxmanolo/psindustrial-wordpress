# Q04 — Las 31 fichas que sólo existen como página

Implementa Q04 = Opción B: los 31 registros de `static-product-supplement.csv` (más 1 ya
migrado en el ensayo de Prompt 6) se convierten en `psi_producto` en borrador, con su
contenido, imágenes y PDF propios — **sin categoría ni marca**, nunca deducidas por
nombre de archivo, menú o parecido con otra ficha.

## Resultado

**29 de 31 nuevas** (+ 1 ya resuelta por la decisión manual del ensayo = **30 de 32**)
resuelven a `CREATE_FROM_STATIC`. **2 quedan `KEEP_REVIEW`**: `dura-glide-20003000-puerta.php`
y `magic-fuerza-del-operador.php`, ambas por la misma causa documentada desde la fase de
análisis — sus imágenes (`images/dura.jpg`, `images/magic.jpg`) están entre las 4
referencias de medio no resueltas conocidas desde el `FULL DRY RUN` original (Q11). No se
inventó un sustituto.

```
static_product total=32 · CREATE_FROM_STATIC=30 (29 Q04 + 1 manual) · REVIEW=2
```

## Algoritmo

Implementado en `EditorialDecisions::q04()`. Para cada fila de
`static-product-supplement.csv`:

1. Unión de `images`/`technical_pdf` de la propia fila, deduplicada por SHA-256 — la misma
   función `media_union()` que usan Q01 y Q02. Si cualquier ruta referenciada no tiene fila
   propia en `media-master.csv` o falla la validación (caso de los dos casos ya
   documentados), la fila entera queda `KEEP_REVIEW`: no se crea un producto con una
   galería a medias.
2. `categories: []`, `brand: ''` **siempre**, sin excepción — ninguna lógica intenta
   deducir ninguna de las dos.
3. El nombre y el contenido llegan automáticamente por el mecanismo ya existente de
   `Planner::build()` (extracción desde `legacy_php`, igual que cualquier producto/página).
4. Se retira (`SKIP`) la fila `php:` equivalente de `content-master.csv` (clasificada
   `PRODUCT_PAGE` sin `related_product_ids`, verificado 1:1 con este listado en una fase
   anterior) — **sin condicionarlo a que el producto se haya resuelto por Q04
   específicamente**: incluso para el único fichero ya cubierto por la decisión manual del
   ensayo, la página se retira igual, porque nada más reclama esa página y el objetivo
   ("un archivo físico, una sola entidad") es el mismo sin importar qué mecanismo resolvió
   el producto.

## No hay categoría ni marca inventada

Verificado explícitamente para las 29 filas resueltas por Q04: `categories: []` y
`brand: ''` en el 100 % de los casos, sin ninguna excepción. Quedan como necesidad
editorial pendiente y explícita — no como bloqueo para reconocer que el producto existe.

## Sin doble enumeración

Comprobado sobre las 32 filas completas de `static-product-supplement.csv`: ningún fichero
físico produce a la vez un `static_product` con acción de creación **y** una `php:` Page
con acción de creación/migración. 0 casos, verificado por test.

## Q03 intacto

`static-product-supplement.csv` no tiene `legacy_product_id`: por construcción, ninguna de
sus 32 filas pertenece a ningún `canonical_candidate_group` ni puede colisionar por
similitud de nombre con un producto SQL — el espacio de claves `static:` es
estructuralmente disjunto de `sql:productos:`.

## Trazabilidad

[q04-static-products.csv](q04-static-products.csv) — una fila por ficha: fuente, título,
clave canónica, acción, estado de borrador, estado de categoría/marca (siempre
"NONE_ASSIGNED"), cantidad de medios, estado del mapeo de URL.

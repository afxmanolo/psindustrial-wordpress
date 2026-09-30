# Categorías y marcas — análisis de los 47 REVIEW

36 categorías + 11 marcas. Fuentes: `category-master.csv`, `brand-master.csv`.

## Principio aplicado: crear el término ≠ asignar productos

Es la distinción que permite la mayor parte de la reducción en este bloque.

Un término de taxonomía es una entidad con nombre, identificador y, cuando existe, logo o
imagen. Crear ese término **no afirma nada sobre qué productos le pertenecen**. La relación
producto↔término es un dato separado que el importador escribe aparte
(`wp_set_object_terms`) y que puede quedar vacía.

Los conflictos conocidos de este proyecto (Modern Steel bajo tres marcas, MOOVI entre Blue
Giant y BFT, LiftMaster con título Blue Giant) son **conflictos de atribución de producto**,
no dudas sobre la existencia de las marcas. Blue Giant y BFT existen las dos, con página y
logo propios; lo que está en disputa es a cuál pertenece MOOVI.

Por tanto los términos pueden crearse sin resolver D03, siempre que no se asigne ningún
producto. Eso es lo que proponen R-T01 y R-T03.

## Categorías — 36 filas

| Confianza de relación | Confianza de padre SQL | Filas |
|---|---|---:|
| STRONG_INFERENCE | CONFIRMED | 34 |
| UNKNOWN | CONFIRMED | 2 |

29 de 36 tienen `image_file_id`. 2 tienen `legacy_page` vacío.

### Inequívocamente identificadas — 33

Nombre e identificador estables, padre SQL `CONFIRMED`, sin aparecer en el registro D06.
→ `MIGRATE` como término en estado `review` (regla R-T01, riesgo LOW).

La jerarquía se toma del padre SQL, que es `CONFIRMED` en las 36 filas. Distribución de
padres entre las 36: 6 sin padre (raíces), y el resto repartido bajo los padres 1–7.

### Conflictivas — 3

| ID | Nombre | Padre | Relación | Página | Productos | Problema |
|---|---|---|---|---|---:|---|
| 25 | Bumpers, semáforos y cepillos | 3 | UNKNOWN | — | 14 | sin página propia; recibe los 14 registros vacíos |
| 26 | Puertas contra incendio | 0 | UNKNOWN | — | 0 | mismo nombre que 33; sin productos ni página |
| 33 | Puertas contra incendio | 5 | STRONG_INFERENCE | `contra-incendio.php` | 1 | mismo nombre que 26, distinto padre |

26 y 33 comparten nombre con padres distintos (0 y 5). Fusionarlos por nombre es exactamente
lo que la documentación prohíbe: *26/33 no se fusionan por nombre*. 25 no tiene página única
y es la categoría que agrupa los registros vacíos, lo que sugiere que fue un contenedor
administrativo más que una categoría comercial.

→ `KEEP_REVIEW` (regla R-T02, riesgo HIGH, decisión D06).

## Marcas — 11 filas

| ID | Nombre | Página | Evidencia de logo | Productos fuertes | Productos en conflicto |
|---|---|---|---|---:|---|
| 2 | Wayne Dalton | `wayne-dalton.php` | `marcas.php:43` | 2 | 6, 14, 16, 20, 57, 107 |
| 3 | Clopay | `clopay.php` | `marcas.php:52` | 3 | 6, 14, 16, 20, 57, 107 |
| 4 | Blue Giant | `blue-giant.php` | `marcas.php:61` | 4 | 43, 44, 81, 123, 125, 135 |
| 5 | Kelley | `kelley.php` | `marcas.php:70` | 2 | 139 |
| 6 | Doorlock | `doorlock.php` | `marcas.php:79` | 15 | — |
| 7 | Rytec | `rytec.php` | `marcas.php:88` | 2 | — |
| 8 | Infraca Quality Doors | `infraca-quality-doors.php` | `marcas.php:97` | 2 | — |
| 9 | GLG Porte Industriali | `glg-porte-industriali.php` | `marcas.php:106` | 3 | — |
| 10 | Dockman | `dockman.php` | `marcas.php:115` | 10 | 150 |
| 11 | LiftMaster | `lift-master.php` | `marcas.php:124` | 0 | 43, 81 |
| 12 | BFT | `bft.php` | `marcas.php:133` | 9 | 44, 123, 125, 135 |

Las 11 tienen: nombre, página dedicada propia y logo con referencia literal de línea en
`marcas.php`. La evidencia de identidad es `CONFIRMED` para las 11, incluida LiftMaster, cuya
marca existe aunque no tenga ningún producto atribuido con confianza.

→ `MIGRATE` como término en estado `review`, **sin asignar productos** (regla R-T03, riesgo LOW).

El campo `marcas.imagen` está vacío en las 12 filas del dump, pero los logos existen en el
frontend y el directorio estático permite asociarlos: esa es la evidencia usada, no el campo
SQL vacío. Los 11 logos correspondientes están en el bloque de medios como
`BRAND_LOGO` y se migran con el término.

## Qué requiere intervención humana

| Cuestión | Entidades | Decisión |
|---|---|---|
| Identidad de 26 vs 33 y naturaleza de 25 | 3 categorías | D06 |
| Qué marca recibe cada producto en disputa | 14 productos, 7 marcas | D03 |

**Ninguna de las dos bloquea la creación de los 44 términos limpios.** D03 afecta a la
relación producto→marca; D06 afecta a 3 categorías concretas.

## Resultado

| Tipo | MIGRATE | KEEP_REVIEW |
|---|---:|---:|
| Categoría | 33 | 3 |
| Marca | 11 | 0 |

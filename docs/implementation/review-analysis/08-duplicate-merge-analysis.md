# Duplicados y MERGE

Fuente: `canonical-candidate-groups.csv` (77 grupos) cruzada con `product-master.csv`.

## Composición

| Tamaño del grupo | Grupos | Filas REVIEW cubiertas |
|---|---:|---:|
| 1 registro | 24 | 22 |
| 2 registros | 37 | 74 |
| 3 registros | 12 | 36 |
| 4 registros | 4 | 16 |
| sin grupo (vacíos + prueba) | — | 15 |

53 grupos tienen más de un registro y cubren **126 filas REVIEW** de producto.

Los 77 grupos tienen `decision = REVIEW_NO_AUTOMATIC_MERGE`. Ninguno viene aprobado.

## Clasificación de duplicados

| Clase | Grupos | Filas | Criterio aplicado |
|---|---:|---:|---|
| `SAME_ENTITY_DIFFERENT_SOURCE` | 26 | 52 | nombre, PHP, `category_id` y `brand_id` idénticos en todos los miembros |
| `POSSIBLE_DUPLICATE` | 27 | 74 | mismo PHP pero divergen nombre, categoría o marca |
| `EXACT_DUPLICATE` | 0 | 0 | ningún grupo es idéntico en todos los campos copiados |
| `DIFFERENT_ENTITY` | 0 | 0 | no demostrable con los datos actuales |
| `HUMAN_DECISION` | 53 | 126 | todas requieren al menos una decisión |

`EXACT_DUPLICATE` queda en 0 por un motivo concreto y verificable, explicado abajo.

## Por qué no hay ningún ganador determinista

El importador exige, en `Planner::build()`, que una fusión declare un ganador **para todos**
los campos copiados: `name`, `content`, `categories`, `brand`, `images`, `pdfs`, `videos`.
Si un solo campo tiene ganador distinto, la entrada vuelve a `REVIEW` con el mensaje
*MERGE mixto requiere paquete editorial normalizado*.

Divergencia medida entre miembros de los 53 grupos múltiples:

| Campo | Grupos en que diverge |
|---|---:|
| `db_image_ids` | 53 de 53 |
| `db_pdf_ids` | 51 de 53 |
| `category_id` | 17 de 53 |
| `name` | 11 de 53 |
| `brand_id` | 5 de 53 |

Ningún grupo diverge en cero campos. Distribución por número de campos divergentes:
1 campo → 1 grupo; 2 campos → 25; 3 campos → 23; 4 campos → 3; 5 campos → 1.

**`db_image_ids` diverge en los 53 grupos.** Por tanto no existe ningún grupo en el que un
único miembro gane todos los campos, y `MERGE` automático es imposible bajo la regla actual
del importador. La propuesta de este análisis contiene **0 filas `MERGE`**, y eso no es una
carencia del análisis: es el resultado correcto.

## El patrón real de duplicación

Las filas duplicadas no son fichas comerciales distintas. El CMS legacy repetía el registro
del producto por cada contexto de navegación en que aparecía. De ahí que el PHP sea el mismo
en los 53 grupos, mientras la categoría cambia en 17: cada fila lleva la categoría del
contexto desde el que se accedía.

Lo que diverge sistemáticamente son **las listas de medios**: cada copia acumuló su propio
subconjunto de imágenes y PDFs.

## Los 26 grupos de identidad idéntica

26 grupos (52 filas REVIEW) cumplen: mismo nombre, mismo PHP, misma categoría y misma marca
en todos sus miembros. **Ninguno interseca los conflictos conocidos D03/D06/D07.**

Ejemplos verificables: `cortina-serie-625.php` (IDs 4, 87), `cortina-serie-620.php` (7, 91),
`coleccion-classic.php` (9, 108), `cortina-serie-630.php` (11, 94),
`operador-comercial-rhx.php` (12, 82), `puerta-contra-explosion.php` (26, 117).

En estos 26 grupos la identidad del producto **no está en duda**: es el mismo producto, la
misma ficha, la misma categoría y la misma marca. Lo único que difiere son las listas de
medios.

Esto reduce la pregunta de «¿cuál de estas filas es el producto real?» —que sería editorial y
difícil— a una pregunta de política, mucho más acotada:

> Cuando varias filas SQL describen la misma ficha con idéntica identidad y difieren sólo en
> las listas de imágenes y PDFs, ¿la ficha resultante debe recibir la **unión** de esos medios,
> o sólo los de una fila designada?

Es una sola decisión que desbloquea 52 filas de producto (26 productos) y sus medios
asociados.

### Por qué no se decide aquí

La unión de medios parece inocua pero no lo es del todo:

- determina **qué imagen queda como destacada**, que es una elección visual;
- determina el **orden de la galería**;
- si dos filas apuntan a fichas técnicas distintas para el mismo modelo, la unión produciría
  un producto con dos PDFs contradictorios, que es justamente el riesgo D07.

Por eso la decisión debe tomarla el negocio, y la regla que la implemente debe excluir los
grupos cuyos `db_pdf_ids` divergentes apunten a PDFs de contenido distinto por hash.

No se propone aquí ninguna regla de unión automática.

## Los 27 grupos restantes

74 filas donde además divergen nombre, categoría o marca. Aquí la pregunta sí es editorial:
si el nombre difiere, puede tratarse de modelos distintos agrupados por error, o del mismo
modelo con dos denominaciones comerciales. `evidence-matrix.md` y D07 documentan casos
concretos (ICARO con PDF LUX, ALUMINA en URL kronos, thermacore 525 en URL 598).

→ `KEEP_REVIEW`, riesgo HIGH, decisión D02 caso por caso.

## Advertencia sobre combinación de campos

El encargo pide explícitamente no combinar campos de fuentes distintas si eso puede producir
una entidad que nunca existió. Ese riesgo es real aquí: en los 27 grupos con nombre
divergente, tomar el nombre de una fila, la categoría de otra y los medios de una tercera
produciría una ficha que ningún registro legacy describe y que ninguna URL histórica sirvió.

La regla del importador que bloquea los ganadores mixtos es correcta y **no debe relajarse**
para reducir el número de REVIEW.

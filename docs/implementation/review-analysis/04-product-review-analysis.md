# Productos — análisis de los 163 REVIEW

Fuente: `product-master.csv` (165 filas; 2 aplicadas en el subset) cruzada con
`canonical-candidate-groups.csv`.

## Agrupación por causa

| Causa | Filas | Determinación |
|---|---:|---|
| Pertenece a grupo canónico con más de un registro | 126 | `record_count > 1` |
| Registro vacío (IDs 151–164) | 14 | `name` y `descripcion` vacíos |
| Registro de prueba (ID 165) | 1 | `name = "Prueba"` |
| Ficha única en su grupo, con conflicto de marca registrado | 1 | ID 150 (D03) |
| Ficha única en su grupo, sin conflicto | 21 | grupo `record_count = 1` |
| | **163** | |

## Estado de la evidencia base

| Campo | Distribución |
|---|---|
| `migration_status` | REVIEW 143, SHOULD_MIGRATE 20 |
| `php_match_confidence` | STRONG_INFERENCE 148, UNKNOWN 15 |
| `unambiguous_php` | YES 134, NO 29 |
| `category_confidence` | CONFIRMED 108, STRONG_INFERENCE 54, WEAK_INFERENCE 1 |
| `brand_confidence` | STRONG_INFERENCE 91, CONFIRMED 49, UNKNOWN 14, WEAK_INFERENCE 14 |

Los 15 `php_match_confidence = UNKNOWN` son exactamente los 14 vacíos más `Prueba`.

## Grupos canónicos

Histograma de filas REVIEW por grupo: 22 grupos de 1, 37 de 2, 12 de 3, 4 de 4, más 15 filas
sin grupo (los vacíos y la prueba). Comprobación: `22 + 74 + 36 + 16 + 15 = 163`.

Los 77 grupos de `canonical-candidate-groups.csv` tienen `decision = REVIEW_NO_AUTOMATIC_MERGE`
sin excepción. **Ningún grupo viene preaprobado para fusión.**

## Las 21 fichas limpias

Condición: grupo canónico de un solo registro, `unambiguous_php = YES`, sin aparecer en los
registros de conflicto D03/D06/D07.

De los 22 grupos unitarios, 21 cumplen. El excluido es el ID 150 (título Dockman frente a
texto y PDF Solmmer, D03).

Perfil de los 21:

- `migration_status`: 20 SHOULD_MIGRATE, 1 REVIEW
- `category_confidence`: 20 CONFIRMED, 1 STRONG_INFERENCE
- `brand_confidence`: 1 CONFIRMED, 19 STRONG_INFERENCE, 1 WEAK_INFERENCE
- `unambiguous_php`: 21 YES

### Tratamiento de la marca

Sólo **una** de las 21 fichas tiene `brand_confidence = CONFIRMED`. Asignar marca a las otras
20 exigiría aceptar `STRONG_INFERENCE` o `WEAK_INFERENCE` como suficiente, lo que la
documentación prohíbe expresamente: *no resolver relaciones inciertas por similitud
semántica*, y D03 sigue abierta.

La regla R-P01 por tanto **migra el producto con sus categorías y difiere la marca**. El
importador ya soporta este caso: `Planner` acepta `brand` vacío y emite la advertencia
`Sin marca: no inferir fabricante.` La marca se asigna después, como relación independiente,
cuando D03 se responda.

Esto convierte un bloqueo total en un bloqueo parcial: se recupera el producto, su contenido,
sus categorías y sus medios, sin afirmar un fabricante que no está demostrado.

## Registros vacíos y de prueba — 15 filas

IDs 151–164 no tienen nombre ni descripción. El importador **no puede crearlos**:
`Planner::build()` lanza `NAME_REQUIRED` cuando el nombre resuelto está vacío. No existe
ninguna ruta por la que estas filas lleguen a ser `MIGRATE` sin aportar contenido nuevo.

La elección real es `SKIP` o `KEEP_REVIEW`, y es una sola decisión humana (D05) que cubre
las 15 filas. Se propone `SKIP` con riesgo MEDIUM porque:

- es reversible: no borra la fila legacy ni el dump;
- es la única acción coherente con el estado del dato;
- pero las filas **tienen medios asociados y categoría SQL explícita** (categoría 25), lo que
  sugiere que fueron altas iniciadas y no terminadas. Descartarlas sin preguntar podría
  perder un alta pendiente.

Por eso no se marca LOW: requiere confirmación, aunque sea una sola.

## Cuántos necesitan realmente decisión humana

| Situación | Filas | Decisión |
|---|---:|---|
| Resolubles sin intervención | 21 | — |
| Una sola decisión de política de medios en duplicados idénticos | 52 | D02 acotada (ver [08](08-duplicate-merge-analysis.md)) |
| Una sola decisión sobre vacíos/prueba | 15 | D05 |
| Fusión editorial real, caso por caso | 74 | D02 completa |
| Conflicto de fabricante o jerarquía | 1 | D03 |

**De 163 productos, 74 requieren decisión editorial caso por caso.** Los otros 89 dependen de
tres decisiones de política, no de 89 juicios individuales.

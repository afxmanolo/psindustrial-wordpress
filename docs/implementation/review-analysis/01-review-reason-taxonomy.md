# Taxonomía de causas de REVIEW

Fecha: 2026-09-18. Fase analítica. No modifica el manifest, el importador ni el contenido.

## Por qué hacía falta una taxonomía

El campo `notes` de `all-review.csv` **no discrimina**: es texto constante por `source_type`.
Los 1.523 medios comparten una única cadena; los 163 productos comparten dos. Agrupar por
`notes` reproduce el conteo por tipo y no explica nada.

La causa real se obtiene cruzando cada fila con su maestro de origen. Esta taxonomía se
construyó desde esos campos verificables, no desde el texto del informe.

## Causa raíz estructural

Antes de las 14 causas específicas hay un hecho que explica el número total.

En `Planner::build()` toda entrada nace con `action = 'REVIEW'`. Sólo cambia si:

1. existe una entrada en `subset-decisions.json` para esa clave (hoy: 15 objetos del ensayo), o
2. la fila está clasificada `LEGACY_INTERNAL` en `content-master.csv` (435 filas → SKIP).

Aritmética exacta: `2.399 − 435 − 15 = 1.949`.

**REVIEW no es un defecto detectado: es la ausencia de un registro de decisión aprobado.**
El importador nunca evaluó estas 1.949 filas y las rechazó; sencillamente no tenía
autorización para ninguna. Esto cambia la naturaleza del trabajo: no hay que "arreglar"
1.949 problemas, hay que **producir decisiones** para 1.949 fuentes, y la mayoría comparte
muy pocas decisiones subyacentes.

## Códigos normalizados

14 códigos estables. Se asigna a cada fila **la causa bloqueante más específica**; cuando
concurren varias, gana la más restrictiva (por ejemplo, un registro vacío que además está
duplicado se clasifica como vacío, porque eso ya impide crearlo).

| Código | Significado | Fuente de la determinación | Filas |
|---|---|---|---:|
| `R01_AUTHORIZATION_ONLY` | Evidencia completa y sin contradicción; sólo falta aprobación editorial | maestros sin conflicto registrado | 79 |
| `R02_ENTITY_DUPLICATE_CANDIDATE` | La fila pertenece a un grupo canónico con más de un registro | `canonical-candidate-groups.csv record_count>1` | 126 |
| `R03_ENTITY_IDENTITY_UNCERTAIN` | Correspondencia PHP↔SQL no unívoca | `product-master.unambiguous_php=NO` | 0 |
| `R04_EMPTY_OR_TEST_RECORD` | Sin contenido publicable | `name`/`descripcion` vacíos; nombre `Prueba` | 15 |
| `R05_BRAND_ATTRIBUTION_CONFLICT` | Fuentes contradictorias sobre el fabricante | registro D03 | 1 |
| `R06_CATEGORY_HIERARCHY_CONFLICT` | Jerarquía o nombre de categoría contradictorio | registro D06 | 3 |
| `R07_SOURCE_OWNERSHIP_UNDEFINED` | El mismo artefacto legacy puede representarse en más de un objeto destino | `content-master.classification` | 167 |
| `R08_STATIC_ONLY_ENTITY` | Ficha estática sin fila SQL equivalente | `static-product-supplement.csv` | 31 |
| `R09_MEDIA_OWNER_PENDING` | Medio con propietario explícito cuya decisión aún no existe | `product-media-relations.csv`, `media-usage-evidence.csv` | 582 |
| `R10_MEDIA_DERIVATIVE` | Derivado generado por el CMS legacy desde un original conservado | `media-master.original_ids` + flag `DERIVED` | 561 |
| `R11_MEDIA_INFRASTRUCTURE` | Asset de interfaz del CMS o fuente de iconos del frontend | prefijo `system/backoffice/`, `system/`, `fonts/` | 285 |
| `R12_MEDIA_PURPOSE_UNPROVEN` | Sin relación con entidad SQL y sin referencia pública atribuible | `usage_type=*_APPARENTLY_UNUSED`, `direct_reference_count=0` | 89 |
| `R13_MEDIA_TYPE_UNSUPPORTED` | Tipo/límite/hash fuera de la política del importador | `media_validation=REVIEW_TYPE_LIMIT_OR_HASH` | 6 |
| `R14_MISSING_RESOURCE` | Referencia no resoluble | `missing-media-references.csv` | 4 |
| | | **Total** | **1.949** |

### Nota sobre `R03`

Se definió porque la condición existe en los datos (29 productos con `unambiguous_php=NO`),
pero **no se aplica a ninguna fila**: los 15 registros vacíos/prueba caen antes en `R04`, y
los 14 restantes están además en grupos canónicos múltiples, por lo que `R02` los absorbe.
Se conserva el código en la taxonomía porque volverá a ser aplicable si se resuelve `D02`.
No se infla ningún conteo con él.

## Niveles de evidencia

| Nivel | Definición operativa | Filas |
|---|---|---:|
| `CONFIRMED` | Identidad verificable por máquina: relación explícita en el dump, hash SHA-256 coincidente, ruta física existente, o clasificación estructural del propio árbol de archivos | 1.345 |
| `STRONG` | Varias señales verificables independientes convergen sin contradicción (nombre exacto + hash de imagen/PDF compartido + solape de descripción + PHP unívoco) | 471 |
| `MODERATE` | Una sola señal verificable, sin contradicción conocida, sin confirmación cruzada | 40 |
| `WEAK` | Sólo proximidad semántica, coincidencia de nombre aproximada o co-ubicación | 0 |
| `NONE` | Ninguna evidencia más allá de la existencia del archivo o registro | 93 |

**La similitud semántica nunca eleva por sí sola a `STRONG`.** `product-page-candidates.csv`
contiene `name_similarity` y `description_overlap`; ambos se usan sólo como refuerzo de una
señal dura previa (hash compartido o PHP unívoco), nunca como fundamento único. Ninguna fila
de esta clasificación obtuvo `STRONG` a partir de texto solamente; por eso `WEAK` queda en 0:
las filas que sólo tendrían apoyo semántico están en `NONE` o quedaron en `KEEP_REVIEW`.

## Acciones propuestas

Vocabulario cerrado, exigido por el encargo:

- `MIGRATE` — podría crearse como objeto WordPress en borrador/revisión.
- `MERGE` — varias fuentes deberían converger en un objeto, con ganador definido.
- `SKIP` — no se crea objeto de contenido. **No borra archivos, no libera URLs, no altera `/legacy`.**
- `KEEP_REVIEW` — la ambigüedad es real y debe preservarse.

`SKIP` merece énfasis porque es la acción más frecuente propuesta (1.025) y la más fácil de
malinterpretar. En este importador `SKIP` significa exactamente lo que ya significa para los
435 SKIP existentes: *no se migra como contenido; archivo y evidencias preservados*. La
decisión sobre URLs históricas es independiente y pertenece a la fase de URLs/SEO.

# Distribución actual de los 932 REVIEW

Reconstruido desde el plan vivo (`Planner::build('full')`, DRY RUN, sin mutación de base de
datos), no desde `all-review.csv`, que describe el estado anterior a las reglas LOW y a las
decisiones PDF. Fila a fila en [02-current-review-932.csv](02-current-review-932.csv).

## Punto de partida verificado

```
total      2.399
UNCHANGED     15
SKIP       1.281
CREATE       171
REVIEW       932
ERROR          0
```

## Por `source_type`

| source_type | REVIEW | % del total |
|---|---:|---:|
| media | 576 | 61,8 % |
| page | 176 | 18,9 % |
| product | 142 | 15,2 % |
| static_product | 31 | 3,3 % |
| missing_media | 4 | 0,4 % |
| category | 3 | 0,3 % |
| brand | 0 | — |
| **total** | **932** | |

Las marcas desaparecieron por completo del REVIEW (los 11 términos se aprobaron con R-T03),
y las categorías bajaron a 3 (sólo las conflictivas 25/26/33).

## Por causa heredada (`normalized_reason_code`)

| Causa | Filas |
|---|---:|
| `R09_MEDIA_OWNER_PENDING` | 481 |
| `R07_SOURCE_OWNERSHIP_UNDEFINED` | 167 |
| `R02_ENTITY_DUPLICATE_CANDIDATE` | 126 |
| `R12_MEDIA_PURPOSE_UNPROVEN` | 89 |
| `R08_STATIC_ONLY_ENTITY` | 31 |
| `R04_EMPTY_OR_TEST_RECORD` | 15 |
| `R01_AUTHORIZATION_ONLY` | 9 |
| `R13_MEDIA_TYPE_UNSUPPORTED` | 6 |
| `R14_MISSING_RESOURCE` | 4 |
| `R06_CATEGORY_HIERARCHY_CONFLICT` | 3 |
| `R05_BRAND_ATTRIBUTION_CONFLICT` | 1 |
| **total** | **932** |

Esta vista es la que induce a error: sugiere 11 problemas distintos, cuando en realidad la
mitad de las filas espera **la misma** decisión.

## Por decisión bloqueante — la vista que importa

Cada fila se atribuyó a la **única decisión** que hoy la mantiene en REVIEW, resolviendo la
cadena de dependencias (un medio cuyo producto propietario está en un grupo canónico se
atribuye a la decisión de ese grupo, no a sí mismo).

| # | Decisión bloqueante | Filas | Grupos/entidades | Composición |
|---|---|---:|---:|---|
| Q01 | Propiedad del contenido de páginas de producto/categoría/marca | **414** | 174 | 250 media + 164 page |
| Q03 | Fusión editorial de 27 grupos divergentes | **164** | 29 | 90 media + 74 product |
| Q02 | Política de medios en 26 grupos de identidad equivalente | **124** | 27 | 72 media + 52 product |
| Q08 | Medios sin referencia demostrable | **89** | 1 | 89 media |
| Q05 | Política de PDF bloqueados por tipo/contenido | **41** | 37 | 41 media |
| Q04 | Entidad y categoría de las fichas estáticas | **31** | 1 | 31 static_product |
| Q06 | Registros vacíos y de prueba | **30** | 2 | 15 media + 15 product |
| Q07 | Landings SEO | **26** | 16 | 17 media + 9 page |
| Q10 | Jerarquía de categorías 25/26/33 | **4** | 4 | 3 category + 1 media |
| Q11 | Recursos ausentes | **4** | 1 | 4 missing_media |
| Q12 | Páginas utilitarias | **3** | 1 | 3 page |
| Q09 | Atribución de fabricante (ID 150) | **1** | 1 | 1 product |
| Q13 | Alias binario del PDF del ensayo | **1** | 1 | 1 media |
| | **total** | **932** | | |

**Tres decisiones (Q01, Q03, Q02) concentran 702 de las 932 filas: el 75 %.**

## Por tipo de dependencia

| Tipo de bloqueo | Filas | Qué significa |
|---|---:|---|
| Espera a que se decida su **entidad propietaria** | 481 | medios con relación explícita a producto/página/término aún no decidido |
| Espera a que se decida **su propio tipo de objeto** | 205 | páginas y fichas estáticas: ¿Page, producto o archivo de taxonomía? |
| Espera a una **decisión de identidad** (duplicado) | 126 | filas de producto en grupos canónicos múltiples |
| Espera **evidencia externa** | 89 | medios sin referencia: hace falta tráfico/logs, no está en el repositorio |
| Espera una **política técnica** | 41 | PDFs rechazados por la comprobación de contenido |
| Bloqueo **individual** | 12 | vacíos/prueba, categorías conflictivas, recursos ausentes, utilitarias, marca |

## Nota sobre doble bloqueo

37 de las 41 filas de Q05 (PDF por tipo) tienen **además** una dependencia de propietario
pendiente. En las simulaciones se cuentan una sola vez, bajo su bloqueo primario, pero una
implementación real debe levantar **ambos** para que esas filas avancen. Las otras 4 están
bloqueadas exclusivamente por el tipo de archivo.

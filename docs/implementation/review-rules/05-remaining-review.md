# Los 950 REVIEW restantes

Desglose real del plan FULL DRY RUN tras la política LOW, cruzado contra
`normalized_reason_code` de la fase analítica anterior
([review-classification.csv](../review-analysis/review-classification.csv)). Ninguna de las
950 filas quedó sin clasificación previa.

## Por `source_type`

| source_type | REVIEW restante |
|---|---:|
| media | 583 |
| page | 176 |
| product | 153 |
| static_product | 31 |
| category | 3 |
| missing_media | 4 |
| **total** | **950** |

## Por causa original (`normalized_reason_code`)

| Causa | Filas | Riesgo original | ¿Por qué sigue en REVIEW? |
|---|---:|---|---|
| `R09_MEDIA_OWNER_PENDING` | 488 | MEDIUM | propietario (producto/página) aún no aprobado; se resuelve solo cuando ese propietario avance |
| `R07_SOURCE_OWNERSHIP_UNDEFINED` | 167 | MEDIUM (R-G01) | página que reexpresa un producto/término ya enumerado; R-G01 no es LOW, no se implementó |
| `R02_ENTITY_DUPLICATE_CANDIDATE` | 126 | HIGH | grupo canónico múltiple; sin ganador determinista (`MERGE=0`, ver [08](../review-analysis/08-duplicate-merge-analysis.md)) |
| `R12_MEDIA_PURPOSE_UNPROVEN` | 89 | MEDIUM (D09) | sin relación de entidad y sin referencia pública; ausencia de referencia no demuestra orfandad |
| `R08_STATIC_ONLY_ENTITY` | 31 | MEDIUM (D02) | ficha estática sin fila SQL; categoría y marca `UNKNOWN` |
| `R01_AUTHORIZATION_ONLY` | 20 | LOW (9 productos) / MEDIUM (9 landings) / 11 productos, ver abajo | 11 productos: revelados por regla LOW, bloqueados por hallazgo de seguridad nuevo. 9 páginas: SEO_LANDING, MEDIUM, no implementada |
| `R04_EMPTY_OR_TEST_RECORD` | 15 | MEDIUM (D05) | IDs 151–165; ninguna regla LOW decide vacíos/prueba |
| `R13_MEDIA_TYPE_UNSUPPORTED` | 6 | MEDIUM | tipo/límite/hash fuera de política; requiere revisión técnica |
| `R14_MISSING_RESOURCE` | 4 | HIGH (D08) | referencia no resuelta |
| `R06_CATEGORY_HIERARCHY_CONFLICT` | 3 | HIGH (D06) | categorías 25/26/33 |
| `R05_BRAND_ATTRIBUTION_CONFLICT` | 1 | HIGH (D03) | ID 150 |
| **total** | **950** | | |

## El caso especial de `R01_AUTHORIZATION_ONLY` (20 filas)

Esta causa agrupa dos situaciones muy distintas que conviene no confundir:

- **9 páginas** (`php:` SEO_LANDING): siguen en REVIEW porque su regla (R-G02 landing) es
  **MEDIUM**, no LOW, y esta fase no la implementa. Comportamiento esperado, sin novedad.
- **11 productos** (65, 66, 67, 68, 69, 70, 71, 72, 73, 77, 95): en la fase analítica
  anterior estaban marcados `R01_AUTHORIZATION_ONLY` con riesgo **LOW** — se esperaba que
  R-P01 los resolviera. **No lo hizo**, porque al validar sus PDF de ficha técnica contra los
  bytes reales, la comprobación de seguridad existente del importador detectó una referencia
  `/EmbeddedFile` y los rechazó. Esto no invalida la clasificación LOW original (la evidencia
  de identidad/categoría/PHP univoco seguía siendo correcta); revela un problema **adicional
  y real** que sólo se podía descubrir ejecutando la extracción de verdad. Detalle completo,
  con las 7 rutas de PDF exactas, en [06-known-issues.md](06-known-issues.md).

## Nada de esto es deuda pendiente por descuido

De las 950 filas: 3+126+1 = **130 son decisiones HIGH** (juicio editorial irreducible,
[10-human-decisions.md](../review-analysis/10-human-decisions.md)); el resto son MEDIUM que
requieren una decisión de política o evidencia que no está en el repositorio (D02, D05, D09,
R-G01), más las 11 que ahora requieren, además, resolver el hallazgo de PDF. Ninguna se
convirtió en SKIP o MIGRATE sólo para reducir el contador.

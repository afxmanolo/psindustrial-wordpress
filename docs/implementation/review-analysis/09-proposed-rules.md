# Reglas candidatas

Reglas deterministas propuestas. **Ninguna está aprobada ni implementada.** No modifican el
manifest, el importador ni las decisiones existentes. Se evalúan en orden: la primera que
casa asigna la acción.

## Resumen

| Regla | Acción | Tipos | Filas | Riesgo | Decisión humana |
|---|---|---|---:|---|---|
| R-P01 | MIGRATE | product | 21 | LOW | — |
| R-P02 | KEEP_REVIEW | product | 126 | HIGH | D02 (+D03/D06) |
| R-P03 | SKIP | product | 15 | MEDIUM | D05 |
| R-P04 | KEEP_REVIEW | product | 0 | HIGH | D02 |
| R-P05 | KEEP_REVIEW | product | 1 | HIGH | D03 |
| R-T01 | MIGRATE | category | 33 | LOW | — |
| R-T02 | KEEP_REVIEW | category | 3 | HIGH | D06 |
| R-T03 | MIGRATE | brand | 11 | LOW | — |
| R-S01 | KEEP_REVIEW | static_product | 31 | MEDIUM | D02 |
| R-G01 | SKIP | page | 164 | MEDIUM | — |
| R-G02 | MIGRATE | page | 14 | LOW (5) / MEDIUM (9) | — |
| R-G03 | KEEP_REVIEW | page | 3 | MEDIUM | D12 |
| R-M01 | MIGRATE | media | 35 | LOW | — |
| R-M01b | KEEP_REVIEW | media | 190 | MEDIUM | D02 |
| R-M02 | SKIP | media | 561 | LOW | — |
| R-M03 | SKIP | media | 285 | LOW | — |
| R-M04 | MIGRATE | media | 42 | LOW | — |
| R-M04b | KEEP_REVIEW | media | 276 | MEDIUM | D02 |
| R-M05 | MIGRATE | media | 38 | LOW | — |
| R-M05b | KEEP_REVIEW | media | 1 | MEDIUM | D06 |
| R-M06 | KEEP_REVIEW | media | 6 | MEDIUM | — |
| R-M07 | KEEP_REVIEW | media | 89 | MEDIUM | D09 |
| R-X01 | KEEP_REVIEW | missing_media | 4 | HIGH | D08 |
| | | | **1.949** | | |

---

## Reglas de reducción segura (LOW)

### R-M02 — Derivados del CMS legacy

- **Descripción:** un derivado generado por el CMS antiguo desde un original conservado no se
  importa; WordPress genera sus propios tamaños.
- **Condiciones exactas:** `media-master.usage_type` contiene `DERIVED` **y** `original_ids`
  no está vacío **y** `usage_type` **no** contiene `IMAGE_USED`, `PDF_USED` ni `LOGO`.
- **Evidencia necesaria:** `original_ids` (relación derivado→original registrada por el
  propio CMS) y ausencia de referencia pública literal.
- **Tipos:** media. **Filas:** 561. **Riesgo:** LOW.
- **Casos excluidos:** cualquier derivado con flag `*_USED` (hoy: ninguno). Si apareciera,
  la regla lo eleva a HIGH y no se automatiza.
- **Resultado:** `SKIP`. No borra el archivo, no libera la URL del derivado.

### R-M03 — Infraestructura

- **Descripción:** assets de la interfaz del CMS legacy y fuentes de iconos del frontend no
  son medios editoriales.
- **Condiciones exactas:** la ruta empieza por `system/backoffice/`, `fonts/`, o por
  `system/` sin ser `system/files/`.
- **Evidencia necesaria:** estructura del árbol de archivos, confirmada por
  `content-master.classification = LEGACY_INTERNAL` para el código del mismo directorio.
- **Tipos:** media. **Filas:** 285. **Riesgo:** LOW.
- **Casos excluidos:** `system/files/` (almacén real de contenido: 258 filas) queda fuera.
- **Resultado:** `SKIP`. Si el tema nuevo necesita una fuente de iconos, se resuelve en el
  repositorio del tema, no en la Biblioteca de medios.

### R-P01 — Ficha única sin conflicto

- **Descripción:** producto que es el único registro de su grupo canónico, con
  correspondencia PHP unívoca y sin conflicto registrado.
- **Condiciones exactas:** `canonical-candidate-groups.record_count = 1` **y**
  `product-master.unambiguous_php = YES` **y** `legacy_product_id` no figura en los registros
  de conflicto D03/D06/D07 **y** el nombre no está vacío.
- **Evidencia necesaria:** grupo canónico unitario, `php_match_confidence = STRONG_INFERENCE`,
  `category_confidence` ∈ {CONFIRMED, STRONG_INFERENCE}.
- **Tipos:** product. **Filas:** 21. **Riesgo:** LOW.
- **Casos excluidos:** ID 150 (conflicto Dockman/Solmmer). Todo grupo múltiple.
- **Resultado:** `MIGRATE` a `psi_producto` en `draft` + `_psi_review_state = pending`, con
  **categorías asignadas y marca deliberadamente vacía** salvo `brand_confidence = CONFIRMED`
  (1 de 21). La marca se asigna después, al resolver D03.

### R-T01 — Categoría sin conflicto

- **Condiciones exactas:** fila de `category-master.csv` cuyo `legacy_id` no está en
  {25, 26, 33} **y** `sql_parent_confidence = CONFIRMED`.
- **Evidencia necesaria:** nombre e identificador estables, padre SQL confirmado.
- **Tipos:** category. **Filas:** 33. **Riesgo:** LOW.
- **Casos excluidos:** 25, 26, 33 (D06).
- **Resultado:** `MIGRATE` a término `psi_categoria` con `_psi_public_state = review`,
  jerarquía desde el padre SQL. **No asigna productos.**

### R-T03 — Marca con página y logo

- **Condiciones exactas:** fila de `brand-master.csv` con `legacy_page` no vacío **y**
  `logo_evidence` con referencia de línea en `marcas.php`.
- **Evidencia necesaria:** página dedicada + logo referenciado literalmente.
- **Tipos:** brand. **Filas:** 11. **Riesgo:** LOW.
- **Casos excluidos:** ninguno; los conflictos de atribución no afectan a la existencia del
  término.
- **Resultado:** `MIGRATE` a término `psi_marca` con logo, `_psi_public_state = review`.
  **No asigna productos**, ni siquiera los `inferred_strong_count`.

### R-M01 / R-M05 / R-M04 — Medios de propietario aprobado

- **Descripción:** un medio se migra cuando su propietario explícito ya está aprobado por
  otra regla.
- **Condiciones exactas:**
  - R-M01 (35 filas): la ruta aparece en `product-media-relations.csv` **y** al menos un
    `product_id` propietario recibió `MIGRATE` por R-P01 **y** el hash del maestro coincide
    con los bytes locales.
  - R-M05 (38 filas): la ruta tiene `DATABASE_CATEGORY` o `BRAND_LOGO` en
    `media-usage-evidence.csv` **y** el término propietario recibió `MIGRATE`.
  - R-M04 (42 filas): la ruta tiene `PUBLIC_SOURCE` desde una página que recibió `MIGRATE`
    por R-G02.
- **Evidencia necesaria:** relación explícita registrada + SHA-256 validado + MIME admitido.
- **Riesgo:** LOW en las tres.
- **Casos excluidos:** propietario no aprobado → variantes `b` (`KEEP_REVIEW`).
- **Resultado:** `MIGRATE` a `attachment`, con `_psi_content_sha256` y rutas de origen
  conservadas.

### R-G02 — Contenido editorial propio

- **Condiciones exactas:** `content-master.classification` ∈ {`INSTITUTIONAL_PAGE`,
  `CONTACT_PAGE`, `SEO_LANDING`} **y** `preserve_url = YES`.
- **Tipos:** page. **Filas:** 14. **Riesgo:** LOW para institucionales y contacto (5),
  MEDIUM para landings (9, confianza `STRONG_INFERENCE` y solape con productos).
- **Resultado:** `MIGRATE` a Page en `draft`. **No publicar.**

---

## Reglas de SKIP con reserva (MEDIUM)

### R-G01 — Página que reexpresa una entidad ya enumerada

- **Condiciones exactas:** `classification` ∈ {`PRODUCT_PAGE`, `CATEGORY_PAGE`, `BRAND_PAGE`}.
- **Evidencia necesaria:** la clasificación del maestro más la existencia del objeto
  propietario (producto SQL vía `related_product_ids`, ficha estática, o término).
- **Tipos:** page. **Filas:** 164. **Riesgo:** MEDIUM.
- **Cómo se evitan falsos positivos:** la regla **no** consulta similitud de texto ni de
  nombre; sólo la clasificación ya auditada del maestro, que tiene
  `classification_confidence = CONFIRMED` en 50 de las 164 y `STRONG_INFERENCE` en las 114
  `PRODUCT_PAGE`.
- **Resultado:** `SKIP` **como Page**. No borra archivo, no libera URL, no reduce
  `preserve_url`. Depende de que la fase de URLs/SEO exista: por eso no es LOW.

### R-P03 — Registros vacíos y de prueba

- **Condiciones exactas:** `name` vacío (IDs 151–164) **o** `name = "Prueba"` (ID 165).
- **Tipos:** product. **Filas:** 15. **Riesgo:** MEDIUM. **Decisión:** D05.
- **Cómo se evitan falsos positivos:** la condición es la ausencia literal de nombre, no una
  heurística. El propio importador ya rechaza estas filas con `NAME_REQUIRED`.
- **Reserva:** las 15 tienen medios asociados y categoría SQL (25), lo que sugiere altas
  iniciadas y no terminadas. Por eso requiere confirmación, no automatización.
- **Resultado:** `SKIP`. Reversible: la fila legacy y el dump permanecen intactos.

---

## Reglas de preservación (KEEP_REVIEW)

| Regla | Condición | Filas | Por qué se preserva |
|---|---|---:|---|
| R-P02 | grupo canónico con `record_count > 1` | 126 | ningún ganador determinista posible ([08](08-duplicate-merge-analysis.md)) |
| R-P04 | `unambiguous_php = NO` sin otra causa previa | 0 | definida; hoy absorbida por R-P02/R-P03 |
| R-P05 | producto en registro de conflicto D03/D06 | 1 | fabricante en disputa |
| R-T02 | categoría 25, 26 o 33 | 3 | jerarquía y nombre duplicado (D06) |
| R-S01 | ficha estática sin fila SQL | 31 | categoría y marca `UNKNOWN` |
| R-G03 | `classification = UTILITY` | 3 | función a reimplementar o descartar (D12) |
| R-M01b/04b/05b | propietario no aprobado | 467 | se desbloquean solas al decidir el propietario |
| R-M06 | `media_validation = REVIEW_TYPE_LIMIT_OR_HASH` o embed externo | 6 | fuera de política de tipos |
| R-M07 | sin relación de entidad y sin referencia pública | 89 | *ausencia de referencia no demuestra orfandad* (D09) |
| R-X01 | referencia no resuelta | 4 | no sustituir ni renombrar (D08) |

---

## Variantes evaluadas y **no** incluidas

Se calcularon y se descartan del escenario base. Se documentan para que la decisión sea
explícita, no para aplicarlas.

### V1 — R-M04c: medios de página de producto aprobada

Mover a `MIGRATE` los medios referenciados por una `PRODUCT_PAGE` cuyo `related_product_ids`
incluya un producto aprobado por R-P01. **Afectaría a 50 filas** hoy en `KEEP_REVIEW`.

Descartada del escenario base porque encadena dos inferencias: la correspondencia
página↔producto es `STRONG_INFERENCE`, no `CONFIRMED`, mientras que R-M01 usa la relación SQL
directa. Riesgo MEDIUM. Es una candidata razonable para una segunda pasada revisada.

### V2 — Unión de medios en grupos de identidad idéntica

Permitir `MERGE` en los 26 grupos donde nombre, PHP, categoría y marca coinciden, uniendo las
listas de imágenes y PDFs. **Afectaría a 52 filas de producto.**

Descartada porque la unión determina imagen destacada y orden de galería, que son decisiones
visuales, y porque podría unir fichas técnicas contradictorias (D07). Requiere decisión de
política del negocio antes de convertirse en regla.

### V3 — SKIP de medios sin referencia

Descartar las 89 filas `R12_MEDIA_PURPOSE_UNPROVEN`. **Rechazada, no aplazada.** Contradice
directamente la documentación del proyecto y D09. No se propondrá aunque reduzca el conteo.

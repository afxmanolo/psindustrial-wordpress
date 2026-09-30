# Auditoría de mapeo de URLs — Q01 + Q04

**No se implementó ningún redirect, regla de reescritura, cambio de `.htaccess` ni
decisión de slug/permalink en esta fase.** Este documento describe únicamente el mapeo
*legacy URL → entidad canónica propietaria del contenido*, la materia prima que la futura
fase de SEO/URLs necesitará para decidir sus redirects 301. El propio código de
`EditorialDecisions.php` lo dice explícitamente en cada `reason`: "no se crea Page
independiente", nunca "esta URL redirige a...".

## Qué contiene [q01-q04-url-mapping.csv](q01-q04-url-mapping.csv)

Una fila por cada una de las 164 páginas `PRODUCT_PAGE`/`CATEGORY_PAGE`/`BRAND_PAGE` de
`content-master.csv`, más una fila por cada una de las 32 fichas de
`static-product-supplement.csv` — **196 filas en total**.

```
legacy_url · source_type · source_key · decision_id · target_entity_type ·
target_entity_key · target_wp_id · confidence · status · notes
```

- `status = RESOLVED_NO_PAGE`: la propiedad del contenido quedó establecida (Q01, Q02 o
  Q04); `target_entity_key` indica qué entidad la reclama. **153 filas.**
- `status = KEEP_REVIEW`: sin propietario único y aprobado; `target_entity_key` vacío. Se
  conserva `legacy_url` y la evidencia de clasificación intacta, para cuando se responda
  Q01 en el resto de casos (fundamentalmente Q03) o se recupere el medio ausente. **43
  filas.**
- `target_wp_id`: siempre `0` en esta fase — ningún objeto WordPress real existe todavía
  (DRY RUN puro). El campo existe para que la futura fase de ejecución real pueda
  completarlo sin cambiar la forma del CSV.

## Cómo se construyó

Generado por un script de sólo lectura sobre el plan `Planner::build('full')` ya
construido — **no se recalculó la propiedad con una segunda lógica independiente**: el
`target_entity_key` de cada fila se recupera del propio texto de `reason` que
`EditorialDecisions.php` escribió en el momento de decidir, evitando que el CSV pueda
divergir de lo que realmente ejecutó el motor.

## Trazabilidad complementaria

[q01-media-ownership.csv](q01-media-ownership.csv) — 339 filas, una por cada ruta de medio
con al menos una página propietaria documentada (`media-usage-evidence.csv`,
`usage_type=PUBLIC_SOURCE`, `owner=page:*`). Por cada una: sus páginas propietarias
originales, la entidad nueva que la reclama (si alguna), su rol (`featured_image`,
`gallery`, `datasheet`, `category_image`, `logo`, `standalone_attachment`) y su SHA-256.

## Qué NO afirma este documento

- No afirma que ninguna URL vaya a dejar de responder 200.
- No decide el slug ni el permalink del producto/categoría/marca destino.
- No implica que las 43 filas `KEEP_REVIEW` vayan a perder su URL: siguen exactamente
  tan preservadas como antes de esta fase (`preserve_url=YES` intacto en
  `content-master.csv`).
- No sustituye la fase de URLs/SEO que `decision-questionnaire.md` (Q01) señaló como
  condición para aplicar redirects reales.

# FULL DRY RUN — resultado final

Run: `b69b080d-9480-44dd-84c2-e56f0ddd07d7`. Tiempo local: 4.794s. Estado VALIDATED, sin ejecución real.

**2.399 decisiones por fuente: 15 UNCHANGED, 435 SKIP de código interno y 1.949 REVIEW. CREATE/UPDATE/MERGE/ERROR ejecutados: 0.** No equivalen a2.399 contenidos ni a1.949 productos.

| Fuente | UNCHANGED | SKIP | REVIEW |
|---|---:|---:|---:|
| media | 8 | 0 | 1523 |
| category | 2 | 0 | 36 |
| brand | 1 | 0 | 11 |
| product | 2 | 0 | 163 |
| static_product | 1 | 0 | 31 |
| page | 1 | 435 | 181 |
| missing_media | 0 | 0 | 4 |

## Interpretación

Los15 objetos son el subset ya ensayado, no nuevos contenidos. El resto de maestros usa SHOULD_MIGRATE/MUST_MIGRATE o REVIEW: expresa valor/evidencia, no autorización editorial por entidad/campo. Se mantiene REVIEW hasta documentar identidad, acción, relaciones y medios. Los435 SKIP son implementación interna del CMS; no se borran archivos ni URLs.

Las617 filas de content-master incluyen435 internas y182 candidatas públicas/utilitarias. Los PRODUCT_PAGE/CATEGORY_PAGE/BRAND_PAGE siguen en el plan como fuentes; no se crea una Page adicional por cada ficha o archivo. Su propietario definitivo continúa pendiente.

## Medios

Se validaron bytes locales sin importar el inventario:1390 medios adicionales tienen tipo/bytes admitidos pero falta aprobación de propósito;130 requieren revisar tipo/límites/hash;3 son videos externos y no attachments. Los8 adjuntos del subset ya estaban aplicados. El conteo no demuestra que1390 deban migrarse.

Las4 referencias no resueltas permanecen REVIEW: images/dura.jpg, images/magic.jpg, FICHATECNICASELLOSSOLMMERS.pdf y la discrepancia Unicode del PDF INFRACA. No se sustituyó ni renombró nada.

## Informes completos

- [Todas las decisiones](importer-reports/full-decisions.csv)
- [Todos los1.949 REVIEW](importer-reports/all-review.csv)
- [Los130 medios que requieren revisión técnica](importer-reports/media-technical-review.csv)
- [Errores:0, CSV sólo cabecera](importer-reports/all-errors.csv)
- [Manifest de diagnóstico](importer-reports/full-dry-run.json)
- [Resumen e igualdad de hashes de tablas](importer-reports/full-summary.json)

La comparación antes/después de posts, postmeta, terms, termmeta, term_taxonomy, term_relationships, options, users y usermeta resultó idéntica. DRY RUN sólo genera archivos privados/reportes, no objetos o ajustes de contenido. No se conectó a la BD legacy.

## Decisión de cierre

DETENIDO después del FULL DRY RUN. No ejecución completa real, publicación, redirects, commit o push. El siguiente paso es revisar estas decisiones; no interpretar el alto número de REVIEW como permiso para saltarse las aprobaciones.

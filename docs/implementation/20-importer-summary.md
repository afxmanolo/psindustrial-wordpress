# Importador legacy — Prompt 6

Implementado en `feature/legacy-importer`, plugin `psindustrial-core` 0.3.0. Sólo destino local `psindustrial_wp_dev`; no hay conexión a la BD legacy. No se publica contenido ni se implementan redirects, SEO definitivo, frontend histórico o importación completa.

## Resultado del ensayo

Se retienen para inspección **15 objetos**: 2 categorías review, 1 marca review, 3 productos draft, 1 Page draft y 8 attachments (7 imágenes y 1 PDF). Dos filas SQL REVIEW no generan objetos. Primera ejecución CREATE15; segunda UNCHANGED15, sin duplicados. El PDF conserva sus dos rutas fuente y file676 en un único attachment. Detalles e IDs en [27](27-importer-subset-test.md).

El FULL DRY RUN y todos sus REVIEW están en [28](28-full-dry-run-report.md). Son decisiones por fuente, no conteo de entidades comerciales. No hubo ejecución completa real; el servidor también la rechaza. El gate de hasta25 objetos mutables impide convertir el subset en una carga masiva por modificar su nombre.

## Componentes

- Sources: CSV, catálogo SQL de sólo lectura, extracción estática PHP sin ejecución, validación de paths/medios.
- Planner: decisiones explícitas, dependencias, hashes, plan sellado; DRY RUN predeterminado.
- Storage: JSON/JSONL y binarios congelados privados, escrituras por reemplazo, lock de archivo y lease WordPress.
- Identity: claves privadas, búsqueda por namespace, snapshot/hashes y detección de edición humana.
- Runner: lotes, APIs WordPress, attachments/relaciones, journal, reejecución y rollback limitado.
- Admin: Herramientas → Migración PS Industrial; sin JS, workers ni WP-CLI obligatorios.

## Correcciones puntuales reveladas por regresión

Media::referenced ahora tolera Pages sin lista de PDFs; antes el cast de cadena vacía producía un fatal al borrar un adjunto de prueba. Roles::map sólo interpreta como ID los argumentos de capabilities de objetos que realmente maneja; evita convertir WP_Block_Editor_Context a entero. No son refactors de estilo ni cambios del modelo.

## Estado de Git recibido

El repositorio estaba en develop, con29 archivos de Content Admin ya en staging. Se creó feature/legacy-importer conservando ese índice. No se hizo commit/push ni se alteró el staging previo. Por ello el estado final incluye cambios heredados; revisar especialmente el diff sin --cached para esta fase. El listado propio está en importer-reports/changed-files.md.

La fase prueba un importador local conservador, no aprueba el catálogo ni certifica su salida a producción. Ver [limitaciones](29-importer-known-issues.md).

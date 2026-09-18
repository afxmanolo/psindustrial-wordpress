# Pruebas del importador

PHP8.4.24, WordPress7.1, MySQL8.0.30, DB psindustrial_wp_dev. Suite `psindustrial-core/tests/importer.php` invoca servicios reales y APIs WordPress; no mocks de persistencia. Requiere CLI para el harness, no para operar el importador.

Resultados estructurados: [tests.json](importer-reports/tests.json) y [regresión](importer-reports/content-regression.json). **67 aserciones del importador y120 del modelo administrativo** al cierre.

Cobertura: CSV/BOM/multilínea/columnas, SQL catálogo sin users, normalización, paths hostiles, PDF falso/activo, manifiesto/sellos/hashes, identidad, categorías/jerarquía/logos, creación producto/Page/estático, imagen destacada/galería/PDF, binario sin extensión, alias PDF con fileID, REVIEW sin crear, no escrituras en dry-run, primera/segunda ejecución, fuente cambiada, edición humana, checkpoints, lock concurrente, MERGE sintético y rollback controlado.

Fallos inyectados en intent/object/relations: FAILED auditable y reintento sin duplicados por bloqueo de identidad parcial. Otro ensayo comprobó que una entidad fallida no impide aplicar la siguiente. Plan inexistente/manipulado y ejecución completa se rechazan.

HTTP autenticado: pantalla200, nonce inválido400, generación de plan302, ejecución/avance de lote302. Se crea sesión administrativa efímera y se destruye al terminar. Gestor bloqueado aun con nonce válido. No se exportan cookies/tokens.

La suite conserva exclusivamente el subset descrito y elimina sus objetos sintéticos de actualización/conflicto/fallo y usuarios temporales. Las primeras dos ejecuciones se preservan como evidencia; las revalidaciones posteriores no reemplazan sus reportes.

Se detectó y corrigió un fatal preexistente del guard de medios al recorrer Page sin fichas. Se restauró la limpieza de los fixtures interrumpidos y la regresión pasó después. El log conserva ese historial; las ejecuciones finales no añadieron warnings/fatals. No se afirma ausencia histórica de errores.

El FULL DRY RUN se ejecuta por separado y **al final**, sin ejecutar el corpus completo. No incluye certificación SEO/routing/visual, catálogo comercial, antivirus, rendimiento o restauración del hosting compartido.

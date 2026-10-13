# Ronda 3 — imágenes editoriales de categorías en Home

2026-10-13. Rama `feature/client-revisions-round-3`. Sólo local, sin commit/push/merge.

## Auditoría y modelo

Antes, `home-data.php` asociaba cada raíz a una imagen estática. `home_category_sections()` en `home-presentation.php` resolvía la categoría por `category:N` y devolvía esa imagen; `front-page.php` la dibujaba con `home_image()`.

`_psi_image_id` ya era editable en TermEditor, contiene el attachment de la imagen general/importada, es escrito por el importer, consumido por `archive.php` y protegido por Identity. No se reutiliza: su valor y significado permanecen intactos.

Nuevo term meta: **`_psi_category_home_image_id`**, entero attachment ID, exclusivo de Home. El usuario autorizó exclusivamente añadir esta clave a `Identity::EDITORIAL_META_KEYS`. No se modifica snapshot(), comparación de hashes, Runner, Planner ni hashes históricos. Las pruebas usan un término sintético para demostrar que `_psi_image_id` y otros campos de contenido siguen alterando el hash y produciendo CONFLICT.

## Uso administrativo

Productos → Categorías → Editar → **Imagen para página de inicio**. Disponible también al crear una categoría. Reutiliza el selector de Media Library y preview ya existente; permite seleccionar/sustituir/quitar. Sólo las siete raíces configuradas son consumidas por Home.

Nonce propio, capability de edición de categorías, validación de attachment/MIME/archivo real en guardado y REST. Cero significa retirada explícita. El campo general «Imagen de categoría» sigue separado. La protección existente contra borrar medios referenciados incorpora la nueva relación de Home; quitar la asociación no borra archivos.

## Inicialización portable

`HomeImagesMigration::VERSION = 1`, marcador `psi_home_images_migration_version`, progreso `psi_home_images_migration_report`, lock `psi_home_images_migration_lock`.

Se ejecuta en `admin_init` prioridad 30 para `manage_options`. Tras desplegar plugin y theme juntos, abrir wp-admin para inicializar. No requiere WP-CLI, documentos, legacy ni reportes del importer. Resuelve mediante Identity::find() y claves portables; nunca IDs físicos ni nombres/slugs.

| Clave | Asset bajo `themes/psindustrial/assets/images/home/` |
|---|---|
| category:1 | industrial-home-670.webp |
| category:3 | equipos-accesorios-anden-carga-670.webp |
| category:4 | salida-emergencia-150.webp |
| category:5 | PuertasIncendios-960.webp |
| category:6 | puerta-metalica-hospital-196.webp |
| category:2 | home-comercial-600.webp |
| category:7 | residencial-2-600.webp |

Comprueba raíz existente, archivo/tipo y SHA-256. Reutiliza attachments WebP de bytes idénticos o importa con APIs WordPress y genera metadata. Conserva cualquier valor existente, incluido cero. El progreso por entidad impide repetir entradas completadas durante reintentos parciales. Sólo marca versión completa con siete resultados applied/preserved; términos ausentes o fallos quedan pendientes y generan aviso administrativo.

Las siete imágenes se inicializaron en local; sus bytes coinciden con los assets. Estos assets ya están versionados. Las clases nuevas están dentro del plugin, incluidas por `tools/staging-package/build.php` cuando se incorporen al commit empaquetado. El ZIP se construye desde Git, no desde cambios sin commit. No se modificó el empaquetador.

## Presentación

Home obtiene únicamente el nuevo term meta y usa `wp_get_attachment_image()`: dimensiones reales, lazy loading y srcset cuando hay variantes válidas. No hay fallback permanente: al quitar la imagen se omite su bloque; permanecen texto, enlaces y composición de la sección. Antes de abrir administración por primera vez tampoco se impone una imagen estática; inicializar antes de validar el despliegue público.

CSS y `narrow` permanecen intactos: Emergencia y Hospitales conservan el tratamiento estrecho. Todas las imágenes usan altura automática y mantienen proporciones, sin exigir dimensiones fijas futuras. Las originales pequeñas no se amplían para fabricar variantes responsive artificiales. Orden 1,3,4,5,6,2,7, reverse/pattern, subcategorías y menú intactos.

## Validación y límites

- Nueva integración `tests/home-category-images.php`: **48/48 PASS**; selección, retirada, MIME inválido, nonce/permisos, REST, preview, hashes, no CONFLICT editorial, protección de `_psi_image_id`, imágenes iniciales, deduplicación por hash, reejecución, fallo parcial, edición/retirada humana y HTTP Home. Fixtures eliminados y cambios temporales del nuevo campo restaurados.
- Identity existente: 41 PASS; importer: 74 PASS; Home/categorías dinámicas: 210 PASS.
- Catálogo: 111 PASS; institucional: 85 PASS; ronda 1: 61 PASS; menú Soluciones: 19 PASS.
- Home global: 36 PASS, 2 fallos editoriales previos: exige cuatro slides/indicadores, mientras la selección publicada actual tiene tres. No se cambia slider ni contenido para satisfacerlos.
- Menú dinámico de categorías: se detiene en la premisa antigua «Industrial no tiene productos», falsa en el estado actual. No se cambian relaciones.
- Se corrigió durante desarrollo un error de encoding de escritura y un error en el test nuevo; no permanecen en el resultado final.
- Navegador: imágenes desde uploads, composición desktop y tablet, sin overflow a 1440/768/375 px. No hubo edición interactiva autenticada en wp-admin; el control reutilizado y guardado/REST se probaron mediante integración.
- Sintaxis PHP: 11 archivos correctos. Última ejecución específica: cero bytes nuevos en debug.log. Los tests antiguos de frontend generan avisos al renderizar templates sin contexto global de post; no se reproduce en la prueba HTTP del cambio.
- Integridad SHA-256: 3.862/3.862 archivos legacy idénticos, sin añadidos. `git diff --check` limpio. Los siete assets necesarios están versionados y dentro de las rutas empaquetables.

Revisión manual: abrir una raíz, seleccionar otra imagen, guardar y comprobar Home; quitarla y verificar que no vuelve al abrir administración; restaurar la elegida. Confirmar que su archivo de categoría no cambia y que el menú sigue igual.

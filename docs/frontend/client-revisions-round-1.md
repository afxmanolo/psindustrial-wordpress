# Ronda 1 — cambios del cliente

Fecha: 2026-09-30. Rama: `feature/client-revisions-round-1`. Sin commit, push ni merge.

## Implementación

- **Home:** `_psi_category_home_order` separado del orden de navegación. Raíces por identidad portable: `category:1,3,4,5,6,2,7`. `home_category_sections()` ordena por ese meta; imagen y `narrow` permanecen asociados a su categoría. `reverse` depende de las posiciones 2/4/6 y `pattern` conserva el ritmo de posiciones 2/4/6/7. No se renombran términos ni slugs.
- **Cards y ficha:** helper `brand_logo()` compartido, `psi_marca` y `_psi_logo_id`, tamaño nativo `medium` sin solicitar el thumbnail recortado. Cards muestran logo/nombre, sólo nombre o nada según los datos. También en el archivo de la misma marca. Relaciones intactas.
- **Logo:** header usa una sola imagen `logo-black-big@2x.png`, dimensiones explícitas y placa blanca discreta en desktop; móvil sin placa. Footer conserva `logo-white@2x.png`.
- **Correos:** Settings incorpora `contact_email_secondary`, labels de correo público y `public_emails()`, que valida, conserva orden y elimina duplicados sin distinguir mayúsculas. Cintillo con `icon-mail`, footer, Contacto y fallback de contacto consumen esa fuente. `Contact::deliver()` continúa usando exclusivamente `mail_recipient`.

## Pasos versionados independientes

1. `HomeOrderMigration::VERSION = 1`, opción `psi_home_order_migration_version`, reporte `psi_home_order_migration_report`. Se ejecuta en `admin_init` con administrador. Resuelve las categorías mediante Identity/entity_key. Sólo escribe el nuevo orden si falta; respeta valores existentes y reintenta entidades ausentes, sin repetir las ya resueltas. No toca publicación, nombres, slugs, relaciones ni menú. `CategoriesMigration::VERSION` permanece en 2.
2. `InstitutionalContentMigration::PUBLIC_EMAILS_VERSION = 1`, opción `psi_public_emails_migration_version`. Paso separado del anterior `run()`, en `admin_init` posterior y con administrador. Principal cambia únicamente desde vacío, `contacto@contacto.com` o el email administrativo establecido anteriormente. Secundario sólo si vacío. La sanitización recibe únicamente los dos campos públicos; no cambia destinatario interno ni otros ajustes.

Ambos pasos se probaron en `psindustrial_wp_dev`; el runtime es portable a staging/producción y no lee `/docs` ni `/legacy`. No se ejecutó ninguna acción remota.

**Excepción autorizada de Identity:** únicamente se añadió `_psi_category_home_order` a `EDITORIAL_META_KEYS`. No cambió `snapshot()`, comparación de hashes, Runner ni Planner. Ninguna entidad real fue rebaselined o reconciliada por esta ronda.

## Validación

La suite nueva es `themes/psindustrial/tests/client-revisions-round-1.php`: hashes reales antes/después de la migración, variaciones mediante caché de metadatos sin persistirlas, protección de metadatos de identidad, navegación, orden/alternancia, cards, emails, idempotencia, HTML público y REST.

Se actualizaron únicamente dos expectativas anteriores afectadas por cambios aprobados: pruebas de hijos de Home ahora se indexan por identidad (no posición visual), y Home espera los dos nuevos correos públicos.

Los tests históricos se ejecutan desde copias temporales que conservan su directorio de referencia y redirigen sólo los reportes a `C:/laragon/tmp/psi-round1-suite/reports/`. No se regeneran reportes versionados ni se ejecuta/modifica el test pendiente de WhatsApp.

Empaquetado comprobado con el builder existente sobre un árbol Git temporal de trabajo, sin crear commit ni alterar el índice real. ZIP privado en temporal, no desplegado. Incluye `HomeOrderMigration.php` y los componentes modificados; no depende de documentación ni assets legacy en runtime. Un paquete de un ref ya commiteado no incluirá esta ronda hasta que el usuario apruebe su commit.

El usuario detuvo Computer Use con Escape; no se continuó la automatización del navegador. La revisión visual desktop/móvil queda pendiente. CSS mantiene dimensiones, wrapping de correos y media queries existentes; no se afirma validación visual no realizada.

### Resultados de esta ronda

- Ronda 1: **61/61**. Identity editorial existente: **41/41**. Migración institucional existente: **59/59**. Home dinámico: **210/210**.
- Frontend: catálogo **111/111**, Home/global **39/39**, institucional **85/85**, slider JS **11/11**. REST responde 200; HTML público sin diagnósticos PHP en las pruebas HTTP.
- Importer general **74/74**; ejecución local **34/34**; smoke **120/120**. La suite histórica se ejecutó completa (38 archivos PHP, excluyendo expresamente el archivo pendiente de WhatsApp).
- Fallos dependientes del estado local, sin alterar datos ni relajar aserciones: `dynamic-categories-menu.php` exige Industrial sin productos; `mojibake-content-fix.php` no encuentra intacta `sql:productos:77`; `partial-pdf-repair.php` espera esa identidad reparable o completada según su fixture histórica. Ninguno corresponde al nuevo meta de categoría.
- El primer timeout de `batch-recovery-model.php` dejó temporalmente su lease de escritura; se esperó su expiración natural y se repitieron las pruebas afectadas. No se modificaron locks ni lógica del importador. Los warnings CLI iniciales de `SERVER_NAME` se evitaron proporcionando contexto de servidor en el harness temporal; no cambiando WordPress core.
- PHP syntax correcto; `git diff --check` sin errores. Los **3.862** archivos legacy coinciden con el baseline. Archivos pendientes de WhatsApp con SHA-256 idénticos; índice Git sin cambios preparados. Sin commits, push ni merge.

Resultado final combinado tras las reejecuciones: **35/38 archivos PHP PASS**; los tres fallos son exactamente los de estado local enumerados arriba. `batch-recovery-model.php`: **96/96 PASS**. No queda timeout ni lease bloqueando la validación.

## Ajuste posterior — Soluciones alineado con Home

Solicitud posterior sustituye la excepción anterior sólo para el header: `header_solutions_navigation()` consume `home_category_sections()` y comprueba `psi_categoria` con `parent=0`. Home expone adicionalmente `term_id` sin cambiar su selección, orden o composición. No hay nuevo array de identidades ni orden duplicado. `catalog_navigation()` conserva su comportamiento para footer/sidebar; Productos y marcas desaparecen únicamente de este desplegable. No se alteran rutas, términos, slugs, jerarquía ni datos WordPress. Sin nuevas migraciones ni cambios adicionales en Identity/importer.

Prueba nueva `tests/header-solutions.php`: **19/19**, incluyendo cambios del orden sólo en caché, independencia del orden de menú, HTML HTTP real y snapshot de tablas de términos. Regresión: catálogo **111/111**, Home **39/39**, institucional **85/85**, ronda 1 **61/61**. No se regeneraron reportes del repositorio.

Comprobación en navegador integrado: dropdown abierto en **1440, 768 y 375 px**; ancho de documento **1425, 753 y 360 px** respectivamente (barra vertical excluida), sin overflow horizontal. Desktop conserva dropdown blanco, móvil despliega en flujo y los textos largos envuelven. Escape y apertura por botón conservados. No fue necesario modificar CSS/JS.

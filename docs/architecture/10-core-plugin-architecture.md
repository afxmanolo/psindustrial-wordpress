# Plugin psindustrial-core

Un plugin pequeño de dominio, sin contenedor DI, ORM ni framework. Responsabilidades: registro del modelo, schema de campos, validación/permisos, configuración comercial, relaciones, queries de catálogo, bloques de datos, rutas de compatibilidad, adaptador SEO, formulario/transporte e importación controlada.

## Árbol propuesto (sólo documentación)

- psindustrial-core.php — bootstrap/versiones, sin trabajo masivo en carga
- includes/content.php, taxonomies.php, fields.php, validation.php
- includes/capabilities.php, settings.php, catalog.php
- includes/media.php, routes.php, seo-adapter.php, contact.php
- admin/product-fields.php, term-fields.php, settings-page.php, migration-page.php
- blocks/collection/, datasheets/, contact/, whatsapp/ — registro, schema de atributos, render semántico mínimo y editor
- migration/manifest.php, planner.php, runner.php, transforms.php, report.php
- assets/admin-fields.js, admin-fields.css
- tests/ — contratos/unidades/integración de dominio; no dependencias de test en hosting
- readme.txt y uninstall.php — no borrar contenido/medios/rutas automáticamente

Secciones pueden permanecer funciones organizadas hasta que complejidad justifique clases. No portal AJAX propio ni SPA administrativa. Usar APIs WordPress; consultas preparadas sólo cuando no haya API razonable. No recrear DAO/SDO ni API REST externa.

## Límites de responsabilidad

Core registra los bloques de datos para que sigan existiendo al cambiar tema; proporciona markup semántico funcional y validación. El tema aporta composición, estilos y componentes visuales. `psi/collection` recibe tipo de contenido y IDs/términos permitidos; no SQL. `psi/datasheets` renderiza lista aprobada, `psi/contact` formulario y `psi/whatsapp` enlace de configuración. Los bloques nativos cubren el resto.

Schema de colección: mode=explicit conserva IDs ordenados; mode=filter acepta category_ids (OR entre ellas), brand_id opcional y combina ambos criterios por AND, con include_children=false. entity_type limita a productos/categorías/marcas, limit≤100 y paginación validada; no argumentos arbitrarios de WP_Query. Datasheets en producto usa la relación _psi_datasheets; en Page permite items explícitos con el mismo schema {attachment_id,label,language}. Contact no admite destinatario en atributos. WhatsApp admite etiqueta/contexto, nunca teléfono independiente. Las referencias por ID se convierten desde entity_key en cada entorno.

Catalog devuelve resultados con filtro exacto/include_children=false, productos publicados y términos aprobados. Routes devuelve permalink primario y respuesta de entrada del registro. Media resuelve adjuntos y uso compartido. Contact valida/envía mediante WordPress. SEO adapta el motor externo sin asumir plantilla o crear metadata duplicada.

## Opciones y ciclo de vida

`psi_site_settings`: teléfono E.164, mensaje WhatsApp, datos de contacto, destinatario; editor por capacidades de campo.
`psi_route_registry`: mapa versionado exacto de rutas, privado, autoload=false.
`psi_schema_version`: revisión de configuración/campos, migraciones pequeñas explícitas.
`psi_import_lock` y `psi_import_run_{uuid}`: bloqueo y checkpoint pequeños, autoload=false; retención limitada y exportación privada.
`psi_release_info`: versiones y hash del paquete/configuración sin secretos.

Activación: registro del modelo/capacidades; flush controlado; nunca importar contenido. Desactivación: no eliminar datos. Desinstalación: no borrar catálogo, medios ni claves de procedencia automáticamente; limpiar sólo transitorios propios si se solicita en otra tarea. Cambios de schema reversibles/aditivos y respaldados.

Formularios/admin guardan con nonce + capacidad + validación de objeto. REST tiene schema y auth explícita. Metadatos de negocio se registran con tipos y revisiones donde corresponda; trazabilidad/rutas no se exponen públicamente. La condición de revisión es del core, no una clase CSS ni menú oculto.

## Dependencias

WordPress/PHP8.4 y Yoast gratuito aprobado para SEO. PHPMailer se usa sólo a través de WordPress, sin versión embebida. Sin ACF, WooCommerce, builder ni SDK externo. Servicios SMTP y YouTube existentes se tratan como integraciones limitadas; no añadir proveedor por anticipado.

Importador comparte servicios de validación; ejecución administrativa separada y deshabilitada por defecto en producción mediante constante privada. No hay conexión runtime con MySQL legacy: tras corte, sitio autónomo. No usar enlaces de la copia legacy como fallback remoto ante datos ausentes.

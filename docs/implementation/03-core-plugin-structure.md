# Plugin psindustrial-core
Versión 0.1.0, namespace `PSIndustrial\\Core`, text domain `psindustrial-core`. Bootstrap explícito sin autoloader/framework/Composer.

| Archivo | Responsabilidad |
|---|---|
| psindustrial-core.php | Cabecera, guard de acceso, includes y hooks de activación |
| includes/Plugin.php | Registro de hooks; activación no destructiva |
| includes/Content.php | CPT, taxonomías y capabilities propias |
| includes/Fields.php | Contratos meta, REST, validación de referencias y marca única |
| includes/ProductEditor.php | Metabox, nonce, guardado parcial, parser YouTube |
| includes/TermEditor.php | Logo/imagen de término con APIs nativas |
| includes/Settings.php | Settings API, opción centralizada y validación |
| assets/admin-fields.js | Selector multimedia nativo, orden, etiquetas y eliminación de asociaciones |
| tests/smoke.php | Suite CLI local con fixtures sintéticos y limpieza |

Activar registra modelo, concede capacidades propias sólo a Administrator y crea opciones vacías si no existen. Flush de reglas solamente en activación/desactivación; no en cada request. Desactivar no elimina contenido, metadata ni capacidades. No existe uninstall destructivo.

No se creó un importador vacío ni carpetas sin funcionalidad. Futuros módulos de Migration, URL compatibility, SEO adapter, roles y contacto se agregarán cuando corresponda. No hay hooks que ejecuten migraciones.

Configuración única: `psi_site_settings`, accesible desde Ajustes → PS Industrial con manage_options. Campos whatsapp_number, whatsapp_message, contact_phone, contact_email. Se guardan vacíos hasta confirmación; no hay números, destinos ni envíos hardcodeados. Settings::get() es el punto de lectura futuro.

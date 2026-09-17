# Bootstrap de PS Industrial
Fecha: 2026-09-17. Rama: `feature/wp-bootstrap`. Fase de infraestructura, sin migración.

## Resultado
WordPress 7.1 instalado y operativo en PHP 8.4.24 (CLI y Apache), MySQL 8.0.30, base independiente `psindustrial_wp_dev`. Theme `psindustrial` y plugin `psindustrial-core` activos. Idioma es_ES; zona America/Mexico_City. Sin contenido legacy, Yoast, ACF, frameworks, Composer ni importador.

- Sitio: http://localhost/psindustrial-wordpress/wordpress/
- Administración: http://localhost/psindustrial-wordpress/wordpress/wp-admin/
- Catálogo temporal: http://localhost/psindustrial-wordpress/wordpress/productos/
- Usuario local: `psi_local_admin`.
- La contraseña inicial aleatoria está únicamente en el archivo ignorado `wordpress/wp-config.php`, constante `PSI_BOOTSTRAP_ADMIN_PASSWORD`. No está reproducida en documentación ni Git. Cambiarla desde Perfil y retirar después esa constante. El email del administrador es un placeholder no entregable; configurar uno propio antes de necesitar recuperación por correo.

Productos, categorías, marcas, Page, biblioteca multimedia y opciones PS Industrial están disponibles. El frontend es deliberadamente provisional.

## Comprobaciones
55 aserciones de integración aprobadas; 28 archivos PHP propios sin errores de sintaxis; 2 scripts JS con sintaxis válida. Se comprobaron por HTTP las siete rutas solicitadas y cuatro pantallas administrativas autenticadas. Core: 3.782 archivos coinciden con checksums oficiales. Legacy: 3.862 archivos idénticos al baseline, sin añadidos ni eliminados. Ver 06 y los informes JSON.

Los datos sintéticos de pruebas se eliminaron: quedan cero productos, Pages, términos propios y adjuntos. Datos de contacto vacíos. Core, configuración privada, uploads y SQL no están preparados para Git. Ningún commit ni push.

## Ajuste explícito de alcance
El diseño anterior deshabilitaba el archivo general. La petición de implementación exige probar un archive de psi_producto: se habilitó `/productos/` para este bootstrap. No decide el destino de ninguna URL legacy. La decisión de exponerlo/indexarlo en producción se revisará en la fase de URLs/SEO.

## Pasos manuales
1. Mantener Apache y MySQL iniciados desde Laragon.
2. Acceder al administrador con los datos locales indicados; cambiar contraseña, retirar su constante temporal y configurar email propio.
3. Crear categoría y marca de prueba; añadir producto, contenido, imagen destacada y relaciones.
4. Guardar categorías antes de elegir categoría principal en el metabox. Seleccionar galería/PDF mediante Medios; publicar y abrir la ficha.
5. Revisar visualmente el selector multimedia y el flujo completo del editor en el navegador habitual del cliente; no se afirma una prueba end-to-end de todos los controles JavaScript.

La enumeración completa de archivos propios está en [created-files.md](created-files.md). Límites de esta entrega en [07-known-issues.md](07-known-issues.md).

# Pruebas de Content Admin
Entorno: WordPress 7.1, PHP 8.4.24, MySQL 8.0.30, psindustrial_wp_dev. La suite local se amplía en plugin/tests/smoke.php; continúa exigiendo CLI + entorno local + DB dedicada.

## Resultados
- 33 archivos PHP propios: lint correcto.
- 2 scripts JavaScript propios: node --check correcto.
- 120 aserciones de integración, detalle en content-admin-test-results.json.
- Inicio, Page, producto, archivo, categoría, marca, PDF y 404 comprobados por HTTP.
- Creación/edición y metadata de producto/término, jerarquías y rechazo de ciclos.
- PDF válido, falsa extensión/MIME, ID negativo, logo de tipo erróneo, duplicación de imagen destacada y galería, autorrelación rechazados.
- REST, guardado parcial/metabox, nonce inválido, saneado y restauración de revisiones.
- Gestor edita productos ajenos, Pages y attachments compartidos; no puede eliminar medios/públicos, acceder a procedencia o modificar rutas públicas.
- Publicación revisada y borrador incompleto; términos review fuera de REST público/archivos.
- Escritura real de Settings API como Gestor, saneado y protección de destinatario; valores originales restaurados en finally.
- Archivo padre no agrega automáticamente productos de hijos.

## HTTP autenticado
Con cuenta temporal Gestor: Productos, Pages, Medios, Categorías, Marcas y Contacto del sitio devolvieron 200. Usuarios, Plugins, Ajustes generales, Entradas y Herramientas devolvieron 403. POST a options.php con nonce inválido devolvió 403. Destinatario privado ausente del formulario del Gestor.

## Navegador
En navegador integrado: sesión como Gestor, apertura de Gutenberg, despliegue de metaboxes, selector de galería, biblioteca compartida, selección y previsualización de imagen. Se guardó y publicó un producto temporal desde Gutenberg en modo código; H1, revisión y galería se verificaron en DB. No se tocó el archivo multimedia compartido.

El lienzo visual basado en iframe blob quedó vacío en ese navegador; Chrome no estaba disponible mediante la herramienta. No se atribuye sin evidencia al theme ni se modifica WordPress core para evitarlo. La edición visual completa debe verificarse con el navegador habitual del usuario. El guardado Gutenberg y el modal de medios sí se probaron.

## Integridad y limpieza
Suite elimina únicamente fixtures propios y restaura configuración. Se eliminó la cuenta/producto temporales de UI; se conservaron productos, medios y términos de prueba que ya existían antes de esta fase. No se importó contenido legacy.

Los 3.862 archivos legacy coinciden con el baseline, sin añadidos, cambios ni eliminados: content-admin-integrity.json. Core/wp-config/uploads/dumps siguen ignorados; sin staging Git, commit o push.

El log contenía errores transitorios de carga durante la escritura incremental de archivos nuevos (una petición AJAX llegó antes de crear Editorial.php); se completaron los archivos. Las ejecuciones finales no añadieron errores/warnings PHP al log. No se borró el historial para ocultarlos.

Comando de integración y flujo general: [05-development-workflow.md](05-development-workflow.md). No es certificación de producción, SEO histórico, importación o paridad visual.

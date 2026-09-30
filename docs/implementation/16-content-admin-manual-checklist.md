# Checklist manual de aceptación
Ejecutar sólo en psindustrial_wp_dev. Usar nombres claramente de prueba y archivos sintéticos/autorizados. No usar /legacy como destino de escritura.

## Administrator
- [ ] Entrar en wp-admin; confirmar Productos, Categorías, Marcas, Páginas, Medios y Contacto del sitio.
- [ ] Crear categoría padre y una hija; editar descripción, slug, orden, H1 e imagen.
- [ ] Verificar que En revisión devuelve 404 públicamente; cambiar a Revisado y público y comprobar archivo.
- [ ] Crear marca con nombre, descripción y logo; guardar, volver a editar y comprobar miniatura/enlace.
- [ ] Crear producto con nombre y descripción. Si Gutenberg abre Cajas meta plegadas, expandirlas.
- [ ] Guardar borrador incompleto; comprobar que publicar sin revisión aprobada muestra error.
- [ ] Seleccionar imagen destacada y dos imágenes diferentes para galería; reordenar y guardar.
- [ ] Quitar una imagen de galería; comprobar que sigue en Medios; volver a abrir y verificar el orden.
- [ ] Seleccionar/subir PDF; editar etiqueta e idioma; abrirlo, sustituir por otro y retirar la asociación. Ambos originales deben permanecer en Medios.
- [ ] Intentar PDF con tipo inválido; debe rechazarse. No usar un archivo peligroso real.
- [ ] Añadir URL YouTube y título; guardar y verificar; comprobar rechazo de otro dominio/ID inválido. Quitar la fila debe dejar la lista vacía.
- [ ] Asignar categoría hija y opcional principal; seleccionar UNA marca o ninguna.
- [ ] Elegir relacionados manuales, H1 y hero opcionales; guardar.
- [ ] Marcar Revisado y publicar. Abrir frontend: nombre/H1, cuerpo, imágenes, términos públicos, PDF y enlace de video.
- [ ] Editar producto; comprobar que los campos no desaparecen y que una revisión restaura metadata editorial.
- [ ] Probar filtros categoría + marca en Productos y columna de PDFs/revisión.
- [ ] Crear Page informativa; editar con bloques nativos y revisión; comprobar que no existe CPT de landings.
- [ ] En Contacto del sitio, probar número/email inválidos y valores válidos autorizados; comprobar enlace WhatsApp sin enviar mensaje. No dejar contactos ficticios activados.
- [ ] Revisar por separado el destinatario privado del futuro formulario.

## Gestor de contenidos
- [ ] Crear cuenta nominal temporal con rol Gestor y entrar en sesión separada.
- [ ] Repetir alta/edición de producto, selección de medios y carga de PDF.
- [ ] Editar alt/título de un medio compartido sin privilegios sobre Entradas.
- [ ] Crear categoría hija y marca/logo; editar descripciones. No debe poder borrar términos.
- [ ] Editar Page/producto de otro autor. Publicar sólo contenido revisado.
- [ ] Borrar borrador propio; intentar borrar publicado o medio: denegado.
- [ ] Intentar cambiar URL o retirar publicado: denegado/conservado. Recargar editor tras publicar para refrescar capacidades de UI.
- [ ] Intentar cambiar padre/slug de término público: sólo Administrator puede.
- [ ] Abrir directamente users.php, plugins.php, options-general.php, edit.php y tools.php: 403.
- [ ] Contacto del sitio disponible; destinatario privado, SMTP y ajustes críticos inaccesibles.
- [ ] Confirmar Gutenberg visual en Chrome/Edge habitual: título, párrafo, imagen, guardar/publicar y volver a abrir. Este punto requiere validación externa a nuestro navegador integrado.
- [ ] Probar selector multimedia mediante teclado y pantalla estrecha.
- [ ] Al terminar, Administrator elimina sólo datos/cuenta creados para esta prueba. No borrar originales compartidos ni archivos históricos.

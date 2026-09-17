# Usuarios y capacidades

Sólo Administrator y `psi_gestor` (Gestor de contenidos). Sin registro/login público ni área privada de clientes. Se crean cuentas nuevas nominales con mínimo privilegio; la única cuenta legacy y sus hashes no aportan continuidad editorial necesaria. No migrar user/permission. Atribuir importaciones a una cuenta administrativa autorizada, conservar autoría legacy sólo si llegara a existir evidencia necesaria.

## Matriz de capacidades

| Operación | Gestor | Administrator |
|---|---|---|
| Leer admin y perfil | read | Sí |
| Crear/editar productos propios y de otros | edit_psi_productos, edit_others_psi_productos, edit_published_psi_productos, edit_private_psi_productos, read_private_psi_productos | Sí |
| Publicar producto revisado | publish_psi_productos + validación core | Sí |
| Borrar borrador propio | delete_psi_productos, sujeto a estado/propiedad | Sí |
| Borrar otros/publicados/privados | No delete_others/delete_published/delete_private_psi_productos | Sí, con plan de URL |
| Crear/editar Pages | edit_pages, edit_others_pages, edit_published_pages, publish_pages | Sí |
| Borrar Pages públicas/de otros | No delete_published_pages/delete_others_pages | Sí, con revisión URL |
| Categorías/marcas: crear, editar, asignar | psi_manage_categories/brands, psi_edit_categories/brands, psi_assign_categories/brands | Sí |
| Cambiar padre de término público | No; core exige psi_manage_structure | Sí |
| Borrar términos | No psi_delete_categories/brands | Sí, validar uso |
| Subir/editar medios | upload_files + psi_edit_media | Sí |
| Borrar medios/compatibilidad | No | Sólo tras análisis de referencias |
| SEO title/description del contenido editable | psi_edit_content_seo + capacidad del objeto | Sí |
| Canonical, robots/rutas, merges/importación | No | psi_manage_routes, psi_manage_migration, psi_manage_seo |
| WhatsApp/datos públicos | psi_edit_contact_settings | Sí |
| SMTP/destinatario/secretos | No | manage_options más configuración privada |
| Usuarios/plugins/tema/core/actualizaciones | No | Capacidades administrativas nativas |

`edit_psi_producto`, `read_psi_producto`, `delete_psi_producto` son metacapacidades mapeadas al objeto; no basta una comprobación de rol por nombre. Taxonomías registran explícitamente su arreglo capabilities, no reutilizan manage_categories del blog. Añadir rol/caps por versión de schema sin reinicializar a mano los roles en cada carga.

## Medios y APIs: condición de implementación

Attachment comparte varias capacidades de post por defecto. Se diseñará un mapeo por objeto para psi_edit_media que permita editar atributos de attachments propios/compartidos sin otorgar borrado ni edición de Entradas. Se deberán probar wp.media, biblioteca, AJAX/REST y permisos de edición del attachment; no conceder edit_posts/delete_posts indiscriminadamente para resolver una pantalla.

Ocultar metaboxes o menús SEO no protege guardados: validar cambios de campos sensibles en todos los canales y filtrar el editor avanzado del proveedor. Un gestor que edita una Page no adquiere permiso para cambiar sus rutas publicadas o modificar opciones globales. El importador sólo corre con psi_manage_migration y permiso ambiental; ningún endpoint nopriv.

Capacidad unfiltered_html no se concede. Contraseñas únicas, HTTPS, mínimo una cuenta Administrator de recuperación custodiada y sin compartir. MFA recomendado para administradores si hosting/solución mantenida lo soporta; su mecanismo se decide al preparar seguridad operativa sin inventar soporte actual de Hepsia. Limitar intentos de login en hosting o solución compatible comprobada; no crear un autenticador casero.

Ocultar Entradas/Comentarios no los vuelve seguros por sí solo: rol no tiene capacidad de publicar Entradas, y pruebas de URLs directas/REST deben demostrarlo. Administrator conserva menús completos; no se elimina WordPress nativo ni se hackea su core.

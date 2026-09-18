# Gestor de contenidos
Identificador psi_gestor. Instalación de capacidades versionada mediante psi_schema_version=2: no se reescribe el rol en cada petición. Rol creado, sin crear ni migrar cuentas permanentes. Administrator asigna el rol a cuentas nominales desde Usuarios.

La matriz de [arquitectura 14](../architecture/14-users-and-capabilities.md) es la referencia. Roles.php implementa:

- Productos: editar propios/de otros, públicos/privados; leer privados; publicar revisados. Borrar sólo borradores/pendientes propios.
- Pages: crear/editar/publicar, incluidos contenidos de otros; borrar sólo borradores/pendientes propios. Sin privilegios extra para Pages privadas.
- Términos: manage/edit/assign categories/brands. Sin delete_terms ni psi_manage_structure.
- Medios: upload_files y psi_edit_media; map_meta_cap permite editar attachments compartidos. No edit_posts/delete_posts para desbloquear la biblioteca.
- Contacto público: psi_edit_contact_settings.
- SEO contextual: psi_edit_content_seo reservado; sin proveedor instalado.
- Sin instalación/edición de plugins/tema, usuarios, actualizaciones, manage_options, exportación, importación, rutas, estructura pública o unfiltered_html.

Las metacapacidades de producto se resuelven por objeto. El borrado de medios por Gestor se deniega; Administrator tampoco puede borrar un medio con referencias detectadas en el modelo, bloques/URLs o términos. La lista de capacidades completas está explícita en Roles.php; no se hereda el rol Editor con privilegios sobrantes.

## Protección de contenido público
El Gestor no puede cambiar slug, padre de Page o estado de contenido publicado/programado para retirarlo. REST devuelve 403; el guardado nativo conserva esos valores y avisa. La retirada debe solicitarse al Administrator fuera del sistema; no se creó una aplicación de tickets.

Términos públicos: slug y jerarquía protegidos, retiro a review reservado. Ocultar menús es únicamente UX. Usuarios, Plugins, Ajustes generales, Entradas y Herramientas se comprobaron directamente por HTTP: 403.

Administrator conserva capacidades completas, incluidos permisos propios. Las reglas semánticas de datos (ID/MIME, ciclos, revisión y medios referenciados) también se aplican al administrador.

## Alta manual
Crear una cuenta nominal desde Usuarios, asignar Gestor de contenidos, usar contraseña única y probar en sesión separada. No dejar cuentas de prueba ni compartir el administrador. Las cuentas temporales de esta fase fueron eliminadas.

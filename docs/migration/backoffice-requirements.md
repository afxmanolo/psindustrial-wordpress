# Requisitos documentados del administrador

Esta matriz separa capacidad visible en código de uso operativo: no se observó una sesión administrativa real. «Nativo» indica capacidad general disponible para cubrir la necesidad; no fija implementación, plugins ni arquitectura. Evidencia base: `legacy/public/system/libs/ProjectLibrary/FrontEnd/BO/` (abreviado BO), vistas `system/backoffice`, y análisis de fase 1.

| Función actual | Usada por | Datos afectados | Equivalente WordPress nativo | Desarrollo personalizado necesario | Puede descartarse | Evidencia |
|---|---|---|---|---|---|---|
| Login/logout | Administrador, uso efectivo no medido | user/sesión | Usuarios y autenticación | No portar autenticación legacy; configuración futura | Motor legacy sí; acceso administrativo no | BO_Authentification; system/backoffice/index.php |
| Alta/edición de producto | Usuario autorizado | nombre, HTML, fecha, categoría, marca | Edición de contenido | Representar y validar relaciones específicas | No | BO/Productos.class.php:148–156,246 |
| Borrado de producto | Rama Super | productos | Papelera y capacidades | Definir permisos seguros de negocio | No eliminar necesidad de retirar contenido; no replicar Super incoherente | BO/Productos.class.php:314; enum SQL Admin/User |
| Selección de categoría y marca | Editor de producto | Dos IDs escalares | Gestión de términos | Validación de relaciones y tratamiento de asociaciones múltiples detectadas | No | BO/Productos.class.php:150–151; product-master.csv |
| Gestión de categorías/padres | Administrador | categorias, padre_id, imagen | Gestión jerárquica de términos | Imagen y reglas editoriales | No | BO/Categorias.class.php:132,240,298 |
| Gestión de marcas/logos | Endpoint autenticado; enlace de menú comentado | marcas, imagen | Gestión de términos y Media como base | Asociación de logo y orden explícitos | No; campo imagen vacío no implica ausencia de logos | BO/Marcas.class.php:133,244; marcas.php; iconos-marcasv1.php |
| Imágenes de producto | Editor | Lista de IDs, hasta 5 en UI | Biblioteca de medios | Asociación y orden de galería | No; límite 5 no debe asumirse requisito de negocio | BO/Productos.class.php:153,272 |
| Fichas técnicas | Editor | Lista de IDs, hasta 5 | Media attachments | Asociación, etiqueta/orden y continuidad de descarga | No | BO/Productos.class.php:154,289; product-media-relations.csv |
| Variantes de imagen | Procesamiento interno | original/small/medium/large | Tamaños derivados | Conservar referencias/compatibilidad cuando proceda | Motor legacy probablemente sí; imágenes necesarias no | file.original_id; ImageHandler |
| Video | Campo BO oculto; tres PHP con iframe | Ningún valor SQL; YouTube en fichas | Edición de contenido/embeds como base | Representación administrable por decidir | No descartar los tres embeds existentes | Productos.class.php:155; MOOVI:70, ICARO:43, P45:42 |
| Páginas institucionales y SEO | Edición manual de archivos | HTML/metadata estáticos | Edición de páginas | Campos/presentación a precisar en otra fase | No | content-master.csv; 9 SEO_LANDING |
| Listado, búsqueda, orden y paginación | Administrador | Lectura de entidades | Listados administrativos | Columnas/filtros específicos si necesarios | No para funciones básicas | BO/Productos.class.php:45; Categorias:45; Marcas:45 |
| Exportación Excel | Rama toExcel; uso desconocido | Listados | No equivalente específico obligatorio | Sólo si negocio confirma necesidad | REVIEW | BO/Productos.class.php:176; Marcas:58; Categorias:57 |
| Alta/edición de usuarios | Administrador | user | Usuarios/roles | Reasignación de responsables autorizados | No trasladar hashes ni roles obsoletos | BO/User.class.php:179,249,304 |
| Permisos nominales | Estrategia comentada; tabla vacía | permission | Roles/capacidades | Matriz mínima de acceso por decidir | Tabla y mecanismo probablemente sí | BO::checkPermission; permission sin INSERT |
| Direcciones/sucursales heredadas | Ramas sin soporte suficiente en esquema | Entidades no demostradas | No requisito demostrado | Ninguno mientras no exista necesidad verificada | PROBABLY_NOT_REQUIRED | BO/User.class.php:19–43,332 |
| Contacto y correo | Visitante; destinatario configurado | Nombre/email/mensaje; sin leads SQL activos | No formulario de contacto listo por defecto | Solución de formulario/envío/validación por decidir | No | contacto.php, enviaContacto.php:46–78 |
| WhatsApp | Visitante | Enlace, sin API | Enlace editable | No integración externa necesaria demostrada | No | footer.php:1, footerv1.php:1 |

El nuevo administrador debe cubrir contenido estático además del SQL: hoy editar un registro no modifica su ficha PHP hardcodeada. No reproducir el CMS interno, las tablas de sesión, el motor de plantillas, los roles contradictorios ni un CRUD de cada tabla técnica. La biblioteca de medios de negocio debe gestionar adjuntos y su relación; no necesita exponer las 1.355 filas históricas como objetos editoriales independientes.

Persisten límites operativos de fase 1: resolución Savant de vistas, autenticación y permisos no fueron probados en ejecución. No se afirma que todas las ramas del backoffice funcionen actualmente.

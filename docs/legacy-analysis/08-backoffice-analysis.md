# Backoffice

## Entrada, autenticación y sesión

system/backoffice/index.php instancia BO_Authentification; muestra login o main. POST cmd=login compara md5(password) con usuario encontrado por email; logout limpia identidad guardada, sin rotación/destrucción completa de sesión demostrada. common.php inicia sesión. Los endpoints de entidades llaman verifyAuthentification antes de ejecutar.

Login acepta Admin, User y Super; esquema sólo permite Admin/User. Tabla permission vacía en el dump poblado; existen clases Access, pero BO::checkPermission tiene la estrategia comentada. No debe asumirse una matriz fina de permisos por su presencia. backoffice/includes/header.php muestra Inicio, Usuarios, Categorias, Productos; Marcas está comentado. El viejo menu.php referencia módulos ausentes: no demuestra noticias/proveedores en uso.

## Funciones presentes en código

| Área | Acciones y campos | Límites/evidencia |
|---|---|---|
| Productos | list, add/edit, save, delete; nombre, categoría, marca, HTML, fecha, imágenes, fichas | hasta 5+5; video hidden; borrado exige Super |
| Categorías | list, add/edit, save, delete; nombre, padre, imagen | descripción hidden, una imagen; selector de padres raíz |
| Marcas | list, add/edit, save, delete; nombre, imagen | endpoint existe; menú oculto; un logo |
| Usuarios | listado, alta/edición, guardado, borrado; email, nombres, rol, contraseña, archivo | roles del enum; ramas heredadas de sucursales/direcciones no respaldadas por esquema |
| Listados | búsqueda q, orden o, página p, tamaño k; toExcel | exportación desde controlador; validar interfaz efectiva |
| Archivos | uploads integrados, descripciones, manejo de referencias y versiones | no se demostró biblioteca global comparable a Media de WordPress |

FrontEnd/BO/Productos.class.php:25 despacha comandos; _save lee entity, construye entidad y utiliza ImageHandler dos veces. SDO/Core/Validator/Productos.class.php:16–32 tiene setup comentado; no hay validación de dominio efectiva por esas pruebas. La validación de uploads es un recorrido separado.

## Límites de lo que puede afirmarse

Savant activo busca system/view, pero list/edit están en system/backoffice; constructor anterior para BO comentado. Hay ramas que llaman clases/campos ausentes. El redirect de autenticación no hace exit en Application::redirect; verificación y resolución deben probarse en entorno aislado antes de afirmar barreras efectivas. No se inició sesión ni se realizaron mutaciones.

Borrado de categorías/marcas carece de FK hacia productos que impida huérfanos. Borrado de producto exige Super y puede quedar inaccesible con el esquema entregado. No asumir borrado coherente de medios: file tiene cascada original→versiones en SQL, pero producto→file está serializado y eliminación de binarios requiere lógica separada.

## Equivalencias conceptuales

WordPress ofrece identidad, sesiones, administración de contenido, taxonomías y medios como capacidades base. Requieren plugin: registrar CPT/taxonomías/capabilities, metadatos de logo/PDF, relaciones ordenadas, importador e IDs, compatibilidad de rutas y pruebas de permisos. Exportación equivalente sólo si se confirma su uso. Tema presenta frontend; no contiene reglas de negocio. No reproducir roles incoherentes ni portar el motor del CMS.

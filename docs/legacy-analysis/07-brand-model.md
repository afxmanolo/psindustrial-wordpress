# Marcas y clic sobre logos

12 marcas: Overhead Door, Wayne Dalton, Clopay, Blue Giant, Kelley, Doorlock, Rytec, Infraca Quality Doors, GLG Porter Industriali, Dockman, LiftMaster y BFT. marcas-catalog.csv conserva IDs y referencias de imagen exactas.

**Hay dos comportamientos del clic:**

1. iconos-marcas.php tiene 12 logos y enlaces fijos a overhead-door.php, wayne-dalton.php, clopay.php, blue-giant.php, kelley.php, doorlock.php, rytec.php, infraca-quality-doors.php, glg-porte-industriali.php, dockman.php, lift-master.php, bft.php. Esas páginas contienen cards y enlaces propios; no se consulta SQL para seleccionar sus productos.
2. iconos-marcasv1.php:1–19, utilizado por portada, consulta GetAllMarcas, construye enlace con getFriendlyNameUrl y envía al filtro `/productos/marca/{id}/{slug}/`. **El logo no procede de marcas.imagen:** usa un array fijo de 12 nombres de imagen por índice del bucle. Agregar/reordenar marcas puede desalinear logo y marca. El nombre/alt sí procede de SQL.

links-menu-marcasv1 también produce enlaces de entidades; versión sin v1 utiliza páginas fijas. marcas.php y marcasv1.php son directorios distintos. system/Marcas.php ofrece además un controlador FO genérico con vistas sencillas de IDs; no es el destino principal del logo.

SQL guarda nombre e imagen, sin descripción editorial, slug o SEO propios. BO dispone de CRUD y subida de un logo con versiones 250×120, 500×240 y 1000×480; su opción de menú está comentada en backoffice/includes/header.php:21.

La relación con producto es productos.marca, sin FK y con 110 productos en 0. Las páginas de marca fijas siguen presentando sus propios productos: migrar sólo esa columna perdería asociaciones de navegación. bft.php tiene SEO escrito como «BTF», diferencia que se registra y no se corrige. Una taxonomía con logo sería adecuada como hipótesis, pero requiere reconciliar cuál logo/contenido es oficial y preservar ambas familias de URLs.

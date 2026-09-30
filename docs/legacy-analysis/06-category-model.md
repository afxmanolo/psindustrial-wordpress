# Categorías y subcategorías

38 categorías: 7 con padre NULL, 1 con padre 0 y 30 con padre positivo. Las referencias de padre positivas existen; no se encontró un tercer nivel en este dump. `categorias-catalog.csv` detalla cada ID, nombre, descripción, imagen y padre sin normalizar el contenido.

GetCategoriasPadre y GetAllInArray incluyen NULL **o** 0. La portada, sin embargo, consulta únicamente IDs 1–7; la categoría 26 («Puertas contra incendio», padre 0) no forma parte de esos siete bloques. Hay además categoría 33 con el mismo nombre bajo padre 5. No son intercambiables sin decisión editorial.

Jerarquías raíz 1–7: Industrial; Comercial; Equipos y Accesorios para Anden de Carga; Puertas peatonales de Salida de Emergencia; Puertas peatonales contra Incendio, Contra Explosión y Blindadas; Puertas peatonales para Hospitales; Residenciales. Algunos valores del dump tienen mojibake; estas son etiquetas explicativas, el CSV conserva la evidencia original.

Dos URLs cumplen funciones distintas: getFriendlyNameUrlPadre genera `/{id}/categoria/{slug}/` para mostrar hijos; getFriendlyNameUrl genera `/productos/categoria/{id}/{slug}/` para productos de ese ID exacto. No hay búsqueda recursiva de productos descendientes. Hay productos asignados directamente a raíces 1 y 7, que no deben perderse al diseñar archivos de taxonomía.

La vista CategoriasMain usa **descripcion**, no nombre, para catName, title/H2; muestra cards con imagen medium y enlace de cada hija. BO oculta descripción, permite nombre y padre seleccionado entre raíces y una imagen; genera 239×239, 478×478, 956×956. La base permite relaciones arbitrarias, pero la UI orienta a dos niveles.

Frontend fijo: header.php, links-menu.php y páginas de soluciones contienen enlaces estáticos; frontend v1 obtiene raíces y categorías de SQL. Coinciden en grandes líneas, pero no constituyen la misma jerarquía verificable: categoría 26, asociaciones cero y páginas fijas son divergencias concretas. Ver enlaces literales y relaciones estáticas; no se impuso correspondencia por nombre.

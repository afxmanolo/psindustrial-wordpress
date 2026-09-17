# Patrones y duplicación

## Recuento defendible

198 PHP raíz = 179 páginas de contenido + 2 controladores + 16 fragmentos + 1 procesador. 617 PHP totales. Público significa función aparente de contenido/entrada sin login, no respuesta HTTP ni publicación actual demostrada.

| Familia raíz | Cantidad | Características |
|---|---:|---|
| Detalle estático probable | 123 | hero interior, menú lateral, cuerpo técnico, imágenes/PDF opcionales |
| Listado estático | 35 | grids/cards y enlaces fijos; categorías/landings/variantes |
| Marca estática | 12 | listado con agrupación editorial de marca |
| Inicio | 3 | index.php, index-estatico.php, index-resp.php |
| Directorio de marcas | 2 | marcas.php y marcasv1.php |
| Institucional/privacidad/contacto/soluciones | 4 | una de cada |
| Controladores | 2 | productos.php, categorias.php; sin layout propio |

Catálogo dinámico principal: **tres vistas** ProductosView, ProductosMain y CategoriasMain. ProductosMain comparte categoría, marca y combinación. Vistas FO genéricas de marcas y otras entidades son restos/entradas auxiliares; no se les atribuye relevancia visual sin comprobar uso.

Estimación: **10–12 familias de composición**, según se agrupen estático/dinámico y variantes institucionales. No es equivalencia pixel a pixel. Detalle tiene variantes de menú soluciones/seccionales, con/sin PDF, una/múltiples imágenes y cuerpos largos; no deben aplanarse.

## Método y límites

1. Clasificar por includes, controladores y contenido; inventario individual en file-inventory.csv.
2. Excluir comentarios HTML/PHP; reconocer cards antes que PDF para no etiquetar listados como productos por una ficha.
3. Marcas: destinos literales de iconos-marcas.php; separar institucionales/directorios/portadas por comportamiento.
4. Secuencia ordenada de etiquetas ignorando texto/atributos/PHP: 145 huellas para 181 páginas/controladores. Cambiar número de párrafos/cards/imágenes altera huella sin cambiar diseño.
5. Duplicación textual por líneas no triviales, sin comentarios, espacios normalizados, longitud ≥40: 741.924 caracteres; 331.890 repetidos entre archivos descontando primera ocurrencia: **44,7 %**. No significa 44,7 % de todos los bytes del sitio.
6. SHA-256 de todos los archivos: 271 grupos idénticos. duplicate-files.csv distingue assets/dependencias de PHP.

Cambian nombre, SEO, copy técnico, imágenes, PDF y enlaces. Familias de seccionales, cortinas, operadores y peatonales comparten composición. 1500-revolving-door.php conserva H2 de otra puerta; ProductosView conserva alt fijo: consecuencias concretas de copiar estructura.

No se clasificó un archivo como eliminado/obsoleto sólo por resp/v1/back. Variantes siguen siendo URLs físicas y algunas tienen referencias. Contadores por basename son orientativos, incluyen comentarios y coincidencias; clases/includes tienen inventarios específicos. Confirmar uso con logs/sitemap/despliegue antes de retirar.

# Productos

## Dos fuentes de contenido

165 filas SQL y 123 PHP clasificados como detalle estático por su estructura. No hay una columna que vincule el producto con el PHP antiguo; no son conjuntos intercambiables. `static-catalog-relations.csv` conserva enlaces directos desde listados/marcas; no los convierte en claves foráneas.

| Propiedad | Evidencia y representación |
|---|---|
| Identidad/nombre | productos_id, nombre; Entity/Productos.class.php |
| Descripción | HTML en descripcion; BO lo conserva, vista lo imprime sin escape |
| Imágenes | IDs file separados por comas; getImagenes los divide y mantiene orden |
| Fichas | IDs file separados por comas; múltiples enlaces system/file.php |
| Video | columna varchar, setter y campo BO hidden; 0 valores poblados; ProductosView no lo muestra |
| Categoría/marca | un entero cada una; no tabla N:M; 54/110 valores cero respectivamente |
| Fecha | date; formulario usa fecha actual por defecto cuando está vacía |
| URL | ID + getFriendlyName; no slug persistido ni historial |
| SEO | nombre + categoría + marca, descripción compuesta y sufijo común; sin campos SEO específicos |
| Relacionados | sin relación específica en esquema ni bloque de recomendación demostrado; listados son navegación |

BO (`FrontEnd/BO/Productos.class.php:133–156,246–299`) permite hasta cinco imágenes y cinco PDFs. Genera versiones 150×108, 403×403 y 700×600. Estos tamaños solicitados no garantizan recorte exacto; interviene el resizer. La vista usa medium para cards y large para detalle.

`ProductosView.php:38–55`: primero PDFs, luego nombre, HTML de descripción y bucle de imágenes. Alt de todas las imágenes está fijado a «AccessPRO FS1000SPEED»: no es el nombre real del producto. El H1 muestra categoría/marca mediante animación JS; el producto se sitúa en H2 y también H5.

Los PHP individuales contienen textos, imágenes y enlaces PDF fijos y normalmente sólo PHP para variables SEO/includes. Ejemplo `1500-revolving-door.php`: habla de puerta giratoria, pero su H2 animado dice «Puerta Holandesa». Es evidencia de errores por copia, no permiso para corregirlos ahora.

Migración futura deberá reconciliar texto, imágenes, PDFs, nombre y asociaciones por entidad; conservar URL de origen e ID. No rellenar automáticamente categoría/marca 0, ni fusionar por similitud de nombres. Catálogo seguro por ID en productos-catalog.csv; no se exportaron cuentas ni hashes.

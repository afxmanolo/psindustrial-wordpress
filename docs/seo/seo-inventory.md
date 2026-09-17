# Inventario SEO legacy

## Alcance y fuentes

2026-09-17. Se analizó la copia local, no se rastreó producción. `url-inventory.csv` contiene **877 URLs únicas**: páginas PHP candidatas, sitemap, rutas por parámetros y amigables derivadas del código/dump, endpoints auxiliares y PDFs. **No son 877 páginas publicadas ni indexadas.** Todas conservan indexable=UNKNOWN porque faltan status HTTP, cabeceras de servidor y verificación de indexación.

La columna notes distingue URL literal de sitemap/enlace de URL probable generada. legacy_file identifica implementación cuando se conoce; en rutas literales sin controlador resuelto puede identificar archivo de referencia y notes lo explica. No se definieron nuevos destinos ni redirects de migración.

## Extracción por página

- 179 páginas de contenido raíz y 2 controladores clasificados. PHP inalcanzable después de die en productos.php excluido.
- Variables literales title/description + sufijo de header reconstruyen metadatos de páginas fijas. 46 páginas no definen title y heredan título genérico; hay 5 grupos de títulos repetidos.
- `page-details.csv`: H1, H2, robots, schema y recursos por archivo raíz. 172 archivos tienen H1 con data-fancy-text; se registra `[JS: …]` separado de texto HTML vacío. No se confundió H1 vacío en fuente con ausencia del texto que JS intenta introducir.
- `dynamic-page-details.csv`: detalle por URL derivada con H1/H2 de fórmulas y límites de medios. No simula una respuesta PHP.
- `images-alt-inventory.csv`: img/src/alt de páginas y fragmentos literales expandidos. UNKNOWN_PHP marca expresiones que no se evaluaron. Imágenes de fondo no tienen alt y requieren referencias CSS/estilos; el inventario de archivos incluye esos assets.
- `links-inventory.csv`: enlaces internos/externos, resolución de URL literal y existencia local cuando aplicable. No afirma HTTP 404 por una ruta amigable sin archivo físico.
- `url-inventory.csv`: columnas solicitadas, campos sin evidencia vacíos; PDFs dinámicos conservan endpoint por ID y ruta física reconciliada.

No se encontraron canonical, robots meta ni structured data activos en la extracción del frontend principal/cabeceras. No hay robots.txt raíz en la copia. Cabeceras HTTP X-Robots-Tag, reglas de hosting y datos insertados por servicios externos quedan UNKNOWN. La búsqueda estática de schema no equivale a validar un DOM renderizado.

## Reglas de URL demostradas

`.htaccess` completo relevante:

```apache
RewriteEngine on
RewriteRule ^productos/([\d]+)/([\d]+)/(.*)/$ productos.php?cmd=search&CategoriaId=$1&MarcaId=$2 [QSA,L]
RewriteRule ^productos/categoria/([\d]+)/(.*)/$ productos.php?cmd=search&CategoriaId=$1 [QSA,L]
RewriteRule ^productos/marca/([\d]+)/(.*)/$ productos.php?cmd=search&MarcaId=$1 [QSA,L]
RewriteRule ^([\d]+)/producto/(.*)/$ productos.php?cmd=loadProductos&productId=$1 [QSA,L]
RewriteRule ^([\d]+)/categoria/(.*)/$ categorias.php?cmd=search&categoriaId=$1 [QSA,L]
ErrorDocument 404 /404.php
```

Sin RewriteCond, flags R=301/R=302, ni eliminación de .php. Son cinco rewrites internos. El texto final no se valida contra nombre; los IDs controlan la consulta. Potencial de múltiples aliases para la misma entidad. Slash final exigido por regex; comportamiento de URL sin slash depende de configuración no entregada. QSA conserva parámetros.

Generadores: SDO/Core/Application/Productos:318–320; Categorias:361–368; Marcas:311–313. Util_String::validStringForUrl reemplaza caracteres y espacios, y devuelve sintitulo si vacío; no es el algoritmo de slug de WordPress. Se derivaron 223 rutas nominales del dump como candidatos. Codificación/mojibake y lowercase pueden afectar bytes, por lo que estas rutas requieren validación antes de usarlas como redirect source definitivo.

## Metadatos dinámicos

Producto: nombre + categoría + marca; descripción «Productos {nombre} Marca {marca}», luego sufijos comunes. Cuando categoría/marca=0, inventario no garantiza metadatos de detalle: la carga de entidad vacía puede fallar o producir texto incompleto.

Listado: title combina «Marca {marca} » y/o «Productos {categoría}»; descripción antepone otra vez «Productos». H1 animado indica Marca o Categoria y H2 el nombre. Categoría padre utiliza **descripcion** como título visible y title, no nombre.

En páginas fijas hay errores de copia: bft.php titula BTF; 1500-revolving-door.php muestra H2 Puerta Holandesa. En detalle dinámico alt de imagen es AccessPRO FS1000SPEED para todos. Se documentan; no se corrigieron ni se presume que deba conservarse cada error editorial.

## Sitemap y recursos

sitemap.xml contiene 207 entradas, incluida raíz e index.php; es estático y declara fechas de 2025. No prueba que cada URL exista ahora ni que incluya catálogo dinámico completo. Se conservaron entradas sin correspondencia local como UNKNOWN.

80 archivos físicos con extensión PDF, además de 159 registros MIME PDF (originales sin extensión) en file. No sumarlos como documentos comerciales únicos: puede haber duplicados. El endpoint devuelve inline por defecto y attachment con cmd=download.

Enlaces literales sin resolución exacta local encontrados: industial.php en index-resp; fichas/INFRACA-RÁPIDA-APILABLE.pdf en rapida-apilable (posible diferencia Unicode); fichas/FICHATECNICASELLOSSOLMMERS.pdf en sellos-nacionales. No afirmar su status remoto. El destino ErrorDocument 404.php también está ausente.

La futura decisión por URL debe registrar contenido actual, destino aprobado, conservar/301/otro status y evidencia. Tráfico, backlinks, canonicals HTTP y screenshots son entradas pendientes; ninguna URL fue modificada.

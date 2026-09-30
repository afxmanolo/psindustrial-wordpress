# Método y lectura de los inventarios — fase 2

Fecha: 2026-09-17. Análisis estático, sin ejecutar PHP, importar SQL, iniciar sesión, enviar formularios ni consultar producción. Se volvió a leer AGENTS.md y la documentación de la fase inicial. Los resultados de esta fase precisan sus conteos; no certifican que la copia local sea la versión actualmente publicada.

## Unidades que no deben confundirse

- **Registro SQL**: una fila, conservando su ID. Hay 165; no equivalen a 165 entidades comerciales únicas.
- **Página**: archivo PHP con contenido, aunque comparta producto con otra página. Su URL se conserva provisionalmente.
- **Grupo candidato**: registros que corresponden a la misma ficha estática. No es autorización para fusionar registros ni URLs.
- **Registro file / ruta física / bytes únicos**: son tres unidades diferentes. 1.355 filas file apuntan a 820 rutas. Una ruta puede representar varios IDs y varias rutas pueden tener bytes idénticos.
- **Referencia observada**: literal en código o relación SQL. No demuestra tráfico, respuesta HTTP, indexación ni aparición efectiva tras ejecutar JavaScript.

## Fuentes y procedimiento

Se analizaron los INSERT del dump poblado `legacy/public/puerta34_administrador.sql` con lectura léxica de comillas, escapes y NULL, sin ejecutar consultas. El dump en `legacy/database/psindustrial_db.sql` sólo aporta esquema. Se excluyeron los valores de usuario, contraseña y credenciales.

Se compararon nombre, encabezados propios, etiquetas de enlaces entrantes, texto descriptivo en fragmentos de cinco palabras y SHA-256 de imágenes/PDF. `product-page-candidates.csv` conserva hasta cinco candidatos por registro: su puntuación sirve para ordenar la revisión, **no mide probabilidad**. Se verificaron las familias y los conflictos antes de construir el maestro. La coincidencia de hash demuestra igualdad del recurso, no identidad comercial del producto.

`page-source-evidence.json` conserva encabezados, imágenes, enlaces y líneas de las 198 fuentes PHP de raíz. Se excluyeron comentarios; no se expandió el menú compartido dentro de cada producto. Así, aparecer en un menú global no convierte una marca o categoría en propietaria de todas las fichas. No es un DOM renderizado ni un parser completo de PHP. La correspondencia SQL ↔ ficha estática siempre es inferida: no existe una clave que enlace ambas representaciones.

CONFIRMED significa hecho explícito (por ejemplo, ID almacenado, enlace literal o firma binaria). STRONG_INFERENCE significa concordancia documental sólida sin enlace relacional explícito. WEAK_INFERENCE identifica evidencia insuficiente o contradictoria. UNKNOWN significa que no se asigna una relación. Un ID SQL positivo puede ser explícito y, aun así, editorialmente incorrecto: los conflictos rebajan la confianza de la relación canónica y conservan el valor bruto en db_*.

## Contrato de los CSV

UTF-8 con BOM; separador CSV coma; celdas multivalor con `|`. Los IDs son del legacy. Vacío significa no establecido, nunca autorización de borrar. Los nombres SQL se conservan con su codificación original; sólo se normalizaron acentos/mojibake en las comparaciones. No se generaron slugs nuevos de WordPress: proposed_slug reproduce el basename de la ficha candidata, sin aprobar cuál debe ser canónica.

`product-master.csv` tiene exactamente una fila por ID SQL. `category_id` puede contener varias categorías respaldadas por grids; no impone una categoría principal. `category_candidates` conserva otras asociaciones de navegación incluso cuando SQL tiene un ID positivo. root_category_ids permite distinguir raíz y subcategoría. `db_image_paths` / `page_image_paths` y sus equivalentes PDF separan ambas fuentes; images/technical_pdf son su unión, no una galería final depurada. Las imágenes de página pueden incluir hero y decoración. Las listas SQL originales conservan el orden en db_*_ids y product-media-relations.csv; la unión ordenada de rutas no debe usarse para importar el orden de galería.

Los 32 archivos de `static-product-supplement.csv` no tienen correspondencia SQL fuerte: deben estudiarse como contenido de producto/familia adicional, sin inventarles un ID, marca o categoría. Sus encabezados también pueden estar copiados. No son automáticamente 32 productos comerciales diferentes. `content-master.csv` cubre los 617 PHP; sólo 179 son páginas de contenido de raíz. Sus proposed_wordpress_type son etiquetas de necesidad editorial, no decisiones de arquitectura.

`category-master.csv` conserva las 38 filas, incluso las dos sin página estática única. parent_legacy_id demuestra la relación SQL; relationship_confidence califica la correspondencia visual. proposed_parent repite el padre SQL existente, **no aprueba una taxonomía nueva**. product_count en categorías y marcas cuenta filas SQL directas, incluidos vacíos/prueba y duplicados; no suma descendientes ni entidades deduplicadas.

## Medios y límites de uso

Se inventariaron 1.528 archivos locales y tres embeds externos. Se leyeron firmas PDF/JPEG/PNG/GIF/WebP/BMP/ICO/SVG/MP4/WebM/WOFF. No se modificaron bytes ni extensiones. Los 259 originales sin extensión quedaron identificados: 143 JPEG, 44 PNG y 72 PDF. Doce fuentes TTF/EOT quedan UNKNOWN por no estar cubiertas por este detector; no son archivos sospechosos por ese motivo.

`media-usage-evidence.csv` distingue relación SQL, logo explícito, referencia en fuente pública, referencia interna y variante derivada. Una referencia CSS puede estar en una regla no aplicada; una página histórica puede no recibir visitas. Por tanto, «usado» significa **referenciado documentalmente**, no visualizado en producción. Las variantes derivadas de un original referenciado se mantienen en REVIEW si no hay referencia literal: el código puede elegirlas dinámicamente. No se suman al conteo estricto de uso para evitar inflarlo.

EXACT_DUPLICATE_BYTES demuestra SHA-256 igual; no justifica eliminar rutas con enlaces o SEO. Un archivo sin referencias sigue en REVIEW. No se ha decodificado íntegramente cada imagen ni abierto cada página de cada PDF. Las cuatro referencias no resueltas están en missing-media-references.csv, separadas de archivos que sí existen.

## URLs

`url-master.csv` distingue existencia de archivo, literal de enlace/sitemap, derivación SQL y patrón .htaccess. Si concurren fuentes, se prioriza DIRECT_FILE, INTERNAL_LINK, HTACCESS_RULE, DATABASE_DERIVED, CODE_DERIVED, SPECULATIVE; se añaden evidencias secundarias en notes. No se cambian HTTP/HTTPS, mayúsculas, parámetros o Unicode para deduplicar.

Las cinco filas con prefijo PATTERN son expresiones de rewrite, **no URLs concretas**. Las 599 derivadas no cuentan como URLs públicamente existentes. Incluso CONFIRMED sólo confirma evidencia local. Las rutas file.php con ID/PDF son descargas públicas potenciales y quedan INVESTIGATE, no INTERNAL_ONLY. Los fragmentos internos se separan de esas descargas. No se ha aprobado ningún redirect ni declarado eliminable ninguna URL de contenido.

# Categorías y jerarquía

Se adopta `psi_categoria`, taxonomía jerárquica de psi_producto. Nombre visible Categorías. REST/UI públicos según estado del término; base para términos nuevos `/categoria/{slug}/`, sin ruta completa de ancestros para evitar que mover un padre cambie todas las URLs. Clave interna no comparte namespace con categorías del blog. Roles mediante capacidades propias. [API de taxonomías](https://developer.wordpress.org/reference/functions/register_taxonomy/).

## Campos

name, slug, description y parent nativos. Term meta: `_psi_image_id` (attachment imagen), `_psi_order` (entero), `_psi_h1` (texto), `_psi_public_state` (review/public), `_psi_source_keys` y hash privados. SEO mediante adaptador único. description admite HTML editorial limitado, sin scripts/iframes. Imagen opcional, no se inventa a partir de un producto arbitrario.

WordPress no ofrece borrador nativo para términos: review se implementa expresamente. Un término review se puede preparar en administración pero no aparece en directorios, sitemap, REST público ni archivos públicos; consulta directa devuelve404. No aprobar un corte mientras una URL legacy requerida dependa de ese término sin representación alternativa válida.

## Reconstrucción

38 categorías SQL son entrada, no38 términos aprobados automáticamente. Cada padre se decide con fuente/razón. El orden de autoridad para jerarquía es: decisión revisada basada en navegación visible coherente → enlaces locales explícitos de padre/subcategoría → padre_id → encabezado/nombre como indicio. Un H1 copiado no vence a los enlaces. No se cambia un padre por un enlace transversal en centros comerciales.

Las siete raíces visibles mantienen su función;26 (padre0, nombre duplicado con33) y25 (14 registros vacíos, sin página propia clara) quedan review. No fusionar26/33. En27/28 se mantienen términos distintos y una Page de colección compartida con ambos grupos; no forzar una URL a ser dos archivos. La categoría35 no se apropia de puerta-para-hospital.php: esa ruta pertenece al producto.

Producto3 requiere decisión de relación;14 productos recuperados con varios contextos pueden asignarse a varias categorías aprobadas. _psi_primary_category_id sólo orienta breadcrumb, no restringe listados. Los menús pueden enlazar varias ramas sin alterar parent.

## Consulta y presentación

Archivo padre: hero, descripción, grid de hijos públicos ordenados y, si hay, productos asignados **directamente** al padre. Hoja: descripción y productos directos. Consulta de términos con include_children=false; no sumar descendientes por comportamiento predeterminado. Filtros categoría+marca son intersección (AND), no unión.

Para preservar un grid estático con orden/asociaciones que no expresan taxonomía (por ejemplo Louver incluyendo Holandesa), usar Page/colección explícita. No contaminar la jerarquía ni afirmar que Holandesa es Louver. Cada archivo canónico tiene composición definida; una colección no se canoniza automáticamente al término.

Orden: _psi_order y nombre; productos por menu_order, título, ID. Nuevos listados largos:24 por página, paginación accesible; listados legacy pequeños conservan su secuencia auditada. SEO de paginación autocanónico por página. Combinaciones no aprobadas: noindex, sin sitemap, con ruta válida si el usuario filtra; ver07/08.

Alternativas: CPT de categoría agrega relaciones y CRUD sin beneficio; Pages como única categoría dificultan filtros; taxonomía recursiva automática pierde semántica legacy. La solución elegida reduce administración pero exige controles de revisión y colecciones para excepciones.

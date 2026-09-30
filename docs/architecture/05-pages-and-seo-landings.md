# Pages y landings SEO

Se utilizarán Pages para empresa, privacidad, contacto, servicios, aplicaciones, sectores, directorios y landings. Sólo se crean las que tengan contenido demostrado o requerimiento editorial autorizado; no se inventan secciones porque sus nombres sean habituales en un sitio corporativo.

Las65 páginas no-producto se revisan individualmente:37 contenedores/categorías/directorios,13 de marca incluidas variantes,9 landings,5 institucionales/inicios,1 contacto. Esa cifra no significa65 Pages finales: las páginas equivalentes a un único término se representan en su archivo. Se preserva la propiedad de cada URL antes de consolidar.

## Plantillas de Page

- Editorial: nosotros, privacidad y futura información de empresa/servicios; contenido de bloques, hero opcional.
- Colección: soluciones, directorios, aplicaciones/sectores; bloques de categorías, marcas o productos seleccionados.
- Landing técnica: texto de gama, secciones técnicas, cards, tablas, PDFs contextuales y CTA; no ficha de producto artificial.
- Contacto: texto/datos + bloque formulario propio.
- Inicio: portada estática con secciones aprobadas, colecciones y hero; las variantes index-resp/index-estatico no se eliminan automáticamente.

Los templates sólo componen; el cuerpo permanece editable en post_content. Patrones no sincronizados para estructuras reutilizables con texto propio; bloques sincronizados sólo para avisos/globales deliberadamente compartidos. Encabezado/pie no son contenido duplicado en cada Page.

## Nueve landings candidatas que conservar

operadores-puerta-abatible.php; operadores-puerta-corrediza-residencial.php; operadores-puertas-ascendentes.php; puertas-residenciales.php; cortinas-enrollables-de-aluminio.php; puertas-de-garaje-aisladas.php; puertas-enrollables-de-garage.php; puertas-rapidas-enrollables.php; tiras-plasticas-hawaianas.php.

Cada una mantiene su propia intención, URL, texto, H1, title/description, enlaces y estado de indexación revisado. Compartir Modern Steel o AccessPRO no la convierte en alias eliminable. CREATE_FROM_STATIC para contenido exclusivamente PHP significa extraer y estructurar texto, no ejecutar PHP ni importar su header/footer/scripts.

## Caso de URL compartida y páginas históricas

puertas-peatonales-estandar-y-reforzada.php queda como Page con colección de productos de27/28: la entidad Page es propietaria de la ruta. puerta-para-hospital.php es ficha, no Page de categoría35. Directorios marcas.php/marcasv1.php y variantes de inicio requieren comparación antes de decidir si son aliases o contenido distinto; mientras tanto REVIEW impide desaparición silenciosa.

Una Page puede contener un bloque `psi/collection` cuyo selector guarda tipo (producto/categoría/marca), IDs ordenados o un filtro de términos aprobado. Sin consultas SQL libres ni IDs legacy en el frontend. Los IDs WP del bloque se resuelven por entity_key durante la importación, no se copian entre entornos.

## SEO y edición

Una sola salida H1, renderizada en servidor; _psi_h1 conserva título visible distinto de post_title cuando se justifique. La etiqueta de sección legacy puede mantenerse como texto secundario. Cambiar un H1 erróneo requiere registro de transformación editorial, no automatismo. Fallback title a post_title sólo si no hay valor legacy válido; errores copiados se señalan.

Bloques permitidos: párrafo, encabezado H2/H3, lista, imagen, galería, tabla, columnas/grupo, botones y los pocos bloques de dominio registrados por core. Gestor no inserta PHP, JS, CSS libre ni iframes arbitrarios. Las páginas de servicios no necesitan otro CPT salvo un requisito futuro con ciclo de vida propio.

Alternativas: CPT landing añade menú/campos sin beneficio; todo como término confunde intención editorial con clasificación; HTML copiado entero mantiene dependencias y duplicación. Pages preserva portabilidad, con coste de convertir contenido y comprobar paridad visual.

# Verificación del análisis legacy

Fecha: 2026-09-17. Se leyó AGENTS.md raíz completamente antes del análisis. Estado Git inicial limpio. Sólo se crearon/modificaron archivos de documentación bajo docs; no hubo commit.

## Comprobaciones

- 20 entregables obligatorios presentes y no vacíos (16 análisis, 3 SEO incluido CSV, 1 arquitectura).
- Inventario recursivo: 3.861 archivos y 617 PHP, 198 de raíz.
- SHA-256 de todos los 3.861 archivos de legacy/public comparado al finalizar contra el inventario: **ningún cambio**, sin altas/bajas de ese árbol.
- git status --short --untracked-files=all: **sólo docs/**; ninguna modificación de legacy, wordpress, base, htaccess o archivos raíz.
- CSV comprobados con lector CSV: todas las filas mantienen cantidad de columnas. UTF-8 (BOM en CSV para facilitar apertura).
- 1.355 registros file localizados en su expected_path; located_paths conserva otros binarios de nombre coincidente. path identifica el esperado, sin confundir varios paths con una ruta literal.
- Relaciones positivas examinadas: producto–categoría/marca/medios, categoría–padre/imagen, marca–imagen, file–original resuelven. Ceros: 54 categoría y 110 marca; no son huérfanos positivos.
- Sintaxis con php -n -l en PHP 8.4.24: 617 archivos, 613 pasan, 4 fallan, 29 que pasan con diagnósticos. No se ejecutó aplicación.
- Revisión de secretos conocidos en documentación: no se reprodujeron valores reales detectados. Una coincidencia inicial fue una subcadena común procedente de un ejemplo de documentación del wrapper DB, no una credencial real publicada. No se guardaron registros de usuarios ni hashes de acceso.

## Método reproducible de análisis

Enumerar recursivamente archivos y registrar ruta/tamaño/extensión/SHA-256. Leer fuente sin ejecutarla; analizar INSERT con delimitación SQL de strings, escapes y filas; nunca cargar el dump en MySQL. Excluir user de catálogos exportados. Reconstruir claves desde CREATE/ALTER y relaciones implícitas desde entidades/DAO/templates. Resolver IDs de medios contra rutas originales en system y derivados en multimedia.

Clasificar PHP por controlador/include/contenido y referencias. Excluir comentarios al extraer comportamiento; conservar posiciones de línea. Sustituir expresiones PHP no evaluadas por UNKNOWN_PHP en extracción HTML, expandiendo sólo includes raíz literales. Reconstruir title/description literales y fórmulas dinámicas demostradas, marcando dependencia de JS en headings. Conservar URLs de sitemap y añadir parámetros/rutas probables de generadores con notas; no declarar indexación real.

Calcular huellas de secuencias de tags y repetición de líneas sustanciales con fórmula explicada en 13-template-patterns. Hacer lint aislado sin bootstrap. Comprobar inventarios, entregables, hashes y estado Git. No se guardaron scripts ejecutables en el proyecto: los anexos son evidencia/documentación, no herramientas de migración.

## Límites

No hubo navegador, crawl remoto, ejecución de endpoints, login, envío de email, SQL, importación ni cambios funcionales. Existencia física no certifica MIME/integridad visual/HTTP; lint no certifica runtime. Bibliotecas y minificados se inventariaron/analizaron por patrones y referencias; no se afirma revisión manual exhaustiva de cada línea vendor. Referencias estáticas no prueban desuso.

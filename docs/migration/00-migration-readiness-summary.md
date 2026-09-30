# Preparación de la migración — segunda fase

Fecha: 2026-09-17. Se construyó una conciliación documental, sin modificar la aplicación ni la base de datos. **Hay información suficiente para abordar posteriormente el diseño, pero no para ejecutar una importación automática sin decisiones editoriales.** Esta fase no diseña ni implementa WordPress.

## Respuestas directas

| Pregunta | Resultado y alcance |
|---|---|
| 1. ¿Cuántos productos tienen correspondencia inequívoca con una página PHP? | **136 de165 registros** tienen una ficha principal documentalmente inequívoca con los criterios del análisis. Otros12 corresponden a familias con varios PHP equivalentes candidatos y2 tienen nombres conflictivos (3/139). En total150 tienen correspondencia candidata fuerte. No existe FK PHP↔SQL: todas estas correspondencias siguen siendo STRONG_INFERENCE, no identidades comerciales certificadas.15 no tienen ficha candidata. |
| 2. ¿Cuántos continúan sin categoría fiable? | **1 relación contradictoria**, ID3: SQL residencial frente a rampa Kelley. Los54 categoría0 recuperan al menos un contexto fuerte:40 uno y14 varios, sin escoger categoría principal. Además,14 registros vacíos y1 Prueba tienen categoría SQL explícita, pero no puede validarse su pertinencia comercial; se mantienen REVIEW. |
| 3. ¿Cuántos continúan sin marca fiable? | **28 registros**:14 UNKNOWN (vacíos) y14 WEAK_INFERENCE. Entre los110 marca0,88 recuperan marca con STRONG_INFERENCE,8 quedan débiles y14 desconocidos. Seis registros con marca positiva también presentan contradicción. CONFIRMED describe la atribución guardada, no prueba el fabricante real. |
| 4. ¿Cuántas categorías reales hay? | **38 registros**:7 raíces NULL,1 raíz0 y30 hijas.36 tienen correspondencia visual candidata;25 y26 no tienen una página estática única. No hay38 páginas de categoría independientes ni38 términos canónicos comerciales ya aprobados.26/33 comparten nombre y requieren decisión. |
| 5. ¿Cuántas marcas reales hay? | **12 registros de marca**,12 páginas principales y12 logos asociados explícitamente por el directorio estático. Hay una variante adicional bft-resp.php. No se deduce que sólo existan12 fabricantes en todo el contenido estático. |
| 6. ¿Cuántas páginas no-producto deben probablemente conservarse? | **65 de179 páginas de contenido**:37 contenedores/categorías/directorios,13 páginas de marca incluidas variantes,9 landings,5 institucionales/inicios y1 contacto. Se conservan también114 páginas de producto/familia. Se excluyen del conteo editorial los2 controladores,1 procesador y16 fragmentos raíz. |
| 7. ¿Cuántas parecen landing pages SEO? | **9 candidatas** por contenido de gama o compuesto. La finalidad SEO es inferencia; tráfico e intención editorial no están demostrados. Algunas solapan productos, por lo que conservar la URL no exige crear un producto nuevo. |
| 8. ¿Cuántos PDFs están realmente asociados? | **159 IDs file referenciados por productos →72 rutas originales PDF**. El frontend referencia78 rutas PDF estáticas:76 localizadas y2 no resueltas literalmente. En total **148 rutas PDF localizadas asociadas/referenciadas**, que representan **74 contenidos binarios únicos por SHA-256**. Igualdad binaria no certifica que sea la ficha técnica correcta de cada modelo. |
| 9. ¿Cuántos medios parecen utilizados? | **612 archivos locales con referencia pública literal o relación de contenido SQL**, más **3 embeds YouTube**. Inventario:1.528 archivos locales +3 externos. Es uso documental, no visualización en producción; incluye reglas CSS y registros vacíos/prueba. Los derivados sólo relacionados mediante original_id no inflan este conteo. |
| 10. ¿Cuántas URLs tienen evidencia directa? | **297 URLs**:285 DIRECT_FILE y12 INTERNAL_LINK como fuente principal. Además599 DATABASE_DERIVED requieren verificación y5 filas HTACCESS_RULE representan patrones, no URLs concretas. Total901 filas frente a877 candidatas anteriores. Ninguna fue comprobada por HTTP. |
| 11. ¿Qué necesita validación humana? | Autoridad del dump, identidad/duplicación comercial, fabricantes contradictorios, modelos/PDF copiados, categorías conflictivas,14 vacíos+Prueba, recursos ausentes, tráfico/hosting y contactos/accesos vigentes. Detalle en manual-decisions-required.md. |
| 12. ¿Podemos diseñar la arquitectura WordPress? | **Sí, posteriormente y con excepciones identificadas**. Todavía no puede aprobarse la deduplicación, exclusión de contenido o mapa definitivo de redirects. Este análisis no toma esas decisiones ni empieza diseño/implementación. |

## Qué cambió respecto de las incertidumbres iniciales

Los165 registros no equivalen a165 productos diferentes:150 filas de contenido se agrupan provisionalmente alrededor de **77 fichas principales** (24 grupos de una fila,37 de dos,12 de tres y4 de cuatro). Son grupos de conciliación, no77 productos canónicos definitivos. IDs151–164 carecen de nombre y descripción;165 se llama Prueba. Se mantuvieron todos los IDs.

Además hay **32 fichas/familias estáticas sin correspondencia SQL fuerte**, inventariadas aparte. Por tanto, ni importar165 filas ni deduplicarlas a77 cubre por sí solo el sitio. Las114 páginas PRODUCT_PAGE incluyen aliases y familias; no deben contarse como114 entidades comerciales únicas.

Los1.355 registros file resuelven a **820 rutas**, no a1.355 archivos diferentes. Se identificaron por firma los **259 originales sin extensión**:143 JPEG,44 PNG y72 PDF. Hay265 grupos de archivos con bytes duplicados en el inventario local; los IDs y URLs deben conservar trazabilidad aun si luego se comparte un adjunto.

Los12 campos marcas.imagen están vacíos, pero los logos sí existen en el frontend. El directorio estático permite asociarlos; la variante dinámica depende de un array por posición. El campo video SQL está vacío en todos los productos, pero tres páginas incrustan videos y se relacionan con11 filas SQL repetidas.

Se documentaron cuatro referencias de medio no resueltas: dos imágenes ausentes, un PDF ausente y un PDF cuyo nombre difiere en normalización Unicode. No se renombró ni sustituyó nada.

## Riesgos que siguen abiertos

- **Identidad y marca:** Modern Steel bajo tres marcas; LiftMaster con título Blue Giant; MOOVI dentro del grid Blue Giant; ID139 mezcla nombres; Thermospan mezcla texto Wayne/Clopay; Sellos Nacionales muestra Dockman y texto/PDF Solmmer. El texto del fabricante y las fichas deben ser autorizados antes de corregir o fusionar.
- **Jerarquía:** el sitio permite varios contextos visuales aunque cada fila SQL sólo tenga una categoría. Una importación que conserve únicamente el escalar perdería navegación; escoger un padre por el H1 copiaría errores.
- **SEO:** las rutas derivadas no demuestran existencia pública. No hay datos de tráfico ni confirmación de hosting/canonicalización. Ninguna variante se elimina ni recibe un301 definitivo.
- **Medios:** duplicados binarios no son autorización para quitar URLs; ausencia de referencia literal no demuestra orfandad. Conservar originales y la relación ID/ruta hasta decidir la importación.

## Entregables y cómo auditarlos

Los seis maestros principales son product-master.csv, category-master.csv, brand-master.csv, content-master.csv, media-master.csv y ../seo/url-master.csv. Se acompañan de backoffice-requirements.md, data-retention.md, evidence-matrix.md, manual-decisions-required.md y este resumen.

Anexos: methodology.md; static-product-supplement.csv; canonical-candidate-groups.csv; product-page-candidates.csv; product-media-relations.csv; media-usage-evidence.csv; missing-media-references.csv; page-source-evidence.json; phase2-integrity-baseline.json; verification.md. Los anexos distinguen hechos, inferencias, duplicados y procedencia sin reproducir credenciales ni datos de usuario.

La verificación final de integridad y el estado Git se registran en verification.md. No se ha hecho commit.

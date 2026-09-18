# Subset representativo conservado

Autorización: Prompt6 permite elegir un ensayo pequeño. [Decisiones exactas](importer-reports/subset-decisions.json) limitadas a LOCAL_DRAFT_REHEARSAL_ONLY; no resuelven identidad comercial ni publicación.

| Caso | Fuente seleccionada | Resultado |
|---|---|---|
| Padre/hija | Categorías1 Industrial →8 Puertas Seccionales |2 términos review; padre coherente SQL/navegación |
| Imagen de categoría | file11, original JPEG sin extensión | Attachment nuevo con extensión; fuente intacta |
| Marca/logo | Marca1 Overhead Door, images/tg.png | Término review + logo ID, no array posicional |
| Producto con galería | SQL63, ficha416 | Draft, categoría8 y marca1 según título/evidencia fuerte; principal +1 galería |
| Producto con PDF | SQL64, ficha420 | Draft, categoría8 y marca1; principal +1 galería y PDF |
| PDF compartido por dos rutas | fichas/puerta-420.pdf y original sin extensión file676 | Mismos bytes, un attachment; ambas referencias preservadas |
| Producto sin marca | static:automatismos-para-cancelas-cubic.php | Draft CUBIC sin marca/categoría inventadas |
| Page | php:nosotros.php | Page draft, texto estático saneado |
| Contradicción | SQL3 | REVIEW; no crear ni corregir categoría |
| Prueba | SQL165 | REVIEW; no crear ni descartar definitivamente |

Total del plan:17 decisiones,15 objetos creados:7 imágenes +1 PDF,2 categorías,1 marca,3 productos,1 Page. Se excluye verficha.png de fotos comerciales. Ningún grupo duplicado legacy se fusionó.

- [Plan inicial](importer-reports/subset-plan.json)
- [Ejecución1 e IDs WordPress](importer-reports/subset-execution-1.json): CREATE15, REVIEW2, ERROR0.
- [Ejecución2](importer-reports/subset-execution-2.json): UNCHANGED15, REVIEW2, ERROR0; mismos IDs.

Los objetos continúan en la DB local para revisión manual. No se aprobaron términos, no se publicaron posts y no se cambiaron datos/contactos existentes del cliente. Attachments son recursos accesibles por su URL local; no se afirma privacidad HTTP de uploads, sólo que el catálogo está sin publicar.

## Verificación manual

Revisar 416/420 en Productos → Borradores: nombre, párrafos, galería, PDF y términos. Abrir el PDF y comparar bytes/nombre. Revisar CUBIC sin marca; Nosotros como Page; categoría hija e imagen; logo Overhead. La previsualización autenticada es de prueba y no promete diseño legacy. Revisar enlaces y contenido antes de cualquier publicación.

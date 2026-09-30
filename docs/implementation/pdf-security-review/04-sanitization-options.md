# Opción de sanitización

**No se creó ningún archivo definitivo.** Se hizo una única prueba de viabilidad sobre una
copia temporal, fuera de `/legacy`, en el directorio de scratch de la sesión, y esa copia se
borró inmediatamente después de medir el resultado. No queda ningún artefacto de esta prueba
en el repositorio ni en `/legacy`.

## Prueba realizada

Sobre el PDF de los productos 65/66 (`639b9801...`, representativo del Grupo A): con
`pikepdf`, se eliminó del catálogo la entrada `/Names/EmbeddedFiles` (y el propio `/Names` si
quedaba vacío) y se guardó la copia resultante. Después:

| Medición | Antes | Después |
|---|---|---|
| ¿Coincide con la expresión regular de `Media::file_valid()`? | Sí | **No** |
| Tamaño del archivo | 815.558 B | 789.792 B (−25.766 B, consistente con retirar el stream de 12.230 B más su cabecera/tabla de referencias) |
| Texto extraído (`pdftotext`) | 7.907 caracteres | **idéntico byte a byte** |
| Número de páginas | 4 | 4 |
| Versión PDF | 1.3 | 1.3 |

**Conclusión probada, no especulada: retirar el objeto `/EmbeddedFile` del Grupo A es
técnicamente viable, no altera el contenido visible ni el texto extraíble, y hace que el
archivo pase la comprobación de seguridad existente sin necesidad de modificarla.**

## Qué podría alterar la sanitización — evaluación por categoría

| Elemento | ¿Se ve afectado? | Razonamiento |
|---|---|---|
| Contenido visual / páginas | No | El adjunto es un objeto de catálogo independiente del árbol de contenido de páginas; verificado por texto idéntico y mismo número de páginas |
| Enlaces | No | Ninguno de los 7 PDF tiene acciones `/URI` ni anotaciones de enlace detectadas en el árbol de anotaciones auditado |
| Formularios | No aplica | Ninguno de los 7 tiene `/AcroForm` |
| Firmas digitales | No aplica | No se detectó ningún diccionario `/Sig` ni `/Perms` en ninguno de los 7; no hay firma que romper |
| Metadatos (Producer/Creator/fechas) | Se conserva por defecto | `qpdf`/`pikepdf` no tocan `/Info` ni XMP salvo que se pida explícitamente; no se pidió en la prueba |
| Calidad de imagen | No | Las imágenes de página son objetos `XObject` separados del adjunto; no se tocaron |

## Grupo A (5 PDF): sanitización es la opción correcta

Retirar el `.joboptions` es una operación **bien definida, reversible en el sentido de que
el original en `/legacy` nunca se toca**, y probada sin efectos secundarios. Es la vía que
permite que el archivo pase la comprobación de seguridad **sin pedir una excepción a la
regla** — el resultado sanitizado simplemente ya no contiene ningún `/EmbeddedFile`.

## Grupo B (2 PDF): la sanitización no aplica

No existe ningún objeto real que retirar (`embedded_file_count = 0` en ambos). "Sanitizar"
estos 2 archivos no tiene sentido porque no hay nada que limpiar: el problema no está en el
archivo, está en que una expresión regular sobre bytes crudos, sin conciencia de la
estructura del PDF, coincidió por azar dentro de un stream comprimido. Ver
[05-recommendations.md](05-recommendations.md) para la recomendación específica de estos 2
casos, que es distinta de "generar una copia".

## Qué NO se implementó aquí

- No se generó ningún archivo sanitizado definitivo para ninguno de los 7 PDF.
- No se sustituyó ningún archivo bajo `/legacy` ni en ningún otro lugar.
- No se cambió `Media::file_valid()` ni ninguna otra parte del importador.
- No se tomó ninguna decisión sobre si sanitizar realmente — sólo se demostró que es posible.

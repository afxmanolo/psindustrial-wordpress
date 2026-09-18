# Recomendaciones de clasificación

**Esta clasificación es únicamente una recomendación analítica.** No modifica el manifest,
`Policy.php`, `Planner`, `Runner` ni ninguna decisión del importador. Los 11 productos y los
7 PDF permanecen exactamente en el estado (REVIEW) en que los dejó la fase LOW.

## Vocabulario usado

`SAFE_FOR_MIGRATION` · `REQUIRES_SANITIZED_COPY` · `KEEP_REVIEW` · `REJECT` (cerrado, según
lo pedido). Ningún PDF recibió `KEEP_REVIEW` ni `REJECT` — la evidencia para los 7 es clara
y no ambigua en ambos sentidos; se explica por qué en cada caso.

## Grupo A — 5 PDF, 9 productos: `REQUIRES_SANITIZED_COPY`

| PDF | Productos | confidence | evidence | reason |
|---|---|---|---|---|
| `639b9801...` | 65, 66 | HIGH | 1 `/EmbeddedFile`, `.joboptions` de Distiller, texto plano confirmado, sin `/OpenAction`/`/AA`/`/AcroForm`/multimedia | Adjunto pasivo de flujo de producción conocido; retirar el objeto hace pasar la comprobación existente sin excepciones, probado sin efectos secundarios |
| `7ab52eec...` | 68, 69 | HIGH | ídem; `Rampage PDF.joboptions`, idéntico byte a byte al de 72/73 | ídem |
| `3af12bf1...` | 70, 71 | HIGH | ídem; `Press Quality.joboptions.joboptions` | ídem |
| `7baea774...` | 72, 73 | HIGH | ídem; `Rampage PDF.joboptions`, idéntico byte a byte al de 68/69 | ídem |
| `7cf4babf...` | 67 | HIGH | ídem; `Rampage PDF.joboptions` (ajustes ligeramente distintos) | ídem |

**Por qué no `SAFE_FOR_MIGRATION` directamente**, pese a que el análisis no encontró ninguna
señal de riesgo: la comprobación de seguridad existente del proyecto es deliberadamente
conservadora y trata cualquier `/EmbeddedFile` como bloqueante, sin distinguir "benigno
conocido" de otra cosa — y este análisis, por instrucción explícita, no está autorizado a
crear esa distinción como excepción de política. La vía que sí respeta tanto la evidencia
(el adjunto es inofensivo) como la postura conservadora del proyecto (no pedir que alguien
confíe en un juicio puntual para siempre) es que el propio archivo deje de contener el
objeto que dispara la regla — verificable de forma objetiva y repetible por cualquiera,
no por confianza en este informe.

**Por qué no `KEEP_REVIEW` ni `REJECT`**: la evidencia estructural es completa y no deja
ambigüedad (ausencia total de mecanismos activos, contenido del adjunto verificado
directamente como texto plano de un formato Adobe estándar y documentado). No hay ninguna
pregunta abierta que una revisión humana adicional del *archivo* pudiera responder mejor que
este análisis. Lo que sí queda pendiente es una decisión de *política* (¿se autoriza generar
copias sanitizadas?), no una decisión de *evidencia*.

## Grupo B — 2 PDF, 2 productos: `SAFE_FOR_MIGRATION`

| PDF | Producto | confidence | evidence | reason |
|---|---|---|---|---|
| `3e620ed8...` | 77 | HIGH | 0 objetos `/EmbeddedFile` reales; coincidencia es 3 bytes dentro de un stream comprimido, 154 KB después del `stream` más cercano; sin `/OpenAction`/`/AA`/`/Names/JavaScript` en ningún nivel | No existe ningún objeto activo ni pasivo que justifique el bloqueo; es un falso positivo de un escaneo de bytes crudos sin conciencia de estructura |
| `98b07de6...` | 95 | HIGH | ídem; coincidencia 51 KB dentro de un stream comprimido | ídem |

**Importante:** clasificar estos 2 como `SAFE_FOR_MIGRATION` es una conclusión sobre el
*contenido del archivo*, no una instrucción para cambiar el comportamiento del importador.
Bajo la comprobación actual (sin modificar, por instrucción de esta fase), **estos 2 PDF
seguirán siendo rechazados** la próxima vez que se ejecute un DRY RUN, porque el byte que
dispara el regex sigue presente en el stream comprimido — no es algo que una copia
"sanitizada" pueda arreglar (no hay nada que quitar). La única vía real de resolverlo es una
comprobación más consciente de la estructura del PDF (por ejemplo, exigir que el patrón
coincida dentro de sintaxis de diccionario, no en cualquier byte del archivo) — un cambio de
código que esta fase tiene explícitamente prohibido hacer.

## Resumen

| Clasificación | PDF | Productos |
|---|---:|---:|
| `REQUIRES_SANITIZED_COPY` | 5 | 9 |
| `SAFE_FOR_MIGRATION` | 2 | 2 |
| `KEEP_REVIEW` | 0 | 0 |
| `REJECT` | 0 | 0 |

## Siguiente paso (no ejecutado aquí)

Dos decisiones humanas independientes, ninguna tomada en esta fase:

1. **¿Se autoriza generar copias sanitizadas de los 5 PDF del Grupo A** (retirar el
   `.joboptions`, conservando el original en `/legacy` intacto) **para que puedan superar la
   comprobación existente sin modificarla?** Si se autoriza, es un trabajo de implementación
   nuevo y acotado (generar el archivo, decidir dónde vive la copia, cómo se referencia desde
   el manifest) — no forma parte de esta auditoría.
2. **¿Se autoriza mejorar la comprobación de `Media::file_valid()` para que el patrón sólo
   dispare dentro de sintaxis de diccionario real, no en cualquier byte del archivo?** Esto
   resolvería el Grupo B (y probablemente reduciría falsos positivos futuros) sin debilitar
   la protección contra el Grupo A ni ningún caso genuinamente activo. Es un cambio de código
   sobre `Media.php`, explícitamente fuera del alcance de esta fase.

Ninguna opción se implementa aquí.

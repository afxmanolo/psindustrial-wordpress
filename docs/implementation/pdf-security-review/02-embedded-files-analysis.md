# Análisis de los objetos embebidos

Fecha: 2026-09-18. Herramienta: `pikepdf` 10.13.0 (envuelve `qpdf`), instalada localmente vía
`pip` para esta auditoría. Lee sólo la estructura de objetos del PDF; **no renderiza
páginas, no ejecuta JavaScript, no abre adjuntos**. Complementado con un escaneo de bytes
crudos (`re` sobre el fichero completo) para reproducir exactamente la condición que activó
`Media::file_valid()`, y con `pdftotext` (poppler) para comparar texto extraído. Metodología
completa y comandos exactos en [00-summary.md](00-summary.md).

## Resultado: dos grupos completamente distintos

### Grupo A — 5 PDF, coincidencia real en `/EmbeddedFile` (9 productos: 65, 66, 67, 68, 69, 70, 71, 72, 73)

Los 5 fueron generados por **el mismo flujo de producción**: QuarkXPress en Macintosh
(`Creator: QuarkXPress: pictwpstops filter 1.0`) exportado vía **Acrobat Distiller 6.0.1 o
7.0.5 para Macintosh**, PDF 1.3. Cada uno contiene exactamente **un** objeto
`/Type/EmbeddedFile`, alcanzable únicamente a través del árbol `/Names/EmbeddedFiles` del
catálogo del documento (nunca vía una anotación `FileAttachment` visible en la página).

El objeto embebido en los 5 casos es un **fichero `.joboptions` de Adobe Distiller** — el
formato estándar de Adobe para guardar los ajustes de conversión a PDF (resolución de
imagen, perfiles de color, compresión, submuestreo de fuentes). Contenido verificado
directamente (texto plano, sintaxis de diccionario PostScript):

```
<<
  /ASCII85EncodePages false
  ...
  /EmbedJobOptions true
  ...
  /ColorImageResolution 300
  ...
>>
```

`/EmbedJobOptions true` explica por sí solo el hallazgo: Distiller estaba configurado para
incrustar sus propios ajustes de conversión en el PDF resultante — un comportamiento
documentado y habitual de esa versión de Distiller, no una anomalía del archivo.

Los nombres de preset (`High Quality.joboptions`, `Press Quality.joboptions`,
`Rampage PDF.joboptions`) son, los dos primeros, **presets estándar de fábrica de Adobe**
("High Quality Print", "Press Quality"); `Rampage PDF` es un preset con nombre propio,
probablemente configurado por la imprenta o el diseñador. El contenido de
`Rampage PDF.joboptions` es **byte-idéntico** (mismo SHA-256) entre el PDF de los productos
68/69 y el de los productos 72/73 — confirma que ambos se generaron con el mismo perfil de
conversión guardado, no una coincidencia.

| PDF (por producto) | Nombre embebido | Tamaño | SHA-256 del stream |
|---|---|---:|---|
| 65, 66 | `High Quality.joboptions.joboptions` | 12.230 B | `dd281453af...` |
| 68, 69 | `Rampage PDF.joboptions` | 15.979 B | `dc5b288852...` |
| 70, 71 | `Press Quality.joboptions.joboptions` | 14.634 B | `d35379b475...` |
| 72, 73 | `Rampage PDF.joboptions` | 15.979 B | `dc5b288852...` (idéntico al de 68/69) |
| 67 | `Rampage PDF.joboptions` | 15.975 B | `c2eb07ffe4...` (mismo nombre, ajustes ligeramente distintos) |

**No se declara ningún MIME/Subtype** para el stream embebido (`/Subtype` ausente en el
diccionario `/EF`) — consistente con un adjunto genérico de Distiller, no con un intento de
disfrazar el tipo real.

### Grupo B — 2 PDF, coincidencia falsa (no es `/EmbeddedFile`; 2 productos: 77, 95)

Ambos generados por **Adobe InDesign** (`Creator: Adobe InDesign CC 2017` / `CS4`), un
productor completamente distinto al Grupo A. **No contienen ningún objeto `/EmbeddedFile`.**
El escaneo exacto con la expresión regular de `Media::file_valid()` confirma que ninguno de
los dos coincide con el patrón `EmbeddedFile`; ambos coinciden con el patrón **`/JS`**.

Verificación exhaustiva de ese byte de coincidencia:

- El offset exacto cae **dentro del cuerpo de un `stream`** (154.049 bytes después de la
  palabra clave `stream` más cercana, muy lejos del `endobj`/diccionario más próximo), en
  una región de **datos binarios de alta entropía** — consistente con una imagen o
  subconjunto de fuente comprimidos con FlateDecode, no con sintaxis PDF legible.
- El contexto de bytes alrededor de la coincidencia no contiene ningún fragmento de sintaxis
  PDF (`<<`, `>>`, `/S`, `obj`, etc.) — es ruido binario.
- El recorrido estructural completo de `pikepdf` (árbol `/Names/JavaScript`, `/OpenAction`
  del catálogo, `/AA` del catálogo, de cada página y de cada anotación) confirma **cero**
  acciones JavaScript, cero `/OpenAction`, cero `/AA`, en ambos documentos.

**Conclusión: es una coincidencia de 3 bytes (`2F 4A 53`) dentro de datos comprimidos, sin
ninguna relación con una acción JavaScript real.** La documentación de la fase anterior
([06-known-issues.md](../review-rules/06-known-issues.md)) afirmó que los 7 PDF coincidían
por `/EmbeddedFile`; **esto era impreciso para estos 2 casos** y se corrige aquí con
evidencia directa. Se mantiene sin cambios la conclusión de que los 11 productos dependientes
permanecieron correctamente en REVIEW — la salvaguarda actuó de forma conservadora y
correcta incluso cuando la causa exacta del match no era la reportada originalmente.

## Comportamiento activo — ausente en los 7

Ninguno de los 7 PDF, en ningún grupo, presenta:

- `/OpenAction` (ninguna acción automática al abrir el documento).
- `/AA` a nivel de catálogo, página o anotación (ninguna acción al enfocar/cerrar/etc.).
- Entradas en `/Names/JavaScript` (ningún script con nombre ejecutable al abrir).
- Acciones `/Launch` (ningún intento de lanzar un programa/archivo externo).
- Acciones `/URI` (ningún enlace de acción embebido — distinto de hipervínculos de texto
  normales, que no se auditaron por no ser relevantes a la salvaguarda).
- `/AcroForm` (sin formularios interactivos).
- Anotaciones `/FileAttachment` (el adjunto del Grupo A no tiene icono de clip visible en
  ninguna página; sólo es alcanzable por el árbol de nombres del documento, típicamente vía
  el panel de "Adjuntos" del lector).
- Anotaciones multimedia (`/Sound`, `/Movie`, `/RichMedia`, `/Screen`).
- Cifrado (`is_encrypted = false` en los 7).

**La presencia de `/EmbeddedFile` por sí sola, en el Grupo A, es un adjunto pasivo: un
archivo de texto que ningún visor abre ni ejecuta automáticamente.** Coincide exactamente con
la instrucción de esta fase: "la existencia de `/EmbeddedFile` por sí sola NO debe
considerarse prueba de malware" — y aquí, además, se confirmó que ningún mecanismo automático
lo acompaña.

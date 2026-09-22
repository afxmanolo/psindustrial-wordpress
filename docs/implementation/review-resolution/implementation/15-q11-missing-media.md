# Q11 — Los 4 recursos ausentes, uno por uno

Cuatro problemas distintos, tratados individualmente como pidió la tarea. Detalle en
[q11-missing-media-audit.csv](q11-missing-media-audit.csv).

## Resultado

```
images/dura.jpg                              ->  MISSING_SOURCE_FILE  (sigue REVIEW, registrado explicitamente)
images/magic.jpg                             ->  MISSING_SOURCE_FILE  (sigue REVIEW, registrado explicitamente)
fichas/INFRACA-RÁPIDA-APILABLE.pdf (NFD)      ->  RESOLVED_REFERENCE_ENCODING       (SKIP; el archivo real ya migra)
fichas/FICHATECNICASELLOSSOLMMERS.pdf         ->  RESOLVED_BY_SQL_PDF_HASH_MATCH    (SKIP; el archivo real ya migra)
```

## Caso 1 y 2: `dura.jpg`, `magic.jpg` — no se fabrica nada

Ningún archivo físico ni variante existe en todo el árbol legacy. Se registran
explícitamente como `MISSING_SOURCE_FILE`, acción `REVIEW`, con `decision_id=Q11` — nunca
`MIGRATE`, nunca un archivo inventado, nunca una imagen "parecida" buscada por semejanza.

### Separación explícita: estado del producto vs. estado del medio ausente

Antes de esta fase, `EditorialDecisions::media_union()` (el mecanismo compartido por Q01,
Q02 y Q04 para construir la unión de imágenes/PDF de una entidad) fallaba **la entidad
entera** en cuanto encontraba una sola ruta sin fila válida en `media-master.csv` — sin
distinguir "falta un archivo opcional" de "no sé qué es esta referencia". Esto dejaba a
`dura-glide-20003000-puerta.php` y `magic-fuerza-del-operador.php` (ambas fichas de Q04,
cada una con **una sola imagen propia, precisamente la ausente**) bloqueadas por completo.

Se corrigió `media_union()` para consultar una tabla explícita de resoluciones Q11
(`EditorialDecisions::q11_resolutions()`): cuando encuentra `images/dura.jpg` o
`images/magic.jpg`, **excluye únicamente esa ruta** y continúa con el resto de la unión, en
vez de rechazarla entera. Resultado verificado: ambos productos ahora migran como borrador
(`CREATE_FROM_STATIC`) con una galería vacía — nunca con una imagen inventada — mientras que
la referencia ausente sigue registrada, por separado, como su propia entidad `REVIEW`.

Esta excepción es **estrictamente estas dos rutas**, no una regla general: cualquier otra
referencia ausente no revisada explícitamente en `editorial-decisions.json` sigue haciendo
fallar la unión completa, exactamente como antes — "esto no debe aplicarse a archivos
esenciales desconocidos de forma global" (punto 10 de la tarea) se cumple por diseño: la
tabla de excepciones sólo tiene las 4 entradas de esta ficha, nunca un patrón.

## Caso 3: INFRACA — resuelto por evidencia, nunca por parecido

El archivo referenciado y el archivo en disco **se ven idénticos** pero difieren en bytes:
la referencia usa la "Á" en forma Unicode descompuesta (NFD, `A` + acento combinante) y el
archivo real la tiene precompuesta (NFC). No es una decisión por parecido de nombre — es
literalmente el mismo nombre visual, verificado carácter por carácter.

Prueba independiente (no sólo la codificación): el SHA-256 del archivo en disco
(`3cbecd21...`) coincide exactamente con el original SQL ya vinculado por
`product-media-relations.csv` a los productos 39 y 104
(`system/files/images/productos/ad6778fc...`, `usage_type=PDF_USED|ORIGINAL`), y
`media-master.csv` **ya registraba este mismo cruce** en su propio campo `duplicate_paths`
desde una fase anterior — no es una relación que esta fase inventa, es una que ya existía
y no se había conectado con la referencia rota.

La entidad `missing:` de este caso pasa a `SKIP` (el contenido real ya migra bajo su propia
ruta correcta; mantener un stub aparte sería redundante). **No se renombró el archivo
legacy. No se modificó `/legacy`.** La normalización vive únicamente en la capa de
migración (`media_union()`), como pidió la tarea.

## Caso 4: SELLOSSOLMMER — hallazgo nuevo, resuelto por hash, explícitamente no por nombre

La tarea fue clara: `SELLOSSOLMMERS.pdf` (la referencia rota, sin espacio) **no debe
darse por igual** a `fichas/SELLOSSOLMMER.pdf` sólo por parecido de nombre — y esta
implementación respeta esa instrucción exactamente: **no se resolvió así.**

Durante la verificación apareció un tercer archivo, no mencionado en la petición original:
`fichas/FICHATECNICASELLOSSOLMMER S.pdf` **(con un espacio antes de la "S")**. La cadena de
evidencia, íntegramente ya presente en el repositorio antes de esta fase:

1. El producto 150 tiene **dos** PDF originales vinculados por SQL
   (`product-media-relations.csv`, `product_id=150`): `file_id 1293` y `file_id 1294`.
2. `media-master.csv` ya registraba, en su propio campo `duplicate_paths` (de una fase
   anterior, no de ahora): `file_id 1293` ↔ `fichas/SELLOSSOLMMER.pdf` (SHA-256
   `397f9ab3...`), y `file_id 1294` ↔ `fichas/FICHATECNICASELLOSSOLMMER S.pdf` (SHA-256
   `cd285d83...`) — **dos documentos distintos, cada uno con su propio hash.**
3. La columna `technical_pdf` del producto 150 (extraída del HTML de `sellos-nacionales.php`
   en una fase anterior) lista **ambos** nombres de página: la referencia rota
   `FICHATECNICASELLOSSOLMMERS.pdf` y `SELLOSSOLMMER.pdf`, como dos enlaces distintos.

La referencia rota (sin espacio) coincide, por SHA-256, con `file_id 1294` — el que a su
vez coincide con el archivo **con espacio**, nunca con `SELLOSSOLMMER.pdf`. Verificado
explícitamente en el test: el hash de `SELLOSSOLMMER.pdf` es distinto del de la resolución
elegida, confirmando que son documentos diferentes y que la elección no fue por parecido.

Esta es una identificación por **evidencia binaria y documental ya existente en el
repositorio** — la excepción que la propia tarea autorizó — no una inferencia semántica
nueva. Se documenta aquí con todo detalle precisamente porque es un hallazgo que va más
allá de lo que la petición original conocía, para que quede abierto a revisión.

## Lo que Q11 no afirma

Ninguno de los cuatro casos desbloquea, por sí solo, el producto o página que lo referencia:
`rapida-apilable.php` (productos 39/104) sigue en Q03 (grupo editorial, fuera de alcance) y
`sellos-nacionales.php` (producto 150) sigue bloqueado por el conflicto de marca Dockman/
Solmmer (Q09, fuera de alcance). Q11 resuelve exclusivamente la clasificación de la
**referencia al medio**, tal como pidió la tarea ("ninguno bloquea la migración").

## Trazabilidad

[q11-missing-media-audit.csv](q11-missing-media-audit.csv) — una fila por caso: referencia,
resolución, ruta resuelta, SHA-256, acción de la entidad stub, evidencia completa.

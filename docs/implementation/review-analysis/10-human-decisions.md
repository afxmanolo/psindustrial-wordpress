# Decisiones humanas reales

De las 12 preguntas de `manual-decisions-required.md`, **7 afectan a filas REVIEW**. Se
desglosan en **9 preguntas accionables**, porque D02 agrupa tres problemas distintos que se
responden por separado y con esfuerzo muy desigual.

Las decisiones se numeran manteniendo la correspondencia con el catálogo original, que el
código ya referencia (`Planner` cita D06 para las categorías 25/26/33).

Orden recomendado: por filas desbloqueadas dividido por esfuerzo. H1 primero.

---

## H1 · D02-a — Política de medios en duplicados de identidad idéntica

**Pregunta:** cuando varias filas SQL describen la misma ficha (mismo nombre, mismo PHP,
misma categoría, misma marca) y difieren sólo en las listas de imágenes y PDFs, ¿la ficha
resultante recibe la **unión** de esos medios, o sólo los de una fila designada?

- **Entidades afectadas:** 26 grupos canónicos, 26 productos comerciales.
- **REVIEW dependientes:** 52 producto + 76 medios = **128 filas**.
- **Evidencia disponible:** identidad idéntica verificada en los 4 campos; `db_image_ids`
  diverge en los 26; ninguno interseca conflictos D03/D06/D07.
- **Alternativas:**
  1. *Unión de medios, primera imagen de la fila de menor ID como destacada.*
  2. *Fila de menor ID gana todos los campos; el resto se descarta.*
  3. *Revisión individual de los 26.*
- **Consecuencias:** (1) recupera todas las imágenes, pero fija la destacada por regla y
  puede unir dos fichas técnicas contradictorias; (2) es la más simple y conservadora, pero
  pierde imágenes que sí pertenecían al producto; (3) máxima fidelidad, coste de 26 revisiones.
- **Recomendación técnica neutral:** la opción (1) con una **exclusión obligatoria**: los
  grupos cuyos `db_pdf_ids` divergentes apunten a PDFs con hash de contenido distinto quedan
  fuera y pasan a revisión individual. Esto evita el riesgo D07 sin perder el resto.

---

## H2 · D02-b — Fusión editorial de grupos divergentes

**Pregunta:** en los 27 grupos donde además difieren nombre, categoría o marca, ¿qué fila
representa el producto comercial y cuáles son variantes, alias o errores?

- **Entidades afectadas:** 27 grupos.
- **REVIEW dependientes:** 74 producto + 88 medios = **162 filas**.
- **Evidencia disponible:** `canonical-candidate-groups.csv`, `evidence-matrix.md`, casos
  concretos documentados en D07 (ICARO con PDF LUX, ALUMINA en URL kronos, thermacore 525 en
  URL 598).
- **Alternativas:** fusionar con paquete editorial normalizado por grupo; mantener las filas
  como productos separados; excluir las variantes del catálogo publicado.
- **Consecuencias:** fusionar sin catálogo autorizado puede crear una ficha que nunca existió;
  mantenerlas separadas duplica el catálogo público; excluirlas pierde modelos reales.
- **Recomendación técnica neutral:** no resolver por regla. Requiere el catálogo de producto
  autorizado por el negocio. Es el trabajo editorial irreducible de esta migración.

---

## H3 · D02-c — Entidad y categoría de las fichas estáticas

**Pregunta:** ¿cada una de las 31 fichas estáticas es un producto independiente, una variante
de un producto SQL existente, o contenido a descartar? Y si es producto, ¿en qué categoría?

- **REVIEW dependientes:** **31 filas** (más 32 filas `page` que ya se proponen SKIP por R-G01).
- **Evidencia disponible:** las 31 son `SHOULD_MIGRATE`, todas tienen imágenes, 2 tienen PDF,
  **las 31 tienen `category_confidence` y `brand_confidence` = UNKNOWN**.
- **Alternativas:** crear 31 productos asignando categoría manualmente; tratarlas como Pages
  editoriales; descartar las que resulten ser variantes.
- **Consecuencias:** crear productos sin categoría los deja fuera de la navegación;
  convertirlas en Pages contradice el modelo si son productos reales.
- **Recomendación técnica neutral:** es la decisión de **mejor relación coste/beneficio**
  después de H1: son sólo 31, tienen contenido y medios propios, y su única carencia es la
  categoría. Una pasada editorial de 31 asignaciones las convierte en productos migrables.

---

## H4 · D05 — Registros vacíos y de prueba

**Pregunta:** ¿qué se hace con los IDs 151–164 (sin nombre ni descripción) y el ID 165 («Prueba»)?

- **REVIEW dependientes:** 15 producto + 16 medios = **31 filas**.
- **Evidencia disponible:** nombre y descripción vacíos; categoría SQL 25; medios asociados;
  el importador los rechaza con `NAME_REQUIRED`.
- **Alternativas:** excluir del catálogo; conservar como borradores para completar; completar
  el contenido ahora.
- **Consecuencias:** excluirlos podría perder altas iniciadas y no terminadas; conservarlos
  como borradores no publica nada y mantiene la trazabilidad.
- **Recomendación técnica neutral:** una sola respuesta cubre las 15. La exclusión es
  reversible (no borra la fila legacy), pero conviene revisar antes los medios asociados por
  si documentan un alta real pendiente.

---

## H5 · D03 — Atribución de fabricante

**Pregunta:** ¿qué marca corresponde a cada producto cuando las fuentes discrepan?

- **Entidades afectadas:** 14 productos (6, 14, 16, 20, 43, 44, 57, 81, 107, 123, 125, 135,
  139, 150) y 7 marcas.
- **REVIEW dependientes directos:** **14 filas**. Indirectos: la asignación de marca de las
  21 fichas aprobadas por R-P01, que hoy se migran sin marca.
- **Evidencia disponible:** Modern Steel bajo tres marcas; LiftMaster con título Blue Giant;
  MOOVI entre Blue Giant y BFT; 150 con título Dockman y texto/PDF Solmmer.
- **Alternativas:** validar con catálogo de fabricante; permitir varias relaciones explicadas;
  dejar sin marca hasta resolver.
- **Consecuencias:** etiquetar por popularidad de enlaces perpetúa el error del sitio actual.
- **Recomendación técnica neutral:** **no bloquea la migración**. Los 11 términos de marca se
  crean igualmente (R-T03) y los productos se migran sin marca. La atribución es una relación
  que se añade después sin rehacer nada.

---

## H6 · D06 — Jerarquía de categorías 25, 26 y 33

**Pregunta:** ¿26 y 33 («Puertas contra incendio», padres 0 y 5) son la misma categoría? ¿Es
25 una categoría comercial o un contenedor administrativo?

- **REVIEW dependientes:** **5 filas** (3 categoría + 1 medio + 1 producto).
- **Evidencia disponible:** 26 sin productos ni página; 33 con 1 producto y
  `contra-incendio.php`; 25 sin página propia y receptora de los 14 registros vacíos.
- **Alternativas:** consolidar 26 en 33; mantener ambas; eliminar 26 por vacía.
- **Consecuencias:** fusionar por nombre es exactamente lo que la documentación prohíbe.
- **Recomendación técnica neutral:** impacto bajo en número de filas, pero afecta a la
  navegación pública. Puede posponerse sin bloquear las otras 33 categorías.

---

## H7 · D08 — Recursos ausentes

**Pregunta:** ¿dónde están `images/dura.jpg`, `images/magic.jpg` y
`FICHATECNICASELLOSSOLMMERS.pdf`, y cómo se regulariza el PDF INFRACA con discrepancia
Unicode NFC/NFD?

- **REVIEW dependientes:** **4 filas**.
- **Alternativas:** recuperar los originales; confirmar sustitutos; autorizar la
  regularización de la referencia.
- **Recomendación técnica neutral:** no renombrar ni sustituir automáticamente. El caso
  INFRACA es el único con solución técnica evidente (normalización Unicode), pero requiere
  autorización explícita.

---

## H8 · D09 — Medios sin uso demostrable

**Pregunta:** ¿qué medios sin referencia literal reciben tráfico o enlaces externos?

- **REVIEW dependientes:** **89 filas**.
- **Evidencia disponible:** ninguna en el repositorio. Requiere Search Console, logs,
  analítica o crawl HTTP autorizado.
- **Recomendación técnica neutral:** mantener en REVIEW. Es el caso más claro donde reducir
  el conteo sería inventar una conclusión: la ausencia de referencia local no demuestra
  orfandad.

---

## H9 · D12 — Páginas utilitarias

**Pregunta:** ¿la función de las 3 páginas utilitarias se reimplementa, se descarta o se
conserva?

- **REVIEW dependientes:** **3 filas**. Impacto mínimo.

---

## Resumen

| Decisión | Filas desbloqueadas | Esfuerzo | Bloquea migración |
|---|---:|---|---|
| H1 · D02-a | 128 | bajo (1 política) | no |
| H2 · D02-b | 162 | alto (27 casos) | no |
| H3 · D02-c | 31 | medio (31 asignaciones) | no |
| H4 · D05 | 31 | bajo (1 política) | no |
| H5 · D03 | 14 + relaciones | medio | no |
| H6 · D06 | 5 | bajo | no |
| H7 · D08 | 4 | bajo | no |
| H8 · D09 | 89 | externo | no |
| H9 · D12 | 3 | bajo | no |

**Ninguna de las nueve bloquea el comienzo de la migración**, porque las 194 filas `MIGRATE` y
las 1.025 `SKIP` propuestas no dependen de ninguna de ellas.

Tres decisiones de política (H1, H3, H4) desbloquean 190 filas. El trabajo editorial
irreducible es H2: 27 grupos que exigen el catálogo de producto autorizado.

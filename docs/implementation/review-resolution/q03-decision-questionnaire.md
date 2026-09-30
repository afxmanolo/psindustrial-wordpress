# Cuestionario de decisión — Q03

Deriva de [q03-analysis/](q03-analysis/00-summary.md), reconstruido desde el plan vivo, no
desde conteos históricos. Una pregunta global y cinco preguntas de grupo — no 27, ni una por
fila. Ninguna respuesta se ha aplicado; nada de esto se implementó.

---

## Q03-GLOBAL · ¿Autorizamos que el mismo producto tenga varias categorías cuando el legacy demuestra presencia real en varias secciones?

**Qué hay que decidir.** 16 de los 27 grupos Q03 son, verificado uno por uno, la misma ficha
de producto repetida porque el catálogo antiguo sólo admitía una categoría por fila SQL.
Nombre, marca, PDF e imágenes coinciden o son compatibles en los 16; lo único que cambia es la
categoría. La evidencia no es sólo "las filas se parecen": hay enlaces literales, con línea
exacta, desde páginas de distintas secciones hacia la misma ficha (ejemplo:
`puertas-blindadas.php`, enlazada desde `centros-comerciales.php:143` **y** desde
`blindadas.php:33`).

**Opción A — Sí: un producto WordPress, varias categorías `psi_categoria`.** Consolida los 16
grupos (49 filas de producto → 16 productos) sin perder ninguna vía de navegación que el sitio
antiguo ya ofrecía. No obliga a decidir todavía cuál categoría es la "principal" — es
técnicamente seguro diferirlo hasta la fase SEO/breadcrumbs (ver
[03-multi-category-policy.md](q03-analysis/03-multi-category-policy.md)).

**Opción B — No: una categoría por producto.** Habría que elegir, para cada uno de los 16, en
qué sección se queda, renunciando a las demás — perdiendo vías de acceso que el sitio hoy
ofrece, sin que ningún dato local indique cuál sección "importa más".

**Recomendación técnica.** Opción A. Es la que corresponde a lo que el sitio antiguo mostraba
realmente, reutiliza sin modificar el mecanismo de unión de medios ya aprobado en Q02, y no
obliga a inventar ningún criterio editorial nuevo.

**Desbloquea:** 166 filas (49 producto + 100 medios distintos + 17 páginas) de los 16 grupos
category-only. Detalle grupo por grupo en
[02-category-only-groups.md](q03-analysis/02-category-only-groups.md); aritmética completa
(Escenario A) en [08-scenarios.md](q03-analysis/08-scenarios.md).

---

## Q03-A · Siete variantes de nombre, sin ninguna categoría en juego

**Qué hay que decidir.** Cada uno de estos 7 grupos son 2 filas SQL de la misma página, sin
ninguna categoría en disputa (a diferencia de Q03-GLOBAL) — sólo el texto del nombre difiere,
y en los 7 casos la diferencia es cosmética o casi: mayúsculas, un espacio, una "s" de más, una
errata corregida, o una palabra descriptiva añadida al mismo modelo.

| ID | Nombres | Qué difiere | Evidencia | REVIEW desbloqueados |
|---|---|---|---|---:|
| Q03-A1 | "Energy Series...3717 1 3/4″" / "...3717 1 3/4″, 3720/3724/3722" (ids 2,17) | Nombre largo añade 3 números de modelo | Descripción **idéntica** byte a byte; misma categoría (8); mismo PDF | 11 |
| Q03-A2 | "511/521" / "511 / 521" (ids 5,88) | Espaciado alrededor de la barra | Descripción, imágenes y PDF idénticos byte a byte | 7 |
| Q03-A3 | "Thermacore serie 592" / "Puerta seccional de acero Thermacore serie 592" (ids 8,62) | Prefijo descriptivo genérico | Descripción idéntica; misma categoría (8) | 7 |
| Q03-A4 | "Fast-Seal ®" / "Fast-Seal® High Performance Door" (ids 36,101) | Forma corta vs. larga | Descripción idéntica; misma categoría (17) | 9 |
| Q03-A5 | "Puerta rápida apilable" / "RÁPIDA APILABLE" (ids 39,104) | Mayúsculas, omisión de "Puerta" | Descripción idéntica; su PDF ya resuelto por Q11 citando estos mismos IDs | 9 |
| Q03-A6 | "...autorreparabale" / "...autorreparable" (ids 40,105) | Errata corregida | Misma categoría (17); mismo PDF | 10 |
| Q03-A7 | "Labio de elevación mecánico" / "...mecánico Dockman" (ids 51,143) | Sufijo de marca añadido | Descripción idéntica; misma categoría (21) | 9 |

**Opción A — Consolidar los 7, usando el nombre más completo/correcto de cada par.** Ninguno
depende de una decisión de marca ni de categoría — riesgo bajo en los 7.
**Opción B — Dejarlos en REVIEW.** No se pierde nada, pero tampoco se gana nada: no hay ningún
dato adicional que una revisión futura vaya a encontrar que esta ya no tenga.

**Recomendación técnica.** Opción A para los 7, sin excepción — es la combinación de evidencia
más limpia de las 27 (nombre, descripción, imágenes y PDF ya concuerdan; sólo la redacción del
título difiere). **Desbloquea:** 62 filas en conjunto.

---

## Q03-B · Colección Modern Steel — identidad confirmada, marca en disputa (ids 6, 16, 20, 107)

**Qué diferencia existe.** Ninguna en identidad: nombre, descripción, imágenes y PDF
**idénticos** en los 4 registros, misma categoría (36, sin divergencia — no depende de
Q03-GLOBAL). La única discrepancia es la marca: Overhead Door / Wayne Dalton / Clopay / sin
marca, las 4 a confianza `WEAK_INFERENCE`. Ya documentado antes de esta tarea en
`manual-decisions-required.md`.

**Opción A — Consolidar en un único producto, sin marca asignada** (mismo mecanismo que Q09
ya ofrece como su propia Opción C).
**Opción B — Consolidar y esperar a Q09** antes de crear el producto.
**Opción C — Mantener los 4 registros separados** hasta que Q09 resuelva la familia de marcas
en disputa.

**Recomendación técnica.** Opción A — la identidad no depende de la marca; nada impide crear
el producto ya y añadir la marca cuando Q09 se resuelva. **Desbloquea:** 15 filas.
**No resuelve Q09** — Modern Steel sigue exactamente como estaba para efectos de marca.

---

## Q03-C · Lift Master Mod H — identidad confirmada, título con marca distinta (ids 43, 81)

**Qué diferencia existe.** Ninguna en identidad: nombre, descripción, imágenes y PDF
**idénticos**, misma categoría (13, sin divergencia). `brand_id` apunta a "LiftMaster"
(confianza débil); la página dependiente trae como título "...| Blue Giant | Marcas" — ya
documentado como "LiftMaster 43/81: título BlueGiant".

**Opción A — Consolidar sin marca asignada**, igual que Q03-B.
**Opción B — Mantener separado** hasta Q09.

**Recomendación técnica.** Opción A, por el mismo razonamiento que Q03-B. **Desbloquea:** 8
filas. **No resuelve Q09.**

---

## Q03-D · Thermospan Modelo 150 — identidad probable, texto mezcla Wayne/Clopay (ids 14, 57)

**Qué diferencia existe.** Nombre casi idéntico (variación tipográfica menor), misma categoría
(8, sin divergencia). Descripción difiere ~4 %; cada miembro tiene una foto propia además de
las compartidas (complementarias, no contradictorias). `manual-decisions-required.md` ya
documenta que el texto de estos dos registros mezcla referencias a Wayne Dalton y Clopay.

**Opción A — Consolidar sin marca asignada**, igual que Q03-B/C.
**Opción B — Mantener separado** hasta que se confirme cuál fabricante es correcto.

**Recomendación técnica.** Opción A para la identidad (la evidencia de que son la misma ficha
es sólida); la marca queda expresamente pendiente. **Desbloquea:** 7 filas. **No resuelve
Q09.**

---

## Q03-E · Rampas de andén hidráulicas Kelley — el caso más complejo (ids 3, 24, 139)

**Qué diferencia existe.** Dos problemas distintos:

**(1) id=139** tiene en el nombre el texto literal "RAMPA NIVELADORA MECÁNICA BLUE GIANT" — el
nombre de otro producto, ya migrado por separado (Q02, ids 23/140) — pero su descripción,
imágenes y PDF son 100 % Kelley, idénticos a id=24 (comparten el mismo hash de descripción).
Parece un error de copiar-pegar en el campo de nombre, no una segunda identidad real.

**(2) id=3** ("NIVELADOR DE ANDÉN HIDRÁULICO SERIE HP") tiene su propia descripción, no
compartida con los otros dos, y su categoría (7, "Residenciales") no pertenece a la misma
rama que la categoría 10 de sus supuestos hermanos. `Policy.php` ya excluye explícitamente
este id (`CATEGORY_CONFLICT_PRODUCTS = ['3']`) de cualquier resolución automática — esta tarea
no lo desactiva, lo confirma.

**Opción A — Corregir editorialmente el nombre de id=139** (quitar el texto de Blue Giant) y
**consolidarlo con id=24**; **mantener id=3 en REVIEW** hasta confirmar si "Serie HP" es el
mismo nivelador con nombre de modelo más específico, o una referencia distinta.
**Opción B — Mantener los 3 completamente separados** hasta revisión de catálogo.
**Opción C — Consolidar los 3 en un único producto**, asumiendo que "Serie HP" es sólo un
nombre más específico del mismo nivelador Kelley.

**Recomendación técnica.** Opción A. Es la única que no requiere adivinar si "Serie HP" es o
no el mismo producto — separa lo que la evidencia sí resuelve (139 es un error de nombre, no
una entidad nueva) de lo que no resuelve (si el nivelador "Serie HP" es el mismo modelo).
**Desbloquea:** entre 0 y 8 filas según la opción elegida (0 si se elige B; hasta 8 si se
elige A o C — la diferencia depende de si id=3 se resuelve junto con los otros dos).

---

## Resumen

| Pregunta | Grupos | Filas afectadas | Riesgo |
|---|---:|---:|---|
| Q03-GLOBAL | 16 | 166 | Bajo — evidencia de enlace literal en los 16 |
| Q03-A | 7 | 62 | Bajo — ninguna depende de marca o categoría |
| Q03-B | 1 | 15 | Medio — identidad segura, marca deliberadamente pendiente |
| Q03-C | 1 | 8 | Medio — idéntico patrón que Q03-B |
| Q03-D | 1 | 7 | Medio — idéntico patrón, con matiz de texto mezclado |
| Q03-E | 1 | hasta 8 | Alto — el único con una contradicción de categoría/nombre sin explicación de copiar-pegar clara |

Si sólo se responde Q03-GLOBAL + Q03-A (las dos de riesgo bajo, sin ninguna dependencia de
marca): **228 filas** se desbloquean sin tocar ninguna decisión de marca pendiente de Q09.

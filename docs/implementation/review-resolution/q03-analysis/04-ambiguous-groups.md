# Los 11 grupos de revisión individual

No 13: la verificación contra el plan vivo movió 2 grupos al conjunto category-only (ver
[02](02-category-only-groups.md)) y confirmó que 1 grupo adicional (Kelley) tiene un problema
más profundo que "sólo el nombre", ya conocido por `Policy.php`. Los 11 se organizan en tres
niveles de fricción, no en una lista plana: 7 no tienen ninguna divergencia de categoría y su
único problema es el nombre (casi siempre cosmético); 1 no tiene divergencia de categoría ni
de nombre, sólo de marca; 3 tienen un problema de identidad o de marca genuino, ya documentado
antes de esta tarea.

## Distinción cosmética vs. técnica (criterio aplicado a los 11)

Se contó como **cosmética** (no cambia la clasificación): mayúsculas/minúsculas, puntuación,
espacios alrededor de una barra o guion, concordancia de plural/singular, una errata corregida
("autorreparabale" → "autorreparable"), o una palabra descriptiva genérica añadida/quitada que
no nombra un modelo distinto ("Thermacore serie 592" vs "Puerta seccional de acero Thermacore
serie 592"). Se contó como **técnica** (si apareciera, movería el grupo a revisión más
estricta): un número de modelo adicional, una marca en conflicto, una capacidad/dimensión
distinta, o un nombre que pertenece verificablemente a otro producto. Ningún grupo de los 11
tiene una diferencia técnica de modelo/capacidad/dimensión — el único candidato a considerarse
así es `energy-series...` (añade tres números de modelo), y se trata explícitamente como tal
más abajo, no se minimiza.

---

## A. Siete variantes de nombre sin ninguna divergencia de categoría — `SAME_PRODUCT_DIFFERENT_NAME`

Los siete no comparten grupo de marca ni de categoría entre sí; lo que comparten es la
estructura de la evidencia: nombre y categoría (única, sin unión) ya coinciden salvo por una
diferencia de redacción, la marca no tiene contradicción, y los PDF son idénticos byte a byte
en los siete. Confianza alta, riesgo bajo en los siete.

| Grupo | IDs | Nombres | Categoría (sin divergencia) | Marca | Descripción | Diferencia real |
|---|---|---|---|---|---|---|
| energy-series-with-intellicore-37171-3-4.php | 2,17 | "Energy Series With INTELLICORE® 3717 1 3/4″" / "ENERGY SERIES WITH INTELLICORE® 3717 1 3/4″, 3720 /3724 / 3722" | 8 (única) | Clopay, sin contradicción | **idéntica** (hash igual) | id=17 añade 3 números de modelo adicionales al nombre — descripción idéntica dice que es la misma línea de producto ampliada, no un modelo distinto |
| cortina-en-aluminio-serie-511-521.php | 5,88 | "511/521" vs "511 / 521" | 14 (única) | Overhead Door, sin contradicción | **idéntica** | espaciado alrededor de la barra — puramente cosmético; imágenes y PDF también idénticos byte a byte (mismas rutas exactas) |
| puerta-seccional-de-acero-thermacore-serie-592.php | 8,62 | "Thermacore serie 592" / "Puerta seccional de acero Thermacore serie 592" | 8 (única) | Overhead Door, sin contradicción | **idéntica** | prefijo descriptivo genérico añadido, mismo modelo |
| fast-seal-high-performance-door.php | 36,101 | "Fast-Seal ®" / "Fast-Seal® High Performance Door" | 17 (única) | Rytec, sin contradicción | **idéntica** | forma corta vs. forma larga del mismo nombre comercial |
| rapida-apilable.php | 39,104 | "Puerta rápida apilable" / "RÁPIDA APILABLE" | 17 (única) | Infraca Quality Doors, sin contradicción | **idéntica** | mayúsculas y omisión de "Puerta" — mismo modelo; su PDF INFRACA ya fue resuelto por Q11 citando estos mismos IDs |
| rolli-zip.php | 40,105 | "...autorreparabale" / "...autorreparable" | 17 (única) | GLG Porter Industriali, sin contradicción | difiere ligeramente (3085 vs 3102 car.) | corrección de errata, consistente con el mismo cambio editorial |
| labio-de-elevacion-mecanico-dockman.php | 51,143 | "Labio de elevación mecánico" / "...mecánico Dockman" | 21 (única) | Dockman, sin contradicción | **idéntica** | sufijo de marca añadido al nombre, mismo producto |

**Recomendación técnica:** consolidar bajo el nombre más completo/correcto de cada par (el que
ya usa el título SEO de la página dependiente, cuando difiere), sin necesidad de resolver
ninguna categoría (no hay ninguna que unir). Riesgo bajo porque ninguno depende de una
decisión de marca o de una tercera entidad.

---

## B. Un caso sin categoría ni nombre en conflicto, sólo marca — `POSSIBLE_DUPLICATE_BRAND_PENDING`

| Grupo | IDs | Nombre | Categoría | Descripción | Imágenes/PDF | El problema |
|---|---|---|---|---|---|---|
| lift-master-mod-h.php | 43,81 | idéntico ("Lift Master Mod H") | 13 (única, sin divergencia) | **idéntica** | idénticos | `brand_id` de id=43 apunta a "LiftMaster" (`WEAK_INFERENCE`); la página dependiente `lift-master-mod-h.php` trae como título "Lift Master Mod H **\| Blue Giant** \| Marcas" — contradicción ya documentada en `manual-decisions-required.md` ("LiftMaster 43/81: título BlueGiant") |

Identidad de producto: **confirmada, sin ninguna duda** (nombre/descripción/imágenes/PDF
100 % coincidentes). Lo único que impide fusionar hoy es a qué marca pertenece — pregunta que
Q09 ya reservó explícitamente para el conjunto "LiftMaster/Blue Giant" y que este análisis no
toca. La consolidación de identidad y la asignación de marca son, aquí, dos preguntas
separables: se puede fusionar en un único producto y dejarlo sin marca (mismo mecanismo que
Q09 ya usa como Opción C), sin esperar a resolver Q09 primero.

---

## C. Tres casos con un problema de identidad o marca genuino — `POSSIBLE_DUPLICATE_NEEDS_REVIEW`

### rampas-de-anden-hidraulicas-kelley.php (ids 3, 24, 139)

| Campo | id=3 | id=24 | id=139 |
|---|---|---|---|
| Nombre | "NIVELADOR DE ANDÉN HIDRÁULICO SERIE HP" | "Rampas de Andén Hidráulicas" | "Rampas de Andén Hidráulicas Kelley **RAMPA NIVELADORA MECÁNICA BLUE GIANT**" |
| Categoría | 7 "Residenciales" (`WEAK_INFERENCE`) | 10 "Rampa Niveladora" | 10 "Rampa Niveladora" |
| Marca | Kelley (`STRONG_INFERENCE`) | Kelley (`CONFIRMED`) | vacía |
| Descripción | única (hash propio) | comparte hash con id=139 | comparte hash con id=24 |
| Imágenes/PDF | idénticos en los 3 (Kelley-Hydraulic-Dock-Leveler-Brochure.pdf, rampak.jpg) | — | — |

Dos problemas distintos, no uno: **(1)** el nombre de id=139 incluye literalmente el nombre
de otro producto ya migrado por separado como grupo Q02 (`rampa-niveladora-mecanica-blue-giant.php`,
ids 23/140) — pero su descripción, imágenes y PDF son 100 % Kelley, idénticos a id=24. Esto
parece un error de copiar-pegar en el campo de nombre durante la carga de datos, no una
segunda identidad real: el contenido dice Kelley, sólo el título dice Blue Giant. **(2)** id=3
tiene su propia descripción (no compartida con los otros dos) y una categoría (7,
"Residenciales") que no pertenece a la misma rama que la categoría 10 de sus supuestos
hermanos; es exactamente el producto que `Policy.php` ya excluye por nombre
(`CATEGORY_CONFLICT_PRODUCTS = ['3']`). Que "Serie HP" sea el mismo nivelador de andén con un
nombre de modelo más específico, o una referencia distinta dentro de la misma línea Kelley, no
se puede determinar sólo con evidencia local.

**Clasificación:** `POSSIBLE_DUPLICATE` para id=24/139 (con corrección editorial de nombre
recomendada, no fusión automática); `INSUFFICIENT_EVIDENCE` para la relación de id=3 con los
otros dos. Evidence level: bajo para id=3, moderado para 24/139.

### coleccion-modern-steel.php (ids 6, 16, 20, 107)

Nombre, descripción, imágenes y PDF **idénticos en los cuatro** (superconjunto de una imagen
extra en id=107, no contradicción). Categoría **idéntica** en los cuatro (36, sin divergencia
— esta consolidación no depende en absoluto de Q03-GLOBAL). El único desacuerdo es la marca:
id=6 → Overhead Door, id=16 → Wayne Dalton, id=20 → Clopay, id=107 → sin marca — las cuatro a
confianza `WEAK_INFERENCE`, ninguna con evidencia fuerte. Coincide exactamente con
`manual-decisions-required.md`: "ModernSteel IDs 6/16/20/107: tres marcas". **Clasificación:**
`POSSIBLE_DUPLICATE` — identidad confirmada, marca deliberadamente fuera de alcance (Q09).

### puertas-seccionales-de-acero-aisladas-thermospan-modelo-150.php (ids 14, 57)

Nombre prácticamente idéntico (variación tipográfica menor), categoría **idéntica** (8, sin
divergencia). Descripción difiere (~4 %, 4609 vs 4783 caracteres); imágenes con una foto
propia por miembro (complementarias, no contradictorias). `manual-decisions-required.md` ya
documenta que el texto libre de estos dos registros mezcla referencias a Wayne Dalton y
Clopay ("14/57 texto Wayne y Clopay") — esta tarea no vuelve a leer el texto completo para
verificarlo de nuevo porque ya está documentado con la cita exacta de los mismos dos IDs; lo
cita como evidencia ya existente, no lo reafirma de forma independiente.
**Clasificación:** `POSSIBLE_DUPLICATE` — identidad probable, marca Q09-adyacente, fuera de
alcance.

---

## Variantes: ¿alguno necesita modelarse como A/B/C/D? (sección 9 de la tarea)

Ninguno de los 27 grupos, category-only o ambiguos, muestra evidencia de ser una variante
comercial real (talla, capacidad, material distinto del mismo producto base) que necesitara
**opción B** ("un producto con información de variantes"). El proyecto no tiene ecommerce ni
sistema de variaciones, y no se encontró ningún caso que lo echara en falta: donde el nombre
difiere, siempre es la misma entidad con una redacción distinta (opción C, consolidar), nunca
dos configuraciones distintas del mismo modelo base que ameritarían mantenerse separadas
(opción A) o representarse como variantes (opción B). El único grupo que queda en revisión sin
recomendación de fusión (Kelley, id=3) se deja explícitamente en **opción D — mantener
REVIEW** hasta que alguien con conocimiento del catálogo confirme si "Serie HP" es el mismo
nivelador o una referencia distinta.

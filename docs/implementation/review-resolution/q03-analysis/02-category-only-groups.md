# Los 16 grupos category-only — verificación uno por uno

Criterio de admisión, aplicado igual a los 27 grupos sin excepción: nombre idéntico tras
normalizar mayúsculas/acentos/puntuación/espacios **y** concordancia gramatical trivial
(singular/plural de la misma frase — p. ej. "Puertas blindadas" / "Puerta Blindada" cuenta
como el mismo nombre); marca sin contradicción (como mucho un `brand_id` no vacío distinto de
cero entre los miembros); PDFs de los miembros mutuamente compatibles (ningún par de miembros
tiene conjuntos de PDF por SHA-256 completamente disjuntos); imágenes con la misma regla;
categoría verificada contra `category-master.csv` (no aceptada a ciegas). Un grupo que falla
**cualquiera** de estos puntos queda fuera, sin excepción — así es como 2 grupos que parecían
"sólo categoría" a primera vista (`operador-para-perfilados-comerciales-sel.php` sobrevivió el
escrutinio; ver más abajo por qué sí se incluyó pese a tener menos filas de respaldo que el
resto) se decidieron con el mismo rigor que los demás.

Los 16 grupos son, en conjunto, mucho más homogéneos de lo que el número por sí solo sugiere:
**14 de los 16 pertenecen a sólo tres familias de marca** (Doorlock: 6 grupos; BFT: 4 grupos;
Overhead Door: 4 grupos), y las seis familias Doorlock comparten un patrón estructural
idéntico verificado en las fechas SQL: una fila creada el 22-oct con categoría doble
(`STRONG_INFERENCE`, sin confirmar en SQL) y descripción a menudo vacía, seguida de dos filas
creadas el 29-oct y el 4-nov, cada una con **una** categoría `CONFIRMED` en SQL. Esto no es
una coincidencia por grupo: es evidencia sistémica de que el catálogo antiguo clonaba la fila
una vez por sección porque el esquema SQL sólo admitía una categoría por fila — exactamente la
hipótesis que Q03-GLOBAL propone confirmar.

## Tabla de verificación

| # | Grupo | IDs | Nombre | Marca | PDFs | Imágenes | Categoría (unión) | Descripción | Nota |
|---|---|---|---|---|---|---|---|---|---|
| 1 | accesspro-fs1000speed.php | 1,109,110,114 | idéntico | Overhead Door, sin contradicción | idénticos (4 miembros) | idénticas (4 miembros) | 37\|38\|39 (verificadas: 3 secciones reales bajo el mismo padre residencial=7) | id=1 difiere ~76 car. del resto (805 vs 881); resto idéntica | La propia fila id=1 ya declara las 3 categorías por `STRONG_INFERENCE` — el patrón "unión + 3 clones confirmados" en su forma más limpia |
| 2 | cortina-serie-600.php | 10,93,127 | idéntico | Overhead Door, sin contradicción | idénticos | id=93 es subconjunto (falta 1 imagen); sin contradicción | 14\|19 | id=93 difiere (1846 vs 1712 car., +134); id=10/127 idénticas | Diferencia de descripción modesta, no cambia identidad |
| 3 | puertas-blindadas.php | 28,118,129 | "Puertas blindadas" / "Puerta Blindada" — sólo concordancia singular/plural | Doorlock, sin contradicción | idénticos | id=129 tiene 1 imagen extra; superconjunto, no contradicción | 19\|32 | **las 3 difieren entre sí** (2332/1776/1662 car.) | Ejemplo ilustrativo original de la tarea; enlaces literales confirmados: `centros-comerciales.php` → cat.19, `blindadas.php` → cat.32 |
| 4 | puertas-contra-incendio.php | 29,116,130 | idéntico | Doorlock, sin contradicción | idénticos | id=116/130 subconjunto de id=29; sin contradicción | 19\|33 | id=29 **vacía** (0 car.); 116/130 difieren entre sí | Cruce con Q10: categoría 33 ya `MIGRATE` |
| 5 | puerta-estandar.php | 31,119,131 | idéntico | Doorlock, sin contradicción | idénticos (131 subconjunto) | igual | 19\|27 | id=31 **vacía**; 119/131 idénticas entre sí | — |
| 6 | puerta-estandar-reforzada.php | 32,120,132 | idéntico | Doorlock, sin contradicción | idénticos (120/132 subconjunto) | igual | 19\|28 | id=32 **vacía**; 120/132 idénticas entre sí | — |
| 7 | puerta-holandesa.php | 33,122,133 | idéntico | Doorlock, sin contradicción | idénticos | idénticas | 19\|29 | **idéntica en los 3** | El más limpio de la familia Doorlock |
| 8 | puerta-y-fijos-louver.php | 35,121,134 | idéntico | Doorlock, sin contradicción | (sin PDF documentado en ningún miembro — sin conflicto posible) | idénticas | 19\|30 | **idéntica en los 3** | — |
| 9 | barreras-estacionamiento-moovi50rm.php | 44,123,125,135 | idéntico | BFT en id=44, resto sin marca — sin contradicción | idénticos (4) | idénticas (4) | 9\|18\|19 | **idéntica en los 4** | Identidad de producto limpia; ver nota de marca más abajo |
| 10 | p45-7-piston-hidraulico-para-puerta-abatible.php | 47,80,113 | idéntico | BFT, sin contradicción | idénticos | idénticas | 12\|38 | id=80 difiere ligeramente (447 vs 430) | Cat.12 y 38 comparten nombre pero son ramas reales distintas (ver 09-risk-analysis.md) |
| 11 | lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php | 48,79,112 | idéntico | BFT, sin contradicción | idénticos | idénticas | 12\|38 | id=48 difiere ligeramente | Mismo par de categorías que el anterior; PDF compartido con icaro-smart (ver 05) |
| 12 | icaro-smart.php | 49,76,124,126 | idéntico | BFT, sin contradicción (4) | idénticos (4) | idénticas (4) | 9\|11\|18 | id=76 difiere ligeramente (570 vs 553) | Su PDF es el de LUX, no uno propio — anomalía ya documentada ("ICARO enlaza PDF LUX"), no es un problema de esta consolidación |
| 13 | cortina-plana.php | 54,89,136 | idéntico | Dockman, sin contradicción | idénticos | id=89/136 subconjunto de id=54; sin contradicción | 14\|19 | **idéntica en los 3** | — |
| 14 | cortina-europea.php | 55,90,137 | idéntico | Dockman, sin contradicción | idénticos | idénticas | 14\|19 | **idéntica en los 3** | — |
| 15 | operador-para-perfilados-comerciales-sel.php | 78,84 | idéntico | Overhead Door, sin contradicción | idénticos | cada miembro tiene 1 foto propia además de las 2 compartidas — complementarias, no contradictorias (mismo patrón que Q02 aprobó para sus 8 casos con fotos distintas) | 11\|13 (sin fila de unión propia, sólo 2 miembros) | difiere (~4 %, 4068 vs 3923) | El único de los 16 sin una tercera fila que muestre la categoría doble explícitamente — incluido por mérito propio, no por el patrón de clonación |
| 16 | cortinas-ventiladas-685.php | 96,128 | idéntico | Overhead Door, sin contradicción | idénticos | idénticas | 14\|19 (sin fila de unión propia) | **idéntica** | Igual que el anterior, sólo 2 filas, pero sin ninguna diferencia salvo la categoría — el caso más limpio de los que no tienen tercera fila |

Todos los 16 producen su decisión `REVIEW` actual con la misma razón genérica del plan vivo
(`Maestro REVIEW: identidad, duplicado o excepción editorial`); ninguno tiene ya una decisión
propia. Verificado contra el plan reconstruido, no asumido.

## Por qué el número es 16 y no 14

La estimación original ("14 de 27") viene de `decision-questionnaire.md`, escrita antes de que
existiera un mecanismo para cruzar cada grupo contra el plan vivo y contra
`category-master.csv` fila por fila. Al hacerlo aquí, dos grupos adicionales
(`operador-para-perfilados-comerciales-sel.php` y `cortinas-ventiladas-685.php`) superan el
mismo criterio que los otros 14 sin ninguna excepción especial: nombre idéntico, marca sin
contradicción, PDFs e imágenes compatibles, categoría genuinamente doble. La única diferencia
frente a los 14 "clásicos" es que estos dos no tienen una tercera fila SQL que declare
explícitamente la categoría combinada — sólo existen 2 filas por grupo, cada una con una
categoría `CONFIRMED` distinta. Esa ausencia no es evidencia en contra: es simplemente que el
catálogo antiguo nunca llegó a crear (o no sobrevivió) una tercera fila "combinada" para estos
dos productos. El resto de la evidencia (nombre/marca/PDF/imágenes) es igual de sólida.

## Grupo que casi entra y se dejó fuera: por qué no

`rampas-de-anden-hidraulicas-kelley.php` (grupo 3, ids 3/24/139) tiene una fila con categoría
doble aparente y dos filas confirmadas — el mismo patrón estructural que los 16 anteriores.
**Se excluyó explícitamente** porque falla dos criterios a la vez, no uno: (a) el nombre de
id=139 incluye literalmente el texto "RAMPA NIVELADORA MECÁNICA BLUE GIANT" — el nombre de
**otro** producto ya migrado por separado (Q02, ids 23/140) — y (b) id=3 tiene su propia
categoría (7, "Residenciales") que no es una de las dos que comparten id=24/139, y que
`Policy.php` ya excluye explícitamente de la resolución automática
(`CATEGORY_CONFLICT_PRODUCTS = ['3']`). Aceptar este grupo como category-only habría requerido
ignorar una contradicción de nombre real, no cosmética. Ver
[04-ambiguous-groups.md](04-ambiguous-groups.md).

## Medios: ninguno de los 16 se excluyó por contradicción de medios

Se revisó explícitamente si algún grupo de los 16 tenía imágenes o PDFs contradictorios
(conjuntos de SHA-256 completamente disjuntos entre dos miembros). **Ninguno la tuvo.** Las
diferencias de imagen encontradas son siempre subconjunto/superconjunto (un miembro con menos
fotos que otro, nunca fotos distintas e incompatibles) o, en el caso de
`operador-para-perfilados-comerciales-sel.php`, fotos complementarias (cada miembro con una
foto adicional propia, ambas del mismo producto) — el mismo patrón que Q02 ya aprobó para 8 de
sus 26 grupos. Detalle metodológico en [05-media-analysis.md](05-media-analysis.md).

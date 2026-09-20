# Cuestionario de decisión

Quedan **932 fichas pendientes de revisión** en la migración. No son 932 problemas: son
**13 decisiones**. Este documento las plantea una por una.

No hace falta responderlas todas de golpe ni en orden. Las tres primeras (Q02, Q05, Q06) no
dependen de nada y se pueden contestar hoy mismo.

Nada de lo que se decida aquí publica contenido: todo entra como borrador y sigue revisándose
antes de salir al público.

---

## Q01 · ¿De quién es el contenido de las fichas y páginas de catálogo? ⭐ la de mayor impacto

**Qué hay que decidir.** El sitio antiguo tiene 164 páginas que son, en realidad, la
presentación de algo que ya existe en el catálogo: la ficha de un producto, el listado de una
categoría o la página de una marca. En el sitio nuevo, ese contenido puede vivir **dentro del
producto / de la categoría / de la marca**, o puede vivir además como una página aparte.

**Ejemplo concreto.** `cortina-serie-625.php` es la ficha de un producto que ya está en el
catálogo. ¿El texto y las fotos de esa página pasan a ser parte del producto "Cortina serie
625", o creamos además una página independiente con el mismo contenido?

**Opción A — El contenido pertenece a la entidad (producto/categoría/marca).**
No se crea una página aparte. El catálogo queda limpio, sin contenido duplicado, y cada
producto tiene una sola ficha.

**Opción B — Se crean también páginas independientes.**
Se conserva todo tal cual, pero el mismo texto acaba en dos sitios: el producto y la página.
Eso genera contenido duplicado y compite consigo mismo en buscadores.

**Hay una parte crítica que va unida.** Esas 164 páginas son el único sitio donde están
registradas **250 imágenes y fichas técnicas**. Si se decide que las páginas no se crean,
hay que decir también qué pasa con esas 250: lo natural es que las imágenes de una ficha de
producto pasen a la galería de ese producto. Si no se dice nada, esas 250 se quedan sin
dueño.

**Qué recomienda la evidencia.** La opción A. La arquitectura del proyecto ya la había
elegido, y la duplicación de contenido es un problema real para posicionamiento. Pero con
una condición: **no aplicarla hasta tener el compromiso de la fase de direcciones web**.
Decidir "esta página no se crea" es correcto para el contenido y peligroso para las
direcciones: hay que garantizar antes que esas 164 direcciones antiguas seguirán funcionando
(redirigiendo al producto correspondiente).

**Desbloquea:** 414 fichas (164 páginas + 250 imágenes/PDF). 174 entidades.
**Riesgo:** medio — no por el contenido, sino por las direcciones web.

---

## Q02 · Productos que están repetidos y son el mismo ⭐ fácil y de alto rendimiento

**Qué hay que decidir.** Hay 26 productos que aparecen 2, 3 o 4 veces en la base de datos
antigua. Hemos comprobado que en los 26 casos **es el mismo producto**: misma ficha, mismo
nombre, misma categoría, misma marca y **exactamente las mismas fichas técnicas en PDF**.

En 18 de los 26, además, las fotos también son idénticas: son copias puras, no hay nada que
decidir salvo confirmar que se conserva un solo producto.

En los 8 restantes, cada copia tiene **fotos distintas del mismo producto**.

**Ejemplo concreto.** El "Operador comercial RHX" está dos veces. Una copia tiene una foto y
la otra copia tiene otra foto diferente — las dos del mismo operador. Entre las dos suman 2
fotos.

**Opción A — Nos quedamos con una copia y descartamos las fotos de la otra.**
Simple y seguro, pero perdemos fotos reales del producto (entre 1 y 3 por caso, en 8
productos).

**Opción B — Juntamos todas las fotos en el producto único.**
No se pierde nada; el producto queda con todas sus fotos, que es lo que el catálogo antiguo
tenía registrado.

**Si se elige B, hay que decir una cosa más:** cuál es la **foto principal** (la que se ve
primero) y en qué orden va el resto. Eso no está en los datos antiguos; es una elección
visual. La propuesta más simple: la primera foto del registro más antiguo.

**Qué recomienda la evidencia.** La opción B. El riesgo que nos hacía dudar era que dos
copias tuvieran **fichas técnicas contradictorias** — y hemos comprobado que **eso no ocurre
en ninguno de los 26 casos**: las fichas PDF son idénticas en todos. Las fotos son
complementarias, no contradictorias.

**Desbloquea:** 124 fichas y consolida 26 productos reales del catálogo.
**Riesgo:** bajo.

---

## Q05 · Fichas técnicas en PDF que el sistema bloquea ⭐ casi resuelta

**Qué hay que decidir.** 32 PDF están bloqueados por la comprobación de seguridad, igual que
los 7 que ya revisamos y aprobaste. Al revisarlos con el mismo método:

- **9 son exactamente el mismo archivo** (byte a byte) que PDFs que **ya aprobaste y ya
  limpiamos**. Sólo están guardados con otro nombre. La copia limpia ya existe.
- **2 son exactamente el mismo archivo** que PDFs que ya aprobaste como falsa alarma.
- **1** tiene el mismo perfil que los que limpiamos (un archivo de configuración de imprenta
  incrustado, inofensivo).
- **20** son falsas alarmas o llevan una instrucción de "abrir en la página 3", que no ejecuta
  nada.

**Ninguno de los 32 contiene código ejecutable, ni programas incrustados, ni nada que se
lance solo al abrir el documento.**

**Opción A — Extender la misma decisión que ya tomaste.**
Los 11 que son copias idénticas de archivos ya aprobados se aprueban automáticamente (es la
misma decisión, aplicada al mismo contenido); los otros 21 se tratan igual que sus gemelos:
limpiar el archivo si lleva algo incrustado, aprobar si es falsa alarma.

**Opción B — Revisar los 21 restantes uno a uno antes de decidir.**
Más lento, mismo resultado previsible.

**Opción C — Dejarlos todos bloqueados.**
Se pierden 32 fichas técnicas descargables del catálogo.

**Qué recomienda la evidencia.** La opción A. Los 11 primeros no requieren ningún criterio
nuevo: es el mismo archivo que ya aprobaste. Los otros 21 tienen un perfil ya conocido y
documentado.

**Desbloquea:** 41 fichas.
**Riesgo:** bajo. Ninguno de los archivos tiene contenido activo real.

---

## Q06 · Registros vacíos y de prueba ⭐ decisión de un minuto

**Qué hay que decidir.** En la base de datos antigua hay 14 registros **sin nombre y sin
descripción** (los números 151 a 164) y 1 llamado literalmente "Prueba" (el 165).

No se pueden crear como productos: no tienen contenido. Pero **tienen imágenes asociadas y
una categoría**, lo que sugiere que fueron altas empezadas y nunca terminadas.

**Opción A — Excluirlos del catálogo nuevo.**
No se borra nada del sistema antiguo; simplemente no se traen. Reversible.

**Opción B — Traerlos como borradores vacíos para completarlos después.**
Aparecen en el panel como productos incompletos, a la espera de contenido.

**Qué recomienda la evidencia.** La opción A, salvo que reconozcas alguno de esos 15 como un
alta pendiente que quieras recuperar. Merece una mirada rápida a las imágenes asociadas antes
de confirmar.

**Desbloquea:** 30 fichas (15 registros + 15 imágenes suyas).
**Riesgo:** bajo y reversible.

---

## Q04 · Las 31 fichas que sólo existen como página

**Qué hay que decidir.** Hay 31 fichas de producto que existen **sólo como página del sitio
antiguo**: nunca se dieron de alta en la base de datos del catálogo. Tienen texto propio e
imágenes propias, pero **no tienen categoría ni marca asignadas**.

Hemos confirmado que **no son duplicados** de ningún producto existente: son contenido real y
adicional. Si no se traen, ese contenido desaparece del sitio nuevo.

**Ejemplo concreto.** Fichas como `bix-al-antena-externa-433-mhz.php` o `cross-3e.php`:
existen, se ven, tienen fotos, pero el catálogo nunca las registró.

**Opción A — Convertirlas en productos, asignando la categoría a mano.**
31 asignaciones, una sesión de trabajo. El contenido y las fotos ya están listos.

**Opción B — Convertirlas en productos sin categoría.**
Entran rápido, pero quedan invisibles en la navegación hasta clasificarlas.

**Opción C — Tratarlas como páginas sueltas, no como productos.**
Conserva el contenido, pero si son productos reales queda inconsistente con el resto.

**Opción D — Descartarlas.** Se pierden 31 fichas con contenido propio.

**Qué recomienda la evidencia.** La opción A. Es la mejor relación esfuerzo/resultado del
proyecto: son sólo 31, ya tienen todo menos la categoría. **Conviene responderla junto con
Q01**, porque son los mismos ficheros vistos desde otro ángulo.

**Desbloquea:** 31 fichas.
**Riesgo:** medio (hay que elegir bien la categoría; no se puede deducir automáticamente).

---

## Q03 · Los 27 grupos que requieren criterio de catálogo

**Qué hay que decidir.** Otros 27 productos aparecen repetidos, pero **a diferencia de Q02,
aquí las copias no son idénticas**: cambia la categoría, el nombre, o ambos.

**Y hay un patrón muy claro:** en **14 de los 27**, lo único que cambia es la **categoría**.

**Ejemplo concreto.** "Puertas blindadas" aparece 3 veces:
- una copia en la categoría 19,
- otra copia en las categorías 19 **y** 32,
- otra copia en la categoría 32.

Es el mismo producto. El sistema antiguo **repetía la ficha una vez por cada sección del
catálogo donde aparecía**, porque sólo admitía una categoría por ficha.

**La pregunta de fondo: ¿un producto puede estar en varias categorías?**

**Opción A — Sí: un producto, varias categorías.**
Se corresponde con lo que el sitio antiguo mostraba realmente. Los 14 grupos se resuelven de
golpe, sin perder ninguna vía de navegación. Queda por decidir cuál es la categoría
*principal* de cada uno (la que manda en las migas de pan).

**Opción B — No: un producto, una sola categoría.**
Hay que elegir, para cada uno de esos 14 productos, en qué sección se queda y **renunciar a
las demás**. El cliente perdería vías de acceso que hoy existen.

**Los 13 grupos restantes** sí requieren mirarlos uno a uno: ahí cambia el nombre, y hay que
decidir si son variantes del mismo modelo o modelos distintos agrupados por error.

**Qué recomienda la evidencia.** La opción A para los 14 de categoría; revisión individual
para los 13 restantes. La estructura de varias categorías por producto no es una función que
estemos buscando dónde usar: es la que el sitio antiguo ya tenía, expresada de forma
repetida.

**Desbloquea:** 164 fichas (74 productos + 90 imágenes/PDF).
**Riesgo:** alto — es el único bloque que exige conocer el catálogo comercial.

---

## Q07 · Las 9 páginas de gama / landing

**Qué hay que decidir.** 9 páginas parecen creadas para posicionamiento: hablan de una gama
o combinan varios productos. No son la ficha de un producto concreto ni el listado de una
categoría.

**Opción A — Traerlas como páginas propias.**
Se conserva el texto y la dirección web. Algunas repiten descripciones de productos que ya
migran por separado.

**Opción B — Considerar que su contenido ya está cubierto** por las categorías o los
productos correspondientes, y no crearlas.

**Qué recomienda la evidencia.** La opción A como borrador, revisando una por una si aporta
texto propio o sólo repite lo que ya dice el producto. No se puede determinar automáticamente
si tienen valor de posicionamiento: eso depende de tráfico real.

**Desbloquea:** 26 fichas (9 páginas + 17 imágenes).
**Riesgo:** medio.

---

## Q08 · Imágenes que no aparecen enlazadas en ningún sitio

**Qué hay que decidir.** 89 imágenes están en el servidor antiguo pero **no encontramos
ningún enlace a ellas** en las páginas copiadas.

Mantenemos un principio: **que no encontremos el enlace no demuestra que nadie las use**.
Pueden estar enlazadas desde algo que no tenemos, o recibir visitas directas.

De las 89, **23 tienen su contenido duplicado en otra ruta** que sí migra: en esos casos la
imagen no se pierde pase lo que pase. Las **66 restantes** son el caso real.

**Opción A — Traerlas todas.**
No se pierde nada; la biblioteca de medios queda con 89 archivos cuyo uso no consta.

**Opción B — Dejarlas fuera.**
Biblioteca limpia, pero si alguna se usaba, se rompe.

**Opción C — Decidir con datos reales de visitas** (Search Console, registros del servidor).

**Qué recomienda la evidencia.** La opción C para las 66. Es la única de las 13 decisiones
que **no se puede resolver con lo que hay en el repositorio**. Si no hay acceso a esos datos,
la opción A es la conservadora.

**Desbloquea:** 89 fichas.
**Riesgo:** medio.

---

## Q10 · Dos categorías con el mismo nombre

**Qué hay que decidir.** Existen dos categorías llamadas **"Puertas contra incendio"**:
- la nº 26: sin productos, sin página propia;
- la nº 33: con 1 producto y su página `contra-incendio.php`.

Y una tercera, la nº 25 ("Bumpers, semáforos y cepillos para rampa niveladora"), que no tiene
página propia y es donde están agrupados los 15 registros vacíos de Q06.

**Opción A — La 26 es un registro sobrante: se descarta y se conserva la 33.**
**Opción B — Son dos ramas distintas del catálogo y ambas se conservan.**
**Opción C — La 25 es un contenedor interno, no una categoría comercial: no se publica.**

**Qué recomienda la evidencia.** La 26 tiene todas las señales de un registro vacío del
panel antiguo (sin productos, sin página). La 25 depende de qué decidas en Q06. Aun así,
fusionar categorías por tener el mismo nombre es justo lo que nos prohibimos hacer
automáticamente: necesita tu confirmación.

**Desbloquea:** 4 fichas. **Riesgo:** medio (afecta a la navegación pública).

---

## Q11 · Cuatro archivos que faltan

**Qué hay que decidir.** Cuatro archivos referenciados en el sitio antiguo no existen:

| Archivo | Lo usa | Situación |
|---|---|---|
| `images/dura.jpg` | ficha Dura-Glide | no aparece por ningún lado |
| `images/magic.jpg` | ficha Magic | no aparece por ningún lado |
| `fichas/INFRACA-RÁPIDA-APILABLE.pdf` | ficha Rápida Apilable | **el archivo sí existe**; el nombre difiere en un acento codificado de dos formas |
| `fichas/FICHATECNICASELLOSSOLMMERS.pdf` | ficha Sellos Nacionales | existe uno de nombre parecido (`SELLOSSOLMMER.pdf`), sin confirmar que sea el mismo |

**Qué hay que responder.** ¿Autorizas corregir la referencia del PDF de INFRACA (caso 3), que
es sólo un problema de codificación del nombre? ¿Y es `SELLOSSOLMMER.pdf` el mismo documento
del caso 4?

**Qué recomienda la evidencia.** El caso 3 es recuperable sin inventar nada. Los casos 1 y 2
requieren el archivo original o aceptar su ausencia. El caso 4 **no debe darse por bueno por
parecido de nombre**.

**Desbloquea:** 4 fichas. **Riesgo:** bajo. Ninguno bloquea la migración.

---

## Q12 · Tres páginas de función

Tres páginas que no son contenido sino función del sitio antiguo. **¿Su función se rehace en
WordPress, se descarta o se conserva?**
**Desbloquea:** 3 fichas. **Riesgo:** bajo.

---

## Q13 · Un residuo técnico

Un archivo PDF que es copia exacta de otro ya migrado en la prueba inicial, guardado en una
segunda ruta. El sistema lo dejó fuera deliberadamente para no crear dos copias del mismo
documento. **¿Se conserva esa segunda dirección o se da por cubierta con la primera?**
**Desbloquea:** 1 ficha. **Riesgo:** muy bajo.

---

## Q09 · El fabricante de "Sellos Nacionales"

**Qué hay que decidir.** El producto nº 150 tiene el título de **Dockman**, pero el texto y su
ficha técnica apuntan a **Solmmer**.

**Dato importante:** Solmmer **no existe** como marca en el catálogo (sólo hay 12 marcas y
ninguna es Solmmer). Decidir "es Solmmer" implicaría **crear una marca nueva**.

**Opción A — Es Dockman** (lo que dice el título).
**Opción B — Es Solmmer**, y se crea la marca.
**Opción C — Se migra sin marca** y se decide más adelante.

**Qué recomienda la evidencia.** Nada en el sitio resuelve la contradicción. La opción C está
disponible y ya funciona: el sistema migra productos sin marca cuando la evidencia no es
concluyente.

**Desbloquea:** 1 ficha directamente (y desatasca el criterio para otros 13 productos con
conflictos de marca parecidos: Modern Steel, LiftMaster/Blue Giant, MOOVI, Thermospan).
**Riesgo:** alto si se asigna mal — etiquetar un producto con el fabricante equivocado es un
error visible para el cliente final.

---

## Resumen para decidir rápido

| Si sólo puedes contestar 3 | Desbloqueas |
|---|---|
| **Q02** (productos repetidos idénticos) | 124 |
| **Q05** (PDF bloqueados) | 41 |
| **Q06** (registros vacíos) | 30 |
| | **195 fichas** |

| Si puedes contestar 5 | Desbloqueas |
|---|---|
| Las 3 anteriores + **Q01** (propiedad de páginas) + **Q04** (fichas sólo-página) | **640 fichas de 932** |

Las demás pueden esperar sin bloquear nada.

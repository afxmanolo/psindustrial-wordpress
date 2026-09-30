# Categorías: jerarquía frente a pertenencia

Dos preguntas distintas que la fase anterior trataba como una sola. Separarlas es lo que
permite avanzar.

## Pregunta 1 — Jerarquía de categorías (Q10, 4 filas)

**Ya resuelta al 92 %.** De las 38 categorías, 33 se aprobaron y crearon con R-T01 usando el
padre SQL confirmado; 2 estaban ya migradas en el ensayo. Quedan 3 en REVIEW:

| ID | Nombre | Padre | Página propia | Productos | Problema |
|---|---|---|---|---:|---|
| 25 | Bumpers, semáforos y cepillos para rampa niveladora | 3 | — | 14 | sin página propia; es la categoría donde caen los 14 registros vacíos |
| 26 | Puertas contra incendio | 0 | — | 0 | mismo nombre que la 33; sin productos ni página |
| 33 | Puertas contra incendio | 5 | `contra-incendio.php` | 1 | mismo nombre que la 26, distinto padre |

Más 1 medio (imagen de categoría) que depende de ellas.

- **26 vs 33:** mismo nombre, padres distintos (0 y 5). La 26 no tiene productos ni página; la
  33 sí tiene ambos. Fusionarlas por nombre es exactamente lo que la documentación prohíbe,
  pero la 26 tiene todas las características de un registro vacío del backoffice.
- **25:** no tiene página propia y agrupa los 14 registros vacíos (IDs 151–164). Parece un
  contenedor administrativo más que una categoría comercial, pero tiene 14 productos
  asociados en SQL, así que no puede descartarse sin decidir antes Q06.

Impacto total: 4 filas. Es una decisión **pequeña** y puede posponerse sin bloquear nada más.

## Pregunta 2 — ¿Un producto puede pertenecer a varias categorías?

Esta es la pregunta de fondo, y la evidencia es mucho más clara de lo que parecía.

### Lo que dicen los datos

| Medida | Valor |
|---|---:|
| Productos cuyo `category_id` resuelto es **multivalor** (`a\|b`) | 14 |
| Productos con `root_category_ids` multivalor | 12 |
| Productos con **categorías candidatas** múltiples | 49 |
| Grupos editoriales (de 27) cuya divergencia **es** la categoría | **14** |

Y el patrón concreto de esos 14 grupos es revelador:

| Grupo | IDs SQL | Valores de categoría por registro |
|---|---|---|
| `puertas-blindadas.php` | 28, 118, 129 | `19`, `19\|32`, `32` |
| `puerta-estandar.php` | 31, 119, 131 | `19`, `19\|27`, `27` |
| `cortina-plana.php` | 54, 89, 136 | `14`, `14\|19`, `19` |
| `accesspro-fs1000speed.php` | 1, 109, 110, 114 | `37`, `37\|38\|39`, `38`, `39` |
| `puertas-contra-incendio.php` | 29, 116, 130 | `19`, `19\|33`, `33` |
| `cortina-europea.php` | 55, 90, 137 | `14`, `14\|19`, `19` |

**El legacy repetía la fila del producto una vez por cada categoría en la que aparecía.** Un
mismo producto físico (mismo PHP, mismo nombre, misma marca) existe como 3 o 4 filas SQL
porque se mostraba en 3 o 4 secciones del catálogo.

### Qué significa esto

La pregunta "¿los datos legacy justifican usar varias categorías por producto?" tiene una
respuesta empírica: **sí**. No es una capacidad de WordPress que estemos buscando dónde usar;
es la estructura que el sitio original ya tenía, expresada de forma redundante porque el CMS
antiguo sólo admitía una categoría escalar por fila.

La consecuencia práctica: si se decide "un producto = un término de categoría", habría que
**elegir** cuál de las 3–4 secciones conserva el producto y **perder** las otras vías de
navegación. Si se decide "un producto = varias categorías", los 14 grupos se resuelven de
forma natural y sin pérdida.

### Lo que sigue sin poder determinarse desde el código

- **Cuál es la categoría *principal*** de cada producto (la que manda en migas de pan, orden
  de archivo o canonical). El legacy no la declara; sólo declara pertenencias.
- Si alguna de esas pertenencias era un error del backoffice y no una decisión comercial.

Ambas son decisiones de negocio.

## Separación explícita

| Concepto | Estado |
|---|---|
| **Jerarquía** (qué categoría cuelga de cuál) | resuelta para 33 de 38; 3 pendientes (Q10) |
| **Pertenencia** (qué productos van en qué categorías) | pendiente; es el núcleo de Q03 y afecta a 14 de los 27 grupos editoriales |

No se inventa ninguna estructura en esta fase. Las 33 categorías creadas usan exclusivamente
el padre SQL confirmado, y ninguna lleva productos asignados todavía.

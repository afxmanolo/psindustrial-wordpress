# Los 26 grupos de identidad equivalente

Grupos canónicos con más de un registro SQL donde **nombre, PHP, categoría y marca coinciden**
en todos los miembros. Matriz completa en [05-merge-groups.csv](05-merge-groups.csv)
(`group_class = IDENTITY_EQUIVALENT`).

## Conclusión principal, verificada fila a fila

En la fase analítica se dijo que `db_image_ids` divergía en los 53 grupos múltiples y que por
eso no existía ningún ganador determinista. Al resolver esos IDs **a sus rutas de archivo
reales**, el cuadro cambia de forma sustancial:

| Comprobación | Resultado |
|---|---:|
| Grupos con conjunto de **PDF idéntico** entre miembros | **26 de 26** |
| Grupos con conjunto de **imágenes idéntico** entre miembros | **18 de 26** |
| Grupos donde las imágenes realmente difieren | **8 de 26** |
| Grupos con **PDF contradictorios** (fichas distintas para el mismo modelo) | **0 de 26** |
| Grupos con duplicados binarios de imagen dentro del grupo | **0 de 26** |
| Grupos cuyo nombre difiere sólo en mayúsculas/espacios | 1 (IDs 19/61) |

**El riesgo que bloqueó esta decisión en la fase anterior —"la unión podría juntar dos fichas
técnicas contradictorias" (D07)— no se materializa en ninguno de los 26 grupos.** Los dos
grupos que aparentaban tener "dos PDFs distintos" (21/147 y 30/115) resultaron tener, cada
miembro, **el mismo par** de documentos (un folleto comercial y una ficha de fabricación);
no hay desacuerdo entre registros.

### Por qué la divergencia de `db_image_ids` era engañosa

La tabla legacy `file` contiene registros duplicados que apuntan al **mismo archivo físico**.
Dos registros del mismo producto podían referenciar IDs distintos (`118` y `146`, por
ejemplo) que resuelven a la misma ruta. Comparar IDs daba divergencia en el 100 % de los
grupos; comparar rutas la reduce a 8 grupos.

## Clasificación por grupo

| Clase | Grupos | Criterio |
|---|---:|---|
| `SAME_ENTITY_CONFIRMED` | **26** | mismo PHP, misma categoría, misma marca, nombre igual (normalizado), PDFs idénticos |
| `SAME_ENTITY_LIKELY` | 0 | — |
| `POSSIBLE_DUPLICATE` | 0 | — |
| `DIFFERENT_ENTITY` | 0 | — |
| `HUMAN_REVIEW` | 0 | — |

Los 26 grupos son demostrablemente **una sola entidad comercial cada uno**, representada por
2–4 filas SQL. Ninguno interseca los registros de conflicto D03/D06/D07.

### Subdivisión operativa

| Subgrupo | Grupos | Filas producto en REVIEW | Qué falta decidir |
|---|---:|---:|---|
| **A — duplicado puro**: todo idéntico, incluidas las imágenes | 18 | 36 | nada sobre medios; sólo confirmar que se conserva un único producto |
| **B — imágenes complementarias**: mismos PDFs, listas de imágenes distintas | 8 | 16 | qué hacer con las imágenes de cada registro |

En el subgrupo B, los solapamientos observados son parciales o nulos — por ejemplo
`operador-comercial-rhx.php` (IDs 12/82) tiene 0 imágenes en común y 2 en total, y
`rapida-enrollable-industrial.php` (IDs 38/103) tiene 1 común de 4 totales. Son **fotos
distintas del mismo producto**, no versiones en conflicto.

## Respuestas a las preguntas planteadas, por grupo

Para los 26 grupos la respuesta es homogénea, lo que permite tratarlos como **una sola
decisión de política** en lugar de 26 decisiones:

- **¿Es demostrablemente una sola entidad?** Sí en los 26. Mismo PHP de origen ⇒ misma ficha
  pública; misma categoría; misma marca; mismo nombre normalizado.
- **¿Qué campos son idénticos?** `name` (normalizado), `legacy_php`, `legacy_url`,
  `category_id`, `brand_id` y el conjunto de PDFs.
- **¿Qué campos divergen?** Sólo las listas de imágenes, y sólo en 8 de los 26.
- **¿Sólo divergen medios?** Sí. Ningún grupo diverge en un campo editorial.
- **¿Complementarios o contradictorios?** Complementarios: distintas fotografías del mismo
  producto. No hay ningún caso de dos documentos técnicos incompatibles.
- **¿Duplicados binarios dentro del grupo?** Ninguno: no hay dos rutas con el mismo hash
  dentro de un mismo grupo.
- **¿Existe URL canónica?** Sí: los miembros de cada grupo comparten `legacy_url`.
- **¿Existe PHP canónico?** Sí: los miembros de cada grupo comparten `legacy_php`; el nombre
  del grupo canónico *es* ese PHP.

## Qué desbloquea

| | Filas |
|---|---:|
| Filas de producto en estos 26 grupos | 52 |
| Filas de medios que dependen de ellos | 72 |
| **Total** | **124** |

Resultado: **1 decisión de política** (ver [04-media-merge-policy.md](04-media-merge-policy.md)
y `Q02` del cuestionario) resuelve 124 filas y consolida 26 productos comerciales.

## Lo que este documento no hace

No fusiona nada, no elige ganador, no escribe en el manifest y no modifica ninguna decisión
existente. Es evidencia para que la decisión Q02 pueda tomarse con datos, no una aprobación.

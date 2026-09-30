# Fichas estáticas (`static_product`)

31 filas en REVIEW. Objetivo: evitar crear productos duplicados.

## Doble enumeración: confirmada y cuantificada

| Comprobación | Resultado |
|---|---:|
| Filas `static_product` en REVIEW | 31 |
| De ellas, presentes **también** en `content-master.csv` como `page` | **31 de 31** |
| Filas `PRODUCT_PAGE` sin `related_product_ids` | 32 |
| Intersección de esas 32 con `static-product-supplement.csv` | **32 (coincidencia exacta)** |

Es decir: **32 ficheros PHP generan 63 filas REVIEW** (31 `static:` + 32 `php:`; la fila
`php:` del artefacto ya migrado en el ensayo sigue en REVIEW). No son 63 problemas: son 32
artefactos contados dos veces bajo dos namespaces distintos.

Esto significa que Q01 (propiedad de páginas) y Q04 (entidad de las fichas estáticas) son
**dos caras de la misma decisión** para estos 32 ficheros, y deberían responderse juntas: si
la ficha estática se convierte en producto, su fila `php:` es la fuente de contenido de ese
producto y no debe crear además una Page.

## ¿Contenido adicional o sólo otra representación?

Las 31 filas son homogéneas:

| Campo | Valor | Filas |
|---|---|---:|
| `migration_status` | `SHOULD_MIGRATE` | 31 |
| `category_confidence` | `UNKNOWN` | 31 |
| `brand_confidence` | `UNKNOWN` | 31 |
| `images` | no vacío | 31 |
| `technical_pdf` | no vacío | 2 |

**Ninguna tiene `related_product_ids`.** No existe ninguna fila SQL que las reclame. Por lo
tanto:

- **No son otra representación de un producto SQL existente.** Si lo fueran, aparecerían
  como `PRODUCT_PAGE` *con* `related_product_ids`, como las otras 82.
- **Sí son contenido adicional real**: 31 fichas con cuerpo editorial e imágenes propias que
  el catálogo SQL nunca registró. Si se descartan, ese contenido desaparece del sitio nuevo.
- La doble enumeración es **entre sus dos filas** (`static:` y `php:`), no con un producto SQL.

Riesgo de duplicación, por tanto: **no es "duplicar un producto SQL"**, es "crear dos objetos
(un producto y una Page) para el mismo fichero". Eso se evita decidiendo Q01 y Q04 de forma
coherente.

## Lo que impide convertirlas automáticamente

Las 31 tienen `category_confidence = UNKNOWN` y `brand_confidence = UNKNOWN`. Crear un
producto sin categoría lo deja fuera de toda navegación, lo que contradice el modelo aprobado
(la categoría es la vía de acceso principal). Por eso la regla R-S01 se clasificó MEDIUM y no
se implementó.

**El bloqueo no es la identidad ni el contenido: es la asignación de categoría.**

## Vías posibles (no se elige ninguna aquí)

| Vía | Qué implica | Coste |
|---|---|---|
| Producto con categoría asignada a mano | 31 asignaciones editoriales; el contenido y las imágenes ya están listos | 31 decisiones pequeñas, una sesión |
| Producto sin categoría, revisar después | migra el contenido, pero queda invisible en navegación hasta clasificarlo | bajo ahora, deuda después |
| Page editorial en vez de producto | conserva el contenido y la URL, pero contradice el modelo si son productos reales | medio |
| Descartar | pierde 31 fichas con contenido propio | alto e irreversible en la práctica |

## Evidencia disponible para clasificarlas

Aunque el maestro no trae categoría, cada ficha tiene: nombre, URL legacy, cuerpo editorial
extraído, imágenes propias y su ubicación en la navegación estática. Ese contexto de
navegación es exactamente el tipo de evidencia que permitiría a una persona del negocio
asignar categoría rápidamente — pero **derivarla automáticamente sería inferencia semántica**,
que esta fase tiene prohibido convertir en decisión.

Recomendación analítica: `KEEP_REVIEW` hasta Q04, y responder Q04 **junto con** Q01.

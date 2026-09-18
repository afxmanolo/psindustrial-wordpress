# Fichas estáticas — análisis de los 31 REVIEW

Fuente: `static-product-supplement.csv` (32 filas; 1 aplicada en el subset).

## Hallazgo estructural: doble enumeración

**Los 32 ficheros PHP de `static-product-supplement.csv` aparecen también como filas `page`
en `content-master.csv`, clasificados `PRODUCT_PAGE` sin `related_product_ids`.**

Comprobación: de las 114 filas `PRODUCT_PAGE` en REVIEW, 32 no tienen `related_product_ids`,
y ese conjunto coincide exactamente con los 32 PHP del suplemento estático — intersección 32,
diferencia 0 en ambos sentidos.

Consecuencia: 31 artefactos legacy generan **63 filas REVIEW** (31 `static:` + 32 `php:`;
la fila `php:` del artefacto ya importado sigue en REVIEW). No son 63 problemas: son 31
artefactos contados dos veces bajo dos namespaces distintos.

Este es el patrón general de `R07_SOURCE_OWNERSHIP_UNDEFINED` y afecta a 167 filas en total.

## Estado de la evidencia

Las 31 filas son homogéneas:

| Campo | Valor | Filas |
|---|---|---:|
| `migration_status` | SHOULD_MIGRATE | 31 |
| `category_confidence` | UNKNOWN | 31 |
| `brand_confidence` | UNKNOWN | 31 |
| `images` | no vacío | 31 |
| `technical_pdf` | no vacío | 2 |

Todas tienen imágenes; sólo 2 tienen ficha técnica. **Ninguna tiene categoría ni marca.**

## Clasificación pedida

| Clase | Filas | Fundamento |
|---|---:|---|
| Producto real sin fila SQL | 31 (candidatas) | `SHOULD_MIGRATE` + cuerpo editorial + imágenes propias |
| Corresponde a un producto SQL existente | 0 demostrados | ninguna tiene `related_product_ids`; si lo tuviera, estaría en las otras 82 `PRODUCT_PAGE` |
| Landing page | 0 | las 9 landings están clasificadas aparte como `SEO_LANDING` |
| Variante de otra ficha | no determinable aquí | requiere D02/D07 |
| Duplicado | no determinable aquí | requiere D02 |
| Requiere decisión | 31 | categoría y marca desconocidas |

La distinción entre «producto real sin SQL» y «variante/duplicado de otro» **no puede
resolverse con los datos actuales**: el suplemento estático no incluye agrupación canónica
equivalente a `canonical-candidate-groups.csv`, y la única señal disponible sería similitud
de nombre o de texto, que esta fase no acepta como evidencia fuerte.

## Por qué no se proponen como MIGRATE

Crear un `psi_producto` desde una ficha estática exige decidir tres cosas que hoy no están en
los datos:

1. **Si la entidad existe** como producto independiente o es una variante de otra.
2. **Qué categoría** recibe: `category_confidence = UNKNOWN` en las 31.
3. **Qué marca** recibe: `brand_confidence = UNKNOWN` en las 31.

El importador podría crear el producto sin categoría ni marca, pero un producto sin ninguna
categoría no es navegable y contradice el modelo aprobado, donde la categoría es la vía de
acceso principal. Un producto huérfano publicado más tarde sería peor que una ficha estática
pendiente.

→ `KEEP_REVIEW` para las 31 (regla R-S01, riesgo MEDIUM, decisión D02).

## Qué las desbloquearía barato

Estas 31 son el mejor candidato a **reducción rápida mediante una sola sesión editorial**:
son pocas, tienen cuerpo y medios propios, no arrastran conflictos de duplicación conocidos
y su única carencia es la asignación de categoría.

Una lista de 31 filas con nombre, URL legacy y categorías candidatas del contexto de
navegación permitiría al negocio asignar categoría en una pasada. Eso convertiría 31
`KEEP_REVIEW` en 31 `MIGRATE` y arrastraría sus medios asociados.

No se elabora esa lista aquí porque implicaría proponer categorías por inferencia, y la
instrucción de esta fase es no hacerlo. Se deja registrado como la vía de menor coste.

## Nota para la fase de URLs

Las 32 rutas PHP tienen `preserve_url = YES` en `content-master.csv`. La decisión sobre la
entidad (producto nuevo, variante o descarte) es **independiente** de la conservación de la
URL. Ninguna propuesta de este documento libera una URL.

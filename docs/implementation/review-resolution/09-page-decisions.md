# Páginas en REVIEW — recomendación analítica

176 filas `page`. Recomendación por clase; **no se decide la URL final en esta fase**, sólo
qué objeto debería ser el dueño del contenido.

## Composición

| Clasificación | Filas | Recomendación analítica |
|---|---:|---|
| `PRODUCT_PAGE` con `related_product_ids` | 82 | `DUPLICATE_OF_OTHER_CONTENT` |
| `PRODUCT_PAGE` sin `related_product_ids` | 32 | `DUPLICATE_OF_OTHER_CONTENT` (de la ficha estática, ver [08](08-static-products.md)) |
| `CATEGORY_PAGE` | 37 | `REPRESENTED_BY_TAXONOMY` |
| `BRAND_PAGE` | 13 | `REPRESENTED_BY_TAXONOMY` |
| `SEO_LANDING` | 9 | `MIGRATE_AS_PAGE` (candidata) / `KEEP_REVIEW` |
| `UTILITY` | 3 | `KEEP_REVIEW` |
| **total** | **176** | |

## 1. Las 164 páginas que reexpresan otra entidad (Q01)

114 `PRODUCT_PAGE` + 37 `CATEGORY_PAGE` + 13 `BRAND_PAGE`. Ninguna aporta una entidad nueva:

- las 82 `PRODUCT_PAGE` con `related_product_ids` son la ficha pública de un producto SQL que
  ya está enumerado como `sql:productos:*`;
- las 32 restantes son exactamente los 32 ficheros de `static-product-supplement.csv`
  (intersección verificada = 32, diferencia 0 en ambos sentidos);
- las 37 `CATEGORY_PAGE` y 13 `BRAND_PAGE` corresponden a términos que **ya se crearon**
  en esta rama (33 categorías y 11 marcas aprobadas por R-T01/R-T03).

La arquitectura aprobada ya respondió el principio: *no se crea una Page adicional por cada
ficha o archivo*. Lo que falta es autorizar su aplicación, que quedó clasificada MEDIUM
porque **depende de que exista la fase de URLs/SEO**: marcar estas 164 como "no crear Page"
es correcto como decisión de contenido y destructivo como decisión de URL si esa fase nunca
llega.

### El efecto oculto: 250 medios

Estas mismas páginas son el único propietario documentado de **250 archivos de medios**
(imágenes bajo `images/` y PDFs bajo `fichas/` referenciados literalmente desde el HTML).
La decisión Q01 determina también qué ocurre con ellos:

| Páginas propietarias del medio | Medios |
|---|---:|
| sólo `PRODUCT_PAGE` | 148 |
| mezcla marca+categoría+producto | 28 |
| `CATEGORY_PAGE` + `PRODUCT_PAGE` | 26 |
| sólo `CATEGORY_PAGE` | 19 |
| sólo `BRAND_PAGE` | 13 |
| `BRAND_PAGE` + `CATEGORY_PAGE` | 7 |
| sólo `LEGACY_INTERNAL` | 6 |
| `BRAND_PAGE` + `PRODUCT_PAGE` | 3 |

Si el contenido de una `PRODUCT_PAGE` pasa a ser propiedad de su producto, sus imágenes son
candidatas naturales a galería de ese producto. Si la página simplemente se descarta sin
reasignar sus medios, esas 250 filas se quedan sin dueño y vuelven a la casilla de salida.
**Q01 debe decidirse junto con el destino de sus medios, no por separado.**

Nota aparte: los 6 medios referenciados **sólo** desde código `LEGACY_INTERNAL` son el único
subconjunto que admitiría una regla estructural determinista, del mismo tipo que la R-M03 ya
aprobada para `system/backoffice/`.

## 2. Las 9 landings SEO (Q07)

Clasificación `SEO_LANDING`, `preserve_url = YES`, confianza `STRONG_INFERENCE`. Son el único
grupo de páginas donde el contenido **no tiene otro dueño posible**: no son la ficha de un
producto ni el archivo de un término, sino páginas de gama o compuestas.

La regla R-G02 que aprobó las 5 páginas institucionales/contacto excluyó deliberadamente
estas 9 por dos motivos que siguen vigentes: la finalidad SEO es inferencia, y algunas
solapan contenido de producto. Arrastran además 17 medios.

Recomendación analítica: `MIGRATE_AS_PAGE` como borrador, **condicionada** a confirmar que
cada una aporta contenido propio y no duplica la descripción de un producto ya migrado.

## 3. Las 3 utilitarias (Q12)

Páginas de función, no de contenido. Su destino depende de si la función se reimplementa en
WordPress, se descarta o se conserva (decisión D12 del catálogo original). Impacto mínimo:
3 filas, sin medios dependientes.

Recomendación analítica: `KEEP_REVIEW`.

## Qué no se determina aquí

- La URL final de ninguna página. `preserve_url = YES` sigue vigente para las 176.
- Ningún redirect.
- Si una landing debe publicarse. Toda página migrada seguiría entrando como borrador.

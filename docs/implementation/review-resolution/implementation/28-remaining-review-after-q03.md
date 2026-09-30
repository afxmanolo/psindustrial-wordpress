# Las 339 REVIEW restantes, después de Q01+Q02+Q03+Q04+Q05+Q06+Q07+Q10+Q11+Q13

Reconstruido contra el plan vivo, no contra conteos históricos. Composición exacta por tipo:

```
media           309
product          13
page             15
missing_media     2
```

## product (13)

| Causa | IDs | Filas | Detalle |
|---|---|---|---|
| Q03 residual — 3 grupos GLOBAL sin contenido extraíble | 29,116,130,31,119,131,32,120,132 | 9 | Ver [22-q03-multi-category.md](22-q03-multi-category.md) — identidad correcta, `NO_STATIC_EDITORIAL_BODY` |
| Q03-E — Kelley, excluido a propósito | 3 | 1 | `Policy::CATEGORY_CONFLICT_PRODUCTS`, confirmado intacto |
| Q02 edge case, ajeno a Q03 | 50,59 | 2 | `puertas-industriales-dockman.php`: grupo Q02 aprobado cuyo ganador no llegó a materializarse esta corrida; ya documentado como fuera de alcance en la fase de análisis previa, no investigado en esta implementación |
| Q09 — fabricante id 150 | 150 | 1 | Sin ninguna decisión; único id de `BRAND_CONFLICT_PRODUCTS` sin identidad Q03 asociada |

## page (15)

| Causa | Archivos | Filas |
|---|---|---|
| Q12 — utilitarias | categorias.php, enviaContacto.php, productos.php | 3 |
| Q03 residual — páginas de los 3 grupos bloqueados | puerta-estandar.php, puerta-estandar-reforzada.php, puertas-contra-incendio.php | 3 |
| Q02 edge case | puertas-industriales-dockman.php | 1 |
| Sin relación con Q03 — un solo id, sin decisión propia | puertas-seccionales-de-aluminio-521.php | 1 |
| Q09 | sellos-nacionales.php | 1 |
| CATEGORY_PAGE/BRAND_PAGE sin propietario único (Q01 parte b, sin cambios) | bft-resp.php, contra-incendio.php, marcas.php, marcasv1.php, puertas-peatonales-estandar-y-reforzada.php, soluciones.php | 6 |

Ninguna de estas 15 páginas fue tocada por el trabajo de esta fase salvo las 3 "residuales" de
arriba (que ya estaban aprobadas por Q03-GLOBAL pero no pudieron materializarse por razones
ajenas a la lógica de propiedad de páginas).

## missing_media (2)

`missing:images/dura.jpg` y `missing:images/magic.jpg` — los 2 casos de Q11 genuinamente sin
archivo físico. Sin cambios: nunca se fabricó ningún reemplazo.

## media (309)

**25** están directamente ligadas a los 3 grupos Q03-GLOBAL bloqueados (mismas imágenes/PDF
que esos productos referencian, verificado por cruce directo con
`product-master.csv`) — se desbloquearían automáticamente si algún día se resuelve el
problema de contenido estático de esas 3 páginas, sin necesitar ninguna decisión de medios
nueva.

Las **284** restantes no se investigaron en esta fase — pertenecen, en su mayoría, al
universo ya identificado como Q08 (medios sin referencia demostrable, 89 según el análisis
original) más otros medios cuya única vía de aprobación depende de un propietario (producto,
página o marca) que sigue sin decisión por razones ajenas a Q03: id 150 (Q09), las 6 páginas
CATEGORY_PAGE/BRAND_PAGE de arriba, y el remanente general de medios "válidos pero sin
aprobación de propósito" que ya documentaba el FULL DRY RUN original. Esta fase no reabre esa
clasificación — explícitamente fuera de alcance ("NO implementes Q08").

## Confirmación explícita: Q08, Q09 y Q12 intactos

- **Q08**: ninguna de las filas de medio sin referencia recibió una decisión `decision_id=Q03`
  ni de ningún otro tipo en esta fase. La distinción "ausencia de referencia local != permiso
  para SKIP" se mantiene sin excepción.
- **Q09**: `Policy::BRAND_CONFLICT_PRODUCTS` conserva exactamente sus 14 ids originales, sin
  ninguno añadido ni quitado. 13 de ellos ahora tienen identidad Q03 resuelta **sin marca**;
  el id 150 (el único caso explícitamente nombrado "Q09" en el cuestionario original) sigue
  completamente sin ninguna decisión. Ningún fabricante se asignó en ningún caso dudoso.
- **Q12**: las 3 páginas utilitarias siguen sin ninguna decisión, sin investigar su
  comportamiento ni su equivalente WordPress — exactamente como se pidió.

## Camino recomendado (sin cambios de fondo respecto a la fase anterior)

Lo que queda es, en su enorme mayoría, exactamente lo que se pretendía dejar así: Q08 (~89
filas concretas, dentro de las 284 sin investigar), Q09 (id 150, trabajo editorial de
catálogo) y Q12 (3 filas). El residual nuevo de esta fase — 12 filas de producto/página de los
3 grupos Q03-GLOBAL bloqueados — no es una decisión pendiente de criterio humano: es una
limitación real y verificada de extracción de contenido sobre 3 páginas legacy concretas,
documentada en detalle en [22-q03-multi-category.md](22-q03-multi-category.md) para que
cualquier futura fase que quiera abordarla sepa exactamente por dónde empezar.

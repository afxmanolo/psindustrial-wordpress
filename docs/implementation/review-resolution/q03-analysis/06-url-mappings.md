# URLs legacy de los 27 grupos — sólo identidad, sin redirects

**30 URLs legacy** están en juego: 27 URLs primarias (una por `canonical_candidate_group`,
compartida literalmente por todos los miembros SQL del grupo — todas las filas de un grupo
apuntan a la misma página, por construcción) más **3 alias documentados** en
`canonical-candidate-groups.csv` (`aliases` no vacío): `q-silent-max-eagle-3-4-hp.php` (alias
de accesspro-fs1000speed), `puertas-seccionales-de-acero-aisladas-thermospan.php` (alias de
thermospan-modelo-150) y `liftmaster-modh.php` (alias de lift-master-mod-h). Ningún grupo
tiene más de una URL primaria — importante porque significa que **consolidar los miembros SQL
de un grupo nunca reduce el número de URLs legacy a preservar**: todas las filas de un mismo
grupo ya comparten una sola URL desde el legacy.

## Qué significa "mapping" aquí

Documentar, no implementar: qué URL legacy correspondería a qué futuro producto WordPress
canónico, **si** se aprueba la consolidación. Esto es sólo trazabilidad — ni siquiera crea el
mapeo real en `Identity.php`, sólo dice qué mapeo *tendría sentido* para que quien apruebe
Q03-GLOBAL sepa exactamente qué URLs quedan cubiertas.

```
27 URLs primarias (una por grupo, ya compartida por 2-4 filas SQL cada una)
+ 3 URLs alias (documentadas en canonical-candidate-groups.csv, ya conocidas antes de esta tarea)
= 30 URLs legacy → 27 futuros productos canónicos (1:1 grupo:producto, si se aprueban los 27)
```

Tabla completa (URL primaria + alias, agrupada por clasificación) en
[01-q03-groups.csv](01-q03-groups.csv), columnas `legacy_url` y `alias_urls`.

## Ningún grupo pierde una URL por consolidarse

A diferencia de Q01 (donde consolidar una página de producto dentro de la ficha SÍ elimina una
URL de página independiente, compensada por preservar la URL como alias del producto), aquí
consolidar los 2-4 registros SQL de un grupo en un único producto WordPress **no elimina
ninguna URL**: las 2-4 filas SQL de un mismo grupo nunca tuvieron URLs propias distintas entre
sí — todas apuntaban, ya en el legacy, a la misma página física. La única URL que "represents"
el grupo entero es la que ya está en `legacy_url`; no hay 2-4 URLs por grupo que reducir a una.
Esto simplifica la futura fase de redirects: para estos 27 grupos, no hará falta decidir cuál
de varias URLs "gana" — sólo confirmar que la única URL que ya existía sigue funcionando.

## No se implementa nada de esto

No se creó ninguna entrada en el sistema de mapeo de URLs existente (usado por Q07/Q01 para
las landings y páginas de producto/categoría/marca). No se decidió ningún 301, canonical ni
regla de `.htaccess`. Esta tabla es únicamente el inventario que la futura fase SEO/compatibilidad
de URLs necesitará como punto de partida — igual que Q07 dejó documentadas, sin implementar,
las 9 URLs de landing.

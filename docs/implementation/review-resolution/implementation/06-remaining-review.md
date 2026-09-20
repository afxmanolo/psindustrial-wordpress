# Las 771 REVIEW restantes

Ninguna decisión fuera de Q02/Q05/Q06 se tocó en esta fase. Lo que sigue en REVIEW es
exactamente lo que [13-human-decisions-map.md](../13-human-decisions-map.md) ya había
identificado como pendiente de **otras** preguntas (Q01, Q03, Q04, Q07–Q13) o de evidencia
externa (Q08, Q09, Q11) — reconstruido aquí contra el plan vivo actual, no reafirmado de
memoria.

## Composición por causa bloqueante

| Causa | Filas aprox. | Por qué sigue abierta |
|---|---:|---|
| Q01 — propiedad de contenido de páginas | ~414 | No implementada en esta fase; sigue intacta. Es, con diferencia, la de mayor rendimiento pendiente. |
| Q03 — fusión editorial (27 grupos) | 164 | Explícitamente fuera de alcance; requiere conocimiento de catálogo comercial caso por caso |
| Q08 — medios sin referencia | 89 | Requiere evidencia de producción (Search Console/logs), no está en el repositorio |
| Q04 — categoría de fichas estáticas | 31 | No implementada en esta fase |
| Q05 residual — 27 de 41 filas | 27 | Seguridad resuelta; falta decisión de propietario (Q01 o marca/categoría) — ver [02-q05-pdf-resolution.md](02-q05-pdf-resolution.md) |
| Q10 — categorías 25/26/33 | 3–4 | Decisión editorial pequeña, no respondida |
| Q11 — recursos ausentes | 4 | 3 requieren recuperar el original |
| Q12 — utilitarias | 3 | Depende de si la función se reimplementa |
| Q09 — fabricante ID 150 | 1 | Requiere catálogo de fabricante |
| Q13 — alias binario | 1 | Residuo técnico del ensayo |
| Q02 residual — 1 grupo | 2 filas (productos 50/59) | Mismatch MIME/extensión preexistente en `images/dockmanint.png`, ajeno a Q02 |

La suma no cierra exactamente 771 porque varias filas están bloqueadas por más de una
causa a la vez (el mismo fenómeno que Q05 exhibe con la propiedad) — el total real por
fila, no por causa, es 771 y está en el propio plan (`entity_key` → `notes`/
`media_validation` de cada entrada REVIEW).

## Lo único nuevo que aparece aquí y no estaba en el mapa de fase 8

El grupo `puertas-industriales-dockman.php` (Q02) y las 27 filas residuales de Q05 no
existían como categoría antes de esta implementación — son la manifestación concreta de
"una decisión puede quedar resuelta aunque la fila siga en REVIEW por otra causa",
exactamente como el usuario anticipó. Documentados con su archivo/producto exacto en
[01-q02-duplicate-resolution.md](01-q02-duplicate-resolution.md) y
[02-q05-pdf-resolution.md](02-q05-pdf-resolution.md) respectivamente.

## Camino recomendado (sin cambios respecto a la fase anterior)

[14-resolution-priority.md](../14-resolution-priority.md) ya proponía Q01+Q04 como el
siguiente bloque de mayor rendimiento (445 filas), seguido de las decisiones pequeñas
(Q07/Q10/Q11/Q12/Q13, 38 filas) y por último Q03 (164 filas, trabajo editorial
irreducible). Esta fase no cambia esa recomendación; sólo ejecutó el primer bloque que ya
estaba identificado como "sin dependencias, riesgo bajo, evidencia cerrada" (Q02+Q05+Q06).

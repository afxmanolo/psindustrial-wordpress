# Las 538 REVIEW restantes, después de Q01 + Q02 + Q04 + Q05 + Q06

Ninguna decisión fuera de Q01/Q02/Q04/Q05/Q06 se tocó en esta fase. Lo que sigue en REVIEW
es exactamente lo que ya se había identificado como pendiente de otras preguntas —
reconstruido aquí contra el plan vivo actual.

## Composición por causa bloqueante

| Causa | Filas aprox. | Por qué sigue abierta |
|---|---:|---|
| Q03 — fusión editorial (27 grupos) | 164 | Explícitamente fuera de alcance; ninguna página ni producto de estos grupos recibió decisión Q01/Q02 |
| Q08 — medios sin referencia | 89 | Requiere evidencia de producción, no está en el repositorio |
| Q01 residual — páginas de propietario ambiguo | 36 | 1 categoría compartida por 2 términos, 4 sin propietario documentado, 31 páginas de producto de grupos Q03 |
| Q05 residual — seguridad resuelta, propiedad pendiente | 17 | Sus productos propietarios son el registro de conflicto D03/D06 u otros no cubiertos por R-P01 |
| Q10 — categorías 25/26/33 | 3–4 | Decisión editorial pequeña, no respondida |
| Q11 — recursos ausentes | 4 | 3 requieren recuperar el original; 2 de ellos son las mismas imágenes que bloquean 2 fichas de Q04 |
| Q12 — utilitarias | 3 | Depende de si la función se reimplementa |
| Q09 — fabricante ID 150 | 1 | Requiere catálogo de fabricante |
| Q13 — alias binario | 1 | Residuo técnico del ensayo |
| Q02 residual — 1 grupo | 2 filas | Mismatch MIME/extensión preexistente, ajeno a Q02 |
| Q07 — landings SEO | 26 | No implementada en esta fase |

La suma no cierra exactamente 538 porque varias filas están bloqueadas por más de una
causa a la vez (el mismo patrón que Q05 ya exhibía) — el total real por fila, no por
causa, es 538 y está en el propio plan.

## Lo nuevo que aparece aquí y no existía antes de esta fase

Las **36 páginas Q01-ambiguas** y las **11 filas de Q05 adicionalmente resueltas por
propiedad** son la manifestación concreta de "una decisión puede quedar resuelta aunque la
fila siga en REVIEW por otra causa" — documentadas con su página/archivo exacto en
[07-q01-content-ownership.md](07-q01-content-ownership.md) y
[10-dry-run-after-q01-q04.md](10-dry-run-after-q01-q04.md).

## Camino recomendado (sin cambios de fondo respecto a la fase anterior)

Con Q01, Q02, Q04, Q05 y Q06 respondidas, el trabajo que queda es mayoritariamente **Q03**
(164 filas, el único bloque que exige conocimiento del catálogo comercial producto por
producto) y **Q08** (89 filas, evidencia de producción que nadie del equipo puede
fabricar). El resto (Q07/Q09/Q10/Q11/Q12/Q13) son 39 filas de decisiones pequeñas y
explícitas, ninguna con dependencias fuertes.

Como ya señalaba [14-resolution-priority.md](../14-resolution-priority.md): responder Q03
empezando por la política de categorías múltiples (14 de los 27 grupos divergen
exclusivamente por categoría) resolvería la mayor parte de ese bloque en lote, dejando un
REVIEW final compuesto casi enteramente por Q08 — trabajo que depende de datos de
producción, no de más análisis del repositorio.

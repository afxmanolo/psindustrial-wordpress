# Las 510 REVIEW restantes, después de Q01+Q02+Q04+Q05+Q06+Q07+Q10+Q11+Q13

Ninguna decisión fuera de estas nueve se tocó en esta fase ni en la anterior. Lo que sigue
en REVIEW es, sin excepción, lo que ya se había identificado como perteneciente a **Q03,
Q08, Q09 o Q12** — los cuatro bloques que esta fase tenía explícitamente prohibido resolver
— reconstruido aquí contra el plan vivo, no contra conteos históricos.

## Composición por causa bloqueante

| Causa | Filas aprox. | Por qué sigue abierta |
|---|---:|---|
| Q03 — fusión editorial (27 grupos) | 164 | Explícitamente fuera de alcance; ningún grupo tocado |
| Q08 — medios sin referencia | 89 | Requiere evidencia de producción, no está en el repositorio |
| Q05 residual (propiedad pendiente, seguridad ya resuelta) | 17 | Sus propietarios son productos de conflicto D03/D06 u otros no cubiertos por R-P01 |
| Q01 residual — páginas de propietario ambiguo | 36 | Grupos Q03, páginas sin owner documentado, 1 categoría compartida (sin cambio respecto a la fase anterior) |
| Q12 — utilitarias | 3 | No implementada: depende de si la función se reimplementa |
| Q09 — fabricante ID 150 | 1 | Requiere catálogo de fabricante |
| Q02 residual — 1 grupo | 2 filas | Mismatch MIME/extensión preexistente, ajeno a Q02 |
| Q11 — 2 archivos genuinamente ausentes | 2 | `dura.jpg`, `magic.jpg`: sin archivo real, registrados como `MISSING_SOURCE_FILE`, correctamente sin resolver más allá de esa clasificación |

La suma no cierra exactamente 510 porque varias filas están bloqueadas por más de una
causa a la vez — el total real por fila, no por causa, es 510 y está en el propio plan.

## Confirmación explícita: los cuatro bloques intactos

- **Q03 (164 filas)**: verificado por test — ningún miembro de un grupo editorial recibió
  `decision_id` de esta fase. El patrón "14 de 27 grupos divergen sólo por categoría"
  documentado en fases anteriores sigue exactamente igual.
- **Q08 (89 filas)**: ningún medio sin referencia recibió decisión. La distinción
  `ausencia de referencia local != permiso para SKIP` se mantiene sin excepción — ninguna
  de las nueve decisiones de esta fase opera sobre medios sin evidencia de propietario.
- **Q09 (1 fila + su influencia sobre otros 13 conflictos de marca)**: Dockman, Solmmer,
  Modern Steel, LiftMaster/Blue Giant, MOOVI y Thermospan permanecen exactamente como
  estaban. El hallazgo de Q11 sobre `sellos-nacionales.php` (caso SELLOSSOLMMER) resuelve
  únicamente la referencia al PDF, nunca el fabricante del producto 150.
- **Q12 (3 filas)**: sin decisión. No se investigó su comportamiento ni su equivalente
  WordPress — exactamente como se pidió.

## Lo que esta fase sí cambió en el terreno de Q05/Q01 (efecto colateral documentado)

17 de las 41 filas originalmente atribuidas a Q05 siguen `REVIEW` por falta de propietario
(sin cambio respecto a la fase anterior: Q07/Q10/Q11/Q13 no tocan ningún propietario de
producto). Las 36 páginas Q01-ambiguas tampoco cambiaron: ningún grupo Q03 se resolvió.

## Camino recomendado (sin cambios de fondo)

Con Q01, Q02, Q04, Q05, Q06, Q07, Q10, Q11 y Q13 respondidas, **el trabajo que queda es,
casi en su totalidad, exactamente lo que se pretendía dejar así**: Q03 (164 filas, trabajo
editorial de catálogo que ninguna herramienta puede automatizar) y Q08 (89 filas, evidencia
de producción que nadie del equipo puede fabricar). El resto — Q09 (1), Q12 (3) y los 2
casos de Q11 genuinamente sin archivo — son 6 filas residuales, ya completamente
documentadas, sin ambigüedad sobre por qué siguen abiertas.

No queda ninguna decisión de bajo riesgo, sin dependencias, pendiente de responder: las
que existían (Q02, Q05, Q06, Q07, Q10, Q11, Q13) ya se implementaron; Q01 y Q04 (impacto
medio, ya resueltas) también. Lo que resta requiere, en cada caso, exactamente el tipo de
criterio humano que este proyecto ha evitado deliberadamente sustituir por inferencia.

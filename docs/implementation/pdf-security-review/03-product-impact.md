# Impacto en productos

Los 7 PDF bloquean, por dependencia (mecanismo existente de `Planner`, sin modificar), a
**11 productos**. Dos PDF se comparten entre dos productos cada uno (mismo objeto legacy
`file`, dos alias de nombre distintos — el patrón ya documentado en
[08-duplicate-merge-analysis.md](../review-analysis/08-duplicate-merge-analysis.md) de la
fase analítica); el resto tiene un producto cada uno.

| Producto | Nombre | PDF (ruta corta) | Grupo | source_key |
|---:|---|---|---|---|
| 65 | Puerta seccional de acero sin aislamiento 424 (uso pesado) | `639b9801...` | A (EmbeddedFile real) | `sql:productos:65` |
| 66 | Puerta seccional de acero sin aislamiento 430 (uso medio) | `639b9801...` | A | `sql:productos:66` |
| 67 | Puertas seccionales de aluminio 521 | `7cf4babf...` | A | `sql:productos:67` |
| 68 | Puerta seccional de acero perfiles 418 | `7ab52eec...` | A | `sql:productos:68` |
| 69 | Puerta seccionable acero perfiles 422 (uso pesado) | `7ab52eec...` | A | `sql:productos:69` |
| 70 | Puertas seccionales de acero perfiles 426 | `3af12bf1...` | A | `sql:productos:70` |
| 71 | Puerta seccional de acero perfiles 432 (uso medio) | `3af12bf1...` | A | `sql:productos:71` |
| 72 | Puerta seccional de acero Thermacore 593 (uso medio) | `7baea774...` | A | `sql:productos:72` |
| 73 | Puerta seccional de acero Thermacore 594 (uso medio) | `7baea774...` | A | `sql:productos:73` |
| 77 | Operador de puerta comercial RHX® | `3e620ed8...` | B (falso positivo) | `sql:productos:77` |
| 95 | Cortina serie 610 | `98b07de6...` | B (falso positivo) | `sql:productos:95` |

## Por qué la reversión fue correcta, no un fallo

Cada uno de estos 11 productos había sido propuesto `MIGRATE` por la regla `R-P01`
(evidencia de identidad: grupo canónico unitario, PHP unívoco, categoría propia aprobada,
sin conflicto D03/D06 — ver
[01-implemented-rules.md](../review-rules/01-implemented-rules.md) de la fase LOW). La
identidad y la categoría del producto **nunca estuvieron en duda**. El bloqueo es
exclusivamente por su ficha técnica (PDF), vía el mecanismo de dependencias existente de
`Planner`: un producto no puede migrar completo si uno de sus medios asociados no supera la
validación de bytes/seguridad.

Esto significa que **si se resuelve la clasificación del PDF, el producto no necesita ningún
trabajo adicional**: su nombre, contenido, categoría y (cuando aplica) marca ya están
aprobados por R-P01; sólo falta que la ficha técnica deje de bloquear la dependencia.

## Ningún producto se desbloqueó en esta fase

Esta auditoría es analítica. No se cambió ninguna decisión del importador, no se tocó
`Policy.php`/`Planner`/`Runner`, y los 11 productos **siguen en REVIEW** exactamente como al
cierre de la fase LOW. La tabla de clasificación propuesta en
[05-recommendations.md](05-recommendations.md) es una recomendación para una decisión
humana posterior, no una aprobación.

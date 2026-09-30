# Reglas LOW — resumen ejecutivo

> **Actualización 2026-09-18 (misma fecha, fase posterior):** las decisiones humanas PDF-A
> y PDF-B sobre los 7 PDF descritos en "Dos hallazgos" más abajo ya se implementaron. Ver
> [`../pdf-security-review/07-implementation-result.md`](../pdf-security-review/07-implementation-result.md).
> Los 950 REVIEW de este documento bajaron a **932** tras esa implementación. Este documento
> se conserva sin reescribir como registro histórico del estado al cierre de la fase LOW.

Fecha: 2026-09-18. Rama `feature/review-rules`. Fase de **implementación acotada**: sólo las
reglas clasificadas riesgo LOW en la fase analítica anterior
([09-proposed-rules.md](../review-analysis/09-proposed-rules.md),
[12-risk-analysis.md](../review-analysis/12-risk-analysis.md)). Ninguna regla MEDIUM o HIGH.
Ninguna decisión humana respondida. Ningún MERGE. Termina, de nuevo, en FULL DRY RUN.

## Qué se construyó

Una clase nueva y separada, `migration/Policy.php` (~250 líneas), que propone decisiones
para 9 reglas LOW a partir de los mismos CSV maestros que `Planner` ya carga. `Planner.php`
cambia en tres líneas puntuales para consultarla **sólo cuando `scope=full`** — el scope
`subset` (el ensayo de 17 objetos) queda byte-idéntico a como estaba. `Runner`, `Storage` e
`Identity` no se tocaron.

## Resultado medido (no simulado)

```
REVIEW antes:      1.949
REVIEW después:      950
Resueltas:            999   (846 SKIP + 153 CREATE, en DRY RUN)
```

Detalle completo, con verificación aritmética, en
[04-full-dry-run-after-low-rules.md](04-full-dry-run-after-low-rules.md). Fila por fila, con
regla y evidencia, en [low-rule-decisions.csv](low-rule-decisions.csv).

## Dos hallazgos, no sólo una implementación

1. **Un bug propio, encontrado y corregido antes de que nada se ejecutara.** La primera
   ejecución del FULL DRY RUN con la política activa fue detenida por la protección
   `SOURCE_KEY_HAS_TWO_OWNERS` **ya existente** de `Planner` — funcionó exactamente como
   debía. Causa: `Policy` no conocía un `binary_aliases` declarado manualmente. Corregido
   pasándole las decisiones manuales; test de regresión específico añadido.
2. **Un hallazgo real sobre los datos, no sobre el código.** 7 PDF de ficha técnica
   contienen una referencia `/EmbeddedFile` y son rechazados por la comprobación de seguridad
   existente, bloqueando 11 productos que de otro modo cumplían todas las condiciones LOW.
   No se tocó la comprobación. Detalle en [06-known-issues.md](06-known-issues.md).

## Respuestas directas

**1. ¿Por qué existían los 1.949 REVIEW?** Sin cambios respecto al análisis previo: ausencia
de decisión registrada, no defectos detectados uno a uno
([00 de la fase anterior](../review-analysis/00-review-analysis-summary.md)).

**2. ¿Cuántas reglas LOW se implementaron?** 9: R-T01, R-T03, R-P01, R-G02-LOW, R-M01, R-M02,
R-M03, R-M04, R-M05. Ninguna MEDIUM ni HIGH.

**3. ¿Cuántas filas se resolvieron?** 999 de 1.949 (51,3 %): 846 SKIP (medios derivados e
infraestructura) + 153 CREATE (94 medios + 33 categorías + 11 marcas + 10 productos + 5
páginas), todo en DRY RUN.

**4. ¿Cuántas se esperaban y no se resolvieron?** 18 (11 productos + 7 medios), por el
hallazgo de PDF con `/EmbeddedFile` — causa única, documentada, no un error de las reglas.
Ver [03-low-rule-simulation.md](03-low-rule-simulation.md).

**5. ¿Cuántas siguen en REVIEW?** 950. Desglose completo por causa en
[05-remaining-review.md](05-remaining-review.md): 130 son decisiones HIGH irreducibles; el
resto son MEDIUM (propietario aún no aprobado, páginas que reexpresan otra entidad, fichas
estáticas, vacíos/prueba, evidencia de tráfico) que esta fase, por diseño, no toca.

**6. ¿Se respondió alguna decisión humana?** No. Ninguna de las 9 preguntas de
[10-human-decisions.md](../review-analysis/10-human-decisions.md) se contestó.

**7. ¿Sigue intacta la filosofía conservadora del importador?** Sí. Ausencia de decisión
sigue significando REVIEW. `Policy` es una capa separada y auditable — nunca hardcodea una
excepción por ID salvo la transcripción literal de los registros D03/D06 ya documentados
(mismo patrón que el propio `Planner` ya usaba para 25/26/33). Ninguna regla asigna marca sin
`CONFIRMED`, ninguna asigna categoría no aprobada por sí misma, ninguna crea término con
productos ya atribuidos.

**8. ¿Siguen intactas las salvaguardas de ejecución?** Sí, verificado explícitamente: un plan
`full` con política activa sigue sin poder ejecutarse (`Runner::batch()` lo rechaza); el
scope `subset` es byte-idéntico (17 entradas, 2 REVIEW); `MERGE=0`; no se tocó
`Storage::guard()`, el límite de 25 objetos, la frase de confirmación, el hash de
integridad del plan ni la detección de edición humana.

**9. ¿Qué pruebas existen?** 3 suites: `smoke.php` (preexistente, sin cambios, pasa),
`importer.php` (preexistente, 67 comprobaciones, pasa idéntico), `policy.php` (nueva, 6.314
comprobaciones: positivo/negativo/límite/debe-permanecer-REVIEW por cada regla, más guardas
explícitas contra los errores pedidos — eliminar originales, clasificar asset público como
backoffice, asignar fabricante/categoría inciertos, duplicar attachments, importar
`static_product` duplicado). Detalle en [02-rule-test-results.md](02-rule-test-results.md).

**10. ¿Cuál es el siguiente paso?** Dos, independientes entre sí:
- **Editorial/dato:** decidir qué hacer con los 7 PDF `/EmbeddedFile` (inspección humana del
  contenido; no es una decisión técnica que este código deba tomar).
- **Proceso:** si se aprueba el resultado, la vía natural para seguir reduciendo REVIEW sin
  inventar información es responder las decisiones de política de bajo esfuerzo de
  [10-human-decisions.md](../review-analysis/10-human-decisions.md) (H1/H3/H4), no escribir
  más reglas automáticas. Ninguna se ejecuta aquí.

## Entregables

| Documento | Contenido |
|---|---|
| [01-implemented-rules.md](01-implemented-rules.md) | Las 9 reglas, el punto de integración exacto con `Planner`, ambos hallazgos |
| [02-rule-test-results.md](02-rule-test-results.md) | Resultado de las 3 suites, cobertura por regla, guardas explícitas |
| [03-low-rule-simulation.md](03-low-rule-simulation.md) | Predicción previa vs resultado real, diferencia explicada |
| [04-full-dry-run-after-low-rules.md](04-full-dry-run-after-low-rules.md) | ANTES/DESPUÉS completo, por `source_type` y por `rule_id` |
| [05-remaining-review.md](05-remaining-review.md) | Los 950 restantes, por causa |
| [06-known-issues.md](06-known-issues.md) | Los 7 PDF, el bug de alias, límites conocidos |
| [low-rule-decisions.csv](low-rule-decisions.csv) | 1.025 filas: regla, evidencia, acción anterior/nueva, resultado |

## Verificación final

Ver [07-verification.md](07-verification.md) para la salida literal de `git status`,
`git diff --check` y la confirmación de que `/legacy`, `/wordpress` (fuera de los tres
archivos de `psindustrial-core` listados) y la base de datos permanecen sin publicación ni
ejecución real.

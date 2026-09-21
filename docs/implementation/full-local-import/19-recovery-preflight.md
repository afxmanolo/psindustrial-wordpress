# Recovery pre-flight — ejecución real, sin mutación

Continúa de [18-recovery-retry-design.md](18-recovery-retry-design.md). **No se ejecutó
ningún reintento real.** Ambas llamadas de esta página (`build_recovery_retry_plan()` y
`recovery_preflight()`) se ejecutaron de verdad, en vivo, contra el estado local actual —
confirmado sin mutación de `wp_posts`/`wp_terms` antes/después de cada una.

## Construcción real del plan de recuperación

```php
Runner::build_recovery_retry_plan( '2e0c1248-8d56-4d92-a33b-c17e37b2732e' );
```

```
recovery_run_id: 9c492018-cc99-4548-ac37-aed5d73688c1
parent_run_id:   2e0c1248-8d56-4d92-a33b-c17e37b2732e
recovery_reason: ORIGINAL_RUN_SNAPSHOT_PRUNED

candidates_considered: 52
retryable:             48
rejected:               4
```

Coincide exactamente con [13-pdf-runtime-failures.md](13-pdf-runtime-failures.md) — no por
reutilizar ese documento como fuente (el código nunca lo lee), sino porque ambos parten,
independientemente, de la misma evidencia real (el log superviviente + el estado actual).
Confirmado sin mutación de WordPress; el plan y su evidencia se escribieron en
`run-9c492018-....json` / `recovery-evidence-9c492018-....json`, privados, fuera de Git.

## Pre-flight real

```php
Runner::recovery_preflight( '9c492018-cc99-4548-ac37-aed5d73688c1' );
```

```json
{
    "run_id": "9c492018-cc99-4548-ac37-aed5d73688c1",
    "parent_run_id": "2e0c1248-8d56-4d92-a33b-c17e37b2732e",
    "ok": false,
    "checks": [
        { "id": "recovery_plan_exists", "passed": true },
        { "id": "is_recovery_scope", "passed": true, "detail": "scope=recovery" },
        { "id": "parent_run_id_present", "passed": true },
        { "id": "environment_id_matches", "passed": true },
        { "id": "recovery_plan_seal_intact", "passed": true },
        { "id": "parent_run_evidence_available", "passed": true },
        { "id": "current_plan_still_equivalent", "passed": true },
        { "id": "applied_449_control_intact", "passed": true },
        { "id": "no_review_or_skip_entries_in_recovery_set", "passed": true },
        { "id": "new_post_first_import_backup_present", "passed": false,
          "detail": "A NEW backup taken AFTER the 449 already-applied objects is required before real execution; none recorded on this recovery plan yet." }
    ],
    "blockers": ["new_post_first_import_backup_present"],
    "counts": { "retryable": 48, "rejected": 4, "candidates_considered": 52 },
    "new_backup_required": true,
    "new_backup_present": false
}
```

## Lectura del resultado

| Pregunta del punto 18 | Respuesta |
|---|---|
| ¿Recovery posible? | **No todavía** — `ok=false`, un único bloqueador. |
| Conteo exacto retryable | **48** |
| Conteo exacto rejected | **4** |
| Bloqueadores | `new_post_first_import_backup_present` — únicamente éste. |
| ¿Evidencia del run padre disponible? | **Sí** — `log-2e0c1248-....jsonl` íntegro, verificado. |
| ¿Equivalencia con el plan actual? | **Sí** — las 48 entradas siguen teniendo `source_hash`/`decision_hash`/`action` idénticos a cuando se construyó el plan de recuperación. |
| ¿Backup nuevo requerido? | **Sí, explícitamente** — y **no está presente**: correcto, porque no se generó (instrucción explícita de no prepararlo si eso implica preparar la ejecución real). |

`ok=false` es, por tanto, **el resultado correcto** — exactamente como anticipaba el punto
18 de la tarea: sin ese backup nuevo, no hay base segura para preservar los 449 objetos ya
migrados si algo saliera mal durante un futuro reintento real.

## Control de los 449 — verificado explícitamente, no asumido

`applied_449_control_intact = true`: las 449 entidades que el log original registra como
`result=CREATE` predicen `UNCHANGED` en el plan actual, sin excepción. Ninguna aparece como
`CREATE`/`UPDATE`/`MERGE` inesperado — si alguna lo hiciera, `recovery_preflight()` lo
reportaría como violación explícita en este mismo check, y por separado
`build_recovery_retry_plan()` habría abortado por completo antes de construir nada
(`APPLIED_CONTROL_VIOLATED`).

## REVIEW/SKIP — confirmado fuera

`no_review_or_skip_entries_in_recovery_set = true`: ninguna de las 48 entradas del plan de
recuperación tiene `action` REVIEW o SKIP — estructuralmente imposible por diseño
(`recovery_candidate_eligibility()` las rechaza en el paso 3), verificado aquí como control
adicional sobre el plan ya construido.

## Qué falta antes de una ejecución real futura

1. Backup nuevo de la base de datos **posterior** a los 449 ya migrados (nunca reutilizar el
   backup pre-primera-ejecución) — no generado en esta fase.
2. Autorización humana explícita, con su propia frase de confirmación (aún no diseñada ni
   solicitada — el mecanismo de ejecución real del recovery plan no se implementó en esta
   fase; sólo construcción + pre-flight, según lo pedido).
3. Revisión humana del conjunto exacto de 48 antes de autorizar nada.

**Ningún reintento se ejecutó. Ninguna base de datos se mutó más allá de las lecturas
documentadas. Ninguna publicación, staging o producción.**

Continúa en [20-retention-lifecycle.md](20-retention-lifecycle.md).

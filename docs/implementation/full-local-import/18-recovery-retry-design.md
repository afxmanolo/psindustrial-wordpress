# Diseño del mecanismo de recuperación

Continúa de [17-run-snapshot-incident.md](17-run-snapshot-incident.md). Todo el código vive
en `migration/Runner.php` y `migration/Storage.php`. **No se ejecutó ningún reintento real
sobre WordPress en esta fase** — cada método descrito aquí, salvo `close_run()`, es de sólo
lectura respecto a la base de datos (sólo escribe JSON en almacenamiento privado local).

## Principio rector: evidencia machine-readable persistente, nunca Markdown

Ningún método de este mecanismo lee un `.md` ni un `.csv` de documentación como fuente de
ejecución. Las únicas fuentes son:

- **`log-<parentRun>.jsonl`** vía `Runner::parse_run_log()` — el registro completo,
  íntegro, por entidad, de qué ocurrió en la ejecución original (ver
  [17-run-snapshot-incident.md](17-run-snapshot-incident.md)).
- **`identity-<token>.json`** (ledgers por entidad) — vía `Identity::ledger()`/`Storage::read()`,
  ya usado por el mecanismo de retry normal.
- **Un plan `full` reconstruido ahora mismo** (`Planner::build('full')`) — determinista,
  reproducible, evidencia de la realidad actual.

Los documentos Markdown (incluido este) pueden **reflejar** estos datos para lectura humana;
nunca los sustituyen como fuente de decisión del código.

## `Runner::parse_run_log( string $run ): array`

Lee `log-<run>.jsonl` línea por línea, devuelve un mapa `entity_key => {run_id, timestamp,
entity, action, result, message}`. Lanza `PARENT_RUN_LOG_NOT_FOUND` si el archivo tampoco
existe — nunca devuelve un resultado parcial fingiendo integridad, nunca inventa una entrada
que no esté literalmente en el archivo.

## `Runner::applied_control_violations( array $parentLog, array $freshPlan ): array`

El control del punto 7: para cada `entity_key` con `result=CREATE` en el log original (es
decir, realmente aplicado), exige `Identity::prediction()===UNCHANGED` contra el plan
actual. Cualquier otra cosa es una violación — devuelta explícitamente, nunca silenciada.
`build_recovery_retry_plan()` **aborta por completo** (lanza `APPLIED_CONTROL_VIOLATED`) si
existe una sola violación, antes de considerar ningún candidato de reintento.

## `Runner::recovery_candidate_eligibility( string $key, array $logRow, array $freshPlan, string $parentRun ): array`

La puerta de equivalencia para **un** candidato, extraída como método propio precisamente
para que cada camino de rechazo sea testeable de forma aislada (ver
[`tests/recovery-model.php`](../../../../wordpress/wp-content/plugins/psindustrial-core/tests/recovery-model.php)).
Un candidato sólo llega a `RETRYABLE` si prueba, en este orden, **todas**:

1. Existe en el plan actual (`MISSING_FROM_CURRENT_PLAN` si no).
2. La `action` actual coincide exactamente con la que el log original registró
   (`ACTION_CHANGED_SINCE_ORIGINAL_RUN` si no) — la única propiedad que el log persiste
   directamente y por tanto la comparación explícita "plan actual vs. intención original"
   que el punto 6 de la tarea pidió, nunca asumida por defecto.
3. La `action` sigue siendo mutable (`MIGRATE`/`MERGE`/`CREATE_FROM_STATIC`) —
   `ACTION_NOT_MUTABLE` en cualquier otro caso (nunca REVIEW/SKIP).
4. `Runner::retry_root_cause()` clasifica la causa como uno de los cuatro bugs ya
   corregidos (`ROOT_CAUSE_NOT_FIXED:<causa>` en cualquier otro caso, incluida
   `LEGITIMATE_CATEGORY_CONFLICT`, `LEGITIMATE_SLUG_COLLISION` u `OTHER`).
5. `Identity::prediction()` no es `UNCHANGED` (`ALREADY_APPLIED_ELSEWHERE_NOW_UNCHANGED` si
   lo es).
6. Si es `CONFLICT`, debe ser el intento fallido propio de **este** `parent_run_id`
   exacto (*ledger* `INTENT`, sin `wordpress_id`, `run_id` coincidente) — nunca un conflicto
   nuevo o de otro run (`CONFLICT_NOT_OWN_STALE_ATTEMPT` si no).

`source_hash`/`decision_hash` **actuales** se registran en cada fila de evidencia para
trazabilidad futura, pero no existe un valor histórico persistido para comparar en el caso
general (el log no lo registra; sólo los 13 casos con *ledger* superviviente tienen un rastro
más directo). La igualdad de `action` (paso 2) es la comprobación explícita y verificable
que sustituye a esa comparación ausente — nunca se asume "plan actual == plan original" sin
esta verificación.

## `Runner::build_recovery_retry_plan( string $parentRun ): array`

Bajo `Storage::locked()`. Nunca toca WordPress — misma categoría de "seguro" que
`Planner::build()` (sólo escribe JSON en almacenamiento privado). Aplica el control de los
449, recorre **todos** los `result=ERROR` del log original (nunca REVIEW/SKIP — esos nunca
entran a la lista de candidatos en absoluto) a través de `recovery_candidate_eligibility()`,
y produce:

```
run-<recoveryRunId>.json:
  scope: 'recovery'
  parent_run_id: <parentRun>
  recovery_reason: 'ORIGINAL_RUN_SNAPSHOT_PRUNED'
  recovery_open: true
  entries: [ ...sólo las entradas RETRYABLE, copiadas del plan actual... ]
  plan_hash: <sello propio, sólo de los campos de este plan>

recovery-evidence-<recoveryRunId>.json:
  parent_run_id, recovery_run_id, generated_at
  rows: [ una fila por CADA candidato considerado — aceptado o no — con
          source_key, original_ledger_status, original_error, current_source_hash,
          current_decision_hash, current_action, destination_state, eligibility, reason ]
```

`recovery_open=true` protege este plan de la misma poda que causó el incidente —
ver [20-retention-lifecycle.md](20-retention-lifecycle.md).

## `Runner::recovery_preflight( string $recoveryRun ): array`

Sólo lectura. Ver resultado real completo en
[19-recovery-preflight.md](19-recovery-preflight.md).

## `Runner::close_run( string $run, string $confirmation ): array`

La única forma de que un run deje de estar protegido — confirmación explícita
(`'CERRAR RUN RESUELTO'`), nunca inferida del estado. Sólo escribe `closed_at`/
`recovery_open=false` en el archivo de plan; nunca toca WordPress. Ver
[20-retention-lifecycle.md](20-retention-lifecycle.md).

## Qué NO hace este mecanismo

No ejecuta ningún `apply()`/`media_handle_sideload()` real. No crea, actualiza ni elimina
ningún objeto WordPress. No toca `category:37`/`category:38` ni ninguna colisión de slug
legítima. No recorre las 2.399 entradas del plan original como operaciones mutables — sólo
los 52 `result=ERROR`, y de ésos, sólo los que prueban las 6 condiciones de equivalencia.

## Verificación

[`tests/recovery-model.php`](../../../../wordpress/wp-content/plugins/psindustrial-core/tests/recovery-model.php) —
2.444 comprobaciones, incluidas: ausencia de snapshot original → sin reconstrucción falsa;
control de los 449 (real, 0 violaciones); construcción real contra el `parent_run_id` real
(48 retryable / 4 rechazados, coincide exactamente con
[13-pdf-runtime-failures.md](13-pdf-runtime-failures.md)); cada camino de rechazo
individual (`action` cambiada, no mutable, entidad ausente, ya aplicada, causa no
corregida); segunda construcción → mismo conjunto de 48 claves; protección de retención
real (sobrevive 3 reconstrucciones de plan adicionales); `close_run()` real (requiere
confirmación exacta, nunca toca WordPress).

Continúa en [19-recovery-preflight.md](19-recovery-preflight.md).

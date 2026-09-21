# Diseño del mecanismo de recuperación

Continúa de [17-run-snapshot-incident.md](17-run-snapshot-incident.md). Todo el código vive
en `migration/Runner.php` y `migration/Storage.php`. **No se ejecutó ningún reintento real
sobre las 48 entradas del recovery plan en ninguna fase.** `build_recovery_retry_plan()`,
`create_post_first_import_backup()`, `verify_backup_restorable()` y `recovery_preflight()`
sólo escriben JSON en almacenamiento privado local o (la verificación de restauración)
mutan exclusivamente una base de datos temporal propia, nunca `psindustrial_wp_dev` — ver
sus propias secciones más abajo. `batch_recovery()` (el ejecutor) sí mutaría WordPress si se
invocara contra el recovery plan real — no se invocó así en ningún momento; toda su
verificación usó exclusivamente fixtures sintéticos seguros (ver
[`tests/batch-recovery-model.php`](../../../../wordpress/wp-content/plugins/psindustrial-core/tests/batch-recovery-model.php)).

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

## Backup post-primera-importación — `Runner::create_post_first_import_backup()`

Distinto del `plan['backup']` que ya se tomaba automáticamente antes de la primera mutación
del run original (ese refleja el estado **anterior** a los 449 objetos, y nunca debe
reutilizarse como si los protegiera). Reutiliza `Storage::backup_database()`/
`backup_uploads()` (mismo mecanismo ya probado, un id nuevo) y añade:

- Copia de la evidencia privada crítica (`log-<parentRun>.jsonl`, el propio recovery plan y
  su artefacto de evidencia) a `backup-evidence-<backupId>/`.
- Un manifest `postimport-backup-<backupId>.json` con hash SHA-256 individual de **cada**
  archivo (DB dump, cada archivo de uploads, cada archivo de evidencia) — nunca sólo "el
  archivo existe".
- `parent_run_completion_time` — el `timestamp` más reciente de `log-<parentRun>.jsonl`,
  la única fuente disponible ya que el propio run padre no tiene snapshot.

Al terminar, llama a `verify_backup_restorable()` (ver abajo) **una sola vez**, aquí, y
graba el resultado (`restore_verified`, `restore_verification`) en el mismo manifest —
`recovery_preflight()` sólo relee ese resultado grabado, nunca repite la restauración
completa en cada llamada.

## Validación real de restaurabilidad — `Runner::verify_backup_restorable()`

Restaura el dump JSON en una base de datos **temporal**, con nombre estrictamente acotado
(`psi_recovery_verify_<hash del backupId>`, nunca igual a `DB_NAME`), usando `mysqli` crudo
— nunca `new wpdb(...)`, cuyo propio fallo de conexión puede invocar `wp_die()` y abortar
todo el proceso de forma no controlada. La conexión global `$wpdb`/`psindustrial_wp_dev`
nunca se redirige ni se escribe; sólo se lee su propiedad `prefix` y su
`@@SESSION.sql_mode` actual (ver abajo). La base temporal se elimina en `finally` —
siempre, éxito o fallo — y sólo la base exacta que este método creó; verificado
directamente (0 bases `psi_recovery_verify_%` remanentes tras cada ejecución).

**Hallazgo real durante la implementación**: la primera restauración falló
(`SCHEMA_RESTORE_FAILED:psi_wp_comments:Invalid default value for 'comment_date'`) — el
`sql_mode` por defecto de una conexión nueva (`STRICT_TRANS_TABLES,NO_ZERO_DATE,...`)
rechaza el valor por defecto histórico de WordPress core para `comment_date`
(`'0000-00-00 00:00:00'`). **No es un defecto del backup**: `$wpdb` de WordPress
**siempre** quita `STRICT_TRANS_TABLES`/`NO_ZERO_DATE`/`ONLY_FULL_GROUP_BY` de su propia
sesión al conectar (`wpdb::set_sql_mode()`, comportamiento núcleo de WordPress, no algo
específico de este proyecto) precisamente por este motivo. La corrección — leer
`$wpdb->get_var('SELECT @@SESSION.sql_mode')` (sólo lectura) y aplicar ese mismo valor a la
conexión temporal antes de restaurar — replica fielmente cómo WordPress ya opera siempre,
no relaja ninguna validación propia de este proyecto.

Comprueba: tablas esenciales presentes (`posts`, `postmeta`, `terms`, `term_taxonomy`,
`options`, `users`), y conteo de filas restauradas == conteo del dump, tabla por tabla. Si
cualquier paso no puede completarse de forma inequívocamente segura (dump ilegible o con
hash alterado, fallo de conexión, nombre de tabla no seguro), devuelve
`restorable=false` con motivo específico — nunca fuerza ni improvisa.

Resultado real, verificado en vivo: `restorable=true`, 12 tablas restauradas, 0 tablas
esenciales faltantes, 0 discrepancias de conteo. Ver
[19-recovery-preflight.md](19-recovery-preflight.md) para el resultado completo.

## `Runner::verify_backup_integrity( array $manifest ): array`

Sólo lectura, re-verificada en cada llamada a `recovery_preflight()` (a diferencia de la
restauración completa, que sólo corre una vez). Comprueba, contra bytes actuales en disco:
`db_name` declarado == `DB_NAME` actual; hash del dump == hash grabado; hash de **cada**
archivo de uploads/evidencia respaldado == hash grabado; `created_at` del backup posterior
al `parent_run_completion_time`. Cualquier archivo faltante o alterado desde que se generó
el manifest → `false`, nunca "el archivo existe" como sustituto de la comprobación de hash.

## El ejecutor — `Runner::batch_recovery( string $recoveryRun, string $confirmation, int $limit = 20 ): array`

Frase de confirmación propia y exclusiva: `'REINTENTAR RECOVERY LOCAL'` — nunca acepta
`FULL_CONFIRMATION` ni `RETRY_CONFIRMATION`, y esta frase nunca es aceptada por `batch()`/
`batch_full_local_resolved_only()`/`retry_failed_resolved_only()`. Exige `scope=recovery`
exacto — un `run_id` de plan `full`/`subset` se rechaza con `VALID_RECOVERY_PLAN_REQUIRED`
antes de mirar la frase de confirmación siquiera. Verifica el sello (`plan_hash` ==
`recovery_digest(plan)`) y llama a `recovery_preflight()` — aborta si no está `ok`.

**Corrección de raíz del defecto de continuación entre lotes** (el motivo por el que este
ejecutor es un método separado, no una reutilización de `retry_failed_resolved_only()`):
recorre `$plan['entries']` — la lista **sellada** en `build_recovery_retry_plan()`, fija por
`plan_hash` — estrictamente por `cursor`, nunca recalculada entre lotes. Lote 1 = índices
`0..19`, lote 2 = `20..39`, lote 3 = `40..47`. El defecto original (`RETRY_SET_CHANGED_SINCE_FIRST_BUILD`)
ocurría porque el mecanismo anterior recalculaba el conjunto de reintento en cada lote vía
elegibilidad fresca — y una entidad que el lote 1 ya aplicó exitosamente deja de aparecer
como candidata (ahora es `UNCHANGED`), cambiando el conjunto. Este ejecutor nunca recalcula
el conjunto: la identidad de las 48 entradas está fija desde el sellado.

**`Runner::recovery_process_entry( array $sealedEntry, array $freshPlan, string $parentRunId ): array`**
— la revalidación individual, extraída como método propio (mismo patrón que
`recovery_candidate_eligibility()`) para que cada camino de rechazo sea testeable sin
romper el sello del plan real. Antes de aplicar cada entrada sellada, compara contra un
plan `full` reconstruido ahora mismo (sustrato de resolución de dependencias, **nunca**
usado para decidir qué entra al conjunto): `source_hash`/`decision_hash`/`action`
idénticos a los sellados, o lanza (`SOURCE_CHANGED_SINCE_RECOVERY_SEALED`/
`DECISION_CHANGED_SINCE_RECOVERY_SEALED`/`ACTION_CHANGED_SINCE_RECOVERY_SEALED`). Si
`Identity::prediction()` es `UNCHANGED` (ya aplicada — por un lote anterior de este mismo
run, o por una reejecución completa) devuelve `UNCHANGED` sin llamar a `apply()` —
la garantía de idempotencia. Si es `CONFLICT`, exige que sea el intento fallido propio de
**este** `parent_run_id` (mismo criterio que `recovery_candidate_eligibility()`), o lanza
`DESTINATION_EDITED_SINCE_RECOVERY_SEALED`.

`batch_recovery()` convierte cualquier excepción no fatal de `recovery_process_entry()` en
`status=CONFLICT` para **esa única entrada** — nunca altera la lista sellada ni la
identidad de ninguna otra entrada, nunca detiene el lote completo. Un error fatal
(`FATAL_ERROR_CODES`, la misma lista ya usada en el resto de `Runner`) sí aborta el lote
completo, dejando el cursor exactamente donde estaba.

## Qué NO se ejecutó en esta fase

Ningún `apply()`/`media_handle_sideload()` real contra las 48 entradas reales del recovery
plan (`9c492018-...`). Ningún objeto WordPress creado/actualizado/eliminado. Ninguna base de
datos real modificada — sólo la base temporal de `verify_backup_restorable()`, creada y
eliminada en el mismo proceso, verificada. `category:37`/`category:38` y las 10 colisiones
de slug permanecen expresamente fuera. Las 2.399 entradas del plan original nunca se
recorren como operaciones mutables — sólo las 48 selladas.

## Verificación

[`tests/recovery-model.php`](../../../../wordpress/wp-content/plugins/psindustrial-core/tests/recovery-model.php) —
2.444 comprobaciones (construcción del plan, elegibilidad por candidato, control de los
449). [`tests/batch-recovery-model.php`](../../../../wordpress/wp-content/plugins/psindustrial-core/tests/batch-recovery-model.php) —
96 comprobaciones: `scope=full`/frase incorrecta → DENY; `scope=recovery` válido → ALLOW;
20+20+8 → COMPLETE con la lista sellada byte-idéntica en cada lote; reejecución de un run
`COMPLETE` → no-op puro, sin duplicados; deriva individual de `source_hash`/`decision_hash`/
`action`/entidad ausente → sólo esa entrada afectada, hermanas intactas; los 449/339/1534/14
nunca aparecen en el conjunto sellado real; backup anterior al run padre / hash alterado /
DB incorrecta → DENY; backup real restaurable → PASS. Ninguna prueba mutó
`psindustrial_wp_dev` (verificado antes/después de cada llamada, en cada archivo).

Continúa en [19-recovery-preflight.md](19-recovery-preflight.md).

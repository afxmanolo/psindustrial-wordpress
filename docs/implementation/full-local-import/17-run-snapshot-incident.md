# Incidente: poda del snapshot de la primera ejecución real

Continúa de [16-retry-preflight.md](16-retry-preflight.md). Detalla exactamente qué se
perdió, qué sobrevivió y por qué, sobre el entorno local/almacenamiento privado — **en
ningún momento se actuó sobre un entorno de producción**; este incidente y su corrección
ocurren enteramente en `psindustrial_wp_dev` y `psindustrial-importer-private` locales.

## Qué ocurrió

`run-2e0c1248-8d56-4d92-a33b-c17e37b2732e.json` — el snapshot agregado (plan + resultados)
de la primera ejecución real `FULL LOCAL RESOLVED-ONLY` — dejó de existir en algún momento
entre el cierre de esa ejecución y el diagnóstico posterior de los bugs PDF, dentro de la
misma sesión de trabajo.

**Causa raíz**: `Storage::retain_recent_runs()` se invoca en **cada** llamada a
`Planner::build()`. Conserva sólo los 30 archivos `run-*.json` más recientes por fecha de
modificación, y — antes de esta corrección — sólo eximía de poda a los planes con
`status=RUNNING`. Un plan `COMPLETE`, aunque representara una ejecución real con objetos
WordPress reales creados a partir de él, competía por esos 30 cupos como cualquier otro. El
propio diagnóstico de los bugs PDF (docenas de reconstrucciones de plan) más la suite de
regresión completa (que reconstruye ~20 planes `full` por corrida) empujaron el archivo
fuera de la ventana de los 30 más recientes.

## Qué se perdió — exactamente

Únicamente el archivo agregado `run-<id>.json`: el `plan_hash` sellado, el array `entries`
completo (2.399 entradas con sus `data`/`dependencies`/hashes) y el array `results`
completo de esa ejecución específica.

## Qué sobrevivió — verificado directamente, no asumido

| Evidencia | Patrón de archivo | ¿Coincide con `run-*.json`? | Estado verificado |
|---|---|:---:|---|
| Backup de base de datos | `backup-<id>.json` | No | Íntegro, 1.856.954 bytes |
| Backup de uploads | `backup-uploads-<id>/` | No | Íntegro |
| Ledger por entidad | `identity-<token>.json` | No | 477 archivos íntegros |
| **Log por entidad** | `log-<id>.jsonl` | No | **2.399 líneas íntegras — una por cada entrada del plan original, incluidas las que nunca llegaron a tener ledger** |
| Objetos WordPress reales | (tablas `wp_posts`/`wp_terms`/etc.) | N/A | 449 objetos intactos |
| `editorial-decisions.json` | `docs/implementation/review-resolution/...` | N/A (Git) | Sin cambios desde el run (confirmado) |
| `pdf-approvals.json` / `sanitization-audit.json` | `docs/implementation/pdf-security-review/...` | N/A (Git) | Sin cambios desde el run (confirmado) |
| CSV maestros / `.php` legacy | `docs/migration/*.csv`, `legacy/public/*.php` | N/A (Git) | Sin cambios desde el run (confirmado) |

**El hallazgo central**: `log-<id>.jsonl` — el registro de auditoría por entidad que
`Storage::log()` escribe para **cada** entidad procesada, éxito o no (`{run_id, timestamp,
entity, action, result, message}`, una línea JSON por entidad) — nunca coincide con el
patrón que poda `retain_recent_runs()` y sobrevivió íntegro. Confirmado: 2.399 líneas,
`result=CREATE` en 449, `result=ERROR` en 52, coincidiendo exactamente con los totales ya
documentados en
[10-first-import-result.md](10-first-import-result.md)/[13-pdf-runtime-failures.md](13-pdf-runtime-failures.md).
Este archivo es la fuente de evidencia machine-readable que hace posible la recuperación sin
reconstruir nada a mano — ver [18-recovery-retry-design.md](18-recovery-retry-design.md).

## Por qué no se reconstruyó el snapshot

Reescribir un `run-2e0c1248-....json` de reemplazo — aunque fuera con datos "fieles" tomados
de este mismo documento — habría sido exactamente el tipo de "editar manualmente
identities/manifest" que este proyecto prohíbe: no existe forma de que ese archivo llevara
un `plan_hash` genuinamente sellado en el momento original, y presentarlo como si lo tuviera
sería falsificar evidencia. La ausencia del snapshot permanece detectable — confirmado:
`Storage::read('run-2e0c1248-....json')` sigue devolviendo vacío, y
`Runner::retry_preflight()` sobre ese `run_id` responde honestamente `ok=false`,
`plan_exists` como bloqueador.

## Corrección aplicada

Dos partes, documentadas por separado:

1. **Recuperación**: [18-recovery-retry-design.md](18-recovery-retry-design.md) — un
   mecanismo que reconstruye un plan de reintento **nuevo y sellado**, evidenciado
   exclusivamente por lo que sobrevivió (`log-<id>.jsonl` + ledgers + plan actual), nunca por
   este documento ni por ningún Markdown/CSV.
2. **Retención**: [20-retention-lifecycle.md](20-retention-lifecycle.md) — `retain_recent_runs()`
   ya no poda un run que siga necesitando su snapshot (falla técnica sin resolver, conflicto
   sin resolver, o marcado explícitamente abierto), independientemente de su antigüedad.

Continúa en [18-recovery-retry-design.md](18-recovery-retry-design.md).

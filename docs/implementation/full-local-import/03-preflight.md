# Pre-flight — `Runner::preflight_full_local()`

De sólo lectura. Nunca requiere la frase de confirmación. Segura de llamar en cualquier
momento, incluso sobre un plan que nadie piensa ejecutar todavía — es exactamente así como se
usó contra el plan real actual para esta misma entrega (sección 17 de la tarea), sin
ejecutarlo.

## Las 14 verificaciones

| # | id | Qué comprueba |
|---|---|---|
| 1 | `plan_exists` | El archivo `run-<id>.json` existe |
| 2 | `plan_scope_full` | `scope === 'full'` |
| 3 | `plan_status_valid` | `status ∈ {VALIDATED, RUNNING, COMPLETE}` |
| 4 | `environment_id_matches` | El plan fue construido para esta misma instalación/DB |
| 5 | `transform_version_matches` | `Planner::VERSION` no cambió desde que se construyó |
| 6 | `plan_seal_intact` | `plan_hash === Planner::digest(plan)` — el archivo no fue alterado |
| 7 | `manual_decisions_unchanged` | `subset-decisions.json` no cambió desde el build |
| 8 | `source_hashes_current` | Cada fuente en `plan['sources']` re-hashea igual (incluye ahora `editorial-decisions.json`/`pdf-approvals.json`) |
| 9 | `actions_within_known_set` | Ninguna entrada tiene una `action` fuera de {MIGRATE,MERGE,CREATE_FROM_STATIC,SKIP,REVIEW,ERROR} |
| 10 | `no_error_entries` | Cero entradas con `action=ERROR` (una comprobación propia, no confundida con la #9 — ver nota) |
| 11 | `review_counted` | Cuenta cuántos REVIEW existen y confirma explícitamente que se preservan |
| 12 | `legacy_source_files_exist` | Cada archivo legacy referenciado por una fila mutable existe |
| 13 | `staged_media_assets_valid` | Cada activo de medio ya copiado privadamente (`package_asset`) existe con el hash correcto |
| 14 | `pdf_approvals_current` | Cada aprobación de PDF (sanitizado o excepción) sigue resolviendo contra los bytes actuales del archivo original |

**Nota sobre #9/#10**: se descubrió, escribiendo el test contra el plan real, que `ERROR` no
estaba en la lista de acciones "conocidas" de la primera versión — una entrada con
`action=ERROR` caía en la comprobación #9 (genérica: "acción no reconocida") y el bucle
cortaba antes de llegar a contar el `ERROR` en la comprobación #10, que entonces reportaba
`0 entr(y/ies)` incorrectamente aunque #9 sí bloqueaba. Corregido: `ERROR` es ahora una acción
reconocida por derecho propio, así que activa específicamente #10 con su diagnóstico correcto,
no la genérica #9. Ver [08-tests.md](08-tests.md).

## Resultado contra el plan real (sin ejecutar nada)

```json
{
  "ok": true,
  "blockers": [],
  "counts": { "total": 2399, "REVIEW": 339, "MIGRATE": 445, "SKIP": 1534, "MERGE": 49, "CREATE_FROM_STATIC": 32 },
  "environment": { "type": "local", "db_name": "psindustrial_wp_dev", "db_host": "localhost" }
}
```

Las 14 verificaciones pasan. **Esto significa que el plan real, hoy, cumple todas las
condiciones estructurales para ejecutarse** — no que se haya ejecutado, ni que deba
ejecutarse sin que un humano lo autorice explícitamente (ver
[09-first-import-checklist.md](09-first-import-checklist.md)).

## Qué NO decide pre-flight

No decide si un objeto concreto resultará `CREATE`/`UPDATE`/`CONFLICT` en tiempo real — eso
depende de `Identity::prediction()`, que consulta WordPress en vivo y puede cambiar entre el
momento del pre-flight y el momento de la ejecución si alguien modifica el destino
mientras tanto (por eso el `CONFLICT` se sigue comprobando también durante la ejecución
misma, no sólo aquí). No decide entorno/capacidad — eso es `Storage::guard()`, ya
incondicional antes de que esta función pueda siquiera leer el plan.

## Uso previsto

- **Antes de mostrar el panel de confirmación** en Herramientas → Migración PS Industrial
  (ver [07-admin-workflow.md](07-admin-workflow.md)): si `ok !== true`, el formulario de
  confirmación ni siquiera se muestra.
- **Como comprobación independiente**, en cualquier momento, para responder "¿podríamos
  ejecutar esto ahora mismo?" sin arriesgar nada.
- **Internamente**, como la primera acción real de `batch_full_local_resolved_only()` —
  nunca se duplica esta lógica en otro sitio.

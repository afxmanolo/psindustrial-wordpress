# Recovery pre-flight — resultado final, sin mutación

Continúa de [18-recovery-retry-design.md](18-recovery-retry-design.md). **No se ejecutó
ningún reintento real sobre las 48 entradas del recovery plan.** Toda llamada de esta página
se ejecutó de verdad, en vivo, contra el estado local actual — confirmado sin mutación de
`wp_posts`/`wp_terms` antes/después de cada una (incluida `verify_backup_restorable()`, que
sólo muta una base de datos temporal propia, creada y eliminada en el mismo proceso).

## 1. Construcción del recovery plan (sin cambios desde el primer intento)

```
recovery_run_id: 9c492018-cc99-4548-ac37-aed5d73688c1
parent_run_id:   2e0c1248-8d56-4d92-a33b-c17e37b2732e
candidates_considered: 52 · retryable: 48 · rejected: 4
```

## 2. Backup post-primera-importación — creado y validado en vivo

```php
Runner::create_post_first_import_backup(
  '2e0c1248-8d56-4d92-a33b-c17e37b2732e', '9c492018-cc99-4548-ac37-aed5d73688c1'
);
```

```
backup_id: postimport-41111c84-e2cd-49e2-8cac-aef08d2438cb
created_at: 2026-09-21T16:19:07+00:00
parent_run_completion_time: 2026-09-21T07:57:16+00:00   (created_at > completion: OK)
db_name: psindustrial_wp_dev
uploads_backup_manifest: 1.061 archivos con hash SHA-256 individual
private_evidence_manifest: 3 archivos (log del run padre, recovery plan, evidencia del recovery)
restore_verified: true
```

**Primer intento de restauración real: falló** —
`SCHEMA_RESTORE_FAILED:psi_wp_comments:Invalid default value for 'comment_date'`. Causa
real, no un defecto del backup: una conexión MySQL nueva usa por defecto
`STRICT_TRANS_TABLES,NO_ZERO_DATE,...`, que rechaza el valor histórico de WordPress core
para `comment_date` (`'0000-00-00 00:00:00'`). WordPress mismo resuelve esto quitando esos
modos de su **propia** sesión al conectar (`wpdb::set_sql_mode()`, núcleo de WordPress, no
algo de este proyecto). Corregido leyendo `$wpdb->get_var('SELECT @@SESSION.sql_mode')`
(sólo lectura sobre la conexión ya existente) y aplicando ese mismo valor a la conexión
temporal antes de restaurar — replica cómo WordPress ya opera siempre, no relaja ninguna
validación propia. **Segundo intento: `restorable=true`, 12 tablas restauradas, 0 tablas
esenciales faltantes, 0 discrepancias de conteo de filas.** 0 bases de datos temporales
remanentes tras la verificación (confirmado).

## 3. Pre-flight real, final

```php
Runner::recovery_preflight( '9c492018-cc99-4548-ac37-aed5d73688c1' );
```

```json
{
    "run_id": "9c492018-cc99-4548-ac37-aed5d73688c1",
    "parent_run_id": "2e0c1248-8d56-4d92-a33b-c17e37b2732e",
    "ok": true,
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
        { "id": "new_post_first_import_backup_present", "passed": true },
        { "id": "db_name_matches", "passed": true, "detail": "manifest=psindustrial_wp_dev actual=psindustrial_wp_dev" },
        { "id": "db_dump_hash_current", "passed": true },
        { "id": "uploads_backup_hashes_current", "passed": true },
        { "id": "private_evidence_hashes_current", "passed": true },
        { "id": "created_after_parent_completion", "passed": true },
        { "id": "backup_restore_verified", "passed": true }
    ],
    "blockers": [],
    "counts": { "retryable": 48, "rejected": 4, "candidates_considered": 52 },
    "new_backup_required": true,
    "new_backup_present": true
}
```

**16/16 comprobaciones en verde. `ok=true`. Cero bloqueadores. Números sin forzar** —
idénticos a la primera construcción del plan, coherente con
[13-pdf-runtime-failures.md](13-pdf-runtime-failures.md).

## Control de los 449 y REVIEW/SKIP — sin cambios, verificados de nuevo

`applied_449_control_intact=true`: las 449 entidades `result=CREATE` del log original
siguen prediciendo `UNCHANGED`, sin excepción. `no_review_or_skip_entries_in_recovery_set=true`:
ninguna de las 48 tiene `action` REVIEW/SKIP.

## Qué falta antes de una ejecución real

1. **Autorización humana explícita** para invocar `Runner::batch_recovery()` con la frase
   `'REINTENTAR RECOVERY LOCAL'` — no otorgada en esta fase; el ejecutor existe, está
   probado (96 comprobaciones en `tests/batch-recovery-model.php`, contra fixtures
   sintéticos seguros) y el pre-flight real está en verde, pero **no se invocó contra las
   48 entradas reales**.
2. Revisión humana del conjunto exacto de 48 (tabla completa en
   [13-pdf-runtime-failures.md](13-pdf-runtime-failures.md)).

**Ningún reintento real se ejecutó. Ninguna base de datos real se mutó. Ninguna
publicación, staging o producción.**

Continúa en [20-retention-lifecycle.md](20-retention-lifecycle.md).

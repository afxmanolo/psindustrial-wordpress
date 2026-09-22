# Modelo de reintento — `RETRY_FAILED_RESOLVED_ONLY`

Continúa de [14-pdf-runtime-fix.md](14-pdf-runtime-fix.md). Describe el mecanismo
construido en `Runner.php` para reintentar, de forma segura y explícita, únicamente las
entidades cuyo fallo original quedó resuelto por las dos correcciones anteriores. **No se
ejecutó ningún reintento real en esta fase** — ver
[16-retry-preflight.md](16-retry-preflight.md) para la demostración de sólo lectura.

## Confirmación explícita, propia y distinta

```php
private const RETRY_CONFIRMATION = 'REINTENTAR FALLOS RESUELTOS';
```

Nunca acepta ni es aceptada por `'IMPORTAR SUBSET EN BORRADOR'` ni por
`'IMPORTAR FULL LOCAL RESUELTO'` — un reintento es una operación de alcance distinto y
menor (sólo el conjunto pre-identificado, nunca un recorrido de cursor completo) y exige su
propia autorización humana explícita, nunca reutilizada de otro alcance.

## Tres piezas, cada una con su propia responsabilidad

### `retry_root_cause( array $entry, array $plan ): string`

Re-deriva **por qué** falló una entidad — nunca leyendo el `notes` guardado (que
`safe_error()` puede haber enmascarado a `OBJECT_OPERATION_FAILED`). Para una entidad media
directa: reconstruye `media_integrity()`; cualquier deriva actual real en cualquiera de las
dos mitades es `OTHER` (nunca asumida retryable). Con ambas mitades correctas — lo que
ambos fixes garantizan para cualquier cosa que cualquiera de los dos bugs bloqueaba — **cuál**
bug era (si alguno) se lee de `pdf_approval_type`, un hecho estable fijado por
`Planner::build()` en tiempo de construcción del plan (`'sanitized'`=Grupo A,
`'exception'`=Grupo B, ausente para un activo ordinario nunca sujeto a ninguna aprobación).

Deliberadamente **nunca** re-derivado desde `media_is_valid()`/`file_valid()` aquí: tras el
fix, un activo ordinario nunca bloqueado (una foto de producto cualquiera) pasa esas
comprobaciones exactamente igual que una excepción Grupo B aprobada — re-derivar desde ahí
habría clasificado erróneamente cada dependencia ordinaria como si fuera un bug corregido
(bug real encontrado y corregido durante la construcción de este mismo mecanismo, ver
tests).

Para todo lo demás: recorre `$entry['dependencies']` y hace la misma pregunta,
recursivamente, un nivel más abajo — la causa de una cascada es la causa de su dependencia.
Devuelve uno de `DIRECT_PDF_A`, `DIRECT_PDF_B`, `CASCADE_FROM_PDF_A`, `CASCADE_FROM_PDF_B`,
`LEGITIMATE_CATEGORY_CONFLICT`, `LEGITIMATE_SLUG_COLLISION`, `OK` (nunca fue un problema), o
`OTHER` (nunca asumido seguro).

### `retry_eligibility( array $originalEntry, array $originalResult, array $freshPlan ): array`

Re-verifica **cada** condición contra el estado actual — nunca confía en el resultado
guardado de la ejecución original:

1. `$originalResult['status'] === 'FAILED'` — nunca `APPLIED`, `UNCHANGED`, `BLOCKED`
   (REVIEW), `SKIPPED` ni `CONFLICT` original.
2. La entrada sigue existiendo en un plan reconstruido, con `action` mutable
   (`MIGRATE`/`MERGE`/`CREATE_FROM_STATIC`).
3. `source_hash` sin cambios desde la primera ejecución — si el `.php`/medio legacy cambió,
   **no** reintenta.
4. `decision_hash` sin cambios — si la decisión editorial cambió, **no** reintenta.
5. `retry_root_cause()` debe estar en el conjunto cerrado
   `RETRYABLE_ROOT_CAUSES = [DIRECT_PDF_A, DIRECT_PDF_B, CASCADE_FROM_PDF_A, CASCADE_FROM_PDF_B]`.
6. Si `Identity::prediction()` devuelve `UNCHANGED` ahora → ya se aplicó por otro medio, no
   se toca.
7. Si devuelve `CONFLICT` → distingue el intento fallido propio (sin objeto WordPress, con
   *ledger* `INTENT` sin `wordpress_id`, **con `run_id` igual al de
   `$originalResult['run_id']`** — nunca comparado contra el `run_id` de un plan reconstruido
   cualquiera, que sería casi siempre distinto y rechazaría erróneamente un intento
   genuinamente propio; bug real encontrado y corregido durante la construcción de este
   mecanismo, ver tests) de un conflicto nuevo o preexistente — sólo el primero es elegible.

### `retry_preflight( string $run ): array`

Sólo lectura, nunca requiere la frase de confirmación. Reutiliza las 14 comprobaciones de
`preflight_full_local()` íntegras (un reintento no tiene sentido si el plan/entorno/backup/
hashes/aprobaciones subyacentes no son en sí mismos sólidos) y añade: `original_run_complete`,
`post_first_import_backup_available`, `retry_not_already_complete`. Reporta conteos exactos:
`retryable`, `non_retryable_conflict`, `already_applied`, `review`, `skip`,
`other_excluded` — y el `retry_set` completo, nunca estimado.

## `retry_failed_resolved_only( string $run, string $confirmation, int $limit = 25 ): array`

Mutante, bajo `Storage::locked()`. Exige plan `scope=full`, `status=COMPLETE`, entorno
coincidente, backup verificado en disco. Llama a `retry_preflight()` primero y aborta si no
está `ok`. Construye el conjunto de reintento **una sola vez** (`plan['retry']['set']`),
**sellado**: una llamada posterior con un conjunto distinto lanza
`RETRY_SET_CHANGED_SINCE_FIRST_BUILD`, nunca sustituye en silencio.

**Nunca reescribe** `plan['results']`/`cursor`/`status` del run original — ese historial es
inmutable una vez escrito. El progreso del reintento vive enteramente bajo la nueva clave
`plan['retry']`, exactamente como `plan['backup']` se añadió sin tocar nada más del plan.

Procesa primero las causas directas (`DIRECT_PDF_A`/`DIRECT_PDF_B`) y después las cascadas
que dependen de ellas — el propio chequeo de dependencias de `process_entry()`/`apply()`
exige que la dependencia ya resuelva a `UNCHANGED`/`UPDATE`.

Cada entidad se re-verifica con `retry_eligibility()` **inmediatamente antes** de mutar —
nunca confía en el veredicto de `retry_preflight()` si el estado cambió entre medias (por
ejemplo, por una entidad anterior de este mismo lote). Fallos de entidad (no fatales) se
registran como `FAILED` en `plan['retry']['results']` y el lote continúa; errores fatales
(`FATAL_ERROR_CODES`, la misma lista ya usada por `batch_full_local_resolved_only()`) abortan
el lote completo. La atribución de *ledger*/`_psi_import_state` de cada objeto reintentado
apunta al `run_id` **original** (nunca al `run_id` efímero del plan reconstruido usado
internamente para resolver dependencias).

## Qué NO hace este mecanismo

No resuelve `category:37`/`category:38` ni ninguna colisión de slug — quedan fuera del
`retry_set` por diseño (`retry_root_cause()` las clasifica como
`LEGITIMATE_CATEGORY_CONFLICT`/`LEGITIMATE_SLUG_COLLISION`, nunca en
`RETRYABLE_ROOT_CAUSES`). No modifica `Identity.php` ni `Media.php`. No relaja ninguna
validación existente. No se ejecutó contra la base de datos real en esta fase.

## Verificación

[`tests/retry-model.php`](../../../../wordpress/wp-content/plugins/psindustrial-core/tests/retry-model.php) —
200 comprobaciones, incluidos los 10 casos de "nunca reintentar" pedidos
(`APPLIED`/`UNCHANGED`/`REVIEW`/`SKIP`/conflicto legítimo), los 48 casos reales
retryable clasificados uno a uno por causa exacta, el reconocimiento del *ledger* `INTENT`
real superviviente, `source`/`decision` alterados sintéticamente, un conflicto ajeno a
nuestro propio intento (entidad y *ledger* sintéticos, nunca datos reales, limpiados en
`finally`), y las garantías estructurales de no-op en segundo reintento. Debido a la poda de
`run-<id>.json` descrita en
[13-pdf-runtime-failures.md](13-pdf-runtime-failures.md#incidente-operativo-descubierto-durante-este-diagnóstico-poda-de-run-idjson),
las claves de entidad de la ejecución original se toman del documento anterior (fuente
permanente, no del archivo podado); ningún dato se inventó. Detalle completo del porqué y
las implicaciones en el propio archivo de test.

Continúa en [16-retry-preflight.md](16-retry-preflight.md).

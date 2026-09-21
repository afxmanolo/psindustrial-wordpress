# Verificación — FULL LOCAL RESOLVED-ONLY

## Resultado global (20 suites)

```
smoke.php                              120 checks   PASS
importer.php                            67 checks   PASS   (subset intacto, sin regresion)
policy.php                           6.040 checks   PASS
pdf-approvals.php                      310 checks   PASS
runner-media-validation.php             82 checks   PASS
editorial-decisions.php                374 checks   PASS
q05-pdf-resolution.php                  66 checks   PASS
q01-content-ownership.php              329 checks   PASS
q04-static-products.php                219 checks   PASS
run-snapshot-retention.php              35 checks   PASS   (+5 nuevos: proteccion de run activo)
q07-landings.php                       113 checks   PASS
q10-category-cleanup.php                23 checks   PASS
q11-missing-media.php                   31 checks   PASS
q13-media-alias.php                     14 checks   PASS
q03-multi-category.php                 275 checks   PASS
q03-name-consolidation.php             225 checks   PASS
q03-brand-unresolved.php                55 checks   PASS
q03-kelley.php                          27 checks   PASS
full-local-import-preflight.php         40 checks   PASS   (nuevo)
full-local-import-execution.php         34 checks   PASS   (nuevo)
──────────────────────────────────────────────────────────
TOTAL                                8.479 checks   0 fallos
```

## `tests/full-local-import-preflight.php` (nuevo, 40 checks, sólo lectura)

Usa el plan `full` **real** actual como fixture, sin ejecutarlo nunca — exactamente el uso
que pidió la sección 17 de la tarea.

| Caso pedido | Verificación |
|---|---|
| Conteo del plan actual | TOTAL/UNCHANGED/SKIP/CREATE/REVIEW coinciden exactamente con el baseline dado, sin forzar ningún número |
| Representación de MERGE | `MIGRATE+MERGE+CREATE_FROM_STATIC (526) === CREATE+UNCHANGED del resumen (526)`; `MERGE=49` contado aparte, nunca oculto |
| `full + production/staging → DENY` | Documentado como estructuralmente garantizado por `Storage::guard()` (constantes de `wp-config.php`, no simulables dentro del mismo proceso PHP) — probado en su forma operativa real: `environment_id` incorrecto → DENY |
| `wrong DB → DENY` | Misma vía: `environment_id` incorpora `DB_NAME` en su hash |
| `wrong environment_id → DENY` | Probado directamente |
| `invalid plan seal → DENY` | Plan alterado tras construirse (campo `created_at` mutado) → `plan_seal_intact` bloquea |
| `ERROR present → DENY` | Entrada con `action=ERROR` → `no_error_entries` bloquea con diagnóstico propio (bug real encontrado y corregido durante esta prueba, ver abajo) |
| `missing staged PDF → DENY` | `package_asset` apuntando a un hash no almacenado → `staged_media_assets_valid` bloquea |
| Aprobación PDF ya no vigente | Ruta legacy inexistente → `pdf_approvals_current` bloquea |
| Cero mutación | Conteo de posts/terms idéntico antes y después de construir el plan Y de ejecutar el pre-flight |

**Bug real encontrado y corregido** (no cosmético): la primera versión de
`preflight_full_local()` no incluía `'ERROR'` en su lista de acciones reconocidas, así que
una entrada con `action=ERROR` activaba la comprobación genérica "acción no reconocida" y el
bucle cortaba *antes* de llegar a contarla en la comprobación dedicada `no_error_entries`,
que entonces reportaba incorrectamente "0 entradas con ERROR" pese a que el plan sí bloqueaba
por otra vía. Corregido para que `ERROR` sea una acción reconocida que activa específicamente
su propio diagnóstico. Encontrado exactamente porque el test se ejecutó contra datos reales
del plan, no sólo contra una aserción hecha a medida.

**Segundo hallazgo, cosmético pero real**: los mensajes de detalle de varias comprobaciones
mostraban el texto de "por qué fallaría" incluso cuando la comprobación **pasaba** (p. ej.
"plan_seal_intact -- plan_hash no coincide..." mostrado junto a un ✅) — confuso para
cualquiera que leyera el reporte. Corregido: el detalle ahora sólo describe el problema
cuando la comprobación realmente falla.

## `tests/full-local-import-execution.php` (nuevo, 34 checks, fixtures sintéticos aislados)

Cada mutación usa una entidad `test:<uuid>` propia, nunca el catálogo real ni los 15 objetos
del ensayo subset; limpieza garantizada incluso si el archivo falla a mitad de camino.

| Caso pedido | Verificación |
|---|---|
| `full local valid → ALLOW` | Fixture sintética se crea correctamente, en borrador |
| Frase equivocada / vacía → DENY | Probado; frase subset contra el método full y viceversa, ambas rechazadas |
| `REVIEW row → zero mutation` | Fila REVIEW intercalada junto a una MIGRATE en el mismo lote: la MIGRATE se crea, la REVIEW no genera nada, `result=REVIEW`/`status=BLOCKED` |
| `SKIP row → zero mutation` | Igual, aislado |
| `CREATE row → executable` | Cubierto por el caso ALLOW |
| `MERGE row → executable` | Misma técnica sintética que ya usa el ensayo subset (dos claves de origen, una identidad) |
| `interrupted batch → resumable` | `$limit=2` sobre 5 entidades fuerza 3 llamadas; cursor persistido coincide exactamente con el punto de corte en cada una |
| `second execution → idempotent` | Reinvocar sobre un run ya `COMPLETE`: cero cambio en conteo de posts y en un hash de toda la tabla `options` |
| `source changed → conflict, no overwrite` | En realidad resuelve a `UPDATE` en sitio (destino intacto) — nunca un segundo objeto |
| `human-edited destination → conflict, no overwrite` | `CONFLICT`, la edición humana sobrevive intacta tras el intento |
| Fatal vs. por entidad | Un fallo inyectado con mensaje no catalogado: el lote continúa, la entidad queda `FAILED`. Un fallo inyectado con el código `PERMISSION_DENIED` (catalogado): el lote entero se aborta, el cursor no avanza sobre la entidad fallida ni sobre nada posterior |
| Backup automático | `backup-<run>.json` y `backup-uploads-<run>/` existen tras la primera llamada, antes verificado que no existían |
| Rollback sólo en fixtures | `rollback_created()` retira la entidad sintética; se confirma explícitamente que ninguna clave real (`sql:productos:`, `category:`, etc.) aparece en el resultado |

## Comandos ejecutados

```bash
php wordpress/wp-content/plugins/psindustrial-core/tests/smoke.php
php wordpress/wp-content/plugins/psindustrial-core/tests/importer.php
php wordpress/wp-content/plugins/psindustrial-core/tests/policy.php
php wordpress/wp-content/plugins/psindustrial-core/tests/pdf-approvals.php
php wordpress/wp-content/plugins/psindustrial-core/tests/runner-media-validation.php
php wordpress/wp-content/plugins/psindustrial-core/tests/editorial-decisions.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q05-pdf-resolution.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q01-content-ownership.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q04-static-products.php
php wordpress/wp-content/plugins/psindustrial-core/tests/run-snapshot-retention.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q07-landings.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q10-category-cleanup.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q11-missing-media.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q13-media-alias.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q03-multi-category.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q03-name-consolidation.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q03-brand-unresolved.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q03-kelley.php
php wordpress/wp-content/plugins/psindustrial-core/tests/full-local-import-preflight.php
php wordpress/wp-content/plugins/psindustrial-core/tests/full-local-import-execution.php
php -l <cada archivo PHP tocado o nuevo>
```

Ningún archivo ejecutó una importación real sobre el catálogo completo. La única ejecución
real fue la que `importer.php` ya hacía antes de esta fase (los 15 objetos del ensayo
subset, sin cambios de comportamiento).

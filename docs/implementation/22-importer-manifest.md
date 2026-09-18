# Manifest, identidad y journal

Se sigue ADR-009: **sin tabla personalizada**. El manifest completo y los logs viven en almacenamiento privado fuera del webroot/repositorio. Las options sólo guardan lock/checkpoints pequeños, autoload=false.

## Persistencia

- run-UUID.json: fuentes/hashes, decisiones normalizadas, plan_hash, entradas, cursor y resultados.
- identity-HASH.json: intención antes de crear, ID después de crear, snapshot previo, resultado y hash aplicado.
- log-UUID.jsonl: run, timestamp UTC, entity, action, result, mensaje reducido.
- asset-SHA256.bin: copia privada comprobada del binario; no attachment todavía.
- backup-UUID.json y backup-uploads-UUID/: snapshots privados del ensayo, excluidos de Git.
- psi_import_lock: owner y expires; psi_import_run_UUID: cursor/total/estado/fecha, sin HTML ni logs masivos.

Campos de manifest: manifest_version, source_namespace/source_type, source_key/entity_key, legacy_id/file/url, source_keys, content_type/target_type, action, wordpress_id, source_hash, decision_hash, last_applied_source_hash/target_hash tras aplicación, confidence, field_ownership, approval_ref, run_id, environment_id, status, migration_date/migrated_at, notes/dependencies. Fechas de aplicación de REVIEW son nulas. IDs son locales y no se trasladan a otro entorno.

Las entradas dry-run tienen PLANNED y planned_result; el run validado tiene VALIDATED. Aplicación: RUNNING → APPLIED/UNCHANGED/CONFLICT/FAILED; SKIP → SKIPPED; REVIEW → BLOCKED. COMPLETE significa cursor terminado, **no** que no haya REVIEW/errores ni que pueda publicarse.

## Metadatos privados

_psi_source_keys conserva namespaces (sql:productos:ID, category:ID, brand:ID, static:PHP, php:PHP, asset:ruta, file:ID). Entity_key es estable, no título/slug/hash de contenido. _psi_import_identity guarda entity_key y created_by_run; _psi_import_origin conserva archivo/URL/ID, aliases de binario y evidencia SEO; _psi_import_state guarda hashes, run y fecha. Además se usan _psi_source_hash, _psi_legacy_date, _psi_content_sha256 y _psi_original_name ya aprobados.

No se exponen en REST público ni en el editor común. Guard de capacidades para escritura/borrado de metadata privada. La consulta de identidad comprueba la clave delimitada dentro del array serializado mediante WP APIs; más de una coincidencia es conflicto. No se reutiliza contenido por título.

## Reejecución

Fuente y decisión iguales + snapshot destino intacto → UNCHANGED. Fuente distinta + destino intacto → UPDATE de objeto existente. Edición humana → CONFLICT del objeto entero (política deliberadamente más conservadora que merge por campo). Archivo binario cambiado → CONFLICT, nunca reemplazo silencioso. Desaparición de fuente no elimina destino.

Una fusión autorizada con un único ganador explícito para todos los campos conserva todas las claves fuente; las relaciones son las del payload aprobado. Ganadores mixtos quedan REVIEW hasta existir su payload editorial validado; no concatenar textos/medios ni resolver marcas por mayoría. El ensayo de MERGE es sintético, no se fusionó ningún grupo legacy.

Los reportes bajo docs contienen sólo resultados técnicos locales autorizados, nombres/URLs públicos y hashes; no son el journal completo ni backups. La copia privada es la autoridad para recuperación. No versionar mapas productivos/credenciales.

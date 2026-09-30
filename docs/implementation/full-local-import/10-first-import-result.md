# Primera ejecución real — FULL LOCAL RESOLVED-ONLY

Fecha: 2026-09-21. Rama `feature/review-resolution`. Entorno exclusivo:
local, `psindustrial_wp_dev`. Autorización explícita del usuario, alcance único:
local + FULL LOCAL RESOLVED-ONLY, confirmación `IMPORTAR FULL LOCAL RESUELTO`.
No staging, no producción, no FTP, no publicación.

## Identidad del plan

```
run_id:          2e0c1248-8d56-4d92-a33b-c17e37b2732e
plan_hash:       afb6f8d2adb2aae55c0357efc03a150d646c818b3c678bdc59a1ac7d0c7ccc00
environment_id:  76989ec9c283b0d2ce7c5b26a91ddee7b2c247efd5bed3f32e4663502a461efc
created_at:      2026-09-21T07:51:20+00:00
scope:           full
```

El plan se generó una sola vez y su `plan_hash` se verificó sin cambios entre el
pre-flight y el inicio de la ejecución (el runner lo revalida en cada lote como
parte de `Storage::locked()`/validación de plan).

## Pre-flight (14/14)

Las 14 verificaciones de `Runner::preflight_full_local()` pasaron sin excepción:
`plan_exists`, `plan_scope_full`, `plan_status_valid`, `environment_id_matches`,
`transform_version_matches`, `plan_seal_intact`, `manual_decisions_unchanged`,
`source_hashes_current`, `actions_within_known_set`, `no_error_entries`,
`review_counted`, `legacy_source_files_exist`, `staged_media_assets_valid`,
`pdf_approvals_current`. Ninguna mutó la base de datos (verificado antes/después
por conteo de `posts`/`terms`).

## Baseline autorizado vs plan real (sin forzar nada)

| | Autorizado | Real | |
|---|---:|---:|---|
| total | 2.399 | 2.399 | OK |
| UNCHANGED | 15 | 15 | OK |
| SKIP | 1.534 | 1.534 | OK |
| CREATE | 511 | 511 | OK |
| REVIEW | 339 | 339 | OK |
| ERROR | 0 | 0 | OK |
| MERGE (dentro de CREATE) | 49 | 49 | OK |

Coincidencia exacta. No hubo necesidad de detenerse antes de mutar la base.

## Backup automático

Confirmado en el primer lote (`cursor=0`, `status=VALIDATED`), antes de cualquier
escritura:

```
database_file: backup-2e0c1248-8d56-4d92-a33b-c17e37b2732e.json  (1.856.954 bytes)
uploads_dir:   backup-uploads-2e0c1248-8d56-4d92-a33b-c17e37b2732e/
taken_at:      2026-09-21T07:52:30+00:00
```

Archivo verificado físicamente en almacenamiento privado (fuera del repositorio,
fuera de `wp-content/uploads` público). Nunca se sube a Git.

## Ejecución por lotes

85 lotes de hasta 100 entidades, mecanismo ya implementado
(`Runner::batch_full_local_resolved_only()`), nunca una sola petición HTTP.
Tiempo total: 289,5 s. Cero errores fatales (`FATAL_ABORT`), cero violaciones del
invariante REVIEW/SKIP→sin `wordpress_id` (`CRITICAL_INVARIANT_VIOLATION`) — ambos
monitoreados y registrados lote a lote en un log independiente
(`execution-log.jsonl`, 85 eventos `BATCH_COMPLETE` + 1 `BACKUP_CONFIRMED`, 0
eventos de error).

`status=COMPLETE`, `cursor=2399/2399` al finalizar.

## Resultado final por estado de procesamiento

```
UNCHANGED  15    (idéntico al subset ya existente)
SKIPPED    1.534 (código interno; 0 objetos)
BLOCKED    339   (REVIEW; 0 objetos — ver 11-post-import-audit.md)
APPLIED    449   (CREATE/MERGE aplicados con éxito)
CONFLICT   10    (destino editado/parcial; conservado, no mutado)
FAILED     52    (error de entidad, no fatal; no mutó nada — ver abajo)
-----------------------------------------------------------------
TOTAL      2.399
```

`449 + 10 + 52 = 511`, exactamente el bucket CREATE autorizado. Ninguna entidad se
perdió ni se reclasificó fuera de su bucket original.

De las 449 `APPLIED`: 383 `MIGRATE`, 36 `MERGE`, 30 `CREATE_FROM_STATIC` (Q04).

## Los 10 CONFLICT

Todos con el mismo motivo estructural (`Identity::prediction()` conservador):
"Destino editado, identidad parcial o versión binaria cambiada: conservar y
resolver." Ninguno mutó nada — se registran como REVIEW y el runner continuó con
el resto, exactamente como especifica el diseño.

```
category:37, category:38
php:cortinas-enrollables-de-aluminio.php, php:index-estatico.php,
php:index-resp.php, php:index.php, php:politica-privacidad.php,
php:puertas-de-garaje-aisladas.php, php:puertas-enrollables-de-garage.php,
php:tiras-plasticas-hawaianas.php
```

`category:37`/`category:38` dependen de `category:7` u otra relación con un
destino ya parcialmente editado. Las 8 páginas `php:` — 4 de ellas son landings
Q07 (ver 11-post-import-audit.md, sección Pages) — comparten la misma causa:
existe ya un objeto WordPress en un estado que el importador no puede confirmar
como "seguro de sobrescribir" (edición humana parcial o binario distinto al
esperado). Correcto y conservador; requiere revisión humana antes de reintentar.

## Los 52 FAILED — causa raíz completa, sin misterios pendientes

Ningún FAILED es un error genérico: los 52 se explican al 100 % por exactamente
dos causas técnicas ya diagnosticadas, más un efecto de cascada sobre productos
que dependían de esos mismos activos. Ninguna mutó la base de datos (el fallo
ocurre antes de cualquier escritura, o el escritor de WordPress rechaza la
operación y no persiste nada).

### Causa A — `MEDIA_CHANGED_REPLAN` (14 casos, todos `media`)

Bug preexistente (no introducido en esta fase) en
`Runner::process_entry()` (`migration/Runner.php:246`). Para verificar que un
archivo de medios no cambió desde el plan, compara `$e['data']['sha256']`
(el hash **ya sobrescrito** por `Planner::build()` con el hash **saneado/staged**
del PDF, cuando aplica Grupo A de `PdfApprovals`) contra el hash del archivo
**original** en `/legacy/public`. Para cualquier PDF saneado (Grupo A: bytes
realmente reescritos para remover contenido activo), ambos hashes **difieren por
diseño** — el saneado cambia los bytes intencionalmente. La comparación correcta
debería usar el hash original conservado en `$e['row']['sha256']`, no el hash
saneado de `$e['data']['sha256']`.

Efecto: **fail-closed** (más conservador de lo necesario), nunca fail-open. Ningún
PDF no aprobado se aceptó por este bug; al contrario, PDFs ya aprobados y saneados
se rechazaron de más.

### Causa B — `MEDIA_SIDELOAD_FAILED` (13 casos, todos `media`)

Bug preexistente y arquitectural, no introducido en esta fase. WordPress registra
dos hooks propios (`wp_handle_upload_prefilter`, `wp_handle_sideload_prefilter`)
sobre `Media::upload()` (`includes/Media.php`), que **revalida el archivo de forma
independiente** llamando a `Media::file_valid()` en crudo — sin ningún
conocimiento de las excepciones ya aprobadas en `PdfApprovals` (Grupo B: falsos
positivos del escáner, bytes sin modificar, aprobados explícitamente por hash +
revisión humana documentada en `pdf-approvals.json`).

`Runner::media_is_valid()` sí consulta `PdfApprovals` **antes** de intentar el
sideload y aprueba correctamente el archivo — pero WordPress vuelve a
revalidarlo internamente durante `media_handle_sideload()`, sin esa misma
excepción disponible, y lo rechaza por segunda vez. Reproducido de forma aislada
y confirmada la causa exacta llamando directamente a `media_handle_sideload()`
sobre `fichas/Clopay-3720-07.pdf` (aprobación Grupo B confirmada,
`sha256 === staged_sha256`): `WP_Error{code: 'upload_error', message: 'Archivo no
permitido...'}`.

Efecto: igual que la causa A, **fail-closed**. Ningún archivo no aprobado pasó
este filtro; al contrario, se rechazaron de más archivos ya aprobados por una
revisión humana explícita y auditable.

### Causa C — cascada de dependencias (25 casos adicionales, `product`/1
`static_product`)

**No es un tercer bug.** `process_entry()` valida `$e['dependencies']` antes de
llamar a `apply()` (`Runner.php:249-252`): si un activo/categoría del que depende
un producto no tiene todavía `wordpress_id` (porque falló por causa A/B, o está
en CONFLICT), lanza `DEPENDENCY_NOT_APPLIED:<clave>`. Verificado por
correlación exhaustiva: **el 100 % de los 25 productos/fichas fallidos dependen,
sin excepción, de un activo de la causa A, de la causa B, o de una categoría en
CONFLICT** (`category:37`/`38`) — no hay ni un solo caso sin esa correlación.

El mensaje registrado en el plan es el genérico `OBJECT_OPERATION_FAILED`, no
`DEPENDENCY_NOT_APPLIED:...` — esto es intencional y correcto: `Runner::safe_error()`
sólo deja pasar mensajes que coincidan con `/^[A-Z0-9_: .-]+$/D` (mayúsculas,
dígitos, `_:.- ` únicamente); la clave de dependencia interpolada
(`asset:fichas/puerta-424.pdf`, con minúsculas y `/`) no cumple ese patrón, así
que el saneador la sustituye por el mensaje genérico antes de guardarla o
mostrarla — exactamente su propósito (nunca filtrar texto dinámico sin controlar
a un log o a la UI). La causa real se reconstruyó cruzando cada producto fallido
contra las claves de dependencia declaradas en su propia entrada del plan, no
contra el mensaje guardado.

Entre ellos: `sql:productos:24` es la consolidación Kelley 24+139 aprobada en Q03
(ver [25-q03-kelley.md](../review-resolution/implementation/25-q03-kelley.md)) —
bloqueada por la causa B sobre
`Kelley-Hydraulic-Dock-Leveler-Brochure_web.pdf`. **`sql:productos:3` (el Kelley
excluido explícitamente por Q03) no se tocó ni se intentó crear** — confirmado
directamente contra la base: cero objetos con `legacy_id=3`.

### Ningún fix se aplicó en esta sesión

Ambos bugs (A y B) son reales, acotados, con causa raíz identificada con
precisión y una corrección candidata clara en cada caso — pero modificar código
de validación de seguridad de medios en caliente, en medio de o inmediatamente
después de una ejecución real autorizada, sin un ciclo de revisión separado,
sería precisamente el tipo de decisión apresurada que este proyecto pide evitar.
Ninguno de los dos bugs compromete la seguridad ya validada (ambos fallan hacia
el lado conservador); ninguno se intentó resolver de forma improvisada. Quedan
documentados aquí para una corrección deliberada, revisada por separado, seguida
de un reintento explícito sobre las 52 entidades (y las que dependen de ellas)
usando el mismo mecanismo ya probado — nunca edición manual de SQL.

## Verificación de integridad

- `/legacy`: sin cambios (`git status` limpio para ese directorio).
- Git: sólo se modificaron/crearon reportes de test (`docs/implementation/importer-reports/*.json`)
  de una suite de regresión ejecutada aparte; cero cambios de código fuente
  (`Runner.php`, `Media.php`, etc. idénticos a antes de la ejecución).
- Rama: `feature/review-resolution`. Sin commit, sin push.
- Versión del plugin: sigue en `0.4.0` (sin incrementar).

Continúa en [11-post-import-audit.md](11-post-import-audit.md).

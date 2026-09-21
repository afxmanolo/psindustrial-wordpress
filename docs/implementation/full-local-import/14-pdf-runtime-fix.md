# Correcciones aplicadas — Bug 1 y Bug 2

Continúa de [13-pdf-runtime-failures.md](13-pdf-runtime-failures.md). Ambas correcciones
están en `wordpress/wp-content/plugins/psindustrial-core/migration/Runner.php`. Ningún otro
archivo de producción se modificó. `Media::file_valid()`/`Media::upload()`
(`includes/Media.php`) permanecen **sin cambios** — ninguna validación general de subida se
relajó. Versión del plugin sigue en `0.4.0`.

## Bug 1 — separación explícita de las dos integridades

Nuevo método puro, sin efectos secundarios, extraído exactamente con el mismo criterio que
ya se usó para `media_is_valid()` (testeable de forma aislada vía Reflection):

```php
private static function media_integrity( array $e ): array {
 $originalOk = true;
 foreach ( array_merge( array( $e['legacy_file'] ), $e['binary_aliases'] ?? array() ) as $path ) {
  if ( ! hash_equals( $e['row']['sha256'], hash_file( 'sha256', Sources::safe( Storage::project() . '/legacy/public', $path ) ) ) ) { $originalOk = false; break; }
 }
 $stagedOk = hash_equals( $e['data']['sha256'], hash_file( 'sha256', Storage::path( $e['data']['package_asset'] ) ) );
 return array( 'original_source_integrity' => $originalOk, 'staged_artifact_integrity' => $stagedOk );
}
```

- **`original_source_integrity`**: cada ruta legacy (principal + alias binarios) contra
  `$e['row']['sha256']` — el hash **original** que `Planner::build()` registró desde
  `media-master.csv` y ya verificó una vez contra este mismo archivo al construir el plan.
  Nunca contra el hash staged/saneado.
- **`staged_artifact_integrity`**: el artefacto que realmente se subirá (el sustituto
  saneado para Grupo A, o los bytes originales para todo lo demás) contra su **propio** hash
  staged en almacenamiento privado. Un sustituto Grupo A difiere legítimamente del legacy
  original — eso es diseño, nunca deriva — y nunca se compara contra el hash original arriba.

`process_entry()` ahora exige ambas:

```php
if ( 'media' === $e['source_type'] ) {
 $integrity = self::media_integrity( $e );
 if ( ! $integrity['original_source_integrity'] || ! $integrity['staged_artifact_integrity'] ) { throw new \RuntimeException( 'MEDIA_CHANGED_REPLAN' ); }
}
```

Mismo código de excepción (`MEDIA_CHANGED_REPLAN`) para ambos fallos — el objetivo era
separar las **comprobaciones**, no inventar una taxonomía de errores nueva; ambos siguen
siendo `BLOCK/REPLAN` no fatal, entity-level, exactamente como antes.

## Bug 2 — bypass aislado, sólo para la operación concreta

Nuevo método puro (excepto que no toca disco ni red — sólo inspecciona `$file`/re-verifica
hash+aprobación):

```php
private static function approved_sideload_override( array $file, string $tmp, string $source, array $d ): array {
 if ( ( $file['tmp_name'] ?? '' ) !== $tmp || empty( $file['error'] ) ) { return $file; }
 if ( ! hash_equals( $d['sha256'], hash_file( 'sha256', $source ) ) ) { return $file; }
 if ( 'application/pdf' !== $d['mime'] || ! PdfApprovals::isApprovedFalsePositive( $d['path'] ?? '', hash_file( 'sha256', $source ) ) ) { return $file; }
 $file['error'] = ''; return $file;
}
```

Cuatro comprobaciones, **todas** deben cumplirse antes de limpiar el error:

1. **Mismo `tmp_name` exacto** — nunca toca ninguna otra subida en curso, en esta o cualquier
   otra petición.
2. **Había un error que limpiar** — si WordPress no rechazó nada, no hay nada que hacer.
3. **El hash actual del archivo sigue coincidiendo** con lo que `Runner::media_is_valid()`
   ya verificó momentos antes.
4. **Aprobación Grupo B exacta** por `path`+`sha256` — nunca Grupo A (que no necesita esto:
   el sustituto saneado ya pasa `Media::file_valid()` por sí solo).

Instalación, alcance y retirada — en `media()`, alrededor únicamente de la llamada a
`media_handle_sideload()`:

```php
$override = static fn( array $file ): array => self::approved_sideload_override( $file, $tmp, $source, $d );
add_filter( 'wp_handle_sideload_prefilter', $override, 20 );
try {
 $id = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), 0, null, array( 'post_title' => sanitize_text_field( $d['name'] ) ) );
} finally {
 remove_filter( 'wp_handle_sideload_prefilter', $override, 20 );
}
```

- Prioridad `20` (después de la `10` por defecto de `Media::upload()`) — el override sólo
  **reacciona** a un rechazo que `Media::upload()` ya produjo; nunca se ejecuta primero,
  nunca oculta `Media::upload()` de nada.
- `try`/`finally` — el filtro se retira incluso si el sideload falla o lanza.
- `Media::upload()` sigue registrado, sin modificar, en ambos hooks
  (`wp_handle_upload_prefilter`, `wp_handle_sideload_prefilter`) para **cualquier otra**
  subida de la instalación — el bypass nunca es global.

## Por qué no se tocó `Identity::prediction()`

Los 13 casos Grupo B dejan un *ledger* `INTENT` huérfano que hace que un plan reconstruido
clasifique esa entidad como `CONFLICT`, no `CREATE` — ver
[13-pdf-runtime-failures.md](13-pdf-runtime-failures.md#camino-1-intento-fallido-propio-verificado-en-los-13-direct_pdf_b).
Se consideró — y se descartó — modificar `Identity::prediction()` para reconocer este caso
directamente: habría sido un tercer cambio de producción, en un método compartido por **cada**
tipo de entidad en **cada** ruta de ejecución, fuera del alcance estricto de "corrige
exclusivamente los dos bugs PDF". En su lugar, el mecanismo de retry
([15-retry-model.md](15-retry-model.md)) resuelve esta distinción **por su cuenta**,
re-verificando el *ledger* real (no confía en su propia afirmación: también reconfirma que
no existe ningún objeto WordPress) antes de decidir que es seguro reintentar — sin tocar
`Identity.php` en absoluto.

## Verificación

- `php -l` limpio en `Runner.php`.
- Suite completa de regresión (smoke, importer, policy, pdf-approvals,
  runner-media-validation, editorial-decisions, q01/q03×4/q04/q05/q07/q10/q11/q13,
  full-local-import-preflight, full-local-import-execution): **sin regresiones**.
- Nuevos tests: [`tests/pdf-runtime-fix.php`](../../../../wordpress/wp-content/plugins/psindustrial-core/tests/pdf-runtime-fix.php)
  — ver detalle de casos en el propio archivo y resultado en
  `docs/implementation/importer-reports/pdf-runtime-fix-tests.json`.
- Ninguna llamada a `media_handle_sideload()`/`apply()` real durante los tests — Reflection
  sobre los métodos puros únicamente.

Continúa en [15-retry-model.md](15-retry-model.md).

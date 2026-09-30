# Ciclo de vida de retención — corrección del diseño

Continúa de [19-recovery-preflight.md](19-recovery-preflight.md). Cubre el rediseño de
`Storage::retain_recent_runs()`/`Storage::run_protected()` en respuesta al incidente de
[17-run-snapshot-incident.md](17-run-snapshot-incident.md). Todo lo descrito aquí opera
exclusivamente sobre el entorno local y el almacenamiento privado
(`psindustrial-importer-private/`) — en ningún momento sobre un entorno de producción; ese
directorio nunca se sincroniza ni se despliega a Hepsia.

## Antes: un único estado protegido

```php
private static function run_in_progress( string $path ): bool {
 ...
 return is_array( $data ) && 'RUNNING' === ( $data['status'] ?? null );
}
```

Sólo `status=RUNNING` impedía la poda. Un plan `COMPLETE` — con `FAILED`/`CONFLICT` sin
resolver, o con un recovery abierto sobre él — competía por los 30 cupos como cualquier
plan `VALIDATED` recién construido y nunca ejecutado.

## Ahora: `Storage::run_protected()`, cuatro señales

```php
private static function run_protected( string $path ): bool {
 ...
 if ( ! empty( $data['closed_at'] ) ) { return false; }
 if ( 'RUNNING' === ( $data['status'] ?? null ) ) { return true; }
 if ( ! empty( $data['recovery_open'] ) ) { return true; }
 if ( 'COMPLETE' === ( $data['status'] ?? null ) ) {
  foreach ( $data['results'] ?? array() as $r ) { if ( in_array( $r['status'] ?? null, array( 'FAILED','CONFLICT' ), true ) ) { return true; } }
 }
 return false;
}
```

Evaluado en este orden exacto:

1. **`closed_at` presente → NUNCA protegido**, incondicionalmente. Es la única salida —
   ver más abajo.
2. **`RUNNING` → protegido** (el comportamiento original, generalizado, no eliminado).
3. **`recovery_open` verdadero → protegido** — un plan de recuperación mismo, o cualquier
   run marcado explícitamente como con recuperación abierta sobre él.
4. **`COMPLETE` con algún `results[].status` en `{FAILED, CONFLICT}` → protegido** — el
   caso exacto que causó el incidente.

Nunca lanza; un archivo no-JSON o sin `status` sigue sin protección por defecto — mismo
comportamiento conservador que antes.

## Cierre explícito — la única salida

```php
public static function close_run( string $run, string $confirmation ): array {
 if ( 'CERRAR RUN RESUELTO' !== $confirmation ) { throw new \RuntimeException( 'EXPLICIT_CONFIRMATION_REQUIRED' ); }
 ...
 $plan['closed_at'] = gmdate( 'c' ); $plan['recovery_open'] = false;
 ...
}
```

Un run protegido **nunca** deja de estarlo por el simple paso del tiempo, ni porque una
reconstrucción posterior del plan ya no muestre las mismas fallas (el `status`/`results`
del run ya escrito no cambian solos). La única vía es `closed_at`, puesto explícitamente,
con confirmación humana explícita (`'CERRAR RUN RESUELTO'`, distinta de cualquier otra frase
del sistema) — nunca inferido automáticamente por este mecanismo. La condición editorial de
"cuándo es correcto cerrar" (todos los retryables resueltos, conflictos restantes
transferidos a REVIEW/decisión editorial, auditoría cerrada) es un juicio humano que
`close_run()` registra, no verifica — deliberadamente: automatizar esa verificación sería
inventar una noción de "resuelto" sin evidencia, exactamente lo que este proyecto evita.

Ningún run se cerró en esta fase — no había nada que cerrar todavía (nada se ejecutó).

## No confundir "protegido" con "activo"

Un plan de recuperación protegido (`recovery_open=true`, `status=VALIDATED`) no está
ejecutándose — sólo existe y espera autorización. La protección cubre *evidencia
potencialmente necesaria*, no *trabajo en curso*; por eso generaliza más allá del antiguo
`RUNNING`.

## Verificación

[`tests/run-snapshot-retention.php`](../../../../wordpress/wp-content/plugins/psindustrial-core/tests/run-snapshot-retention.php) —
55 comprobaciones (20 nuevas sobre las 35 preexistentes), cubriendo exactamente los siete
escenarios pedidos: `RUNNING` protegido (preexistente); `COMPLETE` con fallo retryable sin
resolver protegido (el caso del incidente); `COMPLETE` con conflicto sin resolver protegido;
`CLOSED`/`closed_at` puede podarse aunque conserve `FAILED`; un protegido mucho más viejo
que los 30 normales sobrevive sin ocupar un cupo; 100 runs normales + 5 protegidos conservan
exactamente 30 normales más los 5 protegidos íntegros; `recovery_open` protegido
independientemente de `status`/`results`. Más
[`tests/recovery-model.php`](../../../../wordpress/wp-content/plugins/psindustrial-core/tests/recovery-model.php) —
verifica en vivo que el plan de recuperación real sobrevive tres reconstrucciones adicionales
del plan completo, y que `close_run()` real (sobre una copia, con confirmación correcta)
nunca toca WordPress.

Suite de regresión completa (24 archivos, incluidos los 3 nuevos de esta fase): sin
regresiones.

## Alcance

Esta corrección opera exclusivamente sobre `psindustrial-importer-private/`, un directorio
privado fuera del webroot en el entorno **local**. No se desplegó nada a Hepsia, no se tocó
staging ni producción, y este mecanismo de retención no tiene ninguna contraparte en el
servidor de producción (que no ejecuta este importador).

## Resumen ejecutivo de la fase completa

Ver el mensaje de cierre de esta sesión para el resumen de los 20 puntos de verificación.

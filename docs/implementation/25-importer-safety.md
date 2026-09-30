# Seguridad e integridad

Destino único: local + DB psindustrial_wp_dev + host MySQL localhost/127.0.0.1. Todas las operaciones WordPress usan su conexión actual; no se abre conexión al legacy. SQL de catálogo es lectura/tokenización, nunca query/eval. No servicios externos ni descarga remota de medios.

- Capability administrativa y psi_manage_migration + nonce + POST; confirmar escrituras. Dry-run sin mutación DB de contenido demostrado por conteos/hash de options.
- Paths fuente relativos permitidos, realpath dentro de raíz, rechazo de traversal, rutas absolutas, colon, backslash, NUL y symlinks. No incluyen PHP legado.
- JSON con profundidad limitada/excepciones, CSV hasta20MB; headers únicos/columnas consistentes. SQL hasta20MB, sólo INSERT literales de catálogo. No deserializar payloads PHP externos.
- MIME real, firma, dimensiones/tamaño y extensión. Rechaza dobles extensiones ejecutables. Copia privada SHA-256; media_handle_sideload valida de nuevo y genera metadata WordPress.
- PDFs hasta20MB con controles existentes de patrones activos; no antivirus ni análisis exhaustivo. No se relaja el guard por ser una importación.
- Extensión ausente: sólo binario confirmado, copia con extensión correspondiente; nombre original/ruta/hash conservados. Fuente no se renombra.
- Identidad duplicada o slug ocupado por otro objeto → CONFLICT, no sufijo/merge silencioso. Edición humana bloquea objeto completo.
- Plan sellado y hashes de entradas/decisiones se revisan antes del lote; bytes de cada medio y PHP relevante se revalidan antes de actuar.
- Snapshots DB/uploads y logs fuera del webroot. Errores administrativos reducidos a códigos, no mensajes arbitrarios con paths/secretos. No backups descargables desde el panel.

Las correcciones a Media::referenced y Roles::map son defensivas y puntuales; regresión del modelo sigue pasando. El fatal detectado durante una prueba se documentó y corrigió; se retiraron sólo attachments157–159 y usuarios19–20 de ese fixture identificado. No se borraron registros del usuario ni el historial del log.

No se modificaron core, wp-config, .htaccess, configuración global, credenciales o legacy. No se puede afirmar que uploads sea no ejecutable en el hosting: requiere prueba operacional antes de despliegue. Esta fase sólo acepta bytes de los tipos permitidos, no sube ejecutables de prueba al servidor ni actúa remotamente.

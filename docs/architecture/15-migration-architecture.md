# Arquitectura del importador y manifest

No se ejecuta ni implementa aquí. El diseño tiene dos pasos: extracción/planificación **offline** y aplicación **dentro de WordPress mediante APIs**. No conectar el sitio público al MySQL viejo, no incluir PHP legacy, no ejecutar su dump. Entradas congeladas: SQL de catálogo, fuentes PHP, maestros canónicos, decisiones editoriales aprobadas y paquete de medios permitido.

## Fuente de verdad y acciones

Los CSV son evidencia, no permisos automáticos. Una decisión por campo indica source_ref, valor, confianza y aprobación. Prioridad: decisión editorial documentada → evidencia visible coherente → dato SQL no contradictorio → inferencia revisada. Ninguna reparación se basa sólo en nombre o hash de PDF.

MIGRATE, MERGE, CREATE_FROM_STATIC, REVIEW y SKIP son acciones del plan, distintas de estado de ejecución y post_status. MERGE necesita ganador por campo, unión de relaciones/orden y propietarios de URLs. REVIEW no produce publicación ni desaparición de URL. SKIP requiere razón y, para contenido/medio público, aprobación de exclusión. Nunca convertir PROBABLY_NOT_REQUIRED o UNKNOWN directamente en borrado.

## Manifest versionado

Campos obligatorios por decisión: manifest_version, source_namespace, legacy_id (opcional), legacy_file (opcional), source_key, entity_key estable, content_type, action, target_type, wordpress_id por entorno, environment_id, source_hash, decision_hash, last_applied_source_hash, target_hash, field_ownership, status, migration_date UTC, run_id, approval_ref, notes y relaciones por entity_key.

source_key ejemplos conceptuales: sql:productos:ID; php:ruta; file:ID; asset:ruta. No usar ID SQL sin namespace. entity_key es identificador editorial estable asignado una vez, no slug mutable ni hash del contenido. MERGE permite varias source_keys a una entity_key; un split explícito usa subclaves de fuente. wordpress_id siempre puede diferir entre local/staging/producción. source_hash incluye bytes relevantes y versiones de transformación; decision_hash cambia si se aprueba otra relación.

Estados de ejecución: PLANNED, VALIDATED, RUNNING, APPLIED, UNCHANGED, CONFLICT, FAILED, SKIPPED. REVIEW permanece PLANNED/BLOCKED en reporte (BLOCKED como resultado de dependencia, no post_status). Un manifest no se considera completo por haber creado posts si faltan relaciones, medios o SEO.

Manifest aprobado sin secretos y hashes de fuentes puede versionarse bajo docs/migration; paquetes con contenido privado/dumps, logs y mapas de IDs por entorno se almacenan fuera del repositorio y fuera del webroot. El CSV final-map de esta fase es arquitectura, carece de autorización y no contiene IDs WP reales.

## Orden de ejecución

1. Preflight: versiones PHP/WP/plugin, snapshot DB/uploads, permisos, espacio/tamaño, integridad de paquete, dry-run sin escrituras, colisiones de rutas/keys y cobertura de fuentes.
2. Medios seleccionados y términos (padres antes de hijos); logos se enlazan cuando existe attachment. Términos dudosos no se hacen públicos.
3. Posts/Pages en draft, con entity_key; contenido extraído y saneado, sin scripts/header/footer del legacy.
4. Relaciones: categorías, marca, galería/PDF/videos, bloques de colecciones, menús y links internos convertidos por claves.
5. SEO mediante adaptador probado; rutas primarias/aliases en registro inactivo.
6. Validación de diferencias por objeto y URL; revisión explícita de estado público; activación del conjunto de rutas/publicaciones sólo con dependencias resueltas.
7. Reporte de cobertura, huérfanos, skipped y errores; exportar manifest resultante y backup post-run.

No transacción gigante para todo el sitio. Escrituras pequeñas por objeto/lote; productos con dependencias faltantes quedan draft. Una importación parcialmente exitosa no habilita automáticamente todo el catálogo ni envía correo.

## Idempotencia y conflictos

Buscar entity_key en meta/term meta antes de crear, no título/slug. Las source_keys se conservan aunque se fusionen. source_hash + decision_hash iguales y destino intacto → UNCHANGED. Fuente cambia y destino no fue editado → actualización de campos propiedad del importador. Destino cambia editorialmente desde última aplicación → CONFLICT por campo; conservar edición humana, no sobrescribir ni añadir copia.

Medio con hash ya mapeado se reutiliza si política aprobada; bytes nuevos crean versión, no reemplazo silencioso. Desaparición de fuente no implica borrado. Merges nunca se infieren en reejecución.

Bloqueo único por run con option creada de forma exclusiva, propietario y expiración/heartbeat. Sólo un escritor; reserva de entity_key antes de crear y journal de intención/resultado. Un fallo entre crear objeto y guardar clave puede dejar objeto parcial: recuperación inspecciona journal/objeto/slug técnico de reserva y bloquea si no identifica inequívocamente; no reinsertar por ceguera. Prueba de fallo en cada frontera requerida. Idempotencia es objetivo verificado, no promesa de transacción entre filesystem y MySQL.

Metadatos privados source_keys/hash y mapeo del manifest permiten reconstruir correspondencias; options guardan sólo lock/checkpoint/reservas acotados, no HTML ni miles de logs. Sin tabla personalizada.

## Ejecución sin SSH

Mismo runner invocable por panel de Administrator y, opcionalmente en local, WP-CLI; CLI nunca requisito de producción. Paquete precompuesto con JSON validado y assets, sin scripts/dump SQL ejecutable. Subida autenticada o FTP a directorio privado verificado. Rechazar traversal, symlinks, zip bombs y archivos ejecutables; límite total/por archivo y SHA-256.

Panel inicia lotes de hasta25 objetos o5 medios, límite aproximado5s por petición, checkpoint y botón reanudar; el tamaño se reduce ante límites del host. No depender de sesión abierta ni WP-Cron para completar, ni de memoria2GB/timeout1h heredados. Constante privada habilita temporalmente importación en producción; cada petición verifica nonce/capacidad/lock/run.

Si no hay almacenamiento privado con denegación HTTP demostrable, no subir paquetes/dumps a uploads «ocultos»: bloquea importación hasta disponer de un canal seguro. Reportes descargables sólo autenticados, sin datos personales/secretos. Logs registran keys, hashes, acción, errores reducidos y tiempos.

## Rollback y aceptación

Snapshot DB + uploads + compatibilidad + release anterior antes de operación mutante. Manifest guarda objetos creados/modificados y valores previos de campos controlados; rollback selectivo sólo si target_hash coincide con última escritura, nunca borrar edición posterior. Recuperación completa utiliza snapshot con ventana de congelación, no pretende deshacer emails ni cambios externos.

Ensayo dos veces: segunda ejecución no crea entidades/attachments/rutas duplicadas. Probar fuente modificada, edición humana, merge aprobado, corte de red, falla upload, ruta en conflicto, falta de PDF y reanudación. Toda URL requerida recibe dueño/respuesta esperada; no exigir número de productos final165 o77.

# Ambientes, Git y operación sin SSH

## Flujo

LOCAL (Laragon, PHP8.4) → GitHub revisión/artefacto → STAGING (Hepsia) → PRODUCCIÓN (Hepsia). Es flujo de **código y plan aprobado**, no sincronización automática de bases de datos.

Se valida main→producción, develop→staging y feature/*→trabajo según AGENTS.md. PR a develop, pruebas en staging, PR/release a main y aprobación de despliegue. No trabajar directamente en main. Hotfix excepcional desde main, pruebas, merge a main y retorno a develop. Etiquetas de release y checksum del artefacto; no ajustar prefijos sin motivo.

## Responsabilidades y diferencias

| Elemento | Local | Staging | Producción |
|---|---|---|---|
| Código propio | Desarrollo/test | Mismo artefacto candidato | Artefacto aprobado, sin editar por FTP a mano |
| PHP |8.4 con extensiones | Misma minor8.4 probada | Misma combinación aprobada |
| WP/Yoast | Versiones fijadas en manifiesto de release | Validación de actualización | Actualización controlada |
| DB | Datos de ensayo privados | Catálogo de validación aislado | Contenido editorial vivo |
| uploads | Sólo medios necesarios para ensayo | Paquete permitido | Biblioteca operativa + compatibilidad |
| Hostnames/salts/DB/SMTP | Configuración privada | Credenciales propias | Credenciales propias rotadas |
| Correo | Captura local | Buzón de pruebas/deny transporte real | SMTP autorizado probado |
| Indexación | No público | Autenticación + noindex | Según plan SEO |
| Debug | Logs privados | Logs privados, display off | display off, diagnóstico acotado |
| Importador | Habilitable | Habilitable controlado | Deshabilitado salvo ventana intencional |

WordPress estable mantenido compatible con PHP8.4 se fijará al inicio de implementación y se probará con plugins/tema. No tomar lint del legacy como prueba de compatibilidad del nuevo sitio. [Matriz oficial PHP/WP](https://make.wordpress.org/core/handbook/references/php-compatibility-and-wordpress-versions/), [requisitos](https://wordpress.org/about/requirements/). Confirmar MySQL/MariaDB, HTTPS, mbstring/Fileinfo/GD o Imagick, permisos, memoria, tamaño de uploads, cron, rewrite y TLS con proveedor; no presumir prestaciones de Hepsia por su nombre.

## Qué se versiona

Theme/core propios y sus fuentes, documentación, pruebas, manifest de decisiones sin secretos, inventario de versiones/checksums/licencias y procedimiento de empaquetado. WordPress core y plugins externos se obtienen de distribución oficial en versión fijada, no se editan ni se desarrollan como forks. El artefacto de despliegue debe incluir los archivos de runtime compilados y dependencias permitidas; no requiere Composer/Node/SSH en el host.

Nunca versionar wp-config.php productivo, .env, passwords/keys/FTP, salts, dumps, backups, uploads masivos, logs, paquetes privados ni mapas con datos personales. Las credenciales de despliegue no forman parte del ZIP ni de GitHub público.

## DB y uploads

Primera implantación: WordPress nuevo en DB/raíz separadas, importador aprobado aplicado en destino, sin editar la base legacy. No empujar una DB local directamente a producción. Después del corte, producción es fuente editorial; no sobrescribir con staging. Para pruebas, copia de producción hacia staging sólo autorizada/saneada y con correo desactivado, dominio/configuración adaptados sin sustituciones SQL que rompan serialización.

Uploads se trasladan selectivamente por paquete/hash y mediante APIs; IDs se resuelven por entorno. No sincronización bidireccional que borre archivos. Recursos de compatibilidad con rutas legacy se empaquetan por whitelist, sin copiar system/*.php ni dump público. Backup de uploads no es Git.

## Deploy/actualizaciones

Producción sólo tiene admin/FTP: no depender de git pull, WP-CLI, symlinks, cron shell ni builds remotos. Preparar ZIP de plugin/tema y paquete de recursos localmente; desplegar vía administrador o transferencia del artefacto. Usar FTPS si está disponible; SFTP sólo si realmente se ofrece, no se presupone SSH. FTP sin cifrar no es canal aceptado para enviar secretos; resolver transporte seguro con proveedor antes de corte.

Sin atomicidad de carpetas garantizada: ventana de mantenimiento, backup, paquete completo, verificación checksums/versión y smoke test; rollback con ZIP anterior/backup. Evaluar cambio de document root desde panel para corte; si no existe, documentar procedimiento validado de transferencia/renombrado en mantenimiento. No servir simultáneamente legacy y WP de modo que .php se ejecute fuera del mapa.

Core y Yoast: actualizaciones de seguridad con backup, ensayo y control de compatibilidad; mayores/funcionales pasan staging. No desactivar indefinidamente parches por comodidad. PHP se actualiza desde panel con prueba de reversión. Cambios de DB propios versionados y aditivos, sin cambios de esquema legacy. No autoactualizar código propio desde rama mutable.

Manual oficial de [actualización](https://wordpress.org/documentation/article/updating-wordpress/) y [transferencia FTP](https://developer.wordpress.org/advanced-administration/upgrade/ftp/). La CI/CD futura puede producir artefactos y reportes, pero no se configura aquí.

## Backups y gates

Antes de release/importación/update: DB, uploads/compatibilidad, plugins/tema y configuración privada. Objetivo inicial: backup diario,7 diarios +4 semanales, copia externa cifrada; RPO24h y restauración dentro de una jornada como objetivo sujeto a ensayo del host. No afirmar que el plan contratado lo incluye. Probar restauración antes de corte.

Bloquean despliegue: PHP8.4 y extensiones disponibles, transporte privado, routing .php/PDF, almacenamiento seguro de paquete, backup/restauración, HTTPS, SMTP, autenticación de staging y acceso operativo al panel. No bloquean escribir el modelo en una fase futura autorizada.

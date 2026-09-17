# Entorno local
Proyecto: `C:\laragon\www\psindustrial-wordpress`. Document root de WordPress: subcarpeta `wordpress`; la URL no apunta a la raíz del proyecto.

| Elemento | Verificado |
|---|---|
| PHP CLI y web | 8.4.24 |
| Ejecutable CLI | C:\laragon\bin\php\php-8.4.24-nts-Win32-vs17-x64\php.exe |
| Servidor web | Apache 2.4.54 con mod_fcgid |
| MySQL | 8.0.30, iniciado manualmente por el usuario |
| Base nueva | psindustrial_wp_dev |
| WordPress | 7.1 estable, paquete oficial |
| Extensiones | mysqli, fileinfo, gd, intl, mbstring, openssl, curl, zip, XML disponibles |
| Idioma / zona | es_ES / America/Mexico_City |

Se verificó la conexión antes de instalar. La base nueva estaba vacía. No se conectó WordPress a la base legacy. No se modificó la configuración global de Windows ni Laragon.

## Configuración privada
`wordpress/wp-config.php` está ignorado. Mantiene credenciales locales, salts aleatorios y configuración específica; no copiarlo a staging/producción. No guardar secretos en docs, AGENTS, README o archivos versionados.

Configuración local: WP_ENVIRONMENT_TYPE=local; WP_DEBUG=true; WP_DEBUG_DISPLAY=false; WP_DEBUG_LOG apunta a `wordpress/.local/debug.log`; SCRIPT_DEBUG=true; DISALLOW_FILE_EDIT=true. WP_HOME/WP_SITEURL usan localhost con la subcarpeta del proyecto. DISABLE_WP_CRON=true evita trabajos automáticos en este laboratorio; las tareas programadas no correrán hasta habilitarlas intencionadamente.

`.local/.htaccess` bloquea el acceso HTTP al directorio de diagnósticos (403 comprobado). El WordPress local usa HTTP y no es una configuración de producción. En producción: HTTPS, credenciales propias, debug display desactivado, SCRIPT_DEBUG=false y logging protegido según política del hosting.

La instalación está marcada para desalentar buscadores (`blog_public=0`). Esto no sustituye la protección de acceso de staging.

## Instalación y reproducción
Se descargó la versión estable mediante API oficial; URL y SHA-256 en core-package.json. El core se verificó frente a la API oficial de checksums, sin modificaciones. El idioma se descargó del servicio oficial de traducciones.

Para recrear el entorno: instalar el paquete oficial en una carpeta WordPress vacía, preparar una base local independiente, generar configuración privada/salts y usuario administrativo local; colocar los directorios propios, activar plugin/theme y guardar Enlaces permanentes con `/%postname%/`. No sobrescribir una instalación existente. Regenerar reglas desde WordPress en cada entorno, no copiar rutas absolutas de Apache.

La instalación local generó únicamente `wordpress/.htaccess` con reglas nativas. El .htaccess legacy permanece intacto. No hay configuración de compatibilidad .php ni redirects.

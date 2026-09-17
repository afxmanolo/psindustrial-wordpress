# Requisitos mínimos de seguridad

No se traslada la implementación CMS. La credencial hallada se rota/externaliza antes de usar infraestructura productiva nueva. **SE DETECTÓ UNA CREDENCIAL QUE DEBE ROTARSE/EXTERNALIZARSE**. No reproducir valor en código, docs, manifest, logs o artefactos.

## Entradas, salidas y autorización

Cada mutación: capacidad del objeto/función + nonce cuando aplica a sesión WP + validación semántica + saneado. Nonce no es autenticación ni anti-spam. REST schemas cerrados; admin-post nopriv sólo contacto. Usar APIs WordPress; SQL inevitable preparado y columnas/orden de lista permitida. No aceptar parámetros legacy como cláusulas SQL ni include/path.

Escapar al render: esc_html texto, esc_attr atributos, esc_url enlaces, wp_kses_post o allowlist más estricta para HTML. URLs de iframe sólo proveedor/ID reconstruido. No unfiltered_html para gestor. JSON serializado con API segura; el editor no permite scripts o CSS ejecutable. Prueba XSS persistente en nombre/descripcion/alt/PDF label y payloads REST.

## Medios y rutas

Permitidos de negocio: JPEG/PNG/WebP y PDF verificados; otros formatos requieren evaluación. Límite inicial20MB por PDF,10MB imagen y límite de dimensiones/procesamiento comprobado según host. Evitar doble extensión, MIME falso, SVG/script, ejecutables, symlinks y traversal. Un detector de extensión no garantiza PDF seguro: análisis antimalware del paquete/servidor cuando disponible y revisión de fuentes; bloquear PDF activo sospechoso en vez de incrustarlo automáticamente.

Uploads/compatibilidad no ejecutan PHP; comprobar HTTP que un archivo ejecutable de prueba controlado no se ejecute antes de permitir importación. Descargas sólo por ID mapeado a attachment/ruta local permitida, no filename enviado por visitante. nosniff, MIME/Disposition correctos, basename seguro. No borrar adjuntos compartidos ni servir backups como Media.

Registro de rutas no admite wp-admin/wp-login/wp-json, paths de configuración, dumps ni destinos externos. No redirección abierta ni dos decodificaciones de path. Rutas no aprobadas no revelan ID/inventario privado. La compatibilidad de archivos no incluye todo el directorio system.

## Sesiones, configuración y secretos

Autenticación/contraseñas/sesiones de WordPress, HTTPS, cuentas nominales, roles de14. Sin MD5 legacy, rol Super, user import ni login casero. Administrator custodio; mecanismo MFA/limitación de intentos validado en preparación operativa.

DB con usuario limitado al schema del nuevo sitio; no acceso al legacy desde runtime. Salts y secretos por entorno, fuera de repositorio y backup público. wp-config con permisos mínimos disponibles; DISALLOW_FILE_EDIT para editores de plugin/tema. No imponer DISALLOW_FILE_MODS de forma que impida actualizar por admin en un host sin SSH: procedimiento explícito y seguro de actualización.

Logs privados con rotación/retención30d inicial, sin body de formulario, contraseñas, SMTP tokens ni dumps. display_errors off fuera de local. Errores al usuario genéricos con ID de correlación; no ErrorInfo del transporte.

## Contacto e importación

Contacto: allowlist, límites, anti-spam gradual y destinatario privado; no attachment ni correo a dirección libre. Importador deshabilitado por defecto en producción, paquetes firmados por hash y aprobados, capability dedicada, locks/checkpoints, path permitido, límite tamaño/expansión ZIP. Hash no autentica al remitente: se requiere canal y autorización del paquete, no sólo checksum.

Staging necesita control de acceso HTTP/panel, noindex y correo de prueba; robots.txt no protege datos. No copiar dump a docroot para «importar después». Backups DB/medios/config deben ser privados/cifrados con prueba de restauración y custodio.

Criterios de salida: intentos anónimos/gestor contra endpoints privilegiados403; CSRF rechazado; uploads inválidos rechazados; dump/config no accesibles; archivo PHP no ejecutable en uploads; redirect abierto imposible; importación no habilitable por gestor. La auditoría futura revisa dependencias y rutas reales, no sólo presencia de esc_* en fuente.

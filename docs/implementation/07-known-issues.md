# Límites y pendientes
No hay un bloqueo conocido que impida abrir este WordPress local y crear manualmente productos.

1. **Acceso local**: contraseña inicial en wp-config.php ignorado. Cambiarla en Perfil y retirar PSI_BOOTSTRAP_ADMIN_PASSWORD. El correo del administrador es placeholder; no se ha configurado correo saliente ni recuperación real.
2. **Gestor**: no creado. Sólo Administrator recibe las capabilities propias; todavía no se ocultan menús. La definición e implementación final de permisos requiere su fase.
3. **Edición multimedia**: esquema/guardado y pantallas probados; falta aceptación manual completa del modal multimedia y Gutenberg. PDFs existentes se asocian, no se reemplazan ni migran. El selector muestra IDs; miniaturas/preview mejorados quedan para UX final.
4. **Video**: admite YouTube. Frontend usa enlace, no iframe; metabox asigna título genérico y no ofrece editor de lengua PDF. No hay carga de archivos de video.
5. **Contenido/SEO**: sin migración, importador, metadata SEO, schema específico, compatibility adapter, Yoast ni redirects. Términos creados manualmente son públicos; review/public se implementará antes de importar.
6. **Archivos**: /productos/ habilitado a petición del bootstrap pese al diseño anterior. Su exposición en producción está pendiente. Las categorías usan consulta nativa con descendientes; falta la composición exacta aprobada.
7. **Frontend**: diseño temporal; no replica legacy. Aún sin heroes, galerías avanzadas, breadcrumbs, landings ni contacto funcional. Los valores de contacto permanecen vacíos.
8. **Operación**: WP-Cron deshabilitado sólo localmente; traducciones/core no se versionan. No se configuraron virtual host, HTTPS, staging, FTP, CI/CD ni producción.
9. **Seguridad de despliegue**: los guards actuales cubren entradas implementadas. No publicar esta instalación local ni copiar su wp-config a producción; aplicar endurecimiento, secretos y backups del documento arquitectónico antes del despliegue.
10. **Revisión editorial**: relacionados, hero, trazabilidad y gates de aprobación permanecen fuera del bootstrap. No interpretar esta base como modelo completo listo para migrar contenido.

La credencial histórica no se copió ni utilizó. Su rotación/externalización sigue pendiente de la fase de migración correspondiente.

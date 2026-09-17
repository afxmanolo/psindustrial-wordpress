# Contacto, correo y WhatsApp

## Formulario elegido

Bloque `psi/contact` y handler pequeño en psindustrial-core, preservando nombre/email/mensaje y UX visual de contacto.php. No CPT lead ni base de contactos: el legacy no almacena leads activamente. Alternativa plugin de formularios acelera UI pero añade dependencia para un único formulario de tres campos; solución propia acotada, sin builder, resulta suficiente. Si aparece CRM/múltiples flujos se reevalúa, no se anticipa.

Envío POST a admin-post.php con handler autenticado y nopriv; respuesta accesible y mejora progresiva opcional, no REST público innecesario. Retorno permitido exclusivamente a Page contacto local. Post/redirect/get con token temporal para errores/valores, nunca email/mensaje en query string. Valores privados expirados en15min; no logs de cuerpo ni datos completos.

Nombre obligatorio1–150 caracteres; email obligatorio validado con is_email tras saneado; mensaje opcional como legacy, máximo5.000 caracteres. Sólo estos campos, sin adjuntos. Reject CR/LF en cabeceras. Sanitizar nombre y email, texto de mensaje; no transportar HTML del usuario. Honeypot no anunciado al lector de pantalla, control de tiempo, tamaño de petición y nonce específico; los nonces anónimos no sustituyen anti-spam ni autenticación.

Limitación inicial best-effort por hash HMAC temporal de IP (sin almacenar IP en claro):5 intentos/15min y umbral global100/h; ajustar con mediciones, no usar un IP de proxy no fiable. Transients pueden expirar/competir; no prometer defensa distribuida. Si hay abuso, limitación del hosting/CAPTCHA adicional con verificación server-side, previo análisis de privacidad. No conservar la falsa validación de reCAPTCHA por sólo campo no vacío. Formularios en caché refrescan token mediante endpoint no cacheado o se excluyen del cache; nunca aceptar un nonce caducado para «arreglar» envío.

## Correo

Usar wp_mail, no incluir PHPMailer ni sendMail legacy. Remitente fijo del dominio autorizado; dirección del visitante sólo en Reply-To validado. Destinatario configurable por Administrator en psi_site_settings, nunca desde POST ni por el gestor sin permiso especial. Subject fijo con nombre saneado opcional; cuerpo texto plano, no tabla de campos arbitrarios.

SMTP autenticado configurable mediante constantes/entorno privado y hook phpmailer_init de core; no plugin SMTP adicional por defecto. El proveedor/host deberá confirmar servidor, TLS, puerto y remitente autorizado antes de corte; no se infieren de credenciales viejas. Entorno local/staging captura o redirige a buzón de prueba, nunca destinatario productivo por defecto.

wp_mail=true significa aceptación del transporte, no prueba de entrega. En error, mensaje visible de fallo y opción WhatsApp sin mostrar ErrorInfo. En éxito, «Solicitud enviada», con semántica de envío, no confirmación de lectura. Correlation ID y error técnico reducido en log privado; probar entrega real, SPF/DKIM/DMARC y Reply-To. [wp_mail](https://developer.wordpress.org/reference/functions/wp_mail/), [límites de nonces](https://developer.wordpress.org/apis/security/nonces/).

## WhatsApp: configuración única

Option psi_site_settings: whatsapp_number en formato internacional E.164 validado, whatsapp_message texto≤1.000, whatsapp_enabled booleano. Core genera wa.me con dígitos y mensaje codificado una vez; tema/bloques consumen ese enlace, no forman números propios. Mensaje contextual opcional añade nombre/URL canónica de producto a la base aprobada, sin datos personales del formulario.

Footer, CTA flotante, fichas y contacto comparten configuración. El gestor puede editar número/mensaje mediante capacidad psi_edit_contact_settings; UI valida y muestra preview, registro de cambio. SMTP/destinatario exige Administrator. No asumir cuál de los dos teléfonos legacy es correcto: resolver antes de publicación. No API WhatsApp, tracking externo ni envío automático. Botón accesible con texto y espacio móvil que no tape contenido.

# Formularios, correo y WhatsApp

contacto.php:2 incluye enviaContacto.php; formulario method=post, action vacío, cmd=send y entity con Nombre, Email, Mensaje. Nombre/Email obligatorios en servidor; Mensaje opcional. Hay input type=email y clases de validación, pero no sustituyen el servidor. getValue devuelve directamente POST a atributos/textarea.

enviaContacto.php:46–78 valida campos y comprueba solamente que g-recaptcha-response no esté vacío. No se encontró llamada siteverify en esta ruta. Widget/script existen; no se demostró verificación criptográfica del token, límite de frecuencia ni token CSRF. Registro en DB está comentado: no hay almacenamiento de leads demostrado.

sendMail/sendMail.php:33–109: regex sin delimitadores adecuados y uso de reg en vez de regs impiden confiar en validaciones. genericHTMLFieldsTable itera todas las claves de entity, sin lista permitida ni escape HTML; el correo puede incluir campos adicionales y marcado inyectado.

Destino configurado: administracion@puertasyserviciosindustriales.com; remitente administracion del mismo dominio. Test=false, SMTP=true, envío al usuario=false. Opciones/código de prueba y respuesta automática comentada no son servicios activos. No se envió ningún mensaje.

Ruta activa sendMail→sendSMTP usa PHPMailer **6.9.1**, TLS, puerto 587 y rutas absolutas del hosting para require. Otra implementación mailSMTP queda sin llamada activa; hay PHPMailer **2.0.0 rc1** dentro de system/libs. No declarar una vulnerabilidad de versión sin evaluar recorrido y advisories.

SE DETECTÓ UNA CREDENCIAL QUE DEBE ROTARSE/EXTERNALIZARSE.

Errores SMTP se imprimen con ErrorInfo. Wrapper no devuelve confirmación fiable de entrega; éxito viene de validación y puede mostrarse pese al fallo SMTP. No hay evidencia de entregabilidad, DNS SPF/DKIM o transporte real local.

WhatsApp difiere: footer.php:1 apunta a **+52 477 176 3046**, texto vacío; footerv1.php:1 a **+52 479 107 1234**, texto PSI. Es enlace wa.me, no API ni integración de mensajería. Confirmar cuál conservar y si la diferencia es intencional. Inventario de enlaces registra ambas rutas.

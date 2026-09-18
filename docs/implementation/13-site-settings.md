# Contacto del sitio
La opción única sigue siendo psi_site_settings. Settings API, nonce de options.php y capability psi_edit_contact_settings. Menú propio **Contacto del sitio**, accesible a Administrator y Gestor.

| Clave | Regla | Gestor |
|---|---|---|
| whatsapp_number | E.164 con + y 8–15 dígitos; vacío permitido si desactivado | Edita |
| whatsapp_message | Texto ≤1000, sin HTML | Edita |
| whatsapp_enabled | Booleano; requiere número válido | Edita |
| contact_phone | Texto público saneado ≤200 | Edita |
| contact_email | Dirección válida o vacío | Edita |
| contact_address | Texto público opcional ≤200 | Edita |
| mail_recipient | Dirección válida o vacío; destino futuro de correo | No |

No hay números o destinatarios inventados. Valores ausentes conservan los anteriores; entradas inválidas no sustituyen la configuración válida. El servidor conserva mail_recipient ante una escritura del Gestor, incluso si agrega el campo manipulando la petición. No se publica la option mediante REST.

Settings::whatsapp_url() deriva el enlace wa.me del único número y mensaje configurados. La pantalla permite comprobarlo; no envía mensajes. No se añadió botón histórico, formulario público ni transporte SMTP en esta fase.

Una option privada no autoload psi_contact_previous retiene únicamente el snapshot anterior, fecha UTC y usuario de cambio; no es un log ilimitado. No tiene UI pública ni REST. La restauración operativa corresponde a Administrator mediante respaldo/herramientas autorizadas.

El correo público NO es el destinatario privado. Cambiar teléfono o email público no cambia credenciales ni configuración crítica. El futuro formulario consumirá mail_recipient sólo cuando se implemente su flujo y transporte.

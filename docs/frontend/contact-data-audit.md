# Datos de contacto — 2026-09-21

Auditoría de lectura; no se cambiaron opciones ni contenido WordPress.

| Tipo | Valor | Fuente legacy / ubicación | Consistente |
|---|---|---|---|
| Teléfono | 477 176 3046 | `contacto.php`, `footer.php` | No: otra variante de footer usa otro número |
| Teléfono | 479 107 1234 | `footerv1.php` | No: requiere confirmación |
| WhatsApp | +52 477 176 3046 | `footer.php`, botón flotante | No |
| WhatsApp | +52 479 107 1234 | `footerv1.php`, botón flotante con mensaje PSI | No |
| Email | overheaddoor@hotmail.com | `contacto.php`, `footerv1.php`, contacto visible | Sí entre esas fuentes |
| Email | vicenteaguilarleon@gmail.com | `contacto.php`, `footerv1.php`, contacto visible | Sí entre esas fuentes |
| Email | administracion@puertasyserviciosindustriales.com | `footer.php`, CTA de `footerv1.php`; destinatario activo de `enviaContacto.php` | Rol distinto: recepción del formulario/CTA |
| Dirección | Blvd. Estrella #323 local 5-A, Fracc. Estrella, C.P. 36566, Irapuato, Gto. | `contacto.php` (fila oculta), footers | Sí |
| Email interno de prueba | jcog21983@hotmail.com | `enviaContacto.php`, modo de prueba desactivado | No es destino público; no se reutiliza |

No hay mapa/embed en las tres páginas institucionales auditadas. No se añade otra ubicación.

## Diferencias con WordPress actual

Las opciones existentes contienen `4774103773`, WhatsApp `+524774103773` deshabilitado y `contacto@contacto.com`; dirección y destinatario de formulario vacíos. No están confirmadas por las fuentes institucionales. El footer aprobado continúa leyendo esas opciones, sin cambios silenciosos.

Contacto presenta los dos emails demostrados y la dirección; omite por completo el bloque de teléfono, sin placeholder público. No se selecciona ningún WhatsApp. El cliente debe confirmar teléfono, WhatsApp y contactos definitivos, y el destinatario antes de habilitar correo en producción. La creación/publicación posterior de las Pages institucionales cuenta con autorización específica; esta auditoría no cambia las opciones de contacto.

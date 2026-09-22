# Institucionales — implementación y validación

Fecha: 2026-09-21. Base aprobada: `67e7f16`. Estado: código y previsualizaciones listos; publicación de Pages pendiente de autorización de alcance.

## Fuentes y presentación

| Página | Fuente | Implementación |
|---|---|---|
| Nosotros | `legacy/public/nosotros.php`, `headerv1.php`, `footer.php`, CSS legacy, `images/quienes-int.jpg` | Banner original, subtítulo Nosotros, H1 Quiénes somos, fotografía izquierda y texto derecha, CTA al archive nativo |
| Contacto | `contacto.php`, `enviaContacto.php`, mismos includes; imágenes contact, contact-03, contact-04 | Banner, dos columnas, datos verificados y formulario; mismos iconos convertidos a WebP |
| Privacidad | `politica-privacidad.php`, header/footer estáticos | Banner y estructura; `LEGAL_CONTENT_PENDING` |

Los hashes y dimensiones de imágenes están en `institutional-assets.json`. CSS institucional aislado, sin rediseñar las páginas aprobadas. Header, dropdown y footer reutilizados. Navegación resuelve sólo Pages publicadas; el footer incorpora Nosotros cuando exista destino público y privacidad mediante la API nativa o la Page publicada de ese slug.

Nosotros conserva los 25 años del cuerpo visible legacy y del contenido importado. La meta description legacy dice 15: requiere revisión editorial posterior. No se reproducen métricas comentadas ni el HTML duplicado/malformado importado. No se modifica ese contenido en la base.

Privacidad contiene `NOMBRE EMPRESA`, `abcoach.com` y `www.dermafest.com.mx`; no constituye una política confirmada para publicar íntegramente. Se deja únicamente estructura y aviso pendiente, sin redactar términos nuevos.

Diferencias deliberadas: labels visibles y mensajes accesibles; dirección verificada ahora visible (oculta en Contacto legacy); sin WhatsApp ambiguo ni reCAPTCHA ineficaz; apilado móvil, imágenes optimizadas y headings semánticos. No se incorpora mapa inexistente.

## Formulario

Lógica en `psindustrial-core/includes/Contact.php`; presentación en el theme. Nombre requerido hasta 150, email validado hasta 254, mensaje opcional hasta 5000. Nonce, token temporal firmado (3–3600 segundos), honeypot, rechazo de arrays e inyección CRLF, sanitización y escape. Límites básicos mediante transients: 5 intentos/15 minutos por HMAC de IP y 100/hora global; no son contadores atómicos ni protección volumétrica. No se registra contenido personal.

`wp_mail` sólo se permite en production con `mail_recipient` válido de Settings. Local/staging permanecen bloqueados antes del transporte. From corresponde a WordPress; visitante sólo Reply-To validado. No se enviaron correos reales.

Desviación intencionada de la propuesta inicial: POST a la misma Page, errores sólo en memoria de esa petición y redirección 303 al completar; no bloque adicional ni transients con mensajes personales. Evita persistir datos para un formulario pequeño. Contacto envía cabeceras no-cache y DONOTCACHEPAGE; una futura caché externa debe excluir esa ruta. El transporte de producción no se ha validado en esta fase.

## Publicación inicial pendiente (resuelta por autorización posterior)

Existe Nosotros ID134, draft, `_psi_review_state=pending`. Contacto y Privacidad no existen como Pages. Se solicitó aclaración porque publicar Nosotros contradice la prohibición explícita de modificar REVIEW, y el alcance de creación de Pages debe conciliarse con la exigencia de no mutación de WordPress. No se cambió la BD ni se crearon rutas virtuales para sortear esa restricción.

Las previsualizaciones se generan mediante WP_Query/WP_Post sólo en memoria; no prueban que esas URLs públicas ya existan. La activación de destinos institucionales y la prueba HTTP final del formulario quedan pendientes de resolver ese alcance. Tampoco se configura el destinatario sin confirmación.

## Pruebas

- Existentes: product-catalog 111/111, home-global 33/33, home-slider 11/11; total 155/155.
- Nuevas: institutional 46/46; total combinado 201/201.
- La fixture de Page sin hero de home-global ahora elige una Page no institucional: Nosotros pasa legítimamente a tener banner. Los asserts se conservan.
- Validación de campos/nonce/timing/honeypot/CRLF/tamaños, sanitización, límites aislados en caché de memoria, cero llamadas a correo, renderizado, H1 único, landmarks, enlaces sin placeholders, privacidad pendiente, emails/dirección y ausencia de mapa inventado.
- Hashes antes/después de siete tablas (incluidas options) idénticos en la suite nueva. Post1371 permanece publish.
- PHP lint: 41 archivos, sin errores.
- Navegador: comparación de composiciones desktop con referencias HTML legacy privadas sin ejecutar sus includes dinámicos. Tres previsualizaciones × 1440/1280/1024/768/375/320: ancho real del viewport verificado, cero overflow horizontal. Inspección visual de Contacto y Privacidad móvil; comparación de banners, columnas, fotografía y formulario desktop. No se enviaron formularios.
- Baseline legacy: 3862 archivos, cero cambios, ausencias o extras.

## Cierre con autorización específica

Nosotros ID134 publicado exclusivamente en local, sin reescribir su contenido; revisión aprobada sólo para esta Page. Contacto ID1481 creado/publicado con emails y dirección verificados. Privacidad ID1482 creada draft, sin texto jurídico inventado ni enlace público. La política general de REVIEW no cambia. No se modificaron otras entidades, productos ni taxonomías.

El guard editorial exigió revisión aprobada y contenido no vacío; se respetó mediante APIs WordPress y metadata sólo de las dos Pages autorizadas, sin desactivar validaciones. Contacto no muestra bloque de teléfono ni placeholder. Los datos previos del footer siguen en Settings y requieren confirmación (véase auditoría de contactos).

Pruebas finales: 155 anteriores + 59 institucionales = **214/214 PASS**. Nosotros/Contacto HTTP 200. Home y footer conducen a ambas Pages; Privacidad no tiene enlace público. Navegador real 1440/768/375, sin overflow y H1 correcto. POST local devuelve feedback accesible de envío deshabilitado, sin correo. Se corrigió la colisión del campo `name` con query vars WordPress mediante prefijo `psi_contact_`; la suite incluye regresión HTTP de POST y nonce inválido.

El contenido de Nosotros se conserva y el post1371 sigue publish. Las pruebas no cambian contenido ni opciones; la prueba manual válida de formulario utiliza únicamente los contadores transitorios antiabuso. Pendiente: confirmación de contactos y texto legal. Sin redirects, Yoast ni despliegue.

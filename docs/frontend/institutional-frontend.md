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

## Footer, Contacto completo, Soluciones y Marcas — 2026-09-24

Contactos confirmados por el cliente (staging), cerrando la pendiente de `contact-data-audit.md`: dirección "Blvd. Estrella #323 local 5-A, Fracc. Estrella, C.P. 36566, Irapuato, Gto." (coincide con `footer.php`), teléfono "479 107 12 34" (mismos dígitos que el candidato `footerv1.php` de la auditoría previa, antes sin confirmar), `contact_email`/`mail_recipient` = `administracion@puertasyserviciosindustriales.com` (CTA/footer/destinatario del formulario en `enviaContacto.php` y `footer.php`; rol distinto de los dos emails visibles de Contacto, que no cambian). `psi_site_settings` actualizado vía `Settings::sanitize()` real (no escritura directa), sustituyendo el placeholder `contacto@contacto.com`/`4774103773`. WhatsApp queda fuera de alcance, sin confirmar.

Footer "Soluciones" (Productos + 7 categorías + marcas reales vía `catalog_navigation()`) se auditó contra `legacy/public/footer.php` y se mantiene sin cambios de estructura: ya usa `get_term_link()` real para categorías y marcas, ordenado por `_psi_category_menu_order`/`_psi_brand_home_order`, sin `href="#"` ni hijos. Diverge del HTML legacy (que mostraba sólo un enlace genérico "Marcas") pero coincide con la arquitectura ya aprobada en la fase dynamic-categories-menu; lo que sí eran datos obsoletos (placeholder de contacto) queda corregido arriba.

Contacto agrega bloque de Teléfono (icono `contact-160.webp`, ya existente sin usar) y ahora lee dirección/teléfono desde `Settings::get()` en vez de texto fijo; los dos emails visibles siguen como literales (no forman parte del modelo del footer en legacy ni en `Contact.php`).

Nuevas Pages **Soluciones** (ID2048, `/soluciones/`) y **Marcas** (ID2049, `/marcas/`), publicadas vía `wp_insert_post()`+`_psi_review_state=approved` (mismo guard editorial que Nosotros/Contacto, no desactivado). Soluciones recrea las tres tarjetas de `legacy/public/soluciones.php` (Industrial/Residencial/Marcas, imágenes `puerta432.jpg`/`operadores.jpg`/`marcas.png` convertidas a WebP 480w/800w bajo `assets/images/institutional/`); Industrial/Residencial resuelven `psi_categoria` real vía `Identity::find()` (category:1/category:7, portable, nunca term_id fijo). Marcas reutiliza `home_brands()` sin cambios (mismos 12 logos/enlaces que Home). `legacy/public/header.php` y `headerv1.php` confirman que el texto "Soluciones" del menú era `href="#"` (sólo alternaba el dropdown); no se restauró ese placeholder muerto (contradice la regla del proyecto de no `href="#"`) y en su lugar enlaza a la Page Soluciones real, igual que "Ver servicios" (Nosotros) y "Nuestros servicios" (Home) -- ambos confirmados en `legacy/public/nosotros.php`/`index.php` apuntando a `soluciones.php`. El CTA del slider Home ("Ver más", elemento distinto ya aprobado en una fase previa con su propia prueba de regresión) no se tocó.

`data/legacy-url-map.php` y `docs/seo/redirect-map.csv` ganan `soluciones.php`→page2048 y `marcas.php`→page2049 (mismo patrón que `nosotros.php`/`contacto.php`, wp_id plano ya que el propio comentario del archivo documenta ese tipo como fuera del alcance de la migración a entity_key).

Pruebas: `tests/institutional.php` extendido (85/85, incluye Soluciones/Marcas y el teléfono ahora confirmado en Contacto) y `tests/home-global.php` extendido (39/39, incluye header/Home "Soluciones"/"Nuestros servicios" y datos del footer). Regresión completa del resto de la suite (plugin+theme) verificada aparte. `/legacy` sin cambios; sin commit ni push.

## Portabilidad: InstitutionalContentMigration — 2026-09-25

El trabajo anterior (2026-09-24) creó las Pages Soluciones/Marcas y corrigió `psi_site_settings` mediante scripts locales puntuales -- válido para este entorno, pero el CI/CD de este proyecto sólo despliega theme + `psindustrial-core`, nunca la base de datos local, y los post ID 2048/2049 son autoincrementales y no portables. Se sustituyó por **`InstitutionalContentMigration`** (`psindustrial-core/includes/InstitutionalContentMigration.php`), con el mismo patrón versionado/idempotente que `BrandsMigration`/`CategoriesMigration`/`MojibakeContentMigration`:

- **Pages**: resueltas por `get_page_by_path()`, nunca por post_id. Se reutilizan si ya existen (cualquier estado, nunca se sobrescriben ni duplican); se crean sólo si faltan, publicadas en la misma llamada a `wp_insert_post()` vía `meta_input['_psi_review_state']='approved'` (Editorial::native() lo lee de `$postarr` antes de que el post exista, sin el paso previo de crear-en-draft-y-republicar que usó el script original).
- **Settings**: sólo se escribe un campo si su valor actual está vacío o coincide con un placeholder histórico conocido (`4774103773`, `contacto@contacto.com`); un valor legítimo ya establecido por un administrador se deja intacto (`skipped_existing`). `mail_recipient` exige además `manage_options` en el usuario que dispara `admin_init` -- si no lo tiene (p. ej. psi_gestor), ese único campo queda `pending_capability` y se reintenta automáticamente en cada futura carga de wp-admin hasta que un Administrador real la visite; los otros tres campos no dependen de esa capacidad.
- `data/legacy-url-map.php`: `soluciones.php`/`marcas.php` ahora usan `{wp_type:'page', page_path:'...'}` en vez de `wp_id` fijo -- `LegacyUrls::destination()` se amplió de forma genérica (no hay `if slug===soluciones`) para resolver cualquier regla `page` por `page_path` cuando esté presente, conservando `wp_id` sin cambios para las reglas Page existentes (nosotros.php, contacto.php, ...) que no se tocaron.
- Los dos emails adicionales de Contacto (`overheaddoor@hotmail.com`, `vicenteaguilarleon@gmail.com`) siguen como literales en `page-contacto.php`: son **datos de contacto adicionales de esa página**, no parte de Settings ni de ninguna "fuente única de verdad" para absolutamente todo. Settings es la fuente única sólo para: dirección, teléfono, email/CTA del footer y destinatario del formulario.

Auditoría del header "Soluciones" (sin cambios de código): legacy usaba `href="#"` en `header.php`/`headerv1.php` (sólo alternaba el dropdown Bootstrap). La implementación actual usa un enlace real a `/soluciones/` más un botón `.submenu-toggle` independiente que `navigation.js` inserta junto al enlace (con `aria-expanded`, teclado, Escape) -- y `global.css` ya tiene un tercer mecanismo CSS-only (`:hover`/`:focus-within` en `.sub-menu`) como base sin JS. Los tres conviven sin conflicto: el texto navega, el botón/hover abre el submenú. No se requiere convertir "Soluciones" en `<button>`.

Pruebas: `tests/institutional-content-migration.php` (nuevo, 59/59) -- crea/reutiliza contra Pages fixture desechables (nunca las reales `soluciones`/`marcas`, ya existentes y aprobadas), prueba placeholder→migrado, valor legítimo→intacto, capacidad insuficiente→`pending_capability` con reintento real (usuario `psi_gestor` temporal), segunda ejecución idéntica, y HTTP real de `/soluciones.php`/`/marcas.php` sin depender de 2048/2049. Suite completa (plugin+theme) reverificada en verde. `git diff --check` limpio. `/legacy` intacto. Sin commit ni push.

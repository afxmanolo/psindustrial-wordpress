# Ronda 2: slider administrable y logo del footer

Fecha: 2026-10-01. Implementación local; sin deploy, commit ni cambios al importer.

## Auditoría y decisión

El array estaba en `front-page.php`; `inc/home-data.php` resolvía sus imágenes WebP y variantes de tamaño. Cuatro slides, en este orden:

| Imagen histórica | Texto introductorio | Texto principal |
|---|---|---|
| banner1.jpg | Conócenos. | Bienvenidos a nuestro sitio web. |
| banner2.jpg | Empresa dedicada a la | Venta, instalación y mantenimiento de puertas automáticas |
| banner3.jpeg | Empresa dedicada a la | Venta, instalación y mantenimiento de rampas para Taller o Niveladoras de Andén |
| banner4.jpg | Empresa dedicada a la | Venta, instalación y mantenimiento de rampas para Taller o Niveladoras de Andén |

Todos tenían botón «Ver más» hacia el archivo de productos. No existía imagen artística distinta para móvil: sólo tamaños responsive. El JS `assets/js/home.js` conserva rotación de cuatro segundos, pausa, indicadores, teclado, swipe y reduced-motion. La composición y CSS del hero no cambian.

Se elige CPT `psi_slide` en psindustrial-core frente a options: publicación/borrador/papelera, imagen destacada, revisiones y orden nativos, sin construir una interfaz repetidora propia. No tiene rutas públicas, archivo ni inclusión en búsquedas. Sólo administradores (`manage_options`) lo gestionan. El theme se limita a presentar los datos.

## Administración

WP Admin → **Slides de Inicio** → Añadir/Editar:

- Título: texto principal visible.
- Imagen del slide: selector nativo de Media Library.
- Texto y botón: introducción, texto del botón y enlace.
- Atributos → Orden: número menor primero; ID desempata.
- Publicar para mostrar; borrador/papelera para retirar. Eliminar no regenera el slide.

Se guardan attachment IDs y los metadatos `_psi_slide_eyebrow`, `_psi_slide_button_label`, `_psi_slide_button_url`. Las URLs admiten HTTP(S) o rutas desde el inicio del sitio (`/productos/`). No se añade editor de contenido ni imagen móvil inexistente. Nonce/capabilities/sanitización protegen el metabox; REST exige permisos administrativos para edición.

## Migración nueva, independiente

`SlidesMigration::VERSION = 1`; marcador **`psi_slides_migration_version`**. Se ejecuta en `admin_init` prioridad 25 para administrador, al abrir wp-admin tras desplegar plugin y theme juntos. No usa IDs físicos, documentos ni legacy en runtime.

`data/home-slide-seed.php` conserva los cuatro textos y referencias a assets ya distribuidos con el theme. Se importan las cuatro imágenes optimizadas existentes a Media Library mediante APIs WordPress, comparando SHA-256 con attachments WebP existentes para reutilizar bytes idénticos. WordPress genera sus tamaños responsive. No se añade ninguna copia de imagen al repositorio.

Identidades `_psi_slide_seed` y progreso `psi_slides_migration_created` evitan duplicados y sobrescrituras incluso si una ejecución parcial se reintenta. El progreso conserva la decisión de eliminación posterior. Lock breve en `psi_slides_migration_lock`; fallo visible en administración mediante `psi_slides_migration_error`; el marcador final sólo se escribe al completar las cuatro entradas. No se modifica ninguna versión del importer histórico.

Antes de inicializar, exclusivamente cuando no hay registros de slides ni marcador final, existe un fallback técnico con los assets y seed distribuido para que el deploy no deje el hero vacío antes de abrir wp-admin. Después, la fuente normal y única son los registros. Si el administrador despublica todos, no reaparece el array original.

La migración se ejecutó **sólo en local**: cuatro slides publicados con fotografías de bytes idénticos y textos/orden conservados. Staging/producción no fueron accedidos. La misma migración está preparada para esos entornos.

## Casos vacíos y presentación

- Cero publicados: se omite el hero y la clase que superpone el header al hero.
- Sin imagen: se conserva el fondo azul existente, sin img rota.
- Sin texto de botón o URL: no se emite CTA vacío.
- Un slide: no hay autoplay ni controles innecesarios.
- Imágenes mediante `wp_get_attachment_image`, dimensiones/srcset; primera eager/high, demás lazy.

Footer: utiliza el mismo `logo-black-big@2x.png` institucional del header, dimensiones explícitas y placa blanca discreta (padding 8×12 px, radio 3 px). Fondo general y header intactos.

## Validación

- Integración `core/tests/slides.php`: 42 verificaciones (modelo, migración, bytes, idempotencia, edición humana, nonce, permisos, REST, vacíos, media y HTTP).
- Regresión: product-catalog 111; home-global 39; institutional 85; client-revisions-round-1 61; header-solutions 19.
- JS `tests/home-slider.cjs`: 12 verificaciones, incluidos cero/uno, teclado y reduced-motion.
- Los reportes de pruebas anteriores se redirigieron a temporal; no se regeneran reportes versionados.
- Pruebas locales crean fixtures de slides y los retiran; una edición de prueba del seed se restaura. Productos y Pages ajenos se comparan antes/después y permanecen intactos.
- Resultado: **369/369 PASS**, sintaxis válida en 12 PHP nuevos/modificados; HTTP Home y REST administrativo correctos.
- Navegador: hero comprobado a 1440, 768, 375 y 320 px; sin overflow horizontal. Cambio de slide y pausa operativos; footer a color revisado en móvil. No cambia la composición del hero.
- Baseline SHA-256: **3.862/3.862 archivos legacy idénticos**, sin archivos adicionales.

Revisión manual recomendada: editar título/intro/CTA; cambiar imagen; cambiar orden; pasar uno a borrador; crear/eliminar un slide; despublicar todos y volver a publicar. Confirmar Home tras cada operación. La prueba automatizada usa APIs nativas; no se realizó una sesión interactiva autenticada de edición wp-admin.

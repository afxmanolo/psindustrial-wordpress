# Estrategia de pruebas y aceptación

Esta fase especifica pruebas, no las ejecuta sobre un WordPress inexistente. Las verificaciones documentales finales se registran en architecture-verification.md.

## Capas

| Área | Casos mínimos y resultado esperado | Gate |
|---|---|---|
| Modelo | CRUD de producto/término/meta, arrays ordenados, principal asignada,0..1marca, ciclos rechazados | Antes de editar catálogo real |
| PHP | Lint/estático y tests en8.4; cero fatal y avisos propios relevantes | Cada release |
| Permisos | Anónimo/gestor/admin por UI, URL directa, AJAX y REST; editar/borrar medios separados | Antes de dar acceso al cliente |
| Publicación | Vacíos/test/review no se publican; ausencia de marca aprobada sí | Importación y UI |
| Relaciones | Categoría padre muestra hijos+directos; include_children=false; filtro AND marca/categoría | Paridad del catálogo |
| Importación | Dry-run,2 ejecuciones, source changed, target edited, merge/split aprobado, crash/reanudar, rollback | Antes de corte |
| Medios | Firma/hash/nombre/orden, compartidos, descarga/HEAD/Range, PDF Unicode, archivo sin extensión | Cada lote |
| URLs |200 primaria,301 aprobado un salto,404 real,query funcional/tracking,case/Unicode,slash,IDs antiguos,paths reservados | Antes de publicar registro |
| SEO | Un title/description/canonical,robots HTTP+HTML,sitemap sin aliases,links/OG sin staging,schema coherente | Antes de corte y actualizaciones |
| Visual | Baseline por12 familias y variantes críticas, escritorio/móvil, menús, carrusel,PDFs | Aprobación visual |
| Contacto | Datos inválidos,honeypot,límites,nonce expirado,SMTP fallo/éxito real,no fuga de datos | Antes de producción |
| WhatsApp | Número único aprobado,mensaje/URL codificados,móvil/desktop | Antes de producción |
| Rendimiento | Presupuestos18,cache correcto,LCP no lazy,video diferido | Antes de release |
| Hosting | .php llega aWP;descargas no ejecutan legacy;backups privados;restore comprobado;HTTPS | Gate de despliegue |

## Fixtures PS Industrial obligatorias

- Modern Steel6/16/20/107: conflicto de fabricante no resuelto por mayoría.
- Producto3: categoría7 vs rampa10; no publicar corrección automática.
- IDs151–164 y165: REVIEW conservado sin create/skip público silencioso.
- AccessPRO y aliases QSilent; Thermospan14/57 y sus dos PHP: ninguna fusión de URL automática.
- Estándar/reforzada27/28: Page colección conserva ruta; hospital35: no doble dueño.
- Marcas:12 logos explícitos, sin array posicional; al cambiar orden no cambia logo.
- Tres videos PHP aunque SQL esté vacío;11 filas asociadas no crean11 videos únicos.
- PDF con varios IDs/mismo hash: relaciones conservadas, segunda importación no duplica.
- INFRACA NFC/NFD y PDF Solmmer ausente: REVIEW/error explícito, no enlace falsamente validado.
- Descarga file.php:id parametrizada no se considera endpoint interno descartable.
- 32 estáticos sin SQL y9 landings: contenido cubierto, no convertido todo a productos.

## Evidencia y criterios

El denominador del importador es el manifest **aprobado**, no165 productos finales ni901 URLs públicas. Cada fuente maestra tiene resultado, propietario o REVIEW con responsable. Todas las URLs requeridas para corte tienen status/destino esperado y evidencia; cero fuentes públicas omitidas silenciosamente. Las599 derivadas no se ensayan como URLs existentes hasta su clasificación, pero todas conservan decisión.

Diff por campo: texto/medios/SEO/enlaces antes/después; verificar orden de PDF/galería desde relaciones originales, no unión alfabética del maestro. Preservar bytes del PDF y probar contenido/MIME además de status200. No aceptar HTML de404 servido con200 como éxito.

Tests de integración reales con WordPress y APIs, no sólo mocks que reflejen código. Revisión manual donde importan intención/fabricante/visual. Capturas baseline nuevas necesarias porque fase1 no ejecutó frontend; obtenerlas en entorno controlado, sin formularios/correo productivos. No usar un conteo de archivos como prueba visual.

Publicación: responsable técnico aprueba paquete/restauración; responsable editorial aprueba catálogo/contactos; responsable SEO aprueba rutas/indexación/excepciones. Registrar resultado/versión/fecha; lista P0 sin resolver impide corte, no impide implementar componentes independientes en una futura tarea autorizada.

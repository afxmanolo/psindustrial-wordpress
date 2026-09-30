# Mapa preliminar legacy → WordPress

Hipótesis de arquitectura basadas en análisis local de código/dumps, 2026-09-17. No se instaló WordPress ni se creó código. No son decisiones definitivas de slugs, redirecciones o fusión editorial.

| Origen demostrado | Equivalencia posible | Condiciones |
|---|---|---|
| productos (165 filas) + detalles PHP | CPT producto en psindustrial-core | reconciliar identidad/texto/medios; conservar ID y URLs origen |
| categorias con padre_id | taxonomía jerárquica | conservar NULL/0 e intención padre/hoja; no expandir resultados recursivamente sin decisión |
| marcas + páginas fijas | taxonomía marca con metadatos de logo | una relación explícita, no array posicional; reconciliar 110 marca=0 |
| file original | Media attachment | MIME real, nombre, descripción, fecha y trazabilidad |
| listas imagen/fichas | relaciones ordenadas producto–attachment | múltiples imágenes y PDFs; mantener orden y compatibilidad de file.php |
| file derivado | tamaños de imagen derivados | conservar equivalencias antiguas; no desechar original |
| campo video | metadato si se confirma necesidad | hoy vacío, hidden y no mostrado por vista principal |
| nosotros/privacidad/contacto | páginas editoriales + formulario | diseño y contacto; validación/entrega/anti-spam requeridos |
| soluciones/landings fijas | páginas o archivo de taxonomía enriquecido | sólo tras comparar intención y contenido; no fusionar por nombre |
| menú/header/footer/hero/cards | templates y partes del tema psindustrial | conservar identidad, responsive, navegación y atributos de comportamiento |
| user/login/permission | usuarios y capacidades WordPress | recrear accesos autorizados; no copiar MD5 ni rol Super incoherente |
| rewrites .php/amigables/PDF | compatibilidad de rutas en plugin | inventario por URL; preservar o 301 justificado; probar filtros |
| CRUD/importación | administración WP + código específico de plugin | relaciones, metadatos, validación, importador repetible y reportes |

## Separación de responsabilidades

**Tema `/wordpress/wp-content/themes/psindustrial`:** HTML, CSS, JS de frontend, templates de contenido/archivo, componentes, accesibilidad y comportamiento responsive. Presenta datos; no registra dominio ni contiene importadores/permisos.

**Plugin `/wordpress/wp-content/plugins/psindustrial-core`:** registro CPT/taxonomías, logo de marca y asociaciones de PDF/imagen, IDs legacy, importación repetible, capacidades, URL compatibility y redirecciones aprobadas. Preferir APIs WordPress y soluciones pequeñas sin frameworks innecesarios. No copiar las capas SDO/DAO/Savant del CMS.

**Contenido editorial:** no todo PHP equivale a un producto. Hay 179 páginas de contenido y sólo una correspondencia parcial con SQL; mantener landings únicas y revisar variantes estáticas. No se propone ecommerce, pagos, registro público ni page builder.

## Datos que cambian el diseño de la migración

54 productos sin categoría y 110 sin marca según valor 0; completar sólo con evidencia revisada. Dos fuentes de asociaciones: columnas SQL y enlaces fijos. Guardar decisiones de reconciliación, no convertir inferencias en datos definitivos.

Los 1.355 registros file se localizaron, pero originales están bajo system/files y no llevan extensión. Una importación sólo de multimedia pierde originales/PDF. Hay 458 originales y 897 variantes; deduplicar por identidad/hash con reporte, sin perder relaciones y URLs. Usar metadatos de origen para repetibilidad; no importar cada derivado como documento comercial independiente.

Las categorías padre tienen URL que muestra subcategorías y la hoja otra que filtra productos. El filtro actual es igualdad exacta, incluso cuando hay productos asignados a raíces. El comportamiento por defecto de una futura taxonomía debe probarse contra esa semántica.

Los logos del frontend dinámico usan array posicional; la futura relación debe ser explícita pero el recurso oficial requiere validación. WhatsApp tiene dos destinos. El campo video no justifica construir una videoteca.

## Decisiones aún abiertas

Fuente de verdad por producto, equivalencias PHP↔SQL, estructura definitiva de URLs, políticas de canonicalización, tratamiento de landings, permisos/editorial, campos administrables de portada, número WhatsApp, logos oficiales y estrategia SMTP. No seleccionar plugins externos o versiones por anticipado.

Antes de implementar: confirmar respaldo vigente, capturas y respuestas HTTP, matriz de entidades/URLs y criterios de aceptación. Después, en otra fase autorizada, definir un importador intencional/repetible y pruebas de contenido, SEO, administración, seguridad y visuales. No sincronizar base local a producción.

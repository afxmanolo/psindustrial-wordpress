# Modelo de contenido aprobado para diseño

Estado: decisión de arquitectura, no autorización de ejecución. Fuentes: product-master (165 filas), content-master (617 PHP, 179 páginas de contenido), category-master (38), brand-master (12), media-master (1.531 entradas) y evidence-matrix de fase 2. «Definitivo» fija contratos y reglas; no convierte candidatos editoriales en hechos.

## Objetos y responsabilidades

| Concepto | Representación elegida | Motivo / alternativa descartada | Coste y efecto administrativo | SEO / migración |
|---|---|---|---|---|
| Producto o familia técnica identificada | CPT `psi_producto` | Datos y consultas homogéneos; Pages no ofrecen separación suficiente; WooCommerce añade compras inexistentes | Menú Productos y campos específicos | Una entidad puede recibir varios IDs origen; no un producto por fila SQL |
| Categoría/subcategoría | Taxonomía jerárquica `psi_categoria` | Relación muchos-a-muchos nativa; CPT de categorías duplicaría consultas/relaciones | Editor de jerarquía e imagen | Un padre; navegación transversal independiente |
| Marca | Taxonomía no jerárquica `psi_marca` | Agrupa productos y admite logo/archivo sin segundo CPT | Selector de una marca verificada; admite ninguna | Término con ruta y metadata; no fusionar atribuciones conflictivas |
| Institucional, contacto, sector/aplicación y landing | Page | Composición editorial, no catálogo por defecto | Editor de bloques restringido y patrones | Cada intención/URL tiene propietario; conservar las 9 landings candidatas |
| Directorio/colección editorial | Page + bloque de colección | Puede reunir categorías/productos sin inventar jerarquía | Selección ordenada de objetos, sin SQL libre | Resuelve páginas compartidas como estándar/reforzada |
| Imagen, logo, PDF | Attachment | Biblioteca nativa y referencias por ID | Alt/contexto, previsualización, selección | Identidad de bytes separada de URLs históricas |
| Video externo | Post meta estructurado | Sólo 3 embeds; sin necesidad de videoteca/CPT | URL permitida y título | Preservar asociación; no descargar YouTube |
| Atributos técnicos y relaciones ordenadas | Post meta registrado | Son datos del objeto; no columnas/tablas nuevas | Controles limitados y validados | Trazabilidad, revisión e importación tipadas |
| Imagen/descripción extendida/orden de término | Term meta y description nativa | Evitar términos duplicados como Pages | Formulario de término | Contenido enriquecido sin duplicar autoría |
| WhatsApp/contactos y preferencias globales | Option `psi_site_settings` | Una fuente de configuración | Pantalla Contacto del sitio; destinatario restringido | Sin teléfonos repetidos en templates |
| Reglas de compatibilidad e importación | Options privadas no autoload + manifest externo | Volumen acotado, no motor masivo | Sólo Administrator | Registro exacto de rutas e IDs, sin tabla personalizada |
| Responsables | Administrator y `psi_gestor` | Sin usuarios públicos ni clientes | Privilegios mínimos; cuentas nuevas | No importar hashes/cuentas por inercia |

No CPT separado para PDF, marca, servicio, sector, landing, testimonio o formulario. No se necesita carrito, pedidos, búsqueda externa, API de negocio, REST headless ni framework de aplicación.

## Cardinalidades y propiedad

Producto → categorías: 0..N, una principal opcional para breadcrumbs; principal debe pertenecer a las asignadas. Producto → marca: 0..1 editorialmente; aunque WordPress permite varias, el core valida el límite. Un logo o PDF puede ser compartido por varios objetos. `post_parent` de attachment no define propiedad exclusiva. Producto → PDFs, galería y videos: listas ordenadas. Una Page puede enlazar cualquier producto o término sin adquirir su identidad.

Categoría → padre: 0..1 sin ciclos; hijos 0..N. Una asociación de navegación no equivale a parentesco. Page que agrupa 27/28 permanece Page; no representa dos archivos de taxonomía bajo una URL. La ruta de hospital se asigna a la ficha, no simultáneamente al término 35.

## Almacenamiento y exclusiones

Sin tablas personalizadas propias. Post/term meta para datos pequeños de dominio; options para configuración acotada. Logs voluminosos y paquetes de importación quedan fuera del directorio público. El plugin SEO elegido puede mantener sus índices derivados propios; se acepta explícitamente esa implementación de tercero para no escribir un motor SEO. No son fuente de catálogo ni se importan como datos; ver 08.

Fecha SQL se conserva como procedencia; sólo se convierte en fecha editorial con decisión explícita. Se mantiene UTF-8 corregido por transformaciones auditables, nunca recodificación global automática. Campos desconocidos permanecen ausentes; no inventar precio, SKU, stock, fabricante, certificación ni reseñas.

El CSV wordpress-content-model enumera campos persistidos. legacy-to-wordpress-final-map cubre cada fila de los maestros y cada ruta candidata: fija representación y acción propuesta, pero `execution_approved=NO` para todos. No es un manifest ejecutable.

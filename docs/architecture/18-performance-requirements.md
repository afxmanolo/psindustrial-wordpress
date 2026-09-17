# Rendimiento

Objetivo: conservar identidad visual con menos CSS/JS repetido. El44,7% de repetición de fuente legacy no es un porcentaje de contenido eliminable. Se construyen componentes comunes y se conservan textos/medios necesarios.

## Presupuestos de diseño

En móvil: LCP≤2,5s, INP≤200ms y CLS≤0,1 en percentil75 de visitas reales cuando existan datos suficientes; antes, ensayos de laboratorio por familia. Son objetivos, no resultados medidos del proyecto. [Definición oficial de umbrales](https://web.dev/articles/defining-core-web-vitals-thresholds).

Presupuesto inicial para código propio por página: JS comprimido≤80KB (excluye video diferido), CSS comprimido≤100KB, transferencia inicial objetivo≤1MB, fuentes≤2 archivos con variantes necesarias. Ajustar sólo con medición y excepción documentada, no degradando información o visual. No exigir100 Lighthouse como sustituto de experiencia real.

## Frontend

Encolar assets por necesidad; no vendors.min ni Bootstrap/jQuery/Swiper completos por herencia. Navegación accesible con JS mínimo; carrusel sólo en portada si conserva necesidad visual, implementación pequeña o dependencia aislada auditada. CSS tokens, layout y componentes compartidos; sin estilos inline repetidos por producto.

Hero/LCP con tamaño reservado y carga prioritaria razonada, nunca lazy del principal visible. Imágenes inferiores lazy, srcset/sizes desde Media, width/height, formatos optimizados conservando originales. Logos sin recortes ni pérdida de legibilidad; no convertir indiscriminadamente PDF en imagen.

Videos con thumbnail/placeholder local permitido y carga tras interacción, sin descargar YouTube ni auto-play. Fuente original externa preservada en meta; imagen de preview sólo si licencia/fuente válida. Fonts locales si licencia lo permite, font-display y fallback métricamente próximo. Prefers-reduced-motion y contenido/H1 legibles sin JS.

## Servidor/cache compartido

Sin Redis/Elasticsearch ni worker externo obligatorio. Consultas nativas indexadas por taxonomía/IDs; no meta_query compleja para construir catálogo. Por request cargar relaciones de forma agrupada; evitar consulta por cada card. Registro de rutas autoload=false; lookup acotado, no regex de cientos de reglas genéricas ejecutando lógica cara.

Primera medida: OPcache si disponible y cache de página del hosting comprobada. Si no existe y mediciones lo necesitan, evaluar **un solo** plugin mantenido de cache; no agregarlo por defecto. Excluir login/admin/REST mutante/importación/previews/contacto con tokens y peticiones POST; vary por filtros/paginación sin servir un catálogo incorrecto. Purgar al publicar producto, cambiar términos, rutas, PDF o menús.

Cache de navegador en assets versionados; no cache largo de HTML erróneo de staging/robots. PDF immutable puede cachearse con ETag/Last-Modified; sustitución autorizada requiere purga. Compresión Brotli/gzip según servidor; no asumir control sobre Hepsia.

## Verificación

Muestras: portada, producto con varias imágenes/PDF, producto con video, categoría padre/hoja, marca, landing extensa y contacto. Medir desktop/móvil, cache frío/caliente, terceros bloqueados y JS deshabilitado. Instrumentación inicial con herramientas locales; no instalar analytics nuevo sin propósito/consentimiento definido. Separar latencia del host de JS/imagen. Presupuestos se prueban sobre artefacto de staging, no sólo sobre build local.

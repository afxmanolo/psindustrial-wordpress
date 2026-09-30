# Decisiones abiertas y bloqueos

Se clasifica cada pregunta de manual-decisions-required.md, sin responderla por intuición. BLOCKS_ARCHITECTURE impide fijar el modelo; BLOCKS_MIGRATION impide aplicar/publicar el conjunto afectado o cortar a producción; CAN_BE_RESOLVED_LATER permite construir con política conservadora definida; OPTIONAL sólo añade funcionalidad si se confirma uso.

**Ninguna de las12 preguntas impide fijar esta arquitectura.** Hay bloqueos para importar/publicar elementos y para el corte. Un bloqueo localizado no paraliza el desarrollo futuro de componentes independientes. La presente tarea no autoriza ese desarrollo.

| ID / pregunta original | Clasificación | Alcance y responsable necesario | Política mientras falta respuesta |
|---|---|---|---|
| D01 ¿Copia/dump vigentes? | BLOCKS_MIGRATION | Propietario del catálogo confirma fuente/fecha antes de importación final | Ensayos privados con snapshot identificado; no afirmar producción vigente |
| D02 ¿Identidad comercial y fusiones? | BLOCKS_MIGRATION | Editor de catálogo aprueba cada grupo/MERGE | No fusionar77 candidatos automáticamente; entidades/páginas conservadas en plan |
| D03 ¿Marca en fuentes contradictorias? | BLOCKS_MIGRATION | Bloquea asignación de marca/schema del caso; responsable comercial |28 registros sin marca fiable; puede aprobarse producto sin marca, nunca inventarla |
| D04 ¿AccessPRO→Overhead y otros textos son correctos? | CAN_BE_RESOLVED_LATER | Validación comercial por ficha antes de emitir fabricante como hecho | Mantener evidencia, sin auto-corrección; si no se valida, omitir marca y registrar excepción |
| D05 ¿Vacíos151–164 y Prueba165? | BLOCKS_MIGRATION | Sólo esos15 registros; editor aprueba completar/omitir | REVIEW; no publicación ni SKIP automático |
| D06 ¿Jerarquía comercial en conflictos? | BLOCKS_MIGRATION | Producto3 y términos25/26/33 y relaciones afectadas; editor | Datos candidatos privados; colecciones preservan navegación sin alterar parent |
| D07 ¿Modelo/PDF de páginas contradictorias? | BLOCKS_MIGRATION | Fichas afectadas y enlaces técnicos; catálogo autorizado | No cambiar modelo ni sustituir PDF por semejanza |
| D08 ¿Recursos ausentes/PDF correcto? | BLOCKS_MIGRATION | Páginas/descargas afectadas; recuperar archivo o aprobar alternativa | REVIEW explícito; no decir que recurso inexistente dará200 |
| D09 ¿Tráfico/alias/backlinks? | CAN_BE_RESOLVED_LATER | SEO/hosting aportan logs/crawl; bloquea decisiones destructivas y301 no equivalentes | Preservar rutas públicas validadas y contenido; no eliminar variantes ni activar599 derivadas a ciegas |
| D10 ¿Respuesta/configuración de hosting? | BLOCKS_MIGRATION | Gate de despliegue, proveedor/administrador | Probar routing .php/PDF/HTTPS/404 y rollback antes de corte; revisar ADR si no soporta estrategia |
| D11 ¿Contactos/correo/responsables vigentes? | BLOCKS_MIGRATION | Propietario confirma antes de publicar contacto/accesos | Configuración sin números elegidos por intuición; staging no envía a producción |
| D12 ¿Exportación Excel y otros procesos? | OPTIONAL | Cliente confirma necesidad | No implementar exportación ni módulos heredados por defecto |

## Condiciones técnicas de implementación futura

- Fijar versiones concretas WP/Yoast compatibles y ensayadas en PHP8.4; la arquitectura no afirma compatibilidad por una búsqueda de versiones.
- Prototipo de rutas .php, queries, term links, sitemap y canonical en staging antes de construir el mapa productivo completo.
- Validar capacidad de Hepsia para document root/routing, HTTPS, FTPS o canal seguro, almacenamiento privado de paquetes, uploads no ejecutables, límites y restauración.
- Obtener baseline visual de familias; su ausencia bloquea certificar paridad, no registrar CPT.
- Revisar licencia de plantilla/fonts/recursos reutilizados; sustituir implementación si la licencia no permite redistribución, preservando identidad con recursos autorizados.
- Confirmar mecanismo de backup/MFA/transporte; no presumir características del hosting.

No existe un bloqueo funcional global para comenzar posteriormente el esqueleto de implementación. Sí se requiere una nueva tarea autorizada: este diseño sólo entrega documentación. El corte queda prohibido mientras haya URLs públicas requeridas sin cobertura o gates operativos sin validar.

## Estado de las decisiones tomadas

ADR-001..009: ACCEPTED_FOR_DESIGN. Significa elección técnica razonada para implementar en otra fase, no aprobación de mappings, MERGE/SKIP, versiones o despliegue. Nuevas evidencias pueden sustituir un ADR mediante otro, conservando historia. El mapa preliminar de fase1 queda como evidencia histórica; los documentos00–20 y los ADR tienen prioridad para el diseño.

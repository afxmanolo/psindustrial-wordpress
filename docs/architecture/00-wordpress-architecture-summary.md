# Arquitectura WordPress de PS Industrial

Estado: **diseño formal completado; implementación no iniciada**. Fecha:2026-09-17. Sustituye las hipótesis del mapa preliminar de fase1 para decisiones de diseño; los inventarios anteriores permanecen como evidencia. Las decisiones de catálogo aún no aprobadas siguen pendientes, no se resuelven por arquitectura.

## Respuestas rápidas

| Pregunta | Decisión |
|---|---|
| 1. ¿Tipos de contenido? | Un CPT de producto, Pages editoriales/landings, dos taxonomías y Media attachments. Sin ecommerce ni tablas de negocio personalizadas. |
| 2. ¿Productos? | CPT **psi_producto**, nombre Producto/Productos, categorías múltiples y marca opcional única, imagen destacada, galería/PDF/videos ordenados, revisiones y estado editorial nativo. No uno por fila SQL. |
| 3. ¿Categorías? | Taxonomía jerárquica **psi_categoria**. Un padre, varios términos por producto, principal opcional. Navegación transversal mediante colecciones; archivos con hijos y productos directos, no descendientes automáticos. |
| 4. ¿Marcas? | Taxonomía no jerárquica **psi_marca**, logo attachment, descripción/SEO, orden y archivo público. No CPT ni Page duplicada por defecto. |
| 5. ¿Páginas SEO? | **Pages**, templates y bloques restringidos. Las9 landings candidatas conservan intención/URL y no se convierten automáticamente en productos o términos. |
| 6. ¿Imágenes/PDF? | Biblioteca de medios seleccionada desde uso/evidencia. No importar1.355 filas file ciegamente. Versiones nuevas como attachments nuevos; rutas PDF históricas conservadas selectivamente y descargas por ID mapeadas. |
| 7. ¿Qué URLs conservamos? | Estrategia **híbrida**: rutas legacy públicas validadas, incluidas .php, permanecen primarias200. Altas nuevas limpias: /producto/, /categoria/, /marca/ y Pages. No publicar endpoints internos por figurar como DIRECT_FILE. |
| 8. ¿Redirects? | Futura tabla exacta derivada de url-master y aprobación por equivalencia.301 de un salto sólo cuando corresponde; sin reglas ni destinos definitivos generados ahora. |
| 9. ¿Theme? | **psindustrial**: presentación PHP, theme.json, patrones/componentes, CSS/JS, responsive y accesibilidad. Tema clásico con bloques, sin builder. |
| 10. ¿Core plugin? | **psindustrial-core**: modelo/campos, permisos, relaciones, configuración, consultas, bloques de datos, URLs, adaptador SEO, contacto e importador. |
| 11. ¿Admin del cliente? | Productos, Categorías, Marcas, Páginas, Medios, Contacto del sitio y SEO contextual. Rol Gestor de contenidos; no plugins, código, usuarios, importaciones, redirects o ajustes sensibles. |
| 12. ¿Campos personalizados? | APIs nativas + controles/metaboxes propios y wp.media. **Sin ACF**: conjunto acotado, portabilidad y esquema explícito. |
| 13. ¿Migración? | Plan/manifest aprobado desde SQL+PHP+maestros; paquete privado; importador por APIs en lotes reanudables desde admin, compatible con producción sin SSH. Claves estables/hashes y conflicto por campo; no sobrescribir edición humana. |
| 14. ¿Pendientes? | Fuente vigente, merges/identidad, fabricantes/modelos conflictivos, vacíos/prueba, jerarquías excepcionales, medios ausentes, evidencia HTTP/tráfico, hosting y contactos. Clasificación completa en20. |
| 15. ¿Bloqueo para comenzar implementación? | **No hay bloqueo global de arquitectura.** Puede iniciarse en otra tarea autorizada; hay gates de importación/publicación/corte y casos individuales REVIEW. Aquí no se implementa nada. |

## Decisiones complementarias

- SEO: **Yoast SEO gratuito**, un solo propietario de metadata/sitemap/grafo básico. Lógica de URLs y modelo sigue en core. Se aceptan sus índices derivados de tercero; no tablas personalizadas propias. No módulos Premium, IA o ecommerce necesarios. Justificación y límites en08 y ADR-007.
- Contacto: formulario pequeño en core, wp_mail y SMTP configurado privadamente; validación, límites y anti-spam real. No PHPMailer legacy embebido ni almacenamiento de leads no demostrado.
- WhatsApp: una option de configuración validada, consumida por todos los botones; número vigente pendiente del negocio.
- Usuarios: Administrator y psi_gestor nuevos. No migrar hashes/usuarios/permisos del CMS viejo.
- Operación: Laragon/PHP8.4 → GitHub → Hepsia staging → Hepsia producción; main/develop/feature/*. Artefactos y actualizaciones por admin/transferencia segura, sin SSH ni sincronizar DB local hacia producción.

## Qué evidencia condiciona el diseño

165 registros SQL;136 con una ficha principal documentalmente inequívoca, pero sin FK PHP↔SQL.150 filas se agrupan provisionalmente en77 fichas; hay32 fichas/familias estáticas adicionales,14 vacíos y Prueba. No se fija un número final de productos con esos conteos.

38 categorías y12 marcas;28 registros sin marca fiable. ID3 contradice la categoría;27/28 comparten un grid;35 no puede apropiarse de la URL de una ficha;26/33 no se fusionan por nombre. Las relaciones muchos-a-muchos y colecciones resuelven navegación sin falsificar jerarquía/fabricante.

179 páginas de contenido,65 no-producto y9 landings candidatas. Algunas páginas de categoría/marca serán archivos de término, otras Pages editoriales; conservar URL no exige conservar el tipo de implementación.

612 archivos referenciados y3 videos;148 rutas PDF localizadas asociadas equivalen a74 hashes. Firma/hash identifican archivos, no pertinencia técnica ni permiso de eliminar enlaces.297 URLs con evidencia local directa no prueban tráfico/indexación;599 derivadas y5 patrones siguen diferenciados.

## Cómo leer las especificaciones y los CSV

01–08: modelo, productos, términos, páginas, medios, URLs y SEO.09–14: tema/core, administración, campos, contacto y permisos.15–20: importador, ambientes, seguridad, rendimiento, pruebas y decisiones abiertas. Los9 ADR registran contexto, decisión, alternativas, consecuencias, riesgos y estado.

**wordpress-content-model.csv** es el contrato de objetos/campos/tipos/validación. **legacy-to-wordpress-final-map.csv** cubre3.266 filas de origen:165 productos,38 categorías,12 marcas,617 PHP,1.531 medios/embeds,901 rutas/patrones y2 conjuntos administrativos. Las filas son fuentes, no3.266 objetos a crear.

El mapa es definitivo en representación/reglas arquitectónicas, **no es una orden de importación**. proposed_action indica MIGRATE/CREATE_FROM_STATIC/REVIEW/SKIP; no hay MERGE aprobado por esta fase. execution_approved=NO en todas las filas. target_candidate_key es provisional, no el entity_key final del manifest ni un ID WordPress. source_hash en este CSV identifica la fila JSON canónica del inventario, no sustituye el hash de binarios/fuentes/transformaciones que calculará el importador. Las claves URL quedan REVIEW: ninguna fila constituye un redirect generado.

Fuentes locales principales: [resumen fase2](../migration/00-migration-readiness-summary.md), [evidencia](../migration/evidence-matrix.md), [decisiones humanas](../migration/manual-decisions-required.md), [familias visuales](../legacy-analysis/13-template-patterns.md), maestros de migración y url-master. Fuentes técnicas primarias se enlazan junto a sus decisiones en cada documento; no se consultó producción ni se envió correo.

## Entrega y límites

Los21 documentos00–20,2 CSV y9 ADR son el diseño solicitado. Se añade evidencia de integridad/verificación. /legacy y /wordpress permanecen intactos; estado Git final y comparación de hashes en architecture-verification.md. No se instala WordPress, no se crean plugins/themes, no se ejecuta SQL/importación y no se hace commit.

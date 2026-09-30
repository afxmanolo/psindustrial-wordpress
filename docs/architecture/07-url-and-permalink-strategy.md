# URLs y permalinks

## Elección: estrategia híbrida conservadora

| Estrategia | SEO | Complejidad/mantenimiento | Compatibilidad/riesgo |
|---|---|---|---|
| Todas las URLs .php | Evita cambios de ruta iniciales | Convención artificial para altas futuras; aliases siguen requiriendo control | WP puede resolverlas por reglas, pero no es el permalink nativo |
| Todo limpio +301 | Consistencia futura | Menos excepciones a largo plazo, gran mapa inicial | Cambia URLs sin conocer tráfico de599 derivadas; riesgo innecesario en corte |
| Híbrida elegida | Mantiene rutas legacy de contenido aprobadas; altas nuevas limpias | Registro acotado de rutas, pruebas y disciplina editorial | Compatible con objetos nativos mediante adaptador de URL; evita cambio masivo |

La evidencia son297 URLs con archivo/literal,599 derivadas y5 patrones, no901 páginas publicadas. Las285 DIRECT_FILE incluyen fragmentos/endpoints, no todas deben exponerse. La política conserva URLs públicas de contenido y PDF validadas, **no publica includes, dumps o backoffice**. No se afirma ventaja SEO inherente de quitar .php.

Altas: /producto/{slug}/, /categoria/{slug}/, /marca/{slug}/; Pages /{slug}/. Legacy: /nombre.php como primaria200 cuando su contenido sea propietario inequívoco. Home /. Las rutas exactas primarias se aprobarán en el manifest; ejemplos del documento no constituyen redirects.

## Registro de rutas: contrato

Option privada `psi_route_registry`, autoload=false, versión y hash. Una entrada por clave exacta: path, query_match (pares funcionales), kind (content/media/redirect), target_entity_key, object_type/id resuelto por entorno, canonical_role (primary/alias), status previsto, allowed_query, evidence, approval. Una única primaria por objeto público; una ruta no pertenece a dos objetos.

El tamaño actual no justifica tabla nueva. Leer registro una vez por petición que pueda ser legacy; usar caché de objeto de request. Actualizaciones por Administrator/importador con validación y cambio atómico de option, backup y rollback. Rutas editables no se mezclan con teléfonos en site settings.

Para preservar .php se registra una regla interna exacta y se construye una consulta WP al objeto real (post_type/p o taxonomía/term). No cargar otro PHP ni producir HTML fuera de la query: deben funcionar is_singular/is_tax,404,Yoast y paginación. Adaptar post_type_link, page_link y term_link a la primaria. Ajustar redirect_canonical sólo para rutas explícitamente reconocidas; no desactivar canonicalización global. [API rewrite](https://developer.wordpress.org/reference/functions/add_rewrite_rule/). Flush una vez en activación/cambio de versión de rutas, nunca por petición.

El despliegue final usa un document root nuevo, sin archivos PHP legacy que Apache ejecute antes de WordPress. El cambio de raíz/routing y compatibilidad .php deben probarse en Hepsia. No se modifica .htaccess en esta fase. Si hosting no permite ese recorrido, bloquea el corte y exige ADR revisado; no cambiar silenciosamente a URLs limpias.

## Resolución y precedencia

1. Rutas reservadas WordPress/admin/REST/login no pueden registrarse como legado; archivos públicos de compatibilidad se sirven como binarios estáticos.
2. Entrada exacta path + parámetros funcionales (productId, CategoriaId, MarcaId, cmd, id para descargas); rechazar parámetros repetidos/ambiguos. No confiar en variables arbitrarias como query WP.
3. Primaria:200 y canonical a sí misma. Alias aprobado equivalente: futuro301 al permalink primario, sin cadenas. Alias aún no equivalente: conservar representación editorial independiente o bloquear publicación del mapeo; nunca asumir merge.
4. Tracking permitido no cambia identidad; se excluye de canonical. Filtros sí afectan contenido y no se descartan. Método POST no se redirige como si fuera GET de contenido.
5. Rutas no registradas siguen WP normal;404 real si inexistentes. No regla global /cualquier.php→/cualquier/ ni todo a portada.

Conservar bytes/case relevantes del path; decodificación controlada una sola vez, sin convertir %2F en separador ni permitir traversal. Normalización de query puede ordenar pares funcionales una vez sin perder valores; probar UTF-8, espacios, acentos y NFC/NFD. Destinos sólo locales y publicados. No abrir redirects a URLs del usuario.

Filtros combinados mantienen intersección y enlaces navegables. Rutas derivadas con IDs no se activan hasta aprobación; una vez aprobada una combinación, resolver por los IDs legacy mapeados, no por numeración WP. Paginación nueva mediante /page/2/ en rutas limpias y ?paged=2 en .php; canonical propio, no siempre página1.

## De url-master a futura tabla de redirects

No modificar la evidencia original. Generar en fase autorizada un plan con source_url exacta, source_kind, entity_key, current_status/canonical/traffic si conocidos, decision (KEEP/REDIRECT/REVIEW/EXCLUDE_INTERNAL), destination, status, query_policy, reason, approver, approved_at y test_expected. Conservar URL-master row key/hash y evidencia.

PRESERVE no es una orden ciega para los297; INTERNAL_ONLY no convierte una descarga parametrizada en endpoint descartable. Los5 PATTERN no son source_url válidas de301. Toda fuente sin dueño queda REVIEW. Aprobación exige equivalencia de intención/contenido, ausencia de colisiones/bucles, destino200, prueba de parámetros y binarios. El presente CSV final-map sólo fija arquitectura y revisión, no es la tabla definitiva.

La estrategia mantiene URLs de PDFs según06; las URLs HTML de attachment no compiten. No renombrar slugs al corregir un título. Los futuros cambios de URL publicados los controla Administrator con plan de compatibilidad, no el gestor accidentalmente.

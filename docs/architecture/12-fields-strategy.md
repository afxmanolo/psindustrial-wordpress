# Estrategia de campos personalizados

## Comparación

| Criterio | WordPress + controles en core | ACF gratuito / ACF PRO |
|---|---|---|
| Mantenibilidad | Pocos campos tipados en repositorio; tests propios | UI/configuración rápida; debe versionarse y actualizarse proveedor |
| Dependencia | WordPress únicamente | Runtime y convenciones ACF; no usar funciones ACF deja de ser transparente |
| Cliente | Misma UI sencilla diseñada para producto | Buenas UI listas; puede añadirse panel genérico innecesario |
| Desarrollo | Más trabajo inicial en listas de PDF/galería | Rápido para formularios; funciones avanzadas según edición |
| Portabilidad | IDs, meta y schema documentados | Datos accesibles pero serialización/field keys requieren adaptador |
| Complejidad | Validadores y wp.media acotados | Menos UI propia, más configuración/dependencia |
| Coste | Sin licencia; mantenimiento propio | Free sin licencia comercial; PRO con licencia periódica |

ACF PRO incluye Gallery y Repeater; la página oficial consultada muestra Personal USD49/año para un sitio. Es referencia de fecha2026-09-17, no cotización ni coste necesario del proyecto. [Funciones y precio oficial](https://www.advancedcustomfields.com/pro/).

## Decisión

Campos registrados con APIs nativas, metaboxes/paneles pequeños de psindustrial-core, y wp.media para selección. No ACF ni herramienta equivalente: catálogo reducido, modelos conocidos, pocas relaciones ordenadas. No desarrollar un constructor genérico de formularios para compensar; definir únicamente controles de producto/término/configuración requeridos.

Alternativa ACF queda documentada, no instalada ni dependencia contingente. Cambiar a ella requeriría ADR nuevo con coste real y formato compatible. El ahorro inicial no supera para este caso el control de esquema y la independencia durante importación.

## Persistencia y validación

Producto/Página: register_post_meta por subtype; campos públicos editoriales con schema REST y auth de edición; procedencia privada show_in_rest=false. Arrays definen items, propiedades requeridas y additionalProperties=false. Los enteros son IDs válidos del tipo esperado, no cualquier número positivo. Término: register_term_meta por taxonomía; ajustes mediante Settings API y permisos específicos.

Lista PDF: cada fila attachment_id entero, label texto≤200, language opcional (código definido, p.ej. es/en). Video: provider allowlist youtube, video_id validado, title≤200. Galería/relacionados: IDs únicos en orden; eliminar una referencia no elimina el objeto. Texto H1≤200. Descripción/cuerpo usa HTML/bloques permitidos, salida escapada por contexto.

Metadatos editoriales compatibles con revisiones se registran con revisions_enabled; CPT soporta revisiones y custom-fields aunque se oculte el metabox genérico. [Contrato register_meta](https://developer.wordpress.org/reference/functions/register_meta/). Los términos y options no tienen revisiones de post: Administrator dispone de snapshot/exportación antes de cambios de jerarquía/configuración.

Guardados parciales REST/autosave no vacían campos que no vienen en la petición; ausencia ≠ lista vacía. No mezclar POST sin unslash con valores ya normalizados. UI y servidor ejecutan reglas coherentes; mensajes por campo. Los límites de cantidad serán protectores (p.ej.50 elementos por lista y20MB por PDF), no se arrastra el máximo5 legacy como regla comercial; ampliar límite sólo con motivo.

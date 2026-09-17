# Riesgos de migración

| Prioridad | Riesgo demostrado | Decisión/prueba pendiente |
|---|---|---|
| P0 | Contenido fijo y SQL sin relación de identidad | matriz revisada PHP↔producto, conflictos sin sobrescribir texto |
| P0 | 54 categoría=0 y 110 marca=0 | reconciliación editorial; no adivinar por nombre |
| P0 | URLs .php, amigables, parámetros y PDF | mapa por URL, status/canonical/backlinks/tráfico; 301 tras decisión |
| P0 | Dump/credenciales en árbol público | confirmar despliegue y tratar secretos fuera de alcance documental |
| P1 | 458 originales sin extensión + 897 variantes | identidad, MIME, hash, orden, descripción y URLs |
| P1 | Logos dinámicos por posición fija | reconciliar logo oficial con marca ID |
| P1 | Padres NULL/0 y filtro no recursivo | navegación/resultados esperados, rutas padre/hijo |
| P1 | Backend inconsistente en copia | recorrido real y permisos; no replicar Super inexistente |
| P1 | H1/H2 JS, titles repetidos, HTML/alt copiados | baseline SEO/visual; cambios editoriales trazables |
| P1 | Mojibake, acentos/case de archivos | conservar bytes; ensayo sin cambiar slug involuntariamente |
| P1 | WhatsApp diferente por generación | confirmar número/texto |
| P2 | Búsqueda/exportación/orden de medios | uso real y equivalencia administrativa |
| P2 | PHP 8.4 y librerías heredadas | no reutilizar CMS; pruebas futuras de importador/WP |

Importar sólo tablas pierde páginas fijas, SEO, enlaces técnicos y asociaciones en HTML. Convertir cada PHP en página independiente perpetúa duplicación y puede competir con productos dinámicos. Reconciliar fuentes antes de decidir destinos.

No descartar PDFs por versión/duplicado ni imágenes sin extensión. file.original_id relaciona derivados, pero varias filas pueden apuntar al mismo binario. Las 1.355 referencias se localizaron; integridad MIME/contenido y apertura visual no certificadas.

Criterio futuro: cada URL prioritaria tiene destino/status esperado; productos y medios completos; taxonomías/branding verificados; formulario/WhatsApp correctos; permisos/sanitización probados; capturas comparadas; importación repetible sin credenciales. Aquí no se implementó transformación alguna.

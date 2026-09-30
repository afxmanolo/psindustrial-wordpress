# Pendientes y límites
1. **Gutenberg visual**: iframe blob vacío en navegador integrado. Guardado y publicación desde Gutenberg en modo código, metaboxes y wp.media fueron comprobados; falta la aceptación visual en Chrome/Edge habitual. No se instaló un editor alternativo ni se alteró core.
2. **Revisión de contenido existente**: no se aprobaron ni migraron datos. Categorías/marcas previas sin estado propio necesitan marcarse públicas manualmente; productos/Pages deben aprobarse al editar. No crear una conversión automática pending→approved.
3. **SEO/URLs**: se conserva la excepción /productos/ del bootstrap. No hay Yoast, adaptador, rutas .php, reglas PDF históricas, redirects ni validación del futuro registro de rutas. WordPress conserva sus slugs nativos.
4. **Frontend**: sólo se añaden salidas mínimas para H1/hero/relacionados; falta composición visual, directorios, grids de hijos, colecciones y templates históricos.
5. **Medios**: comprobación de firma/MIME/tamaño y patrones activos de PDF es una defensa preliminar, no antivirus ni parser exhaustivo. Antes de producción/importación hacen falta almacenamiento no ejecutable, revisión del hosting y evaluación antimalware. Nuevos bloques/rutas deberán añadir sus referencias al guard de borrado.
6. **Reemplazo PDF**: sustituye asociación, no bytes ni URL antigua. Compatibilidad histórica se desarrolla después.
7. **Términos**: no tienen revisiones nativas; sin panel propio de snapshots/restauración. Respaldar DB antes de cambios estructurales. Contactos sí conservan último snapshot privado; no hay UI para restaurarlo.
8. **Retirada de publicados**: se protege del Gestor; solicitud al Administrator por el proceso del equipo, sin sistema interno de tickets.
9. **Contacto**: configuración y generador de enlace listos; no existe aún envío de formulario/SMTP. Destinatario no equivale a correo público.
10. **Importador**: únicamente contratos privados registrados. No se implementó runner, manifest ejecutable, merges, reconciliación automática ni importación. El importador deberá trabajar en borrador y resolver dependencias antes de aprobar/publicar.
11. **UX pendiente de aceptación**: lista de relacionados usa selección múltiple nativa; el orden visual final y mejoras de búsqueda para un catálogo mucho mayor pueden requerir otra iteración. No se agregó una librería.
12. **Requisitos operativos**: no se cambió configuración global, HTTPS, MFA ni backups del hosting. La credencial legacy sigue pendiente de rotación/externalización en su fase, sin reproducirla.

Estos límites no cambian nombres internos ni formato de campos para el futuro importador. La checklist manual es la condición de aceptación de la experiencia editorial en el navegador del cliente.

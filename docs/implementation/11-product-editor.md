# Editor de productos
Mantiene CPT psi_producto y los campos nativos del [contrato](../architecture/02-product-architecture.md). Productos aparece antes de Medios; marca y categoría se administran en sus submenús.

## Campos y finalidad
| Campo | Persistencia | Uso |
|---|---|---|
| Nombre / descripción / resumen | post_title/content/excerpt | Identidad y contenido editorial; resumen opcional |
| Imagen destacada | _thumbnail_id | Imagen principal, distinta de la galería |
| Galería | _psi_gallery_ids: integer[] | Imágenes ordenadas, sin duplicados |
| Fichas | _psi_datasheets: {attachment_id,label,language}[] | PDFs compartibles, ordenados; es/en opcional |
| Video | _psi_videos: {provider,video_id,title}[] | YouTube únicamente; no iframe libre |
| Principal | _psi_primary_category_id | Cero o categoría asignada; no inventa asociación |
| Hero / H1 | _psi_hero_id / _psi_h1 | Presentación alternativa aprobada; fallback al nombre |
| Relacionados | _psi_related_ids | Productos elegidos manualmente; sin autoreferencia |
| Orden | menu_order | Entero no negativo; no jerarquía de productos |
| Revisión | _psi_review_state | pending / approved, separado de post_status |
| Fuentes | _psi_source_keys, _psi_source_hash, _psi_legacy_date | Privadas; futuras operaciones autorizadas, no campos del cliente |

Pages comparten H1, hero y revisión; no se crea CPT de landings. Attachments registran _psi_source_keys, _psi_content_sha256 y _psi_original_name privados.

En Gutenberg los controles propios están dentro de **Cajas meta → Datos del producto** (Pages: Revisión y presentación). Cambios se sincronizan mediante core/editor.editPost con el estado REST, manteniendo el guardado nativo de metaboxes para editor clásico. Campos ausentes no se vacían. No se limita la selección de bloques.

## Medios y video
Selector wp.media con selección múltiple de imágenes, miniatura/nombre, enlace, sustituir, subir/bajar y quitar. Guardar IDs, no URLs. Quitar o sustituir jamás borra el attachment. PDF nuevo es otro adjunto; no sobrescribe bytes del anterior.

PDF: límite 20 MB, firma y MIME verificados; rechazo de indicadores activos conocidos. Imágenes: JPG/PNG/WebP hasta 10 MB, 8000 px por eje y 40 megapíxeles. Los límites del servidor pueden ser menores. No SVG, ejecutables o doble extensión peligrosa. El análisis no sustituye antivirus.

Video: pegar enlace YouTube válido y título; se guardan proveedor + ID, no markup. La lista admite reordenar/quitar; al añadir una fila, completarla o retirarla antes de guardar. Frontend sigue usando enlaces, sin librerías de reproducción.

## Revisión y límites de guardado
Publicar/programar exige nombre, cuerpo o resumen y revisión approved. Borradores pueden estar incompletos. Marca/categoría ausentes no bloquean por sí solas; no se asigna un fabricante por inferencia.

REST rechaza campos inválidos antes de publicar. Guardados nativos conservan datos públicos anteriores ante una publicación inválida y muestran aviso; no existe transacción global entre contenido, términos y meta. El futuro importador deberá preparar borrador → relaciones → aprobación → publicación.

La principal debe estar entre las categorías seleccionadas. En editor clásico puede ser necesario guardar primero categorías. Relacionados usa selección múltiple nativa; el formulario guarda la secuencia del selector, mientras el contrato/API admite un orden explícito. Sólo relacionados publicados se muestran.

Metadatos editoriales admiten revisiones, con restauración comprobada. Un estado de revisión pendiente no puede dejar un producto publicado sin aprobación mediante un borrado directo de meta. Procedencia no aparece en REST público ni en UI del Gestor.

# Categorías y marcas
Referencias: [categorías](../architecture/03-taxonomy-architecture.md), [marcas](../architecture/04-brand-architecture.md), ADR-002/003.

psi_categoria mantiene name, slug, description, parent y relaciones nativas. psi_marca es no jerárquica. Producto admite categorías múltiples y cero/una marca. El selector propio de marca sustituye al selector múltiple de etiquetas en la ficha; el servidor conserva la regla incluso sin JavaScript.

## Metadata común
- _psi_h1: texto de hasta 200 caracteres.
- _psi_order: entero no negativo.
- _psi_public_state: review/public; valor inicial review.
- _psi_source_keys y _psi_source_hash: privados.
- Categoría: _psi_image_id; marca: _psi_logo_id. Cero o attachment de imagen válido.

El formulario incluye previsualización del archivo, selección/sustitución/retirada y enlace al original. No se guardan rutas absolutas. Nombre/slug/descripción siguen siendo controles nativos.

## Visibilidad y estructura
Review no aparece en listados públicos de términos ni REST anónimo; su archivo devuelve 404. Usuarios con permisos de términos pueden editarlo. Cambiar a Revisado y público habilita la presentación; el nombre de marca no constituye por sí mismo verificación comercial.

Gestor puede crear hijos y editar descripciones/medios. No puede cambiar slug/parent de términos públicos ni retirarlos a review; eso requiere Administrator. REST rechaza el cambio, las APIs nativas conservan el valor protegido. Se rechazan ciclos. Gestor no puede borrar términos; Administrator conserva las capacidades nativas.

Los archivos de categoría consultan productos asignados directamente, sin agregar automáticamente descendientes. El diseño final del grid de hijos y su orden visual corresponde a la fase de frontend; _psi_order ya está disponible para esa composición.

Los términos existentes sin metadata de revisión requieren decisión manual: no se aprobó nada por lote. Antes de cambios importantes de estructura, Administrator debe respaldar la DB; aún no existe interfaz de snapshots/restauración de términos.

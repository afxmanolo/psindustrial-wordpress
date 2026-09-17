# Content Admin — entrega
Fecha: 2026-09-17. Rama: feature/content-admin. Plugin 0.2.0; WordPress 7.1/PHP 8.4.24. Continúa el [bootstrap](00-bootstrap-summary.md).

## Resultado
Modelo editorial y administración implementados sobre el código integrado, sin sustituir el theme ni reproducir el frontend histórico:
- Producto, categorías jerárquicas, marca única opcional y Pages.
- Galería con miniaturas, orden y eliminación de asociaciones; PDFs con sustitución, enlace, etiqueta e idioma; YouTube con título.
- H1, hero, relacionados manuales, orden y aprobación editorial.
- Términos con logo/imagen, orden, H1 y estado review/public.
- Rol psi_gestor, permisos por objeto y configuración pública separada del destinatario de correo.
- Listado de productos con imagen, categorías, marca, cantidad de PDFs, revisión y fecha; filtros combinados.
- Procedencia privada registrada, sin importador ni ejecución sobre datos legacy.

El modelo se puede utilizar manualmente. Los próximos trabajos de importación deben consumir estos contratos y los ADR, no asumir que un registro SQL equivale a un producto.

## Ajustes intencionados / alcance
Se mantiene /productos/ por la excepción solicitada y documentada en bootstrap; no se resuelve aquí su indexación final. Gutenberg conserva sus bloques nativos: no se impuso la allowlist restrictiva del diseño porque esta petición solicita no restringirlo innecesariamente. Los permisos y el saneado nativo siguen protegiendo HTML del Gestor.

La composición histórica, bloques psi/collection/contact/datasheets/whatsapp, adaptador SEO, canonical/routing .php y manifest/importador no se implementan. Publicación exige revisión y contenido; la validación de colisiones del futuro registro legacy se añadirá con la capa de URLs. Actualmente WordPress resuelve slugs únicos nativos.

No se aprobaron automáticamente contenidos existentes. Los términos del bootstrap sin _psi_public_state permanecen en revisión a efectos públicos hasta revisión manual. Los productos/Pages existentes no se despublican automáticamente; deben aprobarse al editar/publicar. No se modificaron sus datos por lotes.

## Verificación
Suite ampliada en tests/smoke.php, checksums legacy, lint y verificaciones HTTP: [15-content-admin-testing.md](15-content-admin-testing.md). Checklist y límites: [16](16-content-admin-manual-checklist.md), [17](17-content-admin-known-issues.md). Archivos creados/modificados: [content-admin-files.md](content-admin-files.md).

No se importó contenido, no se tocó /legacy, no se instaló Yoast/ACF, no se cambió core/configuración y no hubo commit ni push.

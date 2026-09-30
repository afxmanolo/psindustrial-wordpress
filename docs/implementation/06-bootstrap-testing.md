# Verificación del bootstrap
Fecha: 2026-09-17. PHP 8.4.24 CLI/Apache; MySQL 8.0.30; WordPress 7.1.

| Prueba | Resultado |
|---|---|
| Sintaxis PHP propia | 28 archivos OK |
| Sintaxis JS propia | 2 archivos OK con node --check |
| Integración CLI | 55 aserciones OK |
| Theme/plugin activos | Confirmado por APIs WordPress |
| CPT/taxonomías y capacidades Administrator | Confirmados |
| REST CRUD y metadatos | Producto publicado, relaciones, imagen, galería, PDF, video y logo |
| Validación | PDF como logo, imagen como PDF, principal no asignada y múltiples marcas rechazados |
| Metabox | Nonce inválido no guarda; texto saneado; campos ausentes conservados |
| Permisos | Anónimo no edita; Subscriber no edita producto/meta ni logo |
| YouTube/configuración | Host externo rechazado; URL válida normalizada; email inválido conserva opciones |
| HTTP frontend | Home, Page, single, archive, categoría, marca: 200 con marcadores de template |
| HTTP 404 | Estado 404 y template error404 |
| PDF sintético | URL del archivo responde 200 |
| Directorio privado | HTTP 403 |
| Admin autenticado | Listado Productos, nuevo producto, Marcas y opciones PS Industrial: 200 y controles esperados |
| Core íntegro | 3.782 checksums coinciden; sin faltantes/modificaciones |
| Legacy íntegro | 3.862 archivos coinciden con baseline |
| Git | Sólo archivos propios/documentación nuevos; nada staged |

La suite versionada está en plugin/tests/smoke.php. Se utilizó un PNG diminuto y un PDF sintético mínimo para validar asociaciones y HTTP, no para validar renderizado de documentos comerciales. Todos los fixtures se limpiaron, incluido Subscriber temporal. No hay productos/Pages/medios/términos de ejemplo retenidos.

La portada se abrió en el navegador y muestra cabecera, navegación, contenido temporal y pie. Las pantallas administrativas se comprobaron mediante sesión HTTP autenticada. No se ha automatizado interacción completa con el modal wp.media ni todos los pasos visuales del editor Gutenberg; revisión manual pendiente, no se afirma cobertura E2E.

No se observaron fatales, warnings o deprecated de PHP en las ejecuciones finales. Durante preparación se corrigió la validación REST de término: el filtro de preparación de términos no permite devolver WP_Error de forma segura; se valida antes del callback REST, manteniendo los guards de metadata.

No constituye aceptación de SEO, accesibilidad completa, rendimiento, paridad visual o migración. Esas suites pertenecen a fases posteriores.

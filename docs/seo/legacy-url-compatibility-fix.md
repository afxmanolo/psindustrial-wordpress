# Corrección de compatibilidad legacy — productos y cobertura raíz

Fecha: 2026-09-30. Rama `fix/legacy-url-compatibility`. Sólo local; sin commit/deploy.

## Causa comprobada y límite del diagnóstico

El código anterior de `LegacyUrls::destination()` **ya usaba `get_permalink($rule['wp_id'])`**. No hay una concatenación `/{legacy_id}/producto/{slug}/` en esta clase ni un filtro propio de permalink en el código inspeccionado. Por tanto no se atribuye falsamente a esa línea la generación del prefijo 122.

La omisión comprobada está en `LegacyUrls::resolve()`: reconocía categorías y marcas numéricas, pero no la regla de `legacy/public/.htaccess:5`, `^([\d]+)/producto/(.*)/$ -> productos.php?cmd=loadProductos&productId=$1`. Esa ruta quedaba fuera del resolver, a disposición del servidor y la canonicalización de WordPress. Local devuelve 404, no reproduce el Location incorrecto de producción. Para atribuir **la causa exacta del redirect observado** aún se necesita su URL de origen completa y la cadena de headers Location/X-Redirect-By de producción o configuración efectiva del servidor; no se accedió a producción.

La identidad `sql:productos:122` converge, junto con 33 y 133, en el producto local 1124, slug `puerta-holandesa`, hoy draft. Producción está publicado según QA del usuario. No se publicó localmente para probar. Se usó una copia del objeto sólo en caché para verificar exactamente `/producto/puerta-holandesa/` (descontando el subdirectorio local), usando el permalink nativo.

## Cambios

- Ruta numérica de producto resuelta por `_psi_source_keys` mediante `Identity::find()` existente: respeta alias de merges y no interpreta el número histórico como post ID.
- Rama `wp_id` existente: conserva sus 78 mappings; exige objeto existente, tipo `psi_producto`, estado `publish` y permalink HTTP(S) aceptado por WordPress. Missing/trash/otro tipo/identidad ambigua fallan cerrado.
- Nuevos mappings de producto utilizan `entity_key`, sin conversión masiva de los 78 antiguos.
- Lookup ignora query/fragment; el dispatch conserva query y responde 301. Se rechaza el destino igual al path de origen; rutas WordPress válidas no se interceptan.
- No cambios en Identity/Runner/Planner/importer, .htaccess ni rewrite rules. No flush.

## Cobertura

[legacy-root-coverage.csv](legacy-root-coverage.csv) cruza los 198 PHP físicos raíz y las URLs raíz de evidencia directa/enlaces internos del inventario: **199 rutas distintas** (incluye el enlace `industial.php`, sin archivo).

| Clasificación antes del cambio | Cantidad |
|---|---:|
| Cubiertas por mapa | 133 |
| Faltantes con identidad única demostrada | 21 |
| Internas / 404 intencional | 16 |
| Ambiguas o sin equivalente demostrado | 29 |

Las reglas dinámicas de categoría/marca/producto cubren sus patrones numéricos, **no** sustituyen mappings de raíz `.php`. Los controladores `productos.php`/`categorias.php` con parámetros no se convierten por intuición en un único destino. El filtro combinado categoría+marca sigue sin destino inventado.

Los 21 nuevos casos requieren que **todos** los IDs de `product-master.csv` para ese archivo resuelvan al mismo producto a través de `_psi_source_keys`. La columna evidence enumera IDs y objeto. Todos esos destinos son draft en esta BD local: identidad demostrada no significa URL pública activa; sólo se redirige al publicarse. No se resuelven REVIEW ni se altera contenido.

Nuevas reglas: barreras-estacionamiento-moovi50rm.php; coleccion-modern-steel.php; cortina-en-aluminio-serie-511-521.php; cortina-europea.php; cortina-plana.php; cortina-serie-600.php; cortinas-ventiladas-685.php; energy-series-with-intellicore-37171-3-4.php; fast-seal-high-performance-door.php; icaro-smart.php; labio-de-elevacion-mecanico-dockman.php; lift-master-mod-h.php; operador-para-perfilados-comerciales-sel.php; puerta-holandesa.php; puerta-seccional-de-acero-thermacore-serie-592.php; puerta-y-fijos-louver.php; puertas-blindadas.php; puertas-seccionales-de-acero-aisladas-thermospan-modelo-150.php; puertas-seccionales-de-aluminio-521.php; rapida-apilable.php; rolli-zip.php.

**puerta-estandar.php no se añade aún:** inventarios identifican candidatos SQL 31/119/131; ninguna de esas identidades existe resuelta en la BD local. `q03-multi-category.php` los identifica como grupo bloqueado. No hay prueba de cuál producto de producción los representa. Requiere obtener la identidad/post de destino y evidencia de equivalencia desde ese ambiente; no basta el nombre. Lo mismo aplica a los demás casos de la columna AMBIGUOUS_OR_NO_EQUIVALENT, listados exhaustivamente en el CSV.

## Portabilidad

Los 78 mappings antiguos `wp_id` sólo conservan identidad entre ambientes si se preservaron esos IDs al copiar/restaurar la BD y no se eliminaron/recrearon objetos. La documentación describe export local/import staging, pero no acredita una copia concreta staging→producción. CI/CD transfiere código, no BD. No se afirma que esos IDs estén verificados en producción. Hay riesgo real si divergen; el nuevo check de tipo reduce errores pero no detecta que un ID corresponda a otro producto publicado. No se rediseñó identidad en esta corrección.

## Validación

`tests/legacy-url-compatibility.php`: **540/540 PASS**; fixtures sólo en caché, sin publicar ni editar productos; cubre permalink exacto, 78 mappings antiguos, 21 nuevos, tipos/estados inválidos, query, loops, 301 HTTP, unknown 404 HTTP, taxonomías y Pages, inventario raíz y ausencia de cambios persistidos de post.

`tests/legacy-url-redirects.php`: 79/79. Su caso unknown usa ahora un nombre realmente desconocido, pues puertas-blindadas.php ya tiene identidad demostrada. Reportes de ejecución guardados fuera del repo. Sin cambios de reglas de identidad para satisfacer pruebas.

PHP syntax y `git diff --check`: correctos. Baseline legacy: 3.862 archivos, cero diferencias.

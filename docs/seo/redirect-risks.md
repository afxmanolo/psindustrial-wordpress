# Riesgos de redirección y conservación de URLs

No se creó, modificó ni propuso como definitiva ninguna regla de migración.

| Caso | Riesgo | Acción documental/futura |
|---|---|---|
| PHP fijo y producto dinámico parecido | fusionar entidades distintas o perder contenido | reconciliar campos/medios e intención por URL |
| `/` e `/index.php` | duplicación posible | medir canonical/status real antes de elegir |
| slug libre con ID | múltiples URLs pueden cargar misma entidad | validar aliases observados; no sólo generar slug nuevo |
| rutas con query | perder cmd/IDs/filtros | inventariar equivalencia y precedencia QSA |
| combinación cat/marca | perder intersección de catálogo | decidir representación equivalente sin redirigir genéricamente |
| padre vs hoja | convertir listado de subcategorías en productos diferentes | conservar ambas semánticas o mapping aprobado |
| archivo .php sin enlaces actuales | pérdida de backlinks/histórico | verificar logs y Search Console; no borrarlo por nombre |
| PDF directo vs file.php | romper fichas indexadas/enlazadas | mapear ambos a mismo recurso cuando se demuestre identidad |
| nombres con acentos/Unicode/case | 404 al normalizar | conservar bytes, confirmar NFC/NFD y sensibilidad Linux |
| 404.php ausente | errores de respuesta, posibles soft 404 | medir servidor; no cambiar htaccess en esta fase |
| prod / demo / localhost | base URL distinta y /macheteria | separar entorno de ruta pública de referencia |

`.htaccess` tiene rewrites internos, sin 301/302. `Config::redirecDomain` contiene 301 pero llamada comentada; no contar como redirect efectivo. `system/index.php` envía Location a ../index.php sin status explícito, normalmente 302. Application::redirect también usa Location en rama no-include; su uso es principalmente flujo de administración.

FO::validateURL contiene infraestructura heredada para otros módulos, pero no se observó llamada desde los recorridos principales de productos/categorías. No adjudicar canonicalización automática al catálogo por encontrar el método.

No hay prueba local de redirect global HTTP→HTTPS/www; generar ABS_HTTP_URL=https no lo implementa. Apache superior, hosting o proxy podrían hacerlo. Estado remoto queda UNKNOWN.

Para futuros 301: origen exacto → contenido identificado → destino revisado equivalente → status 301 de un salto → sin bucles → probar parámetros, acentos, PDFs y enlaces internos. No redirigir indiscriminadamente URLs antiguas a portada. Sin ejecución ni cambios en esta fase.

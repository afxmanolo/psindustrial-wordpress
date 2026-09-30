# QA de staging — checklist

Ejecutar después de completar `staging-deployment.md`. Read-only salvo el propio envío de
prueba del formulario (§9, que no debe entregar correo — ver `staging-deployment.md` §8).

## 1. Home
- [ ] 200, sin errores PHP visibles
- [ ] Header, menú, dropdown "Soluciones", footer presentes
- [ ] Slider funciona, marcas visibles, sección de catálogo carga

## 2. Nosotros
- [ ] 200, H1 único, contenido íntegro, imagen carga

## 3. Contacto
- [ ] 200, H1 único, formulario visible, datos de contacto correctos

## 4. Catálogo (`/productos/`)
- [ ] 200, H1 "Productos", lista sólo productos publicados (nada en draft/review visible)

## 5. Producto
- [ ] Producto publicado actual: 200, galería, ficha, botón PDF visible
- [ ] Producto todavía draft/review: 404 normal (nunca visible públicamente)

## 6. PDF
- [ ] Botón "Ver ficha técnica" descarga/abre el PDF real, sin 404

## 7. Mobile (375) y tablet (768)
- [ ] Menú hamburguesa abre/cierra, navega correctamente
- [ ] Sin overflow horizontal en Home/Nosotros/Contacto/producto

## 8. Redirects legacy — muestra representativa

| URL legacy | Esperado |
|---|---|
| `/index.php` | 301 → raíz |
| `/nosotros.php` | 301 → `/nosotros/` |
| `/contacto.php` | 301 → `/contacto/` |
| Un producto legacy ya publicado (ver `docs/seo/redirect-map.csv`, columna `resolution=REDIRECT_301`) | 301 → su URL canónica actual |
| Un producto legacy con destino aún en draft/review | 404 (nunca redirect inventado) |
| `.php` desconocido/inexistente | 404 |
| Cualquier URL moderna (`/nosotros/`, `/productos/`) | 200 directo, nunca pasa por la capa de compatibilidad |

- [ ] Ningún redirect encadena a otro redirect (chain)
- [ ] Ningún redirect vuelve a la misma URL (loop)

## 9. Email
- [ ] Enviar un mensaje de prueba real por el formulario
- [ ] Confirmar que **no llega correo** (staging = `WP_ENVIRONMENT_TYPE=staging`, nunca `production`)
- [ ] Confirmar que el formulario sí valida/rechaza correctamente (nombre vacío, email inválido, mensaje muy largo, doble envío rápido)

## 10. 404
- [ ] Una URL inventada cualquiera devuelve el 404 real del theme, no un redirect genérico a Home

## 11. Sitemap
- [ ] `/wp-sitemap.xml` — confirmar comportamiento (permanece inactivo mientras `blog_public=0`; no es un error)

## 12. Robots
- [ ] Meta `robots: noindex, nofollow` presente en el `<head>` de cualquier página
- [ ] `/robots.txt` responde (en dominio raíz de staging, a diferencia del local en subdirectorio)
- [ ] Verificar que la protección de acceso adicional (§7 de `staging-deployment.md`) está activa si se configuró

## 13. Performance básico
- [ ] Home carga sin recursos rotos (consola del navegador sin 404 de assets)
- [ ] Imágenes usan `srcset`/`loading="lazy"` (ya implementado; confirmar que sigue así tras el despliegue)
- [ ] Sin degradación visible de LCP/CLS respecto al local (comparación cualitativa, no herramienta de medición en esta fase)

## Qué NO se prueba aquí

Entrega real de email (se valida en producción). Rendimiento bajo carga. Compatibilidad
con Cloudflare (fase posterior). SEO editorial (títulos/descripciones siguen en fallback
técnico).

# SEO técnico — estado y preparación

Fecha: 2026-09-21. Verificado en vivo contra el local (`http://localhost/psindustrial-wordpress/wordpress/`), sin Yoast instalado. No se redactó copy SEO editorial (títulos/descripciones siguen siendo fallback técnico).

## Estado por punto

| Punto | Estado | Detalle |
|---|---|---|
| Permalinks | OK | `/%postname%/` nativo; `/producto/`, `/categoria/`, `/marca/` con reglas propias registradas correctamente |
| Titles | OK, fallback técnico | `add_theme_support('title-tag')` activo; WordPress genera `post_title – blogname` de forma nativa. Sin copy editorial nuevo |
| Meta description | **Ausente** | WordPress core no genera meta description por sí solo; requiere Yoast u otro mecanismo (ver abajo) |
| Canonical | OK | `wp_head()` nativo emite `<link rel="canonical">` correcto en cada URL verificada |
| Robots (meta) | OK, correcto para local | `blog_public=0` → WordPress emite `noindex, nofollow` en toda página. Esperado mientras el sitio es local; revertir (`blog_public=1`) es una decisión explícita de despliegue, no de código |
| Robots.txt (endpoint) | **404 en local — comportamiento esperado, no bug** | `wp-includes/class-wp-rewrite.php:1283`: WordPress sólo registra la regla de `robots.txt` cuando la instalación vive en la raíz del dominio. Este local vive en `/psindustrial-wordpress/wordpress/` (subdirectorio); en producción (dominio raíz) se genera solo. **Verificar explícitamente tras el despliegue** |
| Sitemap XML | 404 en local — comportamiento esperado | `wp-sitemap.xml` nativo (WP≥5.5) se autodesactiva mientras `blog_public=0` (evita anunciar un sitemap a la vez que se pide no indexar). Se activará solo al reactivar `blog_public=1` en producción; no requiere código |
| Open Graph | **Ausente** | Ningún tag `og:*` en el theme. Sin Yoast ni implementación propia todavía |
| Schema/JSON-LD | **Ausente** | Ningún `application/ld+json` en el theme |
| Heading hierarchy | OK | H1 único verificado en Nosotros/Contacto/catálogo (`institutional-frontend.md`, `catalog-test-results.json`) |
| Image alt | OK | `alt` presente en las imágenes verificadas (logos, hero institucional); `docs/seo/images-alt-inventory.csv` (fase previa) cubre el inventario legacy completo |
| Pagination/archive | OK | `/productos/` (200, H1 correcto), `/categoria/<slug>/`, `/marca/<slug>/` responden; reglas de paginación nativas de CPT/taxonomía registradas |

## Yoast SEO Free — evaluación

**No instalado** (`get_option('active_plugins')`: sólo `psindustrial-core` + `akismet`). La
arquitectura ya está preparada para admitirlo sin dependencia funcional (ningún código del
plugin/theme requiere su presencia).

Qué ganaríamos instalándolo, concretamente:

- Meta description editable por objeto (hoy: inexistente).
- Open Graph/Twitter Card automáticos (hoy: inexistente).
- Schema.org (Organization, BreadcrumbList, Product básico) automático (hoy: inexistente).
- Sitemap XML con más control editorial (hoy: el nativo de WordPress ya cubre lo esencial
  una vez en producción; Yoast lo sustituye/amplía, no es indispensable).
- Redirects propios — **no lo necesitamos**: la compatibilidad de URLs legacy ya vive en
  `psindustrial-core` (`LegacyUrls`), con evidencia propia; activar el módulo de redirects
  de Yoast sería duplicar una decisión ya tomada en el core del proyecto.

No se instaló ni configuró ningún plugin en esta fase (instrucción explícita). Instalar
Yoast Free sigue siendo una decisión pendiente de autorización, no bloqueante: meta
description/OG/schema básico pueden implementarse igual de forma nativa en
`psindustrial-core` si se prefiere no añadir la dependencia.

## Performance — comprobación rápida, sin cambios de diseño

No se tocó CSS/JS/imágenes del frontend aprobado. Las imágenes institucionales/home ya usan
`srcset`/`sizes`/`loading="lazy"`/WebP (confirmado en la implementación previa,
`institutional-assets.json`, `home-asset-provenance.json`). La nueva capa de redirects sólo
ejecuta un lookup en un array PHP y, como mucho, 1-2 llamadas nativas (`get_post_status`,
`get_permalink`/`get_term_link`) — únicamente en una petición que WordPress ya iba a marcar
404; cero impacto en el resto del tráfico.

## Qué no se hizo

No se instaló Yoast ni ningún otro plugin. No se redactó meta description/OG/schema
editorial (fuera de alcance de esta fase — "SEO editorial masivo" pendiente). No se cambió
`blog_public` ni ningún ajuste de indexación real. No se modificó el diseño visual aprobado.

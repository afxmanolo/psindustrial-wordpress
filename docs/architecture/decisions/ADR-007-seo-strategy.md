# ADR-007 — Un proveedor SEO y lógica de compatibilidad propia

## Context

Metadatos legacy y URLs deben preservarse; core WordPress no proporciona por sí solo toda la administración requerida. Escribir un motor SEO completo no es proporcional.

## Decision

Yoast SEO gratuito como proveedor de head/sitemap/grafo básico. Core controla identidad/rutas/elegibilidad y extensión de producto; un adaptador evita salidas duplicadas. Datos SEO activos en almacenamiento del proveedor, snapshot portable en manifest.

## Alternatives

Sólo WP requiere completar UI/metadatos. SEO entero propio aumenta mantenimiento. Otros plugins son viables pero se fija uno y no se instalan varios.

## Consequences

Sin pago Premium necesario para alcance. Dependencia externa y sus índices derivados aceptados; no tablas propias ni escritura SQL en indexables. Exportación facilita reemplazo futuro.

## Risks

Adaptador/versión y term permalinks deben probarse. No inventar offers/rating ni prometer rich results. Desactivar proveedor es incidente, no permiso para emitir dos motores.

## Status

ACCEPTED_FOR_DESIGN — 2026-09-17. Decisión documental de esta fase; no implementación, migración, aprobación de registros ni despliegue. Se sustituye mediante nuevo ADR si cambia evidencia material.

Referencias: [08-seo-architecture.md](../08-seo-architecture.md), [17-security-requirements.md](../17-security-requirements.md).

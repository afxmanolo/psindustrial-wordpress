# ADR-004 — URLs híbridas conservadoras

## Context

297 URLs tienen evidencia local directa,599 son derivadas y5 son patrones. Hay .php, filtros y PDFs sin comprobación HTTP; cambiar todo carece de justificación SEO.

## Decision

Conservar como primarias las rutas legacy públicas aprobadas, con adaptador exacto al objeto WordPress. Altas nuevas limpias. Redirect301 sólo tras equivalencia/aprobación posterior; PDFs siguen política06. No generar reglas ahora.

## Alternatives

Todo .php prolonga convención para altas; todo limpio+301 aumenta riesgo inicial. Reglas genéricas o portada como destino pierden intención.

## Consequences

Registro acotado en option privada, un propietario por ruta, integración con permalinks/canonical/sitemap. Más pruebas que WP sin legado, menos cambio público en corte.

## Risks

Hosting debe enviar .php al nuevo WP sin ejecutar legacy. Colisiones, Unicode, query y canonicals necesitan prueba; si falla infraestructura, revisar ADR antes de corte.

## Status

ACCEPTED_FOR_DESIGN — 2026-09-17. Decisión documental de esta fase; no implementación, migración, aprobación de registros ni despliegue. Se sustituye mediante nuevo ADR si cambia evidencia material.

Referencias: [07-url-and-permalink-strategy.md](../07-url-and-permalink-strategy.md), [../seo/url-master.csv](../../seo/url-master.csv).

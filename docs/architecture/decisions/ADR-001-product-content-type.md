# ADR-001 — Producto como CPT propio

## Context

165 filas SQL no equivalen a165 productos;150 filas se agrupan provisionalmente en77 fichas y32 fichas/familias no tienen correspondencia SQL fuerte. No hay compras, precios ni pedidos demostrados.

## Decision

Registrar psi_producto en core, con campos nativos, categorías múltiples, marca opcional y relaciones ordenadas de medios. Separar entidad, fuente y URL; acciones MIGRATE/MERGE/CREATE_FROM_STATIC/REVIEW/SKIP con aprobación independiente.

## Alternatives

Pages para todo simplifica inicialmente pero mezcla catálogo/editorial; WooCommerce añade una aplicación comercial innecesaria; un CPT por familia fragmenta gestión.

## Consequences

Menú de catálogo claro, consulta nativa y persistencia portable. Requiere controles específicos y reconciliación por campo. Preservar aliases/landings impide deduplicación ciega.

## Risks

No afirmar77 productos finales ni convertir cada SQL/PHP en entidad. Bloquear fusiones no aprobadas y publicación de vacíos/prueba.

## Status

ACCEPTED_FOR_DESIGN — 2026-09-17. Decisión documental de esta fase; no implementación, migración, aprobación de registros ni despliegue. Se sustituye mediante nuevo ADR si cambia evidencia material.

Referencias: [01-content-model.md](../01-content-model.md), [02-product-architecture.md](../02-product-architecture.md), [../migration/product-master.csv](../../migration/product-master.csv).

# ADR-003 — Marca como taxonomía

## Context

12 marcas agrupan productos y muestran logos; sus campos imagen SQL están vacíos.28 registros de producto carecen de marca fiable y hay atribuciones contradictorias.

## Decision

psi_marca no jerárquica con logo attachment, description, orden, SEO y archivo propio. Producto admite0..1marca aprobada; logo usa relación explícita, no posición.

## Alternatives

CPT ofrece cuerpo complejo pero obliga a otra relación/consulta; Page más taxonomía duplica identidad. Texto libre pierde agrupación.

## Consequences

Un único formulario y destino por marca; no se necesita otro CPT. Variante editorial puede ser Page independiente mientras se revisa equivalencia.

## Risks

No inferir fabricante de un grid erróneo. No inventar Solmmer como término aprobado. Marca ausente no elimina producto.

## Status

ACCEPTED_FOR_DESIGN — 2026-09-17. Decisión documental de esta fase; no implementación, migración, aprobación de registros ni despliegue. Se sustituye mediante nuevo ADR si cambia evidencia material.

Referencias: [04-brand-architecture.md](../04-brand-architecture.md), [../migration/brand-master.csv](../../migration/brand-master.csv).

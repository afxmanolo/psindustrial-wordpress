# ADR-002 — Categorías jerárquicas y navegación separada

## Context

38 categorías, raíz26 conflictiva,27/28 comparten Page y35 comparte nombre con una ficha. El filtro legacy es exacto, no recursivo;14 productos recuperan varios contextos.

## Decision

Taxonomía psi_categoria con padre único, asignación múltiple y principal opcional. Priorizar evidencia visible coherente sobre SQL, pero enlaces transversales se representan como colecciones. Archivos padre muestran hijos+productos directos.

## Alternatives

CPT o Pages como categorías exigen relaciones artificiales. Expandir descendientes por defecto cambia el catálogo. Jerarquía multipadre no está justificada.

## Consequences

Administración sencilla y clasificación consultable; pocas Pages de colección para excepciones. Metadatos de revisión suplen ausencia de drafts de términos.

## Risks

No fusionar26/33 ni mover producto3 automáticamente. Un término review no puede ser dueño de URL pública requerida al corte.

## Status

ACCEPTED_FOR_DESIGN — 2026-09-17. Decisión documental de esta fase; no implementación, migración, aprobación de registros ni despliegue. Se sustituye mediante nuevo ADR si cambia evidencia material.

Referencias: [03-taxonomy-architecture.md](../03-taxonomy-architecture.md), [../migration/category-master.csv](../../migration/category-master.csv).

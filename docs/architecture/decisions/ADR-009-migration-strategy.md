# ADR-009 — Manifest e importación idempotente por APIs

## Context

Fuentes SQL/PHP difieren; producción sólo admin/FTP. Se necesita repetir, auditar y no pisar edición humana; no sincronizar DB local sobre producción.

## Decision

Plan offline aprobado y paquete privado; runner por panel con lotes/checkpoints/lock, WP APIs, claves fuente/entidad, hash y conflicto por campo. CLI local opcional. Rutas/publicación se activan después de validar dependencias.

## Alternatives

SQL directo no preserva APIs/IDs; importador sólo CLI falla sin SSH; copia DB repetida borra edición; importación a ojo no es auditable.

## Consequences

Reejecución sin duplicación como criterio probado, no promesa transaccional. Sin tabla nueva; metadatos privados y options acotadas, logs fuera de público. MERGE sólo autorizado.

## Risks

Fallo entre crear objeto y escribir meta deja parciales: recuperación detecta y bloquea ambigüedad. Paquetes públicos, concurrencia y cambio de fuente invalidan ejecución.

## Status

ACCEPTED_FOR_DESIGN — 2026-09-17. Decisión documental de esta fase; no implementación, migración, aprobación de registros ni despliegue. Se sustituye mediante nuevo ADR si cambia evidencia material.

Referencias: [15-migration-architecture.md](../15-migration-architecture.md), [16-environments.md](../16-environments.md).

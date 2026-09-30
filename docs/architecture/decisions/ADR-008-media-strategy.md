# ADR-008 — Adjuntos seleccionados y continuidad de rutas

## Context

1.355 filas file son820 rutas;612 archivos referenciados incluyen recursos técnicos;148 rutas PDF asociadas son74 hashes.259 originales carecen de extensión y hay3 videos externos.

## Decision

Media Library para originales de negocio seleccionados, identificación por firma/hash, relaciones ordenadas por ID. Derivados se regeneran tras revisar URLs. Preservar copias selectivas de compatibilidad PDF e IDs de descarga; actualizaciones crean nueva versión.

## Alternatives

Copiar1.355 registros duplica adjuntos y residuos; importar sólo archivos con extensión pierde originales; deduplicar rutas por hash rompe enlaces. Video local no requerido.

## Consequences

Menos objetos, trazabilidad many-to-one, continuidad de PDF y reutilización. Copias de compatibilidad pueden duplicar bytes intencionalmente para preservar URL.

## Risks

MIME/hash no validan pertinencia técnica. No borrar compartidos ni reemplazar PDFs históricos sin autorización. Recursos ausentes requieren decisión.

## Status

ACCEPTED_FOR_DESIGN — 2026-09-17. Decisión documental de esta fase; no implementación, migración, aprobación de registros ni despliegue. Se sustituye mediante nuevo ADR si cambia evidencia material.

Referencias: [06-media-architecture.md](../06-media-architecture.md), [../migration/media-master.csv](../../migration/media-master.csv).

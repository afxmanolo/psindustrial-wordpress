# ADR-005 — Presentación en tema; dominio en plugin

## Context

10–12 familias legacy comparten header/footer/grids; se busca preservar diseño sin copiar198 PHP. AGENTS.md exige separación del dominio.

## Decision

Tema clásico propio con bloques/patrones acotados. Core registra CPT/términos/campos, permisos, queries, rutas, importador, configuración y contacto. Bloques de datos viven en core con markup mínimo; tema estiliza.

## Alternatives

Todo en functions.php pierde portabilidad; full-site editing libre amplía superficie de diseño; builder externo añade dependencia; framework CMS repite el problema.

## Consequences

Componentes reutilizables y cambio de tema sin perder catálogo. El gestor edita contenido, no estructura global ni código. Core necesita fallback semántico de bloques.

## Risks

Evitar APIs del tema desde lógica del core; no mantener copias de datos de contacto en templates. Paridad visual requiere capturas, no sólo familias.

## Status

ACCEPTED_FOR_DESIGN — 2026-09-17. Decisión documental de esta fase; no implementación, migración, aprobación de registros ni despliegue. Se sustituye mediante nuevo ADR si cambia evidencia material.

Referencias: [09-theme-architecture.md](../09-theme-architecture.md), [10-core-plugin-architecture.md](../10-core-plugin-architecture.md).

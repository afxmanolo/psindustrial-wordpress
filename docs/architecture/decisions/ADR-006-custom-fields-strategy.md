# ADR-006 — Campos nativos sin ACF

## Context

Galería/PDF/video/logo son pocos campos conocidos; importador requiere tipos/orden y trazabilidad clara. No hace falta un constructor de formularios.

## Decision

register_post_meta/register_term_meta, schemas y auth explícitos, controles wp.media/metaboxes del core. Revisiones para meta editorial soportada y snapshots para términos/options.

## Alternatives

ACF Free acelera controles básicos; ACF PRO ofrece Gallery/Repeater con licencia periódica. Crear un framework genérico propio sería sobreingeniería.

## Consequences

Sin licencia/dependencia ACF y datos portables. Mayor trabajo inicial de UI y validación de arrays, acotado al dominio. No metabox libre de claves para cliente.

## Risks

Probar REST/autosave/revisiones y guardado parcial. No perder arrays porque una petición no incluya el campo.

## Status

ACCEPTED_FOR_DESIGN — 2026-09-17. Decisión documental de esta fase; no implementación, migración, aprobación de registros ni despliegue. Se sustituye mediante nuevo ADR si cambia evidencia material.

Referencias: [12-fields-strategy.md](../12-fields-strategy.md), [wordpress-content-model.csv](../wordpress-content-model.csv).

# Verificación de la fase de arquitectura — 2026-09-17

## Alcance real

Sólo documentación. No se instaló/ejecutó WordPress ni código legacy; no hubo SQL, importación, envío de correo, cambios de .htaccess o commit. Se consultaron documentación local e inventarios completos mediante lectura y análisis de filas, y referencias técnicas primarias oficiales enlazadas en las decisiones. No se consultó el sitio productivo.

## Integridad

- Snapshot SHA-256 de todo el workspace (excluido .git) al iniciar: architecture-integrity-baseline.json.
- legacy: **3862 archivos**, sin cambio de bytes, altas ni bajas.
- wordpress: **0 archivos**, intacto.
- Comparación del workspace: toda diferencia está exclusivamente en docs/architecture. Los inventarios de fases anteriores no cambiaron.
- git diff y git diff --cached para legacy/wordpress: salida vacía y exit0.
- Estado inicial Git limpio; estado final sólo documentación nueva. No se hizo commit.

## Entregables y consistencia

-21 documentos00–20,2 CSV y9 ADR obligatorios presentes; más baseline y esta verificación:34 archivos nuevos.
- wordpress-content-model.csv: 71 entradas de esquema, UTF-8/BOM y filas válidas.
- legacy-to-wordpress-final-map.csv: 3266 filas, claves de fuente únicas, ninguna execution_approved distinta de NO.
- Cobertura exacta de fuentes: sql.productos=165, sql.categorias=38, sql.marcas=12, php=617, media=1531, url=901, sql.user=1, sql.permission=1.
- Acciones usan MIGRATE/MERGE/CREATE_FROM_STATIC/REVIEW/SKIP. No se asignó MERGE a ningún grupo sin aprobación; no se generaron reglas de redirección.
- ADR contienen Context, Decision, Alternatives, Consequences, Risks y Status.
- Enlaces locales Markdown comprobados: ninguno ausente. No caracteres de sustitución en documentos nuevos.
- Revisión de contratos: marca0..1, categoría0..N, término review sin publicación, ruta con dueño único, PDF versionado, filtros exactos y capacidades separadas. Estados de migración no se confunden con post_status.
- Los controles son de documentación: no demuestran todavía funcionamiento, compatibilidad de hosting, paridad visual, rendimiento ni SEO HTTP. Pruebas futuras definidas en19-testing-strategy.md.

## git status --short --untracked-files=all

```text
?? docs/architecture/00-wordpress-architecture-summary.md
?? docs/architecture/01-content-model.md
?? docs/architecture/02-product-architecture.md
?? docs/architecture/03-taxonomy-architecture.md
?? docs/architecture/04-brand-architecture.md
?? docs/architecture/05-pages-and-seo-landings.md
?? docs/architecture/06-media-architecture.md
?? docs/architecture/07-url-and-permalink-strategy.md
?? docs/architecture/08-seo-architecture.md
?? docs/architecture/09-theme-architecture.md
?? docs/architecture/10-core-plugin-architecture.md
?? docs/architecture/11-admin-experience.md
?? docs/architecture/12-fields-strategy.md
?? docs/architecture/13-contact-and-whatsapp.md
?? docs/architecture/14-users-and-capabilities.md
?? docs/architecture/15-migration-architecture.md
?? docs/architecture/16-environments.md
?? docs/architecture/17-security-requirements.md
?? docs/architecture/18-performance-requirements.md
?? docs/architecture/19-testing-strategy.md
?? docs/architecture/20-open-decisions.md
?? docs/architecture/architecture-integrity-baseline.json
?? docs/architecture/architecture-verification.md
?? docs/architecture/decisions/ADR-001-product-content-type.md
?? docs/architecture/decisions/ADR-002-category-model.md
?? docs/architecture/decisions/ADR-003-brand-model.md
?? docs/architecture/decisions/ADR-004-url-strategy.md
?? docs/architecture/decisions/ADR-005-theme-plugin-separation.md
?? docs/architecture/decisions/ADR-006-custom-fields-strategy.md
?? docs/architecture/decisions/ADR-007-seo-strategy.md
?? docs/architecture/decisions/ADR-008-media-strategy.md
?? docs/architecture/decisions/ADR-009-migration-strategy.md
?? docs/architecture/legacy-to-wordpress-final-map.csv
?? docs/architecture/wordpress-content-model.csv
```

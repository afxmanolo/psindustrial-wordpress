# Auditoría de seguridad de los 7 PDF — resumen ejecutivo

Fecha: 2026-09-18. Rama `feature/review-rules`. **Fase exclusivamente de inspección y
análisis.** No se modificó ningún PDF, no se cambió la salvaguarda, no se tocó
`Policy.php`/`Planner`/`Runner`, no se modificó la base de datos, no se ejecutó ninguna
importación, no hubo commit ni push. Continúa este informe el hallazgo documentado en
[06-known-issues.md](../review-rules/06-known-issues.md) de la fase LOW.

## Metodología (para que el resultado sea reproducible)

- **`pikepdf` 10.13.0** (envuelve `qpdf`), instalado localmente vía `pip install pikepdf`
  para esta auditoría. Lee la estructura de objetos del PDF (catálogo, páginas, anotaciones,
  árboles de nombres, streams) sin renderizar ni ejecutar nada.
- Escaneo de bytes crudos con la **expresión regular exacta** de
  `Media::file_valid()` (`/(?:JavaScript|JS|OpenAction|Launch|EmbeddedFile)\b`, sin distinción
  de mayúsculas) para reproducir con precisión qué disparó el rechazo en cada archivo.
- `pdftotext` (poppler) para comparar texto extraído antes/después de una prueba de
  sanitización.
- Toda extracción de bytes de un stream embebido se escribió únicamente a un directorio
  temporal de scratch (fuera de `/legacy` y del repositorio), nunca se abrió con una
  aplicación asociada, nunca se ejecutó, y se borró al finalizar cada prueba — confirmado
  al final de este documento.

## Qué se encontró

**No son 7 casos iguales. Son dos grupos con causas y niveles de riesgo completamente
distintos**, y la documentación de la fase anterior simplificó esa diferencia:

### Grupo A — 5 PDF, 9 productos (65, 66, 67, 68, 69, 70, 71, 72, 73): coincidencia real, contenido benigno confirmado

Producidos por el mismo flujo: QuarkXPress (Mac) → Acrobat Distiller 6.0.1/7.0.5 (Mac), PDF
1.3. El objeto `/EmbeddedFile` en los 5 es, verificado directamente, un fichero
**`.joboptions` de Adobe Distiller** (texto plano, ajustes de conversión a PDF —
`/EmbedJobOptions true` explica por qué se incrustó). Sin `/OpenAction`, `/AA`, `/AcroForm`,
`/Names/JavaScript`, `/Launch`, multimedia, ni anotación de adjunto visible en ninguno de
los 5. Dos de los cinco comparten el mismo `.joboptions` byte a byte.

→ **Recomendación: `REQUIRES_SANITIZED_COPY`**, confianza HIGH.

### Grupo B — 2 PDF, 2 productos (77, 95): falso positivo, no hay ningún objeto embebido

Producidos por Adobe InDesign (CC2017 / CS4). **No contienen ningún `/EmbeddedFile`.** El
regex coincidió con `/JS`, y esa coincidencia es una secuencia de 3 bytes dentro de datos
binarios comprimidos (un stream), a 51–154 KB de cualquier sintaxis de diccionario PDF real.
El recorrido estructural completo confirma cero mecanismos activos y cero objetos embebidos.

→ **Recomendación: `SAFE_FOR_MIGRATION`**, confianza HIGH — con la salvedad de que, bajo la
comprobación actual sin modificar, seguirán bloqueados en el próximo DRY RUN (no hay ningún
objeto que una copia sanitizada pueda quitar).

## Prueba de viabilidad de sanitización (Grupo A)

Sobre una copia temporal (borrada al terminar), retirar el objeto embebido: hace que el
archivo pase la comprobación existente, reduce el tamaño en ~26 KB, y deja el texto extraído
**idéntico byte a byte** y el número de páginas/versión PDF sin cambios. Detalle completo en
[04-sanitization-options.md](04-sanitization-options.md).

## Respuestas directas

**1–3. Identificación, inspección estructural, embedded files** → [01-pdf-structure-analysis.csv](01-pdf-structure-analysis.csv) y [02-embedded-files-analysis.md](02-embedded-files-analysis.md).

**4. ¿Attachment pasivo o comportamiento activo?** Pasivo en los 5 del Grupo A (ninguna
acción automática lo acompaña); inexistente en los 2 del Grupo B (no hay objeto).

**5. ¿Son variantes del mismo patrón?** Sí, el Grupo A es un único patrón (mismo software,
mismo tipo de adjunto, dos `.joboptions` byte-idénticos entre sí). El Grupo B no comparte
nada con el Grupo A: productor distinto, y ni siquiera coincide por el mismo motivo.

**6. ¿Es necesario el archivo embebido?** No. En los 5 casos es metadato de preimpresión
(ajustes de conversión), irrelevante para el visitante de la ficha técnica.

**7. Opción de sanitización** → probada y documentada en [04-sanitization-options.md](04-sanitization-options.md).

**8. Clasificación completa** → [05-recommendations.md](05-recommendations.md).

**9. Reconciliación 999 vs 1.025** → [06-low-rule-count-reconciliation.md](06-low-rule-count-reconciliation.md).
Resumen: 999 aplicadas (846 SKIP + 153 CREATE) + 18 revertidas (exactamente estos 7 PDF y
sus 11 productos) + 8 filas donde la propuesta de política coincidía con una decisión manual
preexistente = 1.025. Nada se ajustó para cuadrar la cifra.

## Entregables

| Documento | Contenido |
|---|---|
| [01-pdf-structure-analysis.csv](01-pdf-structure-analysis.csv) | Una fila por PDF: rutas, hashes, productos, estructura completa, clasificación |
| [02-embedded-files-analysis.md](02-embedded-files-analysis.md) | Análisis detallado de los objetos embebidos, evidencia de bytes |
| [03-product-impact.md](03-product-impact.md) | Los 11 productos afectados y por qué la reversión fue correcta |
| [04-sanitization-options.md](04-sanitization-options.md) | Prueba de viabilidad de sanitización, qué se preserva |
| [05-recommendations.md](05-recommendations.md) | Clasificación completa con confidence/evidence/reason |
| [06-low-rule-count-reconciliation.md](06-low-rule-count-reconciliation.md) | Reconciliación aritmética 999 vs 1.025 |

## Verificación final

1. **`/legacy` intacto** — ningún archivo bajo `/legacy` fue abierto en modo escritura; los
   7 PDF se leyeron con `open(path, 'rb')` (sólo lectura) en todos los scripts de esta
   auditoría. Confirmado por `git status --porcelain -uall -- legacy` (sin salida).
2. **`/wordpress` intacto** — ningún archivo del plugin/tema fue tocado en esta fase.
3. **Ninguna DB modificada** — esta auditoría no abrió ninguna conexión a base de datos, ni
   local ni legacy; sólo leyó PDF y CSV locales.
4. **Ningún archivo embebido ejecutado** — se extrajeron bytes únicamente para hashear y
   detectar magic bytes (`open(...,'rb').read()` + `hashlib`); en ningún momento se invocó
   `os.startfile`, `subprocess` sobre el adjunto, ni una aplicación asociada. Los 5
   `.joboptions` se inspeccionaron como texto plano (son texto plano) sin interpretarlos.
5. **Ningún PDF original modificado** — la única escritura de un PDF fue la copia de prueba
   de sanitización, creada y borrada dentro del mismo script, en el directorio temporal de
   scratch, nunca sobre el archivo de `/legacy`.
6. **Ninguna regla modificada** — `Policy.php`, `Planner.php`, `Runner.php`, `Media.php`: sin
   cambios (confirmado por `git status` más abajo).
7. **Ningún import ejecutado** — no se llamó a `Planner::build()` ni a `Runner::batch()` en
   ningún script de esta fase.
8. **`git diff --check` limpio** — ver salida literal abajo.
9. **Sin commit.**
10. **Sin push.**

```
$ git status --porcelain -uall -- legacy
(sin salida)

$ git status --porcelain -uall -- wordpress
 M wordpress/wp-content/plugins/psindustrial-core/migration/Planner.php
 M wordpress/wp-content/plugins/psindustrial-core/psindustrial-core.php
?? wordpress/wp-content/plugins/psindustrial-core/migration/Policy.php
?? wordpress/wp-content/plugins/psindustrial-core/tests/policy.php
```

Estos 4 cambios son **heredados de la fase LOW anterior** (ya presentes y documentados antes
de que esta auditoría empezara); ningún archivo de código se tocó durante esta auditoría.

```
$ git diff --check
(sin salida — sin problemas de espacio en blanco)

$ git status --short -uall -- docs/implementation/pdf-security-review docs/implementation/review-rules
?? docs/implementation/pdf-security-review/00-summary.md
?? docs/implementation/pdf-security-review/01-pdf-structure-analysis.csv
?? docs/implementation/pdf-security-review/02-embedded-files-analysis.md
?? docs/implementation/pdf-security-review/03-product-impact.md
?? docs/implementation/pdf-security-review/04-sanitization-options.md
?? docs/implementation/pdf-security-review/05-recommendations.md
?? docs/implementation/pdf-security-review/06-low-rule-count-reconciliation.md
```

Único directorio nuevo de esta fase: `docs/implementation/pdf-security-review/`. Nada más.

## Dependencia de análisis instalada en este entorno

`pip install pikepdf` (versión 10.13.0) se instaló localmente para poder hacer esta
auditoría de forma segura y verificable. Es una herramienta de análisis, no una dependencia
del proyecto WordPress/PHP: no se añadió a `composer.json`/`package.json` (no existen tales
archivos para esta necesidad), no se referencia desde ningún código de `psindustrial-core`,
y no es necesaria para que el importador funcione. Se deja instalada en el entorno Python
local por si se necesita para una auditoría de seguimiento; si se prefiere no conservarla,
se puede desinstalar con `pip uninstall pikepdf` sin ningún efecto sobre el proyecto.

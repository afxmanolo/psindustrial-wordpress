# Simulación previa al FULL DRY RUN

Control de coherencia exigido antes de ejecutar el dry-run real (paso 14 del encargo). Se
calculó **antes** de correr `Planner::build('full')` con la política activa, a partir
únicamente de los CSV maestros (sin abrir ni validar los bytes de ningún archivo real).

## Predicción

| source_type | MIGRATE previsto | SKIP previsto |
|---|---:|---:|
| product | 21 | — |
| category | 33 | — |
| brand | 11 | — |
| page | 5 | — |
| media | 101 | 846 |
| **Total** | **171** | **846** |

`REVIEW` restante previsto: `1.949 − (171 + 846) = 932`.

Esta cifra ya incorpora una corrección sobre el análisis de la fase anterior: el escenario
"sólo LOW" de
[11-rule-simulation.md](../review-analysis/11-rule-simulation.md) reportaba 185 MIGRATE de
medios asumiendo que R-M04 heredaba las 14 páginas de R-G02 completo; esta fase sólo
implementa las 5 páginas LOW (institucionales/contacto), así que R-M04 baja de 42 a un
subconjunto menor, y la prioridad producto/marca-categoría sobre página (ya presente en el
análisis, ahora aplicada literalmente en código) reasigna algunos logos de `index-resp.php`
de R-M04 a R-M05. Detalle del recálculo: [scratch de verificación, no versionado].

## Resultado real (FULL DRY RUN, política activa)

| source_type | MIGRATE real (CREATE) | SKIP real |
|---|---:|---:|
| product | 10 | — |
| category | 33 | — |
| brand | 11 | — |
| page | 5 | — |
| media | 94 | 846 |
| **Total** | **153** | **846** |

`REVIEW` restante real: **950**.

## La diferencia es material — 18 filas — y tiene una única causa, ya explicada

Predicho: 1.017 filas resueltas. Real: 999. Diferencia: **18 filas**, exactamente:

- **7 medios** (`R-M01`) y **11 productos** (`R-P01`) que dependían de ellos.

Causa única: 7 PDF de ficha técnica contienen una referencia `/EmbeddedFile` y son
rechazados por la comprobación de seguridad **existente** de `Media::file_valid()`, pese a
que su SHA-256 coincide con el CSV. Ver el análisis completo en
[06-known-issues.md](06-known-issues.md).

**Por qué la simulación previa no podía predecir esto.** Una simulación basada en los CSV
maestros no abre los archivos reales; sólo puede predecir a partir de relaciones y flags ya
documentados (propietario, duplicado, flags de uso). La comprobación de contenido activo en
PDFs es, deliberadamente, responsabilidad exclusiva del código de validación de `Planner`
sobre los bytes reales — es la razón de ser del diseño en capas: la política propone desde
evidencia documental, la extracción/validación ya existente de `Planner` dispone contra la
realidad del archivo. Una diferencia de exactamente 18 filas, con causa única, trazable y
coherente con el resto de la predicción (product/category/brand/page/SKIP coinciden al 100%)
no es un error de este análisis: es la simulación funcionando dentro de su límite conocido.

## Verificación de coherencia

| Cifra | Predicha | Real | Coincide |
|---|---:|---:|---|
| category MIGRATE | 33 | 33 | ✅ |
| brand MIGRATE | 11 | 11 | ✅ |
| page MIGRATE | 5 | 5 | ✅ |
| media SKIP | 846 | 846 | ✅ |
| product MIGRATE | 21 | 10 | ❌ (−11, PDF `/EmbeddedFile`) |
| media MIGRATE | 101 | 94 | ❌ (−7, mismos 7 PDF) |
| **Total resuelto** | **1.017** | **999** | **−18, causa única y documentada** |

No se ajustó ninguna regla para forzar que la predicción coincidiera. El número real (999,
REVIEW final 950) es el que se documenta como autoritativo en el resto de esta fase.

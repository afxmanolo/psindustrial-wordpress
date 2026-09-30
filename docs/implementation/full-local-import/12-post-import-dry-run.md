# DRY RUN posterior — verificación de idempotencia

Continúa de [11-post-import-audit.md](11-post-import-audit.md). Este documento
cubre **exclusivamente** una reconstrucción del plan (`Planner::build('full')`)
después de la ejecución real. **No se ejecutó una segunda importación.** No se
llamó a `Runner::batch_full_local_resolved_only()` en ningún momento de esta
verificación — sólo construcción de plan (lectura/cálculo puro) y consultas
`SELECT`. Confirmado antes/después: conteo de `posts`/`terms` idéntico, cero
mutación.

```
fresh_run_id: 3a890a71-a543-4c61-81de-4247a017b338
```

## Resultado global del nuevo plan

| planned_result | Nuevo plan (post-import) | Plan original (pre-import) |
|---|---:|---:|
| UNCHANGED | 464 | 15 |
| SKIP | 1.534 | 1.534 |
| CREATE | 39 | 511 |
| CONFLICT | 23 | 0 (bucket no existía) |
| REVIEW | 339 | 339 |
| **Total** | **2.399** | **2.399** |

`UNCHANGED` sube de 15 a 464 — exactamente `15 + 449` (los 15 del subset más los
449 que esta ejecución creó con éxito). `CREATE` baja de 511 a 39. Todo el
movimiento se explica por completo a continuación; no hay ningún cambio sin
explicar.

## Las 449 entidades creadas: ¿el plan las reconoce como ya hechas?

```
Fresh planned_result para las 449 APPLIED de este run:
  UNCHANGED: 449   (100%)
```

**Sí, el 100 %.** Ninguna de las 449 entidades que esta ejecución creó aparece
como `CREATE`, `UPDATE` ni `CONFLICT` en el plan reconstruido — `Identity::find()`
las encuentra, y su contenido actual coincide exactamente con el esperado. Una
segunda ejecución real (si se lanzara) no intentaría tocarlas. **Cero riesgo de
duplicado por una repetición.**

## Las 62 entidades no aplicadas (52 FAILED + 10 CONFLICT): ¿son reintentables?

```
Fresh planned_result para las 62 no aplicadas:
  CREATE:   39   (reintentable de forma limpia una vez corregidos los bugs A/B)
  CONFLICT: 23   (ver desglose abajo)
```

**39 pasan a `CREATE` limpiamente** — nada las bloquea salvo los propios bugs
`MEDIA_CHANGED_REPLAN`/`MEDIA_SIDELOAD_FAILED`/la cascada de dependencia; una vez
corregidos, un reintento normal (mismo mecanismo, mismo run o uno nuevo) debería
aplicarlas sin intervención adicional.

**23 aparecen como `CONFLICT`** en el plan reconstruido — más que los 10 CONFLICT
originales de la ejecución. Los 10 originales (`category:37`, `category:38`, 8
`php:`) siguen exactamente igual, sin sorpresa. Los **13 adicionales** son
precisamente los 13 casos de la causa B (`MEDIA_SIDELOAD_FAILED`: 11 PDFs + 2
imágenes) — al reconstruir el plan, `Identity::prediction()` detecta que existe
un registro de intento previo (el `journal` en estado `INTENT`, escrito por
`Runner::apply()` **antes** de llamar a `media_handle_sideload()`, nunca
limpiado porque el sideload falló) y lo clasifica, de forma conservadora, como
`CONFLICT` en vez de `CREATE` limpio — comportamiento correcto y deliberado del
diseño existente ("nunca reintentar a ciegas algo que ya se intentó"), no un
efecto secundario indeseado.

**Implicación para el reintento futuro**: no bastará con corregir los dos bugs y
volver a ejecutar el batch sin más — esas 13 entidades necesitarán que su
`CONFLICT` se revise primero (confirmar que no existe ningún objeto WordPress
real detrás, sólo el `journal` de intento fallido) antes de que el mecanismo
existente las deje reintentar. Ninguna de las dos situaciones se resolvió en esta
sesión; quedan documentadas para la fase de corrección.

## REVIEW y SKIP: verificación de estabilidad total

```
Fresh planned_result para las 339 BLOCKED (REVIEW) originales:
  REVIEW: 339   (100%, sin excepción)

Fresh planned_result para las 1.534 SKIPPED originales:
  SKIP: 1.534   (100%, sin excepción)
```

Ni un solo REVIEW ni un solo SKIP cambió de clasificación al reconstruir el plan
después de la ejecución real. Cero deriva.

## Conclusión de idempotencia

| Pregunta | Respuesta |
|---|---|
| ¿Repetir la ejecución duplicaría algo ya creado? | No — verificado, 449/449 ahora `UNCHANGED`. |
| ¿Los REVIEW/SKIP siguen intactos? | Sí — 339/339 y 1.534/1.534 sin cambio. |
| ¿Hay algún `CREATE`/`UPDATE`/`MERGE` inesperado? | No. Los 39 `CREATE` restantes son exactamente el subconjunto no aplicado por las causas A/B/C ya documentadas — ninguno es una sorpresa nueva. |
| ¿Algo requiere autocorrección o intervención ahora? | No. Este documento sólo reporta; no se corrigió nada automáticamente. |

No se ejecutó ninguna segunda importación real. No se publicó contenido. No se
tocó staging ni producción.

## Verificación final de repositorio

```
git status --short -uall:
  M  docs/implementation/importer-reports/full-dry-run-after-low-rules.json
  M  docs/implementation/importer-reports/pdf-approvals-tests.json
  M  docs/implementation/importer-reports/policy-tests.json
  M  docs/implementation/importer-reports/runner-media-validation-tests.json
  ?? docs/implementation/importer-reports/*-tests.json (13 archivos nuevos de
     una suite de regresión ejecutada en esta misma fase, sin relación con la
     ejecución real)
  (+ estos 3 documentos nuevos, aún no mostrados por git status al momento de
     escribir este archivo)
```

Sin cambios en `/legacy`. Sin cambios en código fuente del plugin/tema. Sin
commit. Sin push. Rama `feature/review-resolution`.

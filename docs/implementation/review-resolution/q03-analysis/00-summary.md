# Q03 — análisis, sin implementación

Rama `feature/review-resolution`. Este análisis reconstruye los 27 grupos Q03 **desde el
plan vivo** (`Planner::build('full')`, DRY RUN, run `3a90b260-0bd6-4f22-bc1d-fee59f42a743`),
no desde conteos históricos. El plan reconstruido reproduce exactamente el baseline dado al
iniciar esta tarea: `total 2.399 · UNCHANGED 15 · SKIP 1.467 · CREATE 407 · REVIEW 510 ·
ERROR 0` — confirma que nada cambió desde el cierre de Q07/Q10/Q11/Q13 y que la
reconstrucción parte del estado real, no de una copia desactualizada.

**Ninguna decisión se implementó.** No se tocó `EditorialDecisions.php`, `editorial-decisions.json`
ni ningún otro archivo de código o datos del plugin. Este directorio y
`q03-decision-questionnaire.md` son los únicos artefactos nuevos.

## Cómo se reconstruyeron los 27 grupos

1. `docs/migration/canonical-candidate-groups.csv` tiene 77 grupos candidatos, de los cuales
   53 tienen `record_count > 1` (más de una fila SQL).
2. De esos 53, **26 ya están aprobados y resueltos por Q02**
   (`editorial-decisions.json → q02_identity_merge.groups`, verificado contra el plan vivo:
   los 26 ganadores están en `MERGE`).
3. Los **27 restantes son exactamente Q03** — ninguno tiene decisión propia en el plan vivo;
   los 74 filas de producto que los componen (`sql:productos:<id>`) están, sin excepción, en
   `REVIEW` con la razón genérica `Maestro REVIEW: identidad, duplicado o excepción
   editorial; no fusionar ni descartar automáticamente.`
4. Esto coincide exactamente con la aritmética documentada en `00-migration-readiness-summary.md`
   (77 grupos = 24 de una fila + 37 pares + 12 tríos + 4 cuartetos) y con
   `13-human-decisions-map.md` (Q03 → 74 productos, 27 grupos): 11 pares + 12 tríos + 4
   cuartetos = 27 grupos, 22+36+16 = 74 filas. **Verificado, no asumido.**

## Resultado de la verificación uno-por-uno (sección 2 de la tarea)

De los 27, **16 grupos cumplen genuinamente los siete criterios exigidos** (misma entidad,
mismo modelo, mismo contenido sustancial, misma marca, PDFs e imágenes compatibles; única
diferencia relevante = categoría). Esto es **dos más que la estimación histórica de "14"**.
El detalle completo, grupo por grupo, con el motivo exacto de cada inclusión o exclusión,
está en [02-category-only-groups.md](02-category-only-groups.md).

Los **11 restantes** (no 13) requieren mirada individual — pero de esos 11, **7 no tienen
ninguna divergencia de categoría en absoluto**: su único problema es una variante de nombre,
en la mayoría de los casos puramente cosmética (mayúsculas, un guion, un espacio, una "s" de
plural, la corrección de una errata). Sólo **4 grupos** arrastran un problema real de
identidad o de marca — y los cuatro coinciden, uno por uno, con conflictos **ya documentados
antes de esta tarea** en `manual-decisions-required.md` (Modern Steel, LiftMaster/Blue Giant,
Thermospan Wayne/Clopay) o con la exclusión ya codificada en `Policy.php`
(`CATEGORY_CONFLICT_PRODUCTS = ['3']`, el caso Kelley). Detalle en
[04-ambiguous-groups.md](04-ambiguous-groups.md).

```
27 grupos Q03
├── 16 SAME_PRODUCT_MULTI_CATEGORY   (misma entidad, sólo cambia la categoría)
├──  7 SAME_PRODUCT_DIFFERENT_NAME   (sin divergencia de categoría; variante cosmética de nombre)
├──  1 POSSIBLE_DUPLICATE_BRAND_PENDING  (LiftMaster/Blue Giant — ya documentado, Q09-adyacente)
└──  3 POSSIBLE_DUPLICATE_NEEDS_REVIEW   (Kelley/id3, Modern Steel, Thermospan — ya documentados)
```

Ningún grupo de los 27 quedó clasificado como `DISTINCT_PRODUCTS` (dos entidades comerciales
genuinamente diferentes agrupadas por error) ni como `INSUFFICIENT_EVIDENCE` puro: en todos
los casos hay suficiente evidencia local (nombre, descripción SQL, imágenes, PDF, fechas) para
proponer una clasificación con un nivel de confianza explícito, aunque en 4 casos esa
clasificación dependa de una decisión de marca que este análisis no toma.

## Las diez respuestas directas

1. **¿Los 14 (16) category-only son realmente la misma entidad?** Sí, verificado
   individualmente contra los siete criterios exigidos; ninguno se aceptó por defecto. Ver
   [02-category-only-groups.md](02-category-only-groups.md).
2. **¿Es seguro consolidarlos como un producto con múltiples categorías?** Sí. WordPress
   soporta nativamente múltiples términos de `psi_categoria` por entrada; la evidencia local
   (enlaces de página a página, con línea exacta, hacia cada sección del catálogo) sustenta
   que el sitio legacy mostraba genuinamente la misma ficha en varias secciones. Ver
   [03-multi-category-policy.md](03-multi-category-policy.md).
3. **¿Podemos diferir primary category?** Sí, técnicamente. Nada en el modelo de datos ni en
   el importador actual exige una categoría principal para crear o mostrar un producto; es
   una decisión de presentación (breadcrumb/SEO) desacoplada de la taxonomía en sí. Ver
   [03-multi-category-policy.md](03-multi-category-policy.md), sección "Primary category".
4. **¿Cuántos de los otros 11 parecen realmente duplicados?** Los 11 son, en distinto grado,
   la misma entidad duplicada — ninguno parece un producto genuinamente distinto agrupado por
   error. La diferencia entre ellos es únicamente cuánta fricción queda antes de poder
   consolidarlos: 7 no tienen ninguna (variante cosmética de nombre), 4 tienen una decisión de
   marca pendiente que este análisis no toma.
5. **¿Cuántos parecen variantes?** Ninguno de los 27 muestra evidencia de ser una variante
   comercial real (distinto modelo/capacidad/dimensión) que debiera modelarse como tal. El
   caso más cercano — `rampas-de-anden-hidraulicas-kelley.php`, id=3 "Serie HP" — es
   precisamente el que queda para revisión humana, sin recomendación automática de fusión.
6. **¿Cuántos son claramente productos distintos?** Cero, con la evidencia disponible en el
   repositorio.
7. **¿Cuántas decisiones humanas quedan para Q03?** Una decisión global (Q03-GLOBAL, sección
   14) y **5 preguntas de grupo** (no 11, porque 7 de los 11 comparten la misma pregunta
   estructural y se agrupan en una sola pregunta compacta con 7 sub-filas). Ver
   [q03-decision-questionnaire.md](../q03-decision-questionnaire.md).
8. **¿Cuántos REVIEW se desbloquearían con la decisión global?** La decisión Q03-GLOBAL por sí
   sola (los 16 grupos category-only) desbloquea **166 filas** (49 producto + 100 medios
   distintos + 17 páginas). Las tres decisiones humanas restantes (nombre cosmético, marca
   Modern Steel/LiftMaster/Thermospan, caso Kelley) desbloquean hasta 268 filas en total si se
   responden todas. Detalle y aritmética completa en
   [07-dependency-impact.md](07-dependency-impact.md) y [08-scenarios.md](08-scenarios.md).
9. **¿Cuántos Q01/Q05 residuales dependen de Q03?** De las 33 páginas `PRODUCT_PAGE` que
   siguen en `REVIEW`, **30 (91 %) dependen directamente de Q03** — son exactamente las
   páginas de estos 27 grupos. Las 3 restantes no dependen de Q03 (dependen de Q02/Q09/otro
   caso sin decisión, ver detalle). De los medios ligados a Q03, **ninguno** conserva ya un
   bloqueo de seguridad PDF (Q05): los que lo tenían ya se resolvieron en la fase Q05. Detalle
   en [07-dependency-impact.md](07-dependency-impact.md).
10. **¿Cuál es el riesgo principal de automatizar demasiado Q03?** Fusionar identidad y marca
    en la misma decisión. Los 4 grupos difíciles muestran que la señal de "misma entidad" (
    nombre/descripción/imágenes/PDF) puede ser sólida mientras la señal de "misma marca" sigue
    siendo genuinamente contradictoria — tratarlas como una sola pregunta arrastraría una
    resolución de marca no autorizada dentro de una consolidación de identidad que sí tiene
    evidencia. El segundo riesgo, ya verificado y descartado en este análisis pero documentado
    para que no se repita en otra fase: categorías con el mismo `name` no son necesariamente
    duplicadas (categorías 12/38 comparten nombre y son ramas reales distintas, igual que ya
    se demostró para 26/33 en Q10). Ver [09-risk-analysis.md](09-risk-analysis.md).

## Nota de precisión sobre SELLOSSOLMMER (sección 15 de la tarea)

Este análisis no ha usado, en ningún grupo, el hallazgo de Q11 sobre
`FICHATECNICASELLOSSOLMMER S.pdf` para nada más que lo ya aprobado: un PDF válido asociado al
producto 150. No se ha inferido de ahí que Solmmer sea la marca, y `sellos-nacionales.php`
(producto 150) se confirmó explícitamente **fuera** de los 27 grupos Q03 (es un caso de un
solo id, bloqueado por Q09, no por Q03 — ver [07-dependency-impact.md](07-dependency-impact.md)).
Q09 permanece completamente intacto y separado.

## Verificación de alcance

`/legacy` no se tocó. `/wordpress` no se tocó salvo la lectura de código ya existente
(`Sources.php`, `Planner.php`, `Policy.php`, `EditorialDecisions.php` — sólo lectura, cero
ediciones). No se ejecutó `Runner::batch()`. No se modificó `editorial-decisions.json` ni
ningún archivo de decisiones. Q03, Q08, Q09 y Q12 permanecen exactamente como estaban;
verificado explícitamente para Q08/Q09/Q12 en [07-dependency-impact.md](07-dependency-impact.md).

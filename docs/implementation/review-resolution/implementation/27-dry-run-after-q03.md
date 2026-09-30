# FULL DRY RUN — después de Q03

Run: `2cbcdec9-fbe2-4fc0-b7da-01efd0a104ef` (último de varios rerun idénticos tras el ajuste
de MOOVI). Modo `DRY_RUN`, estado `VALIDATED`. Sin mutación de base de datos, confirmado antes
y después (conteo de `posts`/`terms` idéntico). `Runner::batch()` sobre este plan sigue
rechazado (`VALID_LOCAL_SUBSET_PLAN_REQUIRED`).

## Cifras

```
                 ANTES (Q07+Q10+Q11+Q13)      DESPUES (+ Q03)
total            2.399                        2.399
UNCHANGED           15                           15
SKIP             1.467                        1.534
CREATE             407                          511
REVIEW             510                          339
ERROR                 0                            0
```

Baseline dado al iniciar esta fase: `total 2.399 · UNCHANGED 15 · SKIP 1.467 · CREATE 407 ·
REVIEW 510 · ERROR 0` — coincide exactamente. El análisis previo había estimado hasta 268
filas potencialmente desbloqueables por Q03; el resultado real es **171** (510→339) —
deliberadamente no forzado a esa cifra: 3 de los 16 grupos Q03-GLOBAL no pudieron
materializarse (9 filas de producto que el análisis no podía anticipar sin intentar la
extracción real de contenido — ver [22-q03-multi-category.md](22-q03-multi-category.md)), y
las 268 filas del análisis incluían medios que ya estaban compartidos con otros grupos Q03
(deduplicados aquí, no sumados).

## MERGE, roto aparte del total de nivel superior

`plan['summary']['actions']` agrupa toda acción que crea un objeto WordPress bajo `CREATE`
(incluye `MIGRATE`, `MERGE`, `CREATE_FROM_STATIC`) — igual que en cada fase anterior de este
proyecto. Desglosado por la acción real de cada decisión:

```
MIGRATE            445
SKIP              1.099
MERGE                49   (25 de Q02 + 24 de Q03)
CREATE_FROM_STATIC   32
REVIEW                2
```

Los 24 `MERGE` de Q03 se reparten: Q03-GLOBAL 13, Q03-A 7, Q03-B 1, Q03-C 1, Q03-D 1, Q03-E 1.

## Q03 resolved

```
Q03-GLOBAL   {"MIGRATE":41,"MERGE":13,"SKIP":27}   -- 13 de 16 grupos; 3 en REVIEW (ver 22)
Q03-A        {"MIGRATE":25,"MERGE":7,"SKIP":7}      -- 7 de 7 grupos
Q03-B        {"MIGRATE":6,"MERGE":1,"SKIP":3}        -- Modern Steel, sin marca
Q03-C        {"MIGRATE":2,"MERGE":1,"SKIP":1}        -- LiftMaster, sin marca
Q03-D        {"MIGRATE":4,"MERGE":1,"SKIP":1}        -- Thermospan, sin marca, descripcion CONFLICT
Q03-E        {"MIGRATE":2,"MERGE":1,"SKIP":1}        -- Kelley 24+139; id 3 fuera (0 filas)
```

**80 filas de medio** recibieron una decisión Q03 directa (suma de los `MIGRATE` de arriba).

## Q01 pages additionally unlocked

**27 páginas** `PRODUCT_PAGE` se retiraron citando un ganador Q03 (vía la generalización de
6 líneas en `q01()`, nunca una segunda lógica de propiedad) — el mismo número que ya se
retiraba citando un ganador Q02, confirmando que la generalización tiene un efecto del orden
de magnitud esperado, no una sobre-aplicación accidental.

## Q09 brand conflicts remaining

`Policy::BRAND_CONFLICT_PRODUCTS` sigue teniendo exactamente sus 14 ids originales, sin
ninguno añadido ni quitado. De ellos:

- **13** ahora tienen una decisión de **identidad** (Q03-B/C/D/E o el grupo MOOVI de
  Q03-GLOBAL) — pero **ninguno** tiene marca asignada (`brand=''` en los 4 ganadores: id 6,
  14, 43, 44; sus hermanos absorbidos en `SKIP` no tienen marca propia que asignar).
- **1** (id 150, "Sellos Nacionales" — Dockman/Solmmer) permanece completamente **sin ninguna
  decisión**, `REVIEW` puro — no pertenece a ningún grupo Q03, es exclusivamente territorio
  Q09.

`Policy::CATEGORY_CONFLICT_PRODUCTS` sigue teniendo exactamente su único id original (3),
verificado sin ninguna decisión.

## Comandos ejecutados

```bash
php -r 'require "wordpress/wp-load.php"; ...Planner::build("full")...'
```

Read-only: `Storage::guard()` + `Planner::build('full')` únicamente. `Runner::batch()` se
invocó una sola vez, deliberadamente, para reconfirmar su propio rechazo — nunca para
ejecutar.

# FULL DRY RUN — después de Q01 + Q02 + Q04 + Q05 + Q06

Run: `0115fe04-2e6e-4440-bb56-fb4452d9c2d7`. Modo `DRY_RUN`, estado `VALIDATED`. Sin
mutación de base de datos. `Runner::batch()` sobre este plan sigue rechazado
(`VALID_LOCAL_SUBSET_PLAN_REQUIRED`).

## Cifras

```
                 ANTES (Q02+Q05+Q06, 771 REVIEW)   DESPUÉS (+ Q01 + Q04)
total            2.399                              2.399
UNCHANGED           15                                 15
SKIP             1.337                              1.460
CREATE             276                                386
REVIEW              771                                538
ERROR                 0                                  0
```

Baseline original (antes de esta fase completa): `total 2.399 · UNCHANGED 15 · SKIP 1.337
· CREATE 276 · REVIEW 771 · ERROR 0`. Como se pidió explícitamente, **no se forzó** el
descenso a 771−445 (los ~414+31 nominalmente "cubiertos" por Q01/Q04): la caída real es de
233 filas, menor que la suma nominal, porque varias páginas quedan `KEEP_REVIEW` por
propietario ambiguo (grupos Q03) y algunos medios dependen de más de una decisión.

## Desglose por origen de decisión

```
Q01                  {"MIGRATE":149,"SKIP":93}                    (242 filas)
Q02                  {"MIGRATE":80,"MERGE":25,"SKIP":25}           (130 filas, sin cambios)
Q04                  {"MIGRATE":34,"CREATE_FROM_STATIC":29,"SKIP":30}  (93 filas)
Q06                  {"SKIP":31}                                   (31 filas, sin cambios)
MANUAL               {"MIGRATE":14,"CREATE_FROM_STATIC":1}          (15 filas, sin cambios)
POLICY:R-T01         {"MIGRATE":3}                                 (bajó de 33: la mayoría absorbida y enriquecida por Q01)
POLICY:R-P01         {"MIGRATE":1}                                 (bajó de 21: la mayoría absorbida y enriquecida por Q01)
POLICY:R-M01         {"MIGRATE":31}
POLICY:R-M02         {"SKIP":561}
POLICY:R-M03         {"SKIP":285}
POLICY:R-M04         {"MIGRATE":26}
POLICY:R-M05         {"MIGRATE":3}
POLICY:R-G02-LOW     {"MIGRATE":5}
NONE                 {"REVIEW":538,"SKIP":435}
```

`POLICY:R-T01`/`POLICY:R-P01` bajaron drásticamente en conteo directo — **no porque se
hayan desaprobado**, sino porque Q01 toma esa MISMA decisión ya calculada y la enriquece
(añadiendo imagen de categoría o medios de página), por lo que el origen final reportado
pasa a ser `Q01`. La condición de aprobación subyacente (R-T01/R-P01) sigue siendo,
exactamente, la de `Policy.php`, sin cambios.

## Q01 — resultado, desglosado por tipo

```
php: (páginas retiradas)         93
sql:productos: (enriquecidos)    20
category: (enriquecidos)         30
brand: (enriquecidos)            11
asset: (medios propios)          88
```

96 de 132 páginas `PRODUCT_PAGE`/`CATEGORY_PAGE`/`BRAND_PAGE` elegibles se resuelven
(51 `PRODUCT_PAGE` [28 vía Q02 + 23 vía enriquecimiento R-P01] + 33 `CATEGORY_PAGE` +
12 `BRAND_PAGE`); 36 quedan `KEEP_REVIEW` por propietario ambiguo, en conflicto o
inexistente. Detalle completo en [07-q01-content-ownership.md](07-q01-content-ownership.md).

## Q04 — resultado

29 nuevas + 1 ya resuelta por el ensayo = 30 de 32 `static_product` resueltas; 2
`KEEP_REVIEW` por medio ausente documentado. 30 páginas equivalentes retiradas, 34 medios
propios aprobados. Detalle en [08-q04-static-products.md](08-q04-static-products.md).

## Cross-dependencias: Q05 desbloqueado por Q01

Reportado por separado, tal como se pidió:

- **Filas resueltas directamente por Q01**: 242 (93 páginas + 149 entidades/medios).
- **Filas resueltas directamente por Q04**: 93 (30 páginas + 29 productos + 34 medios).
- **Filas de Q05 adicionalmente desbloqueadas por Q01**: 11 — de los 32 archivos con
  seguridad ya resuelta por Q05, 4 ya alcanzaban `MIGRATE` antes de esta fase (por ser
  dependencia de un ganador Q02); ahora **15** lo alcanzan — la diferencia son PDF cuyo
  propietario (un producto R-P01) quedó aprobado precisamente por el enriquecimiento de
  Q01 (ejemplos: `puerta-424.pdf`→producto 65, `puerta-seccional-418.pdf`→producto 68).
- **Filas de Q05 que siguen `REVIEW` por otro bloqueo**: 17 de 32 — todas con
  `media_validation=VALID_BYTES_REQUIRES_APPROVAL` (seguridad resuelta, propietario
  todavía no aprobado: en su mayoría los productos del conflicto de marca D03 —
  6/16/20/107 — y de categoría D06 — 3/24/139 — fuera del alcance de esta fase).
- **Medios desbloqueados por resolución de propietario** (más allá de Q05): 88 vía Q01 +
  34 vía Q04 = 122 medios que antes no tenían ningún camino de aprobación y ahora sí.

## Composición de las 538 REVIEW restantes (por tipo)

```
media             399   (de 1.523 originales)
page               53   (de 176; 123 retiradas por Q01+Q04)
product            77   (Q03: 27 grupos/164 filas, conflictos D03/D06, etc. -- sin cambio)
static_product      2   (medios ausentes documentados)
missing_media       4   (Q11)
category            3   (25/26/33, D06 -- sin cambio)
```

`product`/`category` type totals no cambiaron frente al baseline de Q02+Q05+Q06 (77 y 3
respectivamente): Q01 nunca resuelve un producto/categoría que ya estuviera en conflicto
documentado — exactamente el límite pretendido. Detalle completo en
[12-remaining-review-after-q01-q04.md](12-remaining-review-after-q01-q04.md).

# FULL DRY RUN — después de Q07 + Q10 + Q11 + Q13

Run: `64ac76fa-0e9b-4038-87ee-762a5a342c06`. Modo `DRY_RUN`, estado `VALIDATED`. Sin
mutación de base de datos. `Runner::batch()` sobre este plan sigue rechazado
(`VALID_LOCAL_SUBSET_PLAN_REQUIRED`).

## Cifras

```
                 ANTES (tras Q01+Q02+Q04+Q05+Q06)   DESPUES (+ Q07+Q10+Q11+Q13)
total            2.399                               2.399
UNCHANGED           15                                  15
SKIP             1.460                               1.467
CREATE             386                                 407
REVIEW              538                                 510
ERROR                 0                                   0
```

Baseline dado al iniciar esta fase: `total 2.399 · UNCHANGED 15 · SKIP 1.460 · CREATE 386 ·
REVIEW 538 · ERROR 0` — coincide exactamente. Como se pidió, **no se forzó** ningún número
esperado: la caída real es de 28 filas, muy por debajo de la suma nominal de las cuatro
decisiones (26+4+4+1=35), porque varias filas de Q07 comparten medios entre sí (deduplicados,
no sumados) y porque Q11 resuelve principalmente **clasificación**, no nuevas filas
`CREATE`/`MIGRATE` por sí solo.

## Desglose por origen de decisión (nuevo en esta fase)

```
Q07   {"MIGRATE":17}          (9 paginas + 8 medios propios, deduplicados por SHA-256)
Q10   {"MIGRATE":2,"SKIP":2}  (categoria 33 + su imagen; categorias 25 y 26)
Q11   (no aparece como bloque propio de conteo: 2 REVIEW + 2 SKIP, ver mas abajo)
Q13   {"SKIP":1}
```

Q02, Q04, Q06 y MANUAL no cambiaron respecto a la fase anterior. **Q01 sí cambió
indirectamente**: 2 productos adicionales dentro de su alcance (Dura-Glide, Magic — vía
Q04, no Q01, pero por el mismo mecanismo `media_union()` que ambas comparten) pasaron de
`REVIEW` a `CREATE_FROM_STATIC` gracias a la corrección de Q11; `Q01` en sí mismo (149
MIGRATE + 93 SKIP) no cambió de conteo.

## Q07 resolved

**9 de 9 landings** → `MIGRATE` (Page borrador). **8 medios propios** aprobados (7 imágenes
únicas tras deduplicar por SHA-256 entre landings hermanas). 2 landings sin medio propio
(`puertas-residenciales.php`, `tiras-plasticas-hawaianas.php`) migran igual, sólo con texto.

## Q10 resolved

**3 de 3 categorías** decididas: 26 SKIP, 33 MIGRATE (+ 1 imagen propia), 25 SKIP. Cero
fusión por nombre entre 26 y 33 (verificado por test).

## Q11 resolved

**4 de 4 casos** clasificados explícitamente. 2 (`INFRACA`, `SELLOSSOLMMERS`) resueltos por
evidencia binaria — sus entidades `missing:` pasan a `SKIP`. 2 (`dura.jpg`, `magic.jpg`)
permanecen `REVIEW` con motivo `MISSING_SOURCE_FILE` explícito — **nunca se fabricó
ningún archivo**. Efecto indirecto medible: **2 productos** (Dura-Glide, Magic) que antes
no podían resolverse por depender de esa única imagen ausente ahora migran como borrador
con galería vacía.

## Q13 resolved

**1 de 1 fila** conocida: el alias binario del ensayo pasa a `SKIP` explícito, citando a su
ganador ya migrado. Ningún segundo attachment.

## Composición de las 510 REVIEW restantes (por tipo)

```
media             389   (de 1.523 originales)
page               42   (de 176; 134 retiradas acumuladas entre Q01+Q04+Q07)
product            77   (Q03: 27 grupos/164 filas, conflictos D03/D06 -- sin cambio)
static_product      0   (las 32 se resuelven o quedan REVIEW por Q11 -- 0 sin decisión propia)
missing_media       2   (los 2 casos Q11 genuinamente sin archivo)
category            0   (las 3 gestionadas por Q10; el resto sin cambio respecto a la fase anterior)
```

`product`/`Q03` no cambiaron (77, igual que antes): ninguna decisión de esta fase tocó un
grupo editorial. Detalle completo en
[20-remaining-review.md](20-remaining-review.md).

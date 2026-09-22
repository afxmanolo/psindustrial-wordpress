# FULL DRY RUN — después de Q02 + Q05 + Q06

Run: `75ebe251-e498-44f6-8f44-49efb008c827`. Modo `DRY_RUN`, estado `VALIDATED`. Sin
mutación de base de datos (confirmado: conteo de `posts`/`terms` idéntico antes/después).
`Runner::batch()` sobre este plan sigue rechazado
(`RuntimeException: VALID_LOCAL_SUBSET_PLAN_REQUIRED`) — el gate de importación completa
no se tocó.

## Cifras

```
                 ANTES (932 REVIEW, baseline)   DESPUÉS
total            2.399                          2.399
UNCHANGED            15                             15
SKIP              1.281                          1.337
CREATE              171                            276
REVIEW              932                            771
ERROR                 0                              0
```

`CREATE` aquí es `planned_result` (igual que en el baseline original): incluye tanto
`action: MIGRATE`/`CREATE_FROM_STATIC` como los 25 `action: MERGE` de Q02 (un `MERGE`
resuelto siempre predice `CREATE`, nunca tiene su propio bucket separado en el resumen —
así funcionaba ya `Planner::summary()` antes de esta fase).

## Desglose por origen de decisión

```
Q02                  MERGE: 25   SKIP: 25   MIGRATE: 80    (130 filas)
Q06                  SKIP: 31                               (31 filas)
POLICY:R-T01         MIGRATE: 33                            (categorías)
POLICY:R-T03         MIGRATE: 11                            (marcas)
POLICY:R-P01         MIGRATE: 21                            (productos singleton)
POLICY:R-G02-LOW     MIGRATE: 5                             (páginas institucionales/contacto)
POLICY:R-M01         MIGRATE: 37                            (medios con relación SQL)
POLICY:R-M04         MIGRATE: 26                            (medios referenciados por página aprobada)
POLICY:R-M05         MIGRATE: 38                            (medios de categoría/marca)
POLICY:R-M02         SKIP: 561                               (derivados del CMS)
POLICY:R-M03         SKIP: 285                               (infraestructura)
MANUAL (subset)      MIGRATE: 14, CREATE_FROM_STATIC: 1     (ensayo privado previo, sin cambios)
NONE                 REVIEW: 771, SKIP: 435                  (sin decisión de ninguna fuente)
```

Q02 (130) y Q06 (31) son decisiones nuevas de esta fase, 161 filas en total — coincide
exactamente con la caída neta de REVIEW (932 → 771 = −161).

## Q02 — resultado

**25 grupos consolidados** (`MERGE` + `SKIP`), **1 sin resolver**
(`puertas-industriales-dockman.php`, por un mismatch MIME/extensión preexistente en una de
sus imágenes, ajeno a Q02). Las 130 filas con `decision_id: Q02` se reparten: 25 `MERGE`
(productos ganadores), 25 `SKIP` (perdedores), 80 `MIGRATE` (medios de la unión, incluyendo
algunos compartidos con productos fuera de los 26 grupos, como el ícono genérico
`verficha.png`).

## Q05 — resultado, reportado por separado tal como se pidió

- **Decisiones de seguridad resueltas: 32 de 32** — los 32 archivos tienen ahora una
  entrada en `pdf-approvals.json` gateada por hash+ruta exactos.
- **Filas completamente desbloqueadas (alcanzan `MIGRATE`): 4** — porque además son
  dependencia de un producto que Q02 fusionó con éxito.
- **Filas que siguen en `REVIEW` por otro bloqueo (no de seguridad): 27** —
  `media_validation = VALID_BYTES_REQUIRES_APPROVAL` confirma que el motivo es falta de
  propietario aprobado, nunca el tipo de archivo.
- **1 fila con destino `SKIP` en vez de `CREATE`**: el archivo de doble bloqueo
  (`system/files/images/productos/800c352920...`), cuya seguridad resuelve Q05 pero cuyo
  destino final decide Q06 (único propietario: producto 165, excluido).

## Q06 — resultado

**15/15 productos → `SKIP`. 16/16 medios exclusivos → `SKIP`.** Cero productos vacíos
creados. El ID 155 no tiene medios asociados en `product-media-relations.csv` (0 filas,
no es un error).

## Composición de las 771 REVIEW restantes (por tipo)

```
media             480   (de 1.523 originales; la mayoría del descenso viene de aquí)
page              176   (Q01 no implementado en esta fase — sigue intacto)
product            77   (incluye Q03: 27 grupos/164 filas, conflictos D03/D06, etc.)
static_product     31   (Q04 no implementado)
missing_media       4   (Q11, requiere recuperar el original)
category            3   (25/26/33, D06)
```

No se tocó ninguna de estas causas fuera de Q02/Q05/Q06 — el detalle completo de qué queda
y por qué está en [06-remaining-review.md](06-remaining-review.md).

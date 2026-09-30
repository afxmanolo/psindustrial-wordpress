# Verificación — Q01 y Q04

## Resultado global (las 9 suites, tras Q01+Q04)

```
smoke.php                       120 checks   PASS
importer.php                     67 checks   PASS
policy.php                    6.040 checks   PASS
pdf-approvals.php               310 checks   PASS
runner-media-validation.php      82 checks   PASS
editorial-decisions.php         374 checks   PASS   (Q02+Q06, sin regresión)
q05-pdf-resolution.php           68 checks   PASS   (1 fila menos "aún REVIEW": desbloqueada por Q01)
q01-content-ownership.php       299 checks   PASS   (nuevo)
q04-static-products.php         203 checks   PASS   (nuevo)
──────────────────────────────────────────────────
TOTAL                         7.563 checks   0 fallos
```

Ninguna suite existente perdió cobertura; `policy.php`, `pdf-approvals.php` y
`runner-media-validation.php` necesitaron un ajuste de muestreo (excluir del "PDF sin
relación"/"grupo A/B" las rutas que Q05 ya aprobó), documentado en
[04-tests.md](04-tests.md) — nada de eso cambió en esta fase.

## `tests/q01-content-ownership.php` (nuevo)

Cubre la lista mínima pedida, con fixtures reales verificados contra el plan vivo:

| Caso | Fixture real |
|---|---|
| página → producto inequívoco: sin Page, contenido pasa al producto | `puerta-seccional-de-acero-sin-aislamiento-424-uso-pesado.php` → `sql:productos:65` (R-P01 enriquecido) |
| página → categoría: sin Page, campo soportado pasa al término | `fraccionamientos-y-condominios.php` → `category:9` (imagen vía `image_file_id`, no sombreada por el ensayo) |
| página → marca: sin Page, campo soportado pasa al término | `clopay.php` → `brand:3` (logo vía `brand-master.csv:logo`) |
| medio propiedad exclusiva de una página → entidad canónica | La imagen de `category:9` recibe su propia aprobación `MIGRATE` |
| medio binariamente duplicado → no se duplica | Galería de `sql:productos:65`: cero repeticiones por ruta **ni por hash** (verificado hasheando cada imagen) |
| propietario ambiguo → `KEEP_REVIEW` | Grupo Q03 `accesspro-fs1000speed.php` (4 IDs); página de categoría compartida `puertas-peatonales-estandar-y-reforzada.php` (categorías 27 y 28); páginas sin propietario documentado (`marcas.php`, `soluciones.php`); categoría en conflicto D06 (`contra-incendio.php` → categoría 33) |
| campo no soportado: no se inventa / se conserva evidencia | `category:9` nunca recibe una clave `images` (galería) — sólo `image`, el único slot que el modelo admite |
| URL legacy: mapeo creado | Las tres páginas resueltas anteriores conservan su `legacy_url` en la entrada (retirada) |
| misma ejecución repetida → estable | Plan completo reconstruido dos veces; 0 diferencias en >100 filas con `decision_id=Q01` |

Además, más allá de la lista mínima: auto-consistencia de la unión página+SQL frente a
R-P01 (categoría/marca sin cambios); barrido de precedencia (`category:8`/`brand:1`
conservan su decisión manual del ensayo, no la de Q01); dependencia cruzada con Q05
(`fichas/puerta-424.pdf`, antes bloqueado por falta de propietario, ahora `MIGRATE`).

## `tests/q04-static-products.php` (nuevo)

| Caso | Verificación |
|---|---|
| `static_product` aprobado → `psi_producto` borrador | `1500-revolving-door.php`, `target_type=psi_producto`, `planned_result=CREATE` |
| sin categoría automática / sin marca automática | Barrido de las 29 filas resueltas por Q04: `categories=[]`, `brand=''` en el 100% |
| contenido preservado | Cuerpo extraído no vacío; nombre verbatim de la fuente |
| medios preservados | `images/revolving.jpg` presente en la galería y aprobado independientemente |
| namespace Page equivalente → sin Page | `php:1500-revolving-door.php` → `SKIP`, `decision_id=Q04` |
| namespace `PRODUCT_PAGE` equivalente → sin segundo producto | Barrido completo de las 32 filas: 0 casos de doble creación |
| mapeo de URL único | El producto y su página retirada comparten el mismo `legacy_url` |
| ejecución repetida → estable | >50 filas `decision_id=Q04` comparadas entre dos reconstrucciones; 0 diferencias |
| parecido semántico con producto SQL → sin fusión automática | `static-product-supplement.csv` no tiene `legacy_product_id`; namespace `static:` estructuralmente disjunto de `sql:productos:` |
| grupo Q03 intacto | Ningún fichero de `static-product-supplement.csv` coincide con un fichero de grupo Q03 conocido |
| medio ausente documentado → `KEEP_REVIEW`, no improvisado | `dura-glide-20003000-puerta.php`, `magic-fuerza-del-operador.php` |

## Un bug real encontrado durante la implementación (y corregido)

La primera versión de `EditorialDecisions::q01()`'s enriquecimiento de productos R-P01
omitió dar a cada imagen/PDF de la unión su propia decisión `MIGRATE` — el mismo defecto
que ya se había encontrado y corregido en Q02 durante la fase anterior, pero reintroducido
aquí por no reutilizar ese mismo bloque de código. Efecto observado en la primera
ejecución de prueba: **20 productos** que antes resolvían a `CREATE` bajo R-P01
retrocedieron a `REVIEW` (dependencia no aprobada). Corregido replicando exactamente el
mismo `foreach` que ya usa Q02; verificado que el conteo de `product: CREATE` volvió a su
valor esperado (46, el mismo que antes de tocar Q01) antes de continuar.

## Comandos ejecutados

```bash
php wordpress/wp-content/plugins/psindustrial-core/tests/smoke.php
php wordpress/wp-content/plugins/psindustrial-core/tests/importer.php
php wordpress/wp-content/plugins/psindustrial-core/tests/policy.php
php wordpress/wp-content/plugins/psindustrial-core/tests/pdf-approvals.php
php wordpress/wp-content/plugins/psindustrial-core/tests/runner-media-validation.php
php wordpress/wp-content/plugins/psindustrial-core/tests/editorial-decisions.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q05-pdf-resolution.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q01-content-ownership.php
php wordpress/wp-content/plugins/psindustrial-core/tests/q04-static-products.php
php -l <cada archivo PHP tocado o nuevo>
```

Ninguna ejecutó `Runner::batch()` sobre un plan `full` con éxito — todas verifican que
sigue rechazado.

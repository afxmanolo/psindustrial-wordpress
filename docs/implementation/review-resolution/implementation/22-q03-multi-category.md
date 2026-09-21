# Q03-GLOBAL — SAME_PRODUCT_MULTI_CATEGORY

16 grupos aprobados (`editorial-decisions.json → q03_group_consolidation.global_multi_category`).
**13 consolidan**, **3 quedan en REVIEW** (identidad correcta, contenido no extraíble — ver
más abajo). Ningún grupo se aceptó fuera de la lista de 16 ya verificada en
[q03-analysis/02-category-only-groups.md](../q03-analysis/02-category-only-groups.md).

## Mecánica

`EditorialDecisions::q03_consolidate()` (compartido por las 4 sub-decisiones): para cada
grupo, verifica que los IDs aprobados siguen perteneciendo exactamente al mismo
`canonical_candidate_group` en los datos vivos (igual que ya hacía `q02()`); calcula el
**ganador de identidad** como el miembro más antiguo por `productos.fecha` **entre los que
tienen descripción SQL no vacía**, nunca el más antiguo a secas — esto es lo que garantiza que
una fila hermana con descripción, más reciente, nunca pierda frente a una fila más vieja mas
vacía (ver [26-q03-tests.md](26-q03-tests.md) para la prueba explícita); calcula la **unión de
categorías** a partir de `category_id` de TODOS los miembros (nunca sólo la del ganador);
calcula la marca por acuerdo de valor entre todos los miembros, salvo que
`Policy::brand_conflict_products()` marque algún ID como conflictivo (ver
[24-q03-brand-unresolved.md](24-q03-brand-unresolved.md)); reutiliza `media_union()` de Q02
sin ninguna modificación.

## Categoría principal — deliberadamente ausente

Ningún decisión Q03-GLOBAL escribe `primary_category`, `breadcrumb_category` ni ningún
metadato `_yoast_wpseo_primary_category` — verificado explícitamente por test. Queda pendiente
para la futura fase SEO, tal como aprobó el usuario.

## Los 13 que consolidan

| Grupo | IDs | Categorías unidas | Marca | Descripción |
|---|---|---|---|---|
| accesspro-fs1000speed.php | 1,109,110,114 | 37\|38\|39 | ASSIGNED (Overhead Door) | CONFLICT |
| cortina-serie-600.php | 10,93,127 | 14\|19 | ASSIGNED (Overhead Door) | CONFLICT |
| puertas-blindadas.php | 28,118,129 | 19\|32 | ASSIGNED (Doorlock) | CONFLICT |
| puerta-holandesa.php | 33,122,133 | 19\|29 | ASSIGNED (Doorlock) | AGREED |
| puerta-y-fijos-louver.php | 35,121,134 | 19\|30 | ASSIGNED (Doorlock) | AGREED |
| barreras-estacionamiento-moovi50rm.php | 44,123,125,135 | 9\|18\|19 | **BRAND_UNRESOLVED_Q09** | AGREED |
| p45-7-piston-hidraulico-para-puerta-abatible.php | 47,80,113 | 12\|38 | ASSIGNED (BFT) | CONFLICT |
| lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php | 48,79,112 | 12\|38 | ASSIGNED (BFT) | CONFLICT |
| icaro-smart.php | 49,76,124,126 | 9\|11\|18 | ASSIGNED (BFT) | CONFLICT |
| cortina-plana.php | 54,89,136 | 14\|19 | ASSIGNED (Dockman) | AGREED |
| cortina-europea.php | 55,90,137 | 14\|19 | ASSIGNED (Dockman) | AGREED |
| operador-para-perfilados-comerciales-sel.php | 78,84 | 11\|13 | ASSIGNED (Overhead Door) | CONFLICT |
| cortinas-ventiladas-685.php | 96,128 | 14\|19 | ASSIGNED (Overhead Door) | AGREED |

`description=CONFLICT` en 6 de los 13 **no bloquea la consolidación**: identidad y contenido
son ejes separados (sección 3 de la tarea). El campo queda registrado como `CONFLICT` en
`decision.field_provenance.description`, auditable en
[q03-field-provenance.csv](q03-field-provenance.csv), sin sintetizar ni concatenar nada.

## Los 3 que quedan en REVIEW — hallazgo no anticipado

`puertas-contra-incendio.php` (29,116,130), `puerta-estandar.php` (31,119,131) y
`puerta-estandar-reforzada.php` (32,120,132): **ninguno de los tres tiene cuerpo HTML
estático extraíble** en su página legacy compartida — `Sources::content()` sólo recupera
`<p|h2|h3|ul|ol|table>` fuera de `nav/header/footer/form`, y estas 3 páginas concretas no
tienen ninguno. Esto es una propiedad **del archivo**, no de qué fila SQL gane: los tres
miembros de cada grupo comparten literalmente el mismo `legacy_php`, así que el resultado es
idéntico sin importar cuál sea el ganador.

`Planner`'s propio guardia (`NO_STATIC_EDITORIAL_BODY`, ya existente, nunca modificado)
detecta esto y hace caer la entidad a `REVIEW` — pero **eso solo no bastaba**: antes de
añadir la verificación previa, los 6 miembros perdedores de estos 3 grupos ya habían recibido
`SKIP` ("consolidado en el ganador") en el momento en que `EditorialDecisions::q03()` se
ejecuta, **antes** de que Planner intente extraer el contenido más adelante en el mismo build.
El resultado habría sido: ganador en `REVIEW`, sus 2 hermanos en `SKIP` citando un ganador que
nunca llegó a crearse — **9 filas de producto perdidas silenciosamente**, sin publicarse
nada pero también sin poder recuperarse desde el plan.

**Corrección aplicada**: `q03_consolidate()` ahora llama a `Sources::content()` sobre el
archivo del grupo y verifica que el cuerpo no esté vacío **antes** de escribir ninguna
decisión — si está vacío, rechaza el grupo completo (los 3 miembros permanecen en `REVIEW`
exactamente como estaban), nunca una consolidación parcial. Verificado explícitamente por
test: ninguno de los 6 miembros perdedores queda huérfano en `SKIP`.

```
sql:productos:29:  REVIEW (sin decision_id)
sql:productos:116: REVIEW (sin decision_id)
sql:productos:130: REVIEW (sin decision_id)
sql:productos:31:  REVIEW (sin decision_id)
sql:productos:119: REVIEW (sin decision_id)
sql:productos:131: REVIEW (sin decision_id)
sql:productos:32:  REVIEW (sin decision_id)
sql:productos:120: REVIEW (sin decision_id)
sql:productos:132: REVIEW (sin decision_id)
```

Esto no es una limitación de esta implementación: es una propiedad real, verificable, del
contenido legacy de estas 3 páginas, descubierta al intentar consolidarlas — no estaba (ni
podía estarlo) en el análisis anterior, que nunca llamó a `Sources::content()`.

## MOOVI: marca no asignada pese al acuerdo de valores

Ver [24-q03-brand-unresolved.md](24-q03-brand-unresolved.md) para el detalle completo — MOOVI
es uno de estos 16 grupos GLOBAL, no uno de los 3 B/C/D, pero comparte el mismo tipo de
salvaguarda.

## Trazabilidad

[q03-consolidation-audit.csv](q03-consolidation-audit.csv) (filas 1-16) y
[q03-field-provenance.csv](q03-field-provenance.csv).

# Q03-E — Kelley, consolidación parcial

`rampas-de-anden-hidraulicas-kelley.php` (ids 3, 24, 139): **sólo 24+139 consolidan**. id=3
permanece completamente fuera, sin ninguna decisión, exactamente como aprobó el usuario.

## Cómo se implementó la exclusión de id=3

`q03_consolidate()` recibe `$memberIds = ['24', '139']` — id 3 nunca entra en la lista de
miembros a consolidar. La verificación de membresía (`$requireExactMembership = false` para
este caso, a diferencia de los otros 23 grupos) comprueba que 24 y 139 pertenecen
individualmente al mismo `canonical_candidate_group`, pero **no exige** que sean *todos* los
miembros vivos del grupo — permite que id 3 exista fuera de la lista, intacto. El resultado:
ninguna entrada de `$out` se escribe nunca para `sql:productos:3` desde `EditorialDecisions.php`
— su estado (`REVIEW`, sin `decision_id`) es exactamente el que tenía antes de esta fase.

## Título: el de id=24, verbatim

La instrucción fue explícita: usar el título ya aprobado de id=24 tal cual, sin
transformación heurística del texto de id=139. `editorial-decisions.json` declara
`canonical_title_source_id: "24"` — `q03_consolidate()` usa ese id directamente como
preferencia de ganador (coincide, además, con el ganador natural: id 24 es el más antiguo de
los dos y su descripción no está vacía). El título emitido es exactamente
`product-master.csv`'s propio campo `name` para id 24, sin ningún procesamiento de texto.

## Por qué 24+139 son la misma identidad

- Descripción SQL **byte-idéntica** entre 24 y 139 (verificado por hash).
- Al menos una imagen compartida exactamente (misma ruta) entre ambos.
- El mismo PDF (Kelley-Hydraulic-Dock-Leveler-Brochure.pdf).
- El nombre de id 139 incluye literalmente "RAMPA NIVELADORA MECÁNICA BLUE GIANT" — el nombre
  de **otro** producto, ya migrado por separado en Q02
  (`rampa-niveladora-mecanica-blue-giant.php`, ids 23/140) — tratado como un error de
  copiar/pegar en el campo de nombre durante la carga de datos, nunca como evidencia de una
  segunda identidad real.

## Por qué id=3 sigue fuera

`id_3` tiene descripción SQL propia (no compartida con 24/139) y una categoría (7,
"Residenciales") ajena a la rama de sus supuestos hermanos (categoría 10, "Rampa Niveladora").
`Policy::CATEGORY_CONFLICT_PRODUCTS = ['3']` ya excluía este id de cualquier resolución
automática antes de esta fase — **no se desactivó, se confirmó**: verificado por test que la
constante sigue teniendo exactamente su único valor original.

## Marca: sin resolver, por prudencia

id 24 por sí solo tendría evidencia razonablemente fuerte de marca Kelley
(`brand_confidence=CONFIRMED`) — pero id 139, que forma parte de esta misma entidad
consolidada, **ya está** en `Policy::BRAND_CONFLICT_PRODUCTS` (la misma comprobación
automática descrita en [24-q03-brand-unresolved.md](24-q03-brand-unresolved.md), no una regla
especial para Kelley). Se deja la marca explícitamente sin asignar, fuera del alcance de Q09,
en vez de asumir que la señal individual de id 24 basta para invalidar la cautela ya
documentada sobre id 139.

## La página dependiente se desbloquea reutilizando exactamente la lógica de Q01

`rampas-de-anden-hidraulicas-kelley.php` (la página) tiene `related_product_ids = 3|24|139` —
las tres filas comparten el mismo `canonical_candidate_group`, así que `Q01` la considera
elegible; su búsqueda de un ganador `MERGE` entre esos 3 ids encuentra el de id 24 (id 3 no
tiene ninguna decisión, así que simplemente no coincide) y retira la página citando a
`sql:productos:24`. Ningún código nuevo de propiedad de páginas: es la misma búsqueda que Q01
ya hacía para Q02, ahora también consciente de los grupos Q03 (ver
[21-q03-implementation-summary.md](21-q03-implementation-summary.md)).

## Trazabilidad

[q03-consolidation-audit.csv](q03-consolidation-audit.csv) (fila 27) y
[q03-field-provenance.csv](q03-field-provenance.csv).

# Q10 — Categoría 26 vs 33, y categoría 25

Tres decisiones independientes por `legacy_id` explícito, nunca por nombre. Detalle en
[q10-category-audit.csv](q10-category-audit.csv).

## Resultado

```
categoria 26  ->  SKIP      (registro sobrante, 0 productos, sin pagina)
categoria 33  ->  MIGRATE   (pagina propia contra-incendio.php, 2 productos: 29, 116)
categoria 25  ->  SKIP      (sin uso legitimo mas alla de los 14 vacios de Q06)
```

## Categorías 26 y 33: mismo nombre, evidencia opuesta

Ambas se llaman literalmente "Puertas contra incendio" en `category-master.csv` — la
tentación de fusionarlas por nombre es exactamente lo que la tarea prohibió. La evidencia,
verificada contra los datos vivos (no contra el campo `product_count` estático del CSV,
que en el caso de la 33 subestima el conteo real por no contar categorías múltiples):

| | 26 | 33 |
|---|---:|---:|
| `legacy_page` | (vacío) | `contra-incendio.php` |
| Productos que la referencian (`category_id`, live) | 0 | 2 (IDs 29, 116) |
| `parent_legacy_id` | 0 | 5 |
| `sql_parent_confidence` | CONFIRMED | CONFIRMED |
| `image_file_id` | 100 (huérfano — sin página ni productos que lo acompañen) | 132 |

La 26 no tiene ningún uso público verificable; la 33 sí. `EditorialDecisions::q10()` decide
por `legacy_id`, nunca compara nombres — verificado explícitamente por test
(`tests/q10-category-cleanup.php`: mismo nombre, acciones opuestas).

### Por qué categoría 33 necesitó una decisión propia, no un enriquecimiento

A diferencia de las categorías que Q01 enriquece (que ya tenían una decisión R-T01 de
`Policy.php` sobre la cual construir), la categoría 33 está **excluida estructuralmente**
de R-T01: `Policy::CATEGORY_CONFLICT_PRODUCTS` la lista explícitamente, precisamente por
compartir nombre con la 26 (el registro de conflicto D06 original). No hay decisión previa
que enriquecer. `EditorialDecisions::q10()` origina la decisión completa para categoría 33
replicando la misma lógica de resolución de `parent` que usa R-T01 — únicamente para esta
categoría, únicamente porque esta fase la autorizó explícitamente — y además le asigna su
`image_file_id` (mismo mecanismo que Q01 usa para otras categorías).

## Categoría 25: verificación exhaustiva antes de excluir

Antes de decidir, se comprobó cada tipo de evidencia que la tarea pidió revisar:

| Evidencia | Resultado |
|---|---|
| `legacy_page` | vacío |
| `parent_frontend_evidence` | vacío |
| `frontend_product_links` | 0 |
| Productos que la referencian (`category_id`, live) | **exactamente los 14 IDs 151–164** |
| Contenido descriptivo propio | ninguno más allá del nombre |
| Navegación pública documentada | ninguna |

Los 14 productos que la referencian son, sin excepción, los registros vacíos que Q06 ya
excluyó por su propio mérito (sin nombre, sin contenido real). **No hay ningún uso de
categoría 25 que no dependa de esos 14 registros.** Sin ellos, la categoría no tiene
ningún producto, página ni evidencia de navegación — exactamente la condición que la tarea
fijó para `SKIP`. Se conserva su ID para trazabilidad; no se borra nada.

Si categoría 25 hubiera tenido evidencia adicional (un producto válido no vacío, una
página, un enlace de navegación), la decisión habría sido `KEEP_REVIEW`, documentando esa
evidencia explícitamente — verificado que el código nunca fuerza `SKIP` sin antes
comprobar los cinco tipos de evidencia solicitados.

## Trazabilidad

[q10-category-audit.csv](q10-category-audit.csv) — una fila por categoría: acción, padre,
imagen, conteo de productos vivo, si tiene página propia, razón completa.

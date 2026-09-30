# Política SAME_PRODUCT_MULTI_CATEGORY — evaluación formal

No implementada. Este documento evalúa si la hipótesis "el legacy duplicaba filas porque su
modelo sólo permitía una categoría por registro" está respaldada por los datos, y si la
arquitectura ya aprobada del proyecto permite diferirla sin inventar nada.

## 1. ¿Los datos sustentan la hipótesis?

Tres líneas de evidencia independientes, ninguna inventada:

**a) Evidencia de enlace literal, con línea exacta.** `product-master.csv` registra, para
cada fila, exactamente qué página legacy enlaza a ella y bajo qué contexto de categoría. Para
`puertas-blindadas.php` (id=28), la columna `notes` cita:
`centros-comerciales.php:143 -> puertas-blindadas.php [category 19]` y
`blindadas.php:33 -> puertas-blindadas.php [category 32]` — la misma página, enlazada desde
dos secciones distintas del sitio, con la línea fuente exacta. Esto se repite, con el mismo
patrón exacto de tres enlaces por sección, en los 16 grupos category-only.

**b) Evidencia estructural en las fechas SQL.** En 14 de los 16 grupos existe una fila creada
primero (siempre `2025-10-22` para la familia Doorlock) con categoría doble o triple marcada
`STRONG_INFERENCE` (inferida por enlace, no confirmada en SQL), seguida de filas creadas
después (`10-29`, `11-04`) cada una con **una sola** categoría `CONFIRMED` en SQL. Esto es
exactamente la firma que dejaría un editor clonando manualmente una fila por sección — no es
una coincidencia de nombres, es una secuencia temporal verificable fila por fila.

**c) Las categorías involucradas son secciones reales, no un artefacto.** Se verificó cada
`category_id` de los 16 grupos contra `category-master.csv`: todas tienen su propia página
(`legacy_page`), su propio padre (`parent_legacy_id`) y su propio conteo de productos. No hay
ningún caso, entre los 16, de una categoría-fantasma sin página propia participando de la
unión (a diferencia de la categoría 26 que Q10 ya identificó y excluyó por esa razón exacta).

**Conclusión: sí, la hipótesis está sustentada para los 16 grupos**, con evidencia
verificable independiente del propio hecho de la duplicación (el enlace literal y la
secuencia de fechas no dependen de que haya varias filas — son observaciones externas que
corroboran por qué las hay).

## 2. La política propuesta (para aprobación futura, no activa)

```
SAME_PRODUCT_MULTI_CATEGORY:
Cuando un canonical_candidate_group tiene 2+ filas SQL cuyo único desacuerdo sustantivo es
category_id (nombre, marca, PDFs e imágenes coinciden o son compatibles), consolidar en un
único producto WordPress psi_producto, asignado a la UNIÓN de todas las categorías
observadas entre sus miembros. El ganador (para campos escalares como fecha de creación o
descripción de referencia) se determina con el mismo criterio ya aprobado en Q02: el registro
más antiguo por productos.fecha, empate por legacy_product_id más bajo.
```

Esto **reutiliza** el mecanismo de ganador de Q02 sin modificarlo; sólo cambia qué ocurre con
`categories`: en vez de tomar la categoría escalar del ganador (como hace `q02()` hoy,
`'categories' => array('category:' . $winner['category_id'])`), tomaría la unión de las
categorías de **todos** los miembros del grupo.

## 3. Primary category — ¿es seguro diferirla?

**Sí.** Verificado contra el código y el modelo actual, no supuesto:

- `psi_categoria` es una taxonomía jerárquica estándar de WordPress
  (`00-wordpress-architecture-summary.md`, punto 3). WordPress permite de forma nativa que una
  entrada tenga múltiples términos de la misma taxonomía sin que ninguno sea "principal" a
  nivel de dato — eso es simplemente `wp_set_object_terms()` con varios IDs.
  `TermEditor.php`/`Fields.php` (revisados en la fase anterior) no tienen hoy ningún campo de
  "categoría principal"; no existe un contrato que romper.
  - Nota de alcance: se hizo lectura, no modificación, de `TermEditor.php`/`Fields.php` en
    esta tarea, únicamente para confirmar que el modelo actual no depende de una categoría
    principal.
- El breadcrumb y el SEO (Yoast Free, `00-wordpress-architecture-summary.md` punto 8) son
  capas de **presentación**, explícitamente pospuestas a una fase futura
  ("SEO: arquitectura preparada para Yoast Free... adaptador SEO... no se implementa"). Yoast
  ya resuelve "categoría principal" con su propio mecanismo (`_yoast_wpseo_primary_category`)
  cuando se instale — no es algo que el importador deba decidir ahora ni que deba inventarse
  como metadato propio.
- No decidir la categoría principal **no bloquea** crear el producto, asignarle su galería,
  ni mostrarlo en el frontend: un producto con 2-3 términos de la misma taxonomía se lista y
  se muestra igual de bien en cada uno de ellos sin que el sistema necesite saber cuál es "la
  principal".

**Conclusión: es seguro diferir `primary category` / `breadcrumb primary` / `canonical
category` / `SEO primary taxonomy` hasta la fase SEO/breadcrumbs futura.** Forzar esa
decisión ahora, sólo para poder consolidar, inventaría un criterio editorial (¿cuál categoría
"manda"?) que ningún dato local resuelve — exactamente lo que el proyecto se ha prohibido
hacer por intuición.

## 4. Relación con Q10

Q10 ya aprobó la categoría 33 ("Puertas contra incendio", con página propia y producto real)
como `MIGRATE`. El grupo Q03 `puertas-contra-incendio.php` (ids 29/116/130) referencia
exactamente esa categoría 33 en su unión (`19|33`). Esto no es una coincidencia que haya que
resolver: es el mismo dato, visto desde dos ángulos distintos (Q10 decidió que la categoría 33
existe y se migra; Q03-GLOBAL, si se aprueba, decidiría que el producto que la usa puede
pertenecer a ella *y además* a la categoría 19). Ambas decisiones son compatibles sin
modificación mutua.

## 5. Qué NO decide esta política

No decide qué marca tiene un producto (los 16 grupos ya se verificaron sin contradicción de
marca — ver 02 — pero la política en sí no resolvería una contradicción si existiera). No
decide redirects ni URLs canónicas (ver [06-url-mappings.md](06-url-mappings.md)). No decide
si dos categorías con el mismo `name` deben fusionarse (ver
[09-risk-analysis.md](09-risk-analysis.md) — el caso 12/38 se verificó y son ramas reales
distintas, no duplicadas).

# Medios de los 16 grupos category-only

## Política evaluada: reutilizar el mecanismo de Q02 sin modificarlo

Q02 ya resuelve exactamente este problema para 26 grupos: unión de imágenes/PDF únicos por
SHA-256, deduplicados, con un featured image estable (`EditorialDecisions::media_union()`,
ver [16-q13-media-alias.md](../implementation/16-q13-media-alias.md) para la mecánica de
deduplicación y `01-q02-duplicate-resolution.md` para la política original). La pregunta de
esta sección no es si el mecanismo existe — existe y ya está probado con 7.790 verificaciones
— sino si los 16 grupos category-only lo pueden usar **sin modificarlo**.

**Respuesta: sí, sin cambios.** `media_union()` ya opera sobre exactamente los campos que
estos 16 grupos usan (`images`, `technical_pdf` de `product-master.csv`), ya deduplica por
SHA-256 real (nunca por ruta o nombre), y ya falla cerrado (rechaza el grupo entero) si
cualquier ruta referenciada no es válida — el mismo comportamiento conservador que protegió a
Q02. No se necesita una regla nueva de validación de medios para Q03; sólo una fuente de datos
distinta (unión de categorías en vez de categoría del ganador), que es un cambio de
`EditorialDecisions::q02()`/una función equivalente, no de `media_union()`.

## Verificación de contradicción, grupo por grupo

Se comprobó, para cada uno de los 27 grupos (no sólo los 16), si dos miembros cualesquiera
tienen conjuntos de SHA-256 de imagen o de PDF **completamente disjuntos** (ninguna imagen o
PDF en común) — la señal que Q02 mismo usaría para rechazar una unión. **Ningún grupo de los
27 tiene esa contradicción.** Los patrones observados, todos compatibles con unión segura:

- **Idénticos** (mismo conjunto exacto en todos los miembros): 9 de los 16 category-only
  (puerta-holandesa, puerta-y-fijos-louver, barreras-moovi50rm, p45-7-piston, lux-2b,
  icaro-smart, cortina-plana, cortina-europea, cortinas-ventiladas-685) y 5 de los 11
  ambiguos.
- **Subconjunto/superconjunto** (un miembro con menos fotos que otro, nunca fotos
  incompatibles): accesspro-fs1000speed (fila más antigua con menos fotos), cortina-serie-600,
  puertas-contra-incendio, puerta-estandar, puerta-estandar-reforzada, puertas-blindadas,
  cortina-plana.
- **Complementario** (cada miembro con una foto adicional propia, ninguna en conflicto):
  operador-para-perfilados-comerciales-sel (RMX), energy-series, rolli-zip,
  thermospan-modelo-150 — el mismo patrón que Q02 ya aprobó explícitamente para 8 de sus 26
  grupos ("cada copia tiene fotos distintas del mismo producto").

Ninguno de los 27 se sacó del conjunto category-only por razón de medios — las 2 exclusiones
(Kelley, y el desempate de RMX/cortinas-ventiladas hacia dentro en vez de hacia fuera) se
decidieron por nombre/marca, no por medios. Esto responde directamente a la instrucción de la
tarea: no se encontró ningún grupo con medios contradictorios que hubiera que sacar de
category-only.

## Featured image

Q02 ya resuelve esto con una regla estable y determinista (primera imagen del registro más
antiguo por `productos.fecha`). Los 16 grupos category-only tienen la misma forma de dato
(varias filas con fecha SQL distinta) y por tanto la misma regla se aplicaría sin cambios: no
hace falta inventar un criterio nuevo.

## Anomalía documentada, no resuelta aquí: PDF de icaro-smart.php

El grupo `icaro-smart.php` (categoría-only, ids 49/76/124/126) referencia
`fichas/pistones-hidraulicos-lux.pdf` — el mismo PDF que usa el grupo
`lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php` (ids 48/79/112), un producto
distinto (un pistón hidráulico, no un operador ICARO). Esto coincide exactamente con
`manual-decisions-required.md`: "ICARO enlaza PDF LUX" — una anomalía ya conocida antes de
esta tarea, no un hallazgo nuevo. No es un problema para la consolidación category-only de
ninguno de los dos grupos (ambos PDF son válidos, con SHA-256 verificable, y compartir un
adjunto entre dos entidades no es distinto de lo que Q07 ya maneja para medios compartidos
entre landings hermanas) — pero si en el futuro se decide que ICARO necesita su propia ficha
técnica, esa es una decisión de contenido/marca, no de identidad de producto, y queda fuera
del alcance de esta tarea.

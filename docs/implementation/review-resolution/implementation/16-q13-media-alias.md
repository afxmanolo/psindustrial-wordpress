# Q13 — PDF duplicado en segunda ruta

Un residuo técnico del ensayo original (Prompt 6): `fichas/puerta-420.pdf` fue aprobado
como ganador con un `binary_alias` declarado —
`system/files/images/productos/9856266836c012c90673224e9379a1a7f4b32389`, el mismo
contenido alcanzado por una segunda ruta legacy. `Planner::build()` ya verifica esa
identidad binaria al construir la entidad ganadora
(`BINARY_ALIAS_NOT_IDENTICAL` si no coincidiera) — pero la ruta alias, al tener su **propia**
fila en `media-master.csv`, generaba **su propia** entidad independiente, sin ninguna
decisión propia, y por tanto quedaba en `REVIEW` indefinidamente. Detalle en
[q13-media-alias-audit.csv](q13-media-alias-audit.csv).

## Resultado

**1 fila** (`asset:system/files/images/productos/9856266836...`) pasa a `SKIP`, citando al
ganador (`asset:fichas/puerta-420.pdf`, ya `MIGRATE`). Ningún segundo attachment se crea.

## Regla, no caso hardcodeado

`EditorialDecisions::q13()` no mantiene una lista propia de rutas — lee directamente
`Planner::decisions()` (el mismo `subset-decisions.json` del ensayo, ya aprobado) y, para
**cualquier** entidad manual que declare `binary_aliases`, marca cada ruta alias como
`SKIP` explícito. Hoy sólo hay un caso; la regla cubre automáticamente cualquier otro que
se añada al ensayo en el futuro, sin tocar código.

## Por qué es seguro

La identidad SHA-256 no se reverifica aquí: ya la verificó `Planner::build()` al construir
`fichas/puerta-420.pdf` (línea `if (!$other['valid'] || !hash_equals($asset['sha256'],
$other['sha256'])) { throw BINARY_ALIAS_NOT_IDENTICAL; }`). `Q13` sólo actúa **después** de
que esa verificación ya pasó, nunca la sustituye ni la relaja.

## Trazabilidad conservada, sin redirect

La ruta alias no desaparece del sistema: queda registrada tanto en
`decision.binary_aliases` del ganador como en su propia entrada `SKIP` con motivo explícito
citando al ganador — exactamente lo que pidió la tarea ("conserva la segunda ruta legacy en
un mapping/alias auditable"). **No se implementó ningún redirect** para esa segunda ruta:
queda para la futura fase de SEO/compatibilidad de URLs, tal como se indicó.

## Trazabilidad

[q13-media-alias-audit.csv](q13-media-alias-audit.csv) — la fila única: entidad ganadora,
su ruta legacy, la ruta alias, acción de la entidad alias, SHA-256 compartido.

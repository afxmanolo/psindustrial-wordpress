# Simulación de escenarios

Simulación analítica sobre el conjunto real de 932 filas. **No se cambió código, ni el
manifest, ni ninguna decisión.** Cada fila se considera resuelta sólo cuando **todos** sus
bloqueos quedan levantados.

Punto de partida:

```
total 2.399 · UNCHANGED 15 · SKIP 1.281 · CREATE 171 · REVIEW 932 · ERROR 0
```

## Escenario A — sólo reglas deterministas adicionales

Sin ninguna decisión humana nueva: únicamente lo que se deduce de evidencia ya aprobada.

| Regla determinista | Filas |
|---|---:|
| PDF byte-idénticos a archivos ya aprobados/saneados (extensión de la aprobación por hash) | 11 |
| Medios referenciados **sólo** desde código `LEGACY_INTERNAL` (misma familia estructural que R-M03) | 7 |
| **Total resuelto** | **18** |

```
CREATE 171 → ~182     SKIP 1.281 → ~1.288     MERGE 0     REVIEW 932 → 914
```

Techo real de la automatización sin decisiones: **18 filas (1,9 %)**. El problema restante ya
no es automatizable; es de decisión.

## Escenario B — deterministas + políticas globales razonables

Añade las decisiones de política que no requieren conocimiento del catálogo comercial:
Q01 (propiedad de páginas), Q02 (identidad equivalente), Q04 (fichas estáticas),
Q05 (política PDF completa), Q06 (vacíos/prueba), Q07 (landings).

```
resueltas 663  →  REVIEW restante 269
```

Destino estimado de las 663:

| Destino | Filas |
|---|---:|
| `CREATE` (attachments de medios) | ~380 |
| `SKIP` (páginas que reexpresan otra entidad) | 164 |
| `MERGE`/`CREATE` (productos de identidad equivalente) | 52 |
| `SKIP` (vacíos y prueba) | 30 |
| `CREATE` (fichas estáticas y landings) | ~37 |

Composición de las 269 restantes:

| Decisión | Filas |
|---|---:|
| Q03 fusión editorial | 164 |
| Q08 medios sin referencia | 89 |
| Q10 categorías 25/26/33 | 4 |
| Q11 recursos ausentes | 4 |
| Q05 residual (no PDF: SVG/PNG/vídeo) | 3 |
| Q12 utilitarias | 3 |
| Q13 alias binario | 1 |
| Q09 fabricante ID 150 | 1 |

## Escenario C — todo lo anterior + los 27 grupos editoriales

```
resueltas 827  →  REVIEW restante 105
```

Composición de las 105 restantes:

| Decisión | Filas | Por qué sigue abierta |
|---|---:|---|
| Q08 medios sin referencia | **89** | requiere evidencia de producción, no está en el repositorio |
| Q10 categorías 25/26/33 | 4 | decisión editorial pequeña |
| Q11 recursos ausentes | 4 | 3 requieren recuperar el original |
| Q05 residual | 3 | SVG/PNG/vídeo externo fuera de la política de tipos |
| Q12 utilitarias | 3 | depende de si la función se reimplementa |
| Q13 alias binario | 1 | residuo técnico del ensayo |
| Q09 fabricante | 1 | requiere catálogo de fabricante |

**Las 105 no son deuda por descuido**: 89 esperan información externa que nadie del equipo
puede fabricar, y las otras 16 son decisiones pequeñas y explícitas.

## Resumen comparativo

| Escenario | Decisiones humanas | Resueltas | REVIEW restante | % resuelto |
|---|---:|---:|---:|---:|
| Hoy | — | — | 932 | — |
| A · sólo deterministas | 0 | 18 | 914 | 1,9 % |
| B · + políticas globales | 6 | 663 | 269 | 71,1 % |
| C · + 27 grupos editoriales | 33 | 827 | 105 | 88,7 % |

## Lo que la simulación no afirma

- No afirma que deba crearse ningún objeto: afirma que la evidencia lo permitiría.
- No sustituye a un DRY RUN real, que recalcularía hashes y validaría bytes y podría
  rechazar filas que aquí aparecen como viables.
- No fuerza REVIEW a cero, ni lo intenta: **105 REVIEW correctos son preferibles a 0 REVIEW
  inventados**.

## Precisión del modelo

37 de las 41 filas de Q05 tienen un segundo bloqueo (su propietario). En los escenarios B y C
ese propietario queda resuelto de todas formas, por lo que las cifras se mantienen; una
implementación real debe levantar ambos bloqueos, no sólo el de tipo.

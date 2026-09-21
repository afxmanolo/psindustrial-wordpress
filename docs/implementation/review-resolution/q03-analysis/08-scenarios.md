# Tres escenarios simulados — aritmética, no ejecución

**Sin tocar el plan.** Estas cifras son una proyección aritmética sobre los conteos ya
verificados en [07-dependency-impact.md](07-dependency-impact.md), no el resultado de
implementar ninguna decisión ni de ejecutar `Planner`/`Runner` con lógica nueva. Se apoyan en
dos hechos ya verificados del plan vivo, no en supuestos:

1. El resumen de nivel superior del plan (`plan['summary']['actions']`) agrupa **todas** las
   acciones que crean un objeto WordPress nuevo (`MIGRATE`, `MERGE`, `CREATE_FROM_STATIC`) bajo
   un único contador `CREATE`, y todo lo que se descarta/consolida bajo `SKIP` — verificado
   directamente: el plan actual ya reporta `CREATE:407` pese a que ese número mezcla las 149
   `MIGRATE` de Q01, las 26 `MERGE` de Q02, las 32 `CREATE_FROM_STATIC` de Q04, etc. Por eso
   estos escenarios sólo proyectan CREATE/SKIP/REVIEW/UNCHANGED, no un desglose por acción
   interna.
2. La deduplicación real por SHA-256 (la que aplicaría `media_union()`) puede fusionar dos
   filas `asset:` de entrada en un solo adjunto final — Q02 ya lo demuestra (124 filas de
   entrada, 26 productos, pero menos de 124 adjuntos finales únicos). Las cifras de "medios"
   de estos escenarios son **filas de entrada que salen de REVIEW**, no adjuntos finales — la
   misma convención que ya usan todos los informes de esta fase y la anterior.

## Escenario A — sólo los 16 category-only

```
+16 producto ganador, -33 producto perdedor (consolidado)  => 49 filas de producto resueltas
+~100 medios (101 filas de entrada, -1 por el par compartido lux-2b/icaro-smart dentro del mismo bucket)
+17 páginas retiradas
─────────────────────────────────────
CREATE     407 + 16 + 100 = 523
SKIP     1.467 + 33 + 17  = 1.517
REVIEW     510 - 166      = 344
UNCHANGED                 = 15
TOTAL                     = 2.399  ✓
```

## Escenario B — A + los 7 nombre-cosmético (`SAME_PRODUCT_DIFFERENT_NAME` sin categoría)

```
+7 producto ganador, -7 producto perdedor  => 14 filas de producto
+41 medios
+7 páginas retiradas
─────────────────────────────────────
CREATE     523 + 7 + 41  = 571
SKIP     1.517 + 7 + 7   = 1.531
REVIEW     344 - 62      = 282
UNCHANGED                = 15
TOTAL                    = 2.399  ✓
```

## Escenario C — B + los 4 casos difíciles (Kelley, Modern Steel, Thermospan, LiftMaster), asumiendo que se resuelven

```
+4 producto ganador, -7 producto perdedor  => 11 filas de producto (grupos de 2,2,3,4 miembros)
+24 medios
+6 páginas retiradas
─────────────────────────────────────
CREATE     571 + 4 + 24  = 599
SKIP     1.531 + 7 + 6   = 1.544
REVIEW     282 - 41      = 241
UNCHANGED                = 15
TOTAL                    = 2.399  ✓
```

## Lectura

```
                    Baseline    Escenario A   Escenario B   Escenario C
CREATE                 407           523           571           599
SKIP                 1.467         1.517         1.531         1.544
REVIEW                  510           344           282           241
UNCHANGED                15            15            15            15
```

El Escenario C deja **241 REVIEW**, muy cerca de los 242 que resultarían de restar
aritméticamente las 268 filas de Q03 de las 510 actuales (510-268=242; la diferencia de 1 es
el mismo ajuste por el par compartido lux-2b/icaro-smart). Esto confirma, con una segunda vía
de cálculo independiente, que **prácticamente todo lo que queda en REVIEW tras Q03 es
exactamente Q08 (89), Q09 (1), Q12 (3), los 2 casos genuinamente ausentes de Q11, y los 3
residuales de página ya identificados como ajenos a Q03** (ver
[07-dependency-impact.md](07-dependency-impact.md)) — no un resto sin explicar.

Ningún escenario se fuerza a una cifra redonda ni se presenta como el resultado real de
implementar nada: son proyecciones para que la decisión humana sepa qué está en juego antes de
responder, tal como pidió la tarea.

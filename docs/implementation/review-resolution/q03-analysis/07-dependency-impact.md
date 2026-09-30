# Filas desbloqueadas por grupo, sin doble conteo

Metodología: para cada grupo se cuentan (a) sus propias filas de producto (`sql:productos:<id>`,
siempre en `REVIEW` hoy, verificado contra el plan vivo), (b) los medios (`asset:<path>`) que
referencia **cualquiera** de sus miembros y que hoy siguen en `REVIEW`, y (c) las páginas
`PRODUCT_PAGE` de `content-master.csv` cuyo `related_product_ids` interseca los IDs del grupo,
también si siguen en `REVIEW`. Un medio se cuenta una sola vez por grupo aunque varios
miembros lo compartan (deduplicado por `asset:<path>`, no por fila SQL).

## Tabla completa (27 grupos)

| Grupo | Tipo | Producto | Medios | Páginas | Total (grupo) |
|---|---|---:|---:|---:|---:|
| accesspro-fs1000speed.php | CO | 4 | 5 | 2 | 11 |
| energy-series-with-intellicore-37171-3-4.php | AMB | 2 | 8 | 1 | 11 |
| rampas-de-anden-hidraulicas-kelley.php | AMB | 3 | 4 | 1 | 8 |
| cortina-en-aluminio-serie-511-521.php | AMB | 2 | 4 | 1 | 7 |
| coleccion-modern-steel.php | AMB | 4 | 10 | 1 | 15 |
| puerta-seccional-de-acero-thermacore-serie-592.php | AMB | 2 | 4 | 1 | 7 |
| cortina-serie-600.php | CO | 3 | 6 | 1 | 10 |
| puertas-seccionales-de-acero-aisladas-thermospan-modelo-150.php | AMB | 2 | 6 | 2 | 10 |
| puertas-blindadas.php | CO | 3 | 10 | 1 | 14 |
| puertas-contra-incendio.php | CO | 3 | 8 | 1 | 12 |
| puerta-estandar.php | CO | 3 | 9 | 1 | 13 |
| puerta-estandar-reforzada.php | CO | 3 | 8 | 1 | 12 |
| puerta-holandesa.php | CO | 3 | 6 | 1 | 10 |
| puerta-y-fijos-louver.php | CO | 3 | 5 | 1 | 9 |
| fast-seal-high-performance-door.php | AMB | 2 | 6 | 1 | 9 |
| rapida-apilable.php | AMB | 2 | 6 | 1 | 9 |
| rolli-zip.php | AMB | 2 | 7 | 1 | 10 |
| lift-master-mod-h.php | AMB | 2 | 4 | 2 | 8 |
| barreras-estacionamiento-moovi50rm.php | CO | 4 | 4 | 1 | 9 |
| p45-7-piston-hidraulico-para-puerta-abatible.php | CO | 3 | 5 | 1 | 9 |
| lux-2b-y-lux-gv-piston-hidraulico-para-puerta-abatible.php | CO | 3 | 4 | 1 | 8 |
| icaro-smart.php | CO | 4 | 5 | 1 | 10 |
| labio-de-elevacion-mecanico-dockman.php | AMB | 2 | 6 | 1 | 9 |
| cortina-plana.php | CO | 3 | 9 | 1 | 13 |
| cortina-europea.php | CO | 3 | 8 | 1 | 12 |
| operador-para-perfilados-comerciales-sel.php | CO | 2 | 5 | 1 | 8 |
| cortinas-ventiladas-685.php | CO | 2 | 4 | 1 | 7 |

CO = category-only (16). AMB = revisión individual (11).

## Totales sin doble conteo

```
74 filas de producto   (suma directa: cada id pertenece a exactamente 1 grupo)
30 filas de página     (suma directa: cada página depende de exactamente 1 grupo)
164 filas de medio DISTINTAS  (166 en la suma por-grupo de la tabla; 2 de esas apariciones son
                                el mismo PDF+su imagen compartidos ENTRE lux-2b e icaro-smart,
                                dos grupos category-only — no un medio compartido con nada
                                fuera de Q03, así que no representa un conflicto de alcance)
────────────────────────────────────────────────────────────────────────────────────────────
268 filas REVIEW distintas dependen directamente de resolver los 27 grupos Q03
```

Esto es notablemente más alto que la estimación histórica de "164 filas (74 productos + 90
medios)". La diferencia no es un error de este análisis ni del histórico: el histórico contaba
probablemente adjuntos finales post-deduplicación (como hace Q02 al hablar de "124 fichas"
para sus 26 grupos, que también es un conteo de filas de entrada, no de adjuntos finales — ver
nota metodológica en [08-scenarios.md](08-scenarios.md)); este análisis cuenta filas `asset:`
individuales tal como existen hoy en el plan, antes de cualquier deduplicación por SHA-256 que
la resolución real aplicaría. Ambos son correctos para lo que miden; éste es el que responde
literalmente "cuántas filas REVIEW están bloqueadas hoy por Q03".

## Sección 16 — ¿cuántos residuales Q01/Q05 dependen de Q03?

**Q01 (páginas de propietario ambiguo):** de las **33** filas `PRODUCT_PAGE` que siguen en
`REVIEW` en el plan vivo, **30 (91 %) son exactamente las páginas de estos 27 grupos** — ya
incluidas en las 268 de arriba, no una cifra adicional. Las **3 que no dependen de Q03**:

| Página | `related_product_ids` | Por qué sigue REVIEW (no es Q03) |
|---|---|---|
| `puertas-industriales-dockman.php` | 50\|59 | Es un grupo **Q02** (`puertas-industriales-dockman.php => 50,59`), ya aprobado — la página no se retiró porque su ganador Q02 no llegó a materializarse en `$out` en esta ejecución del plan; caso de Q02, no de Q03. No investigado más porque está fuera del alcance de esta tarea. |
| `puertas-seccionales-de-aluminio-521.php` | 67 | Un solo id, no es un grupo multi-fila; no es materia Q03. |
| `sellos-nacionales.php` | 150 | El producto del propio Q09 (Dockman/Solmmer) — confirma explícitamente que el hallazgo de Q11 sobre el PDF con espacio **no** movió esta página fuera de Q09. |

**Q05 (PDF bloqueados por seguridad):** se verificaron las 164 filas de medio ligadas a Q03 y
**ninguna** conserva hoy una marca de validación de seguridad pendiente (`media_validation`
vacío de banderas PDF en las 164). Esto significa que **0 de los 17 residuales Q05** dependen
de un medio de Q03: los 17 pertenecen enteramente a productos de los conflictos Q09/Q10 que
`20-remaining-review.md` ya identificó como su causa ("D03/D06"), no a Q03. Verificado, no
asumido de la tabla de correspondencias de `13-human-decisions-map.md`.

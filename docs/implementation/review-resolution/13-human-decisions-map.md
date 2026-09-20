# Mapa de decisiones humanas

13 decisiones distintas cubren las 932 filas. Cada fila del CSV lleva su `question_id`.

## Clasificación por naturaleza

| Tipo | Qué significa | Decisiones | Filas |
|---|---|---|---:|
| `GLOBAL_POLICY` | una regla que se aplica igual a todo un tipo de contenido | Q01, Q02, Q05, Q06, Q08 | **698** |
| `GROUP_POLICY` | una decisión por grupo, con criterio homogéneo | Q04, Q07 | 57 |
| `ENTITY_DECISION` | caso por caso sobre entidades concretas | Q03, Q10 | 168 |
| `CONTENT_EDITORIAL` | requiere conocer el catálogo comercial | Q09 | 1 |
| `EXTERNAL_INFORMATION_REQUIRED` | necesita datos que no están en el repositorio | (Q08 parcial), Q11 | 4 |
| `TECHNICAL_CLEANUP` | residuo técnico sin contenido en juego | Q12, Q13 | 4 |

Nota: Q08 aparece como `GLOBAL_POLICY` porque la decisión *de política* ("qué hacemos con
medios sin referencia") es global, aunque su resolución óptima requiera información externa.

## Las 13 decisiones

| ID | Decisión | Tipo | Filas | Entidades | Riesgo | Esfuerzo | Info externa |
|---|---|---|---:|---:|---|---|---|
| **Q01** | Quién posee el contenido de las páginas de producto/categoría/marca, y qué pasa con sus 250 medios | GLOBAL_POLICY | **414** | 174 | Medio | Medio | No |
| **Q03** | Fusión editorial de 27 grupos divergentes | ENTITY_DECISION | **164** | 27 | Alto | Alto | Parcial |
| **Q02** | Política de medios en 26 grupos de identidad equivalente | GLOBAL_POLICY | **124** | 26 | Bajo | Bajo | No |
| **Q08** | Medios sin referencia demostrable | GLOBAL_POLICY | 89 | — | Medio | Bajo (decidir) / Alto (resolver bien) | Sí (66 de 89) |
| **Q05** | Política para los PDF bloqueados por contenido | GLOBAL_POLICY | 41 | 37 | Bajo | Bajo | No |
| **Q04** | Entidad y categoría de las 31 fichas estáticas | GROUP_POLICY | 31 | 31 | Medio | Medio | No |
| **Q06** | Registros vacíos y de prueba (151–165) | GLOBAL_POLICY | 30 | 15 | Bajo | Muy bajo | No |
| **Q07** | Landings SEO | GROUP_POLICY | 26 | 9 | Medio | Bajo | Parcial |
| **Q10** | Categorías 25 / 26 / 33 | ENTITY_DECISION | 4 | 3 | Medio | Muy bajo | No |
| **Q11** | Recursos ausentes | EXTERNAL_INFORMATION_REQUIRED | 4 | 4 | Bajo | Muy bajo | Sí (3 de 4) |
| **Q12** | Páginas utilitarias | TECHNICAL_CLEANUP | 3 | 3 | Bajo | Muy bajo | No |
| **Q13** | Alias binario del PDF del ensayo | TECHNICAL_CLEANUP | 1 | 1 | Muy bajo | Muy bajo | No |
| **Q09** | Fabricante del ID 150 (Dockman / Solmmer) | CONTENT_EDITORIAL | 1 | 1 | Alto | Bajo | Sí |
| | | | **932** | | | | |

## Dependencias entre decisiones

```
Q01 (propiedad de páginas) ──┬──> desbloquea 164 páginas
                             └──> determina el destino de 250 medios
                                  └──> acoplada con Q04 (32 ficheros son los mismos)

Q02 (identidad equivalente) ─────> 52 productos + 72 medios
                                   (independiente de todo lo demás)

Q03 (27 grupos editoriales) ─────> 74 productos + 90 medios
                                   └──> 14 de los 27 grupos son, en realidad,
                                        la pregunta "¿varias categorías por producto?"
                                   └──> 5 de los 27 arrastran el conflicto de marca (Q09 ampliado)

Q05 (PDF) ───────────────────────> 41 medios, de los cuales 37 esperan ADEMÁS a Q01/Q02/Q03

Q06 (vacíos) ────────────────────> 15 productos + 15 medios
                                   └──> condiciona parcialmente Q10 (la categoría 25 los agrupa)
```

**Q02, Q05 y Q06 no dependen de nada.** Pueden responderse hoy, en cualquier orden.

## Lo que ya no es una decisión

481 filas (`R-M01b/04b/05b`) figuraban como regla MEDIUM pendiente y **no requieren ninguna
decisión propia**: son propagación automática de dependencias. Se resuelven solas cuando se
respondan Q01, Q02 y Q03. Contarlas como trabajo humano pendiente sobreestima el problema en
más de la mitad.

## Correspondencia con el catálogo original de decisiones

| Nueva | Catálogo anterior (`10-human-decisions.md` / `manual-decisions-required.md`) |
|---|---|
| Q02 | H1 · D02-a |
| Q03 | H2 · D02-b |
| Q04 | H3 · D02-c |
| Q06 | H4 · D05 |
| Q09 | H5 · D03 |
| Q10 | H6 · D06 |
| Q11 | H7 · D08 |
| Q08 | H8 · D09 |
| Q12 | H9 · D12 |
| Q01, Q05, Q07, Q13 | **nuevas**: emergieron al implementar las reglas LOW y auditar los PDF |

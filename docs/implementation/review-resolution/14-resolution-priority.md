# Prioridad de resolución

Orden propuesto para maximizar **REVIEW desbloqueados por decisión humana**, sin sacrificar
seguridad.

## Métrica por decisión

| ID | Filas | Entidades | Riesgo | Esfuerzo | Dependencias | Filas / esfuerzo |
|---|---:|---:|---|---|---:|---|
| Q02 | 124 | 26 | Bajo | Bajo | 0 | **muy alto** |
| Q05 | 41 | 37 | Bajo | Bajo | 0 | **muy alto** |
| Q06 | 30 | 15 | Bajo | Muy bajo | 0 | **muy alto** |
| Q01 | 414 | 174 | Medio | Medio | 0 (pero acopla Q04) | **alto** |
| Q04 | 31 | 31 | Medio | Medio | acoplada a Q01 | medio |
| Q07 | 26 | 9 | Medio | Bajo | 0 | medio |
| Q10 | 4 | 3 | Medio | Muy bajo | parcial de Q06 | bajo |
| Q12 | 3 | 3 | Bajo | Muy bajo | 0 | bajo |
| Q13 | 1 | 1 | Muy bajo | Muy bajo | 0 | bajo |
| Q11 | 4 | 4 | Bajo | Muy bajo | externa | bajo |
| Q09 | 1 | 1 | Alto | Bajo | externa | bajo |
| Q03 | 164 | 27 | Alto | **Alto** | Q09 parcial | bajo (pero inevitable) |
| Q08 | 89 | — | Medio | Alto (externo) | externa | bajo |

## Orden propuesto

### Bloque 1 — decisiones sin dependencias, riesgo bajo, efecto inmediato
**Q02 + Q05 + Q06 → 195 filas, 3 decisiones.**

Las tres se pueden responder hoy, no dependen de nada y su evidencia está cerrada:
- **Q02** (124): la identidad está demostrada en los 26 grupos; lo único que falta es fijar
  qué imagen va primero.
- **Q05** (41): 11 son extensión trivial de una aprobación ya vigente; los otros 30 tienen
  perfil estructural ya conocido y documentado.
- **Q06** (30): una sola respuesta cubre los 15 registros vacíos y el de prueba.

Riesgo conjunto: bajo. Ninguna toca contenido comercial en disputa.

### Bloque 2 — la decisión de mayor impacto
**Q01 + Q04 → 445 filas, 2 decisiones acopladas.**

Q01 sola desbloquea 414 filas: es, con diferencia, la decisión de mayor rendimiento del
proyecto. Debe responderse **junto con Q04** porque 32 ficheros PHP son literalmente los
mismos artefactos en ambas preguntas.

Condición de seguridad: Q01 no debería ejecutarse hasta que exista un compromiso explícito
sobre la fase de URLs/SEO. Marcar 164 páginas como "no crear Page" es correcto como decisión
de contenido y **destructivo como decisión de URL** si esa fase no llega. La decisión de
contenido puede tomarse ya; su *aplicación* debería quedar condicionada.

### Bloque 3 — decisiones pequeñas de limpieza
**Q07 + Q10 + Q12 + Q13 + Q11 → 38 filas, 5 decisiones.**

Bajo volumen, bajo riesgo individual, sin dependencias fuertes. Se pueden agrupar en una sola
sesión de revisión.

### Bloque 4 — el trabajo editorial irreducible
**Q03 → 164 filas, 27 decisiones de grupo.**

Es el único bloque que exige conocimiento del catálogo comercial producto por producto. La
buena noticia: [07-category-conflicts.md](07-category-conflicts.md) demuestra que **14 de
los 27** grupos divergen exclusivamente por la categoría, y que el patrón es siempre el
mismo (el legacy repetía la fila una vez por sección). Si Q03 se aborda primero decidiendo la
política de categorías múltiples, esos 14 grupos podrían resolverse en bloque y dejar sólo
13 casos genuinamente individuales.

### Bloque 5 — pendiente de información externa
**Q08 (+ Q09) → 90 filas.**

No se resuelve con más análisis del repositorio. Requiere Search Console, logs o crawl
autorizado (Q08) y el catálogo de fabricante (Q09).

## Resumen del camino

| Bloque | Decisiones | Filas | REVIEW acumulado restante |
|---|---:|---:|---:|
| inicio | — | — | 932 |
| 1 | 3 | 195 | ~737 |
| 2 | 2 | 445 | ~292 |
| 3 | 5 | 38 | ~254 |
| 4 | 27 (agrupables a ~14) | 164 | ~90 |
| 5 | 2 | 90 | ~0–66 |

**Con 10 decisiones (bloques 1–3) se pasa de 932 a ~254 REVIEW.** El resto es trabajo
editorial de catálogo y evidencia de producción.

## Advertencia sobre el orden

El orden anterior maximiza filas desbloqueadas, no seguridad de publicación. Ninguna de estas
decisiones autoriza publicar contenido: todo lo que resulte de ellas entra como borrador y
sigue sujeto al gate de importación vigente.

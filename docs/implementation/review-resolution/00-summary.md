# Resolución de los 932 REVIEW — resumen ejecutivo

Fecha: 2026-09-18. Rama `feature/review-resolution`. **Fase de análisis y decisión.** No se
modificó código, WordPress, la base de datos ni ninguna decisión existente. No se ejecutó
ninguna importación. `/legacy` intacto. Sólo se crearon documentos bajo
`docs/implementation/review-resolution/`.

Estado reconstruido desde el plan vivo (`Planner::build('full')`, DRY RUN), no desde el
`all-review.csv` antiguo:

```
total 2.399 · UNCHANGED 15 · SKIP 1.281 · CREATE 171 · REVIEW 932 · ERROR 0
```

## Las diez respuestas

### 1. ¿De qué están compuestos realmente los 932 REVIEW?

De **muy pocas causas repetidas muchas veces**:

| source_type | Filas |
|---|---:|
| media | 576 |
| page | 176 |
| product | 142 |
| static_product | 31 |
| missing_media | 4 |
| category | 3 |

Pero la composición que importa es por **decisión bloqueante**: tres decisiones concentran
**702 de las 932 filas (75 %)** — propiedad del contenido de páginas (414), fusión editorial
de 27 grupos (164) y política de medios en 26 grupos idénticos (124).

Además, **481 filas no requieren ninguna decisión propia**: son medios que esperan a que se
decida su entidad propietaria y se resolverán solos. Tratarlas como trabajo pendiente
sobreestima el problema en más de la mitad.

### 2. ¿Cuántas decisiones humanas diferentes existen?

**13.** Ni una más. Todas las 932 filas están atribuidas a una de ellas en
[02-current-review-932.csv](02-current-review-932.csv).

### 3. ¿Cuántas son políticas globales?

**5** (Q01, Q02, Q05, Q06, Q08) y cubren **698 filas — el 75 %**. Son reglas que se aplican
igual a todo un tipo de contenido; no requieren mirar producto por producto.

### 4. ¿Cuántas son decisiones por grupo?

**2** (Q04 fichas estáticas, Q07 landings), 57 filas. Criterio homogéneo dentro del grupo.

### 5. ¿Cuántas son realmente caso por caso?

**2** (Q03 con 27 grupos, Q10 con 3 categorías), 168 filas. Y de los 27 grupos de Q03,
**14 comparten un único patrón** (el producto repetido una vez por categoría), así que en la
práctica son ~14 decisiones individuales reales, no 27.

Las 4 restantes (Q09, Q11, Q12, Q13) son residuales: 9 filas en total.

### 6. ¿Qué decisión única desbloquearía más REVIEW?

**Q01 — de quién es el contenido de las páginas de producto/categoría/marca: 414 filas**
(164 páginas + 250 archivos de medios), 174 entidades.

Es, con diferencia, la de mayor rendimiento. Hallazgo importante de esta fase: esa decisión
**no es sólo sobre páginas**. Esas 164 páginas son el único propietario documentado de 250
imágenes y fichas técnicas; decidir que la página no se crea sin decir qué pasa con sus
medios deja 250 archivos sin dueño.

### 7. ¿Cuántos REVIEW podrían resolverse sin información externa?

**839 de 932 (90,0 %).** Sólo Q08 (89 filas), Q09 (1) y 3 de las 4 de Q11 requieren datos que
no están en el repositorio.

De esos 839, **18 se resolverían sin ninguna decisión humana** (escenario A: extensión por
hash de aprobaciones ya vigentes y medios referenciados sólo desde código interno). El resto
requiere decisión, pero no información nueva.

### 8. ¿Cuántos requieren conocimiento del cliente/catálogo?

**196 filas**: Q03 (164, fusión editorial), Q04 (31, categoría de las fichas estáticas) y
Q09 (1, fabricante). Es el trabajo irreducible que ninguna herramienta puede hacer.

### 9. ¿Cuántos requieren información externa/producción?

**93 filas**: Q08 (89, de las cuales 66 son realmente críticas — las otras 23 tienen su
contenido duplicado en otra ruta y no corren riesgo de pérdida), Q11 (3 de 4) y Q09 (1).

### 10. ¿Cuál sería el camino más seguro hacia la primera importación completa local?

En cuatro pasos, en este orden ([14-resolution-priority.md](14-resolution-priority.md)):

1. **Responder Q02, Q05 y Q06** — tres decisiones sin dependencias, riesgo bajo, evidencia
   cerrada. Desbloquean 195 filas. Se pueden contestar hoy.
2. **Responder Q01 junto con Q04**, condicionando su *aplicación* a que exista compromiso
   sobre la fase de direcciones web. Desbloquean 445 filas más.
3. **Cerrar las decisiones pequeñas** (Q07, Q10, Q11, Q12, Q13): 38 filas.
4. **Abordar Q03** empezando por la política de categorías múltiples, que resuelve 14 de los
   27 grupos en bloque.

Tras los pasos 1–3 quedarían ~254 REVIEW; tras el 4, ~90, casi todos a la espera de datos de
producción. **Sólo entonces tendría sentido plantear una importación completa local**, y aun
así como borrador, con el gate actual intacto.

## Tres hallazgos que cambian el cuadro

### El riesgo que bloqueaba los 26 grupos idénticos no existe

La fase anterior no propuso una regla de unión de medios porque `db_image_ids` divergía en
todos los grupos y por temor a unir fichas técnicas contradictorias (D07). Al resolver esos
IDs **a rutas de archivo reales**:

- los **26 de 26** grupos tienen **conjuntos de PDF idénticos** — cero contradicción;
- **18 de 26** tienen incluso las imágenes idénticas: son duplicados puros;
- sólo **8** tienen listas de imágenes distintas, y son fotos complementarias del mismo producto.

La divergencia de IDs era un artefacto de registros `file` duplicados apuntando al mismo
archivo. Q02 pasa de "decisión editorial arriesgada" a "decisión de presentación de bajo
riesgo": lo único que falta decidir es **qué foto va primero**.

### Los 32 PDF bloqueados se reducen a una extensión trivial

El triaje estructural (mismo método seguro de la auditoría aprobada) muestra que **ninguno**
contiene JavaScript real, acciones de lanzamiento ni contenido auto-ejecutable. Y **11 de los
32 son byte-idénticos a archivos que ya aprobaste**; para 9 de ellos la copia saneada ya
existe y está verificada. Lo que parecían 32 auditorías es 1 extensión por hash más 1
decisión de política sobre 21 archivos de perfil conocido.

### La pregunta de las categorías múltiples ya está contestada por los datos

14 de los 27 grupos editoriales divergen **exclusivamente** por la categoría, siempre con el
mismo patrón: `19`, `19|32`, `32`. El sistema antiguo repetía la ficha del producto una vez
por cada sección donde aparecía, porque sólo admitía una categoría por fila. No es una
función de WordPress buscando dónde aplicarse: es la estructura que el sitio ya tenía.

## Entregables

| Documento | Contenido |
|---|---|
| [01-current-review-distribution.md](01-current-review-distribution.md) | Distribución por tipo, causa, decisión y dependencia |
| [02-current-review-932.csv](02-current-review-932.csv) | Una fila por REVIEW real, con su decisión bloqueante |
| [03-identity-groups.md](03-identity-groups.md) | Los 26 grupos de identidad equivalente, verificados |
| [04-media-merge-policy.md](04-media-merge-policy.md) | Alternativas A/B/C/D y condiciones de una regla segura |
| [05-merge-groups.csv](05-merge-groups.csv) | Matriz de los 53 grupos (26 idénticos + 27 editoriales) |
| [06-brand-conflicts.md](06-brand-conflicts.md) | Los 6 conflictos de fabricante, con evidencia |
| [07-category-conflicts.md](07-category-conflicts.md) | Jerarquía frente a pertenencia múltiple |
| [08-static-products.md](08-static-products.md) | Las 31 fichas sólo-página y la doble enumeración |
| [09-page-decisions.md](09-page-decisions.md) | Recomendación por clase de página |
| [10-unreferenced-media.md](10-unreferenced-media.md) | Subdivisión de los 89 medios sin referencia |
| [11-missing-media.md](11-missing-media.md) | Los 4 recursos ausentes, uno por uno |
| [12-medium-rules-reassessment.md](12-medium-rules-reassessment.md) | Reevaluación de todas las reglas MEDIUM |
| [13-human-decisions-map.md](13-human-decisions-map.md) | Las 13 decisiones, su tipo y sus dependencias |
| [14-resolution-priority.md](14-resolution-priority.md) | Orden propuesto y rendimiento por decisión |
| [15-scenarios.md](15-scenarios.md) | Escenarios A / B / C con cifras |
| [decision-questionnaire.md](decision-questionnaire.md) | **Las 13 preguntas, en lenguaje de negocio** |

## Lo que esta fase no hizo

No se fusionó ningún producto, no se asignó ningún fabricante, no se creó ninguna categoría,
no se resolvió ninguna decisión humana, no se implementó ninguna regla MEDIUM o HIGH, no se
tocó el manifest ni ninguna decisión existente, y no se ejecutó ninguna importación.

# Política de medios para una misma entidad

Pregunta: si dos registros SQL representan inequívocamente el mismo producto y sólo difieren
en la lista de imágenes, ¿qué política es correcta?

Base de evidencia: [03-identity-groups.md](03-identity-groups.md). Datos duros que acotan la
pregunta:

- afecta a **8 grupos** (no a 26): en los otros 18 las imágenes ya son idénticas;
- **0 grupos** tienen PDFs contradictorios;
- **0 grupos** tienen duplicados binarios internos;
- todos comparten PHP y URL canónicos.

## Comparación de alternativas

### A — Elegir un registro ganador y descartar los medios del otro

| Criterio | Evaluación |
|---|---|
| Fidelidad al legacy | **Baja en 8 grupos.** El sitio mostraba la ficha con las imágenes agregadas; descartar un subconjunto muestra menos de lo que había |
| Riesgo | Bajo en seguridad, **medio en contenido**: pérdida silenciosa y difícil de detectar después |
| Pérdida de contenido | Sí: entre 1 y 3 imágenes por grupo afectado |
| SEO | Neutro para URLs; ligeramente negativo por menos contenido visual |
| Duplicados | Ninguno |
| Auditabilidad | Alta: regla simple ("gana el ID menor") |

### B — Unión de medios únicos cuando no sean contradictorios

| Criterio | Evaluación |
|---|---|
| Fidelidad al legacy | **Alta.** Reproduce el conjunto de imágenes que el catálogo asociaba al producto |
| Riesgo | Bajo: la evidencia descarta contradicción de PDFs en los 26 grupos; las imágenes son del mismo producto |
| Pérdida de contenido | Ninguna |
| SEO | Neutro/positivo |
| Duplicados | Ninguno dentro del grupo (verificado por hash) |
| Auditabilidad | Alta si la regla registra de qué registro vino cada imagen |

**Punto débil de B, y es real:** la unión no dice **cuál es la imagen destacada** ni en qué
orden va la galería. Eso hay que fijarlo explícitamente (por ejemplo, "primera imagen del
registro de menor ID"), y es una elección visual, no un dato del legacy.

### C — Mantener únicamente los medios referenciados por la página PHP pública

| Criterio | Evaluación |
|---|---|
| Fidelidad al legacy | **La más alta en teoría**: es literalmente lo que el visitante veía |
| Riesgo | **Alto en la práctica**: exige resolver primero qué imágenes referencia cada PHP, lo que depende de la extracción estática y de Q01 (propiedad de páginas), todavía sin decidir |
| Pérdida de contenido | Posible: imágenes del catálogo SQL que la ficha no mostraba pero que el backoffice sí tenía |
| SEO | Neutro |
| Duplicados | Ninguno |
| Auditabilidad | Media: la evidencia es la extracción de HTML, más frágil que una relación SQL |

C es atractiva conceptualmente pero **hoy no es ejecutable**: crea una dependencia con una
decisión (Q01) que aún no está tomada, y sustituye una relación SQL explícita por una
inferencia sobre HTML.

### D — Mantener REVIEW individual

| Criterio | Evaluación |
|---|---|
| Fidelidad | No aplica: no se migra nada |
| Riesgo | Nulo |
| Coste | **124 filas siguen bloqueadas** y 26 productos reales no entran al catálogo |
| Auditabilidad | Máxima |

## ¿Puede existir una regla segura «misma entidad + medios complementarios»?

Sí, y la evidencia la acota con precisión. Una regla de este tipo sería defendible **si y
sólo si** exige todas estas condiciones, verificables por máquina:

1. el grupo canónico tiene `record_count > 1`;
2. **todos** los miembros comparten `legacy_php` y `legacy_url`;
3. **todos** comparten `category_id` y `brand_id`;
4. los nombres coinciden al normalizar mayúsculas y espacios;
5. el **conjunto de PDFs resuelto a rutas** es idéntico entre miembros;
6. ningún miembro figura en los registros de conflicto D03/D06/D07;
7. la imagen destacada y el orden quedan fijados por una regla explícita y registrada.

Las condiciones 1–6 se cumplen hoy en los 26 grupos. La condición 7 **no es un dato del
legacy**: es una elección que debe autorizarse, no derivarse.

Con esas siete condiciones, la regla no inventa contenido: cada imagen de la unión ya estaba
asociada a ese mismo producto en el catálogo original, y ningún documento técnico entra en
conflicto con otro.

## Recomendación técnica neutral

La evidencia favorece **B con la condición 7 explícita**, y reduce A a una alternativa
conservadora con pérdida acotada y conocida (imágenes de 8 grupos). C no es viable hoy por
dependencia con Q01. D tiene coste alto y beneficio nulo dado que la identidad ya está
demostrada.

**La decisión que falta no es "¿son el mismo producto?" —eso está demostrado— sino "¿qué
imagen se ve primero?".** Es una decisión de presentación, no de identidad.

No se implementa ninguna regla en esta fase.

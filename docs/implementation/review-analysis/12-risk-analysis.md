# Análisis de riesgo

## Criterios

El riesgo mide **la dificultad de detectar y revertir un error**, no la probabilidad de que
la regla se equivoque.

| Nivel | Criterio | Uso |
|---|---|---|
| **LOW** | La condición es un hecho verificable por máquina (hash, ruta, relación explícita, estructura de directorios). Un error sería visible de inmediato y revertible sin pérdida de información. No depende de ninguna decisión pendiente. | Candidata a automatización futura sin intervención |
| **MEDIUM** | La condición es verificable, pero la consecuencia depende de que otra fase se complete, o de una política que aún no está escrita. Un error sería recuperable, pero podría no detectarse hasta después. | Requiere revisión antes de implementar |
| **HIGH** | La condición depende de un juicio editorial o de evidencia que no existe en el repositorio. Un error produciría contenido incorrecto y difícil de detectar. | Permanece humana |

Distribución: **LOW 1.031 · MEDIUM 784 · HIGH 134**.

## Reglas LOW — 1.031 filas

| Regla | Filas | Por qué es LOW | Qué la degradaría |
|---|---:|---|---|
| R-M02 derivados | 561 | `original_ids` lo registró el propio CMS; los 561 están sin referencia pública | Que un derivado apareciera con flag `*_USED` → la regla lo eleva a HIGH automáticamente |
| R-M03 infraestructura | 285 | Prefijo de directorio; el backoffice desaparece con la migración | Que `system/files/` se incluyera por error en el prefijo |
| R-M04/05/01 medios de propietario aprobado | 115 | Relación explícita + SHA-256 validado | Que el propietario perdiera la aprobación |
| R-T01 categorías | 33 | Padre SQL `CONFIRMED`; crear término no asigna productos | Asignar productos en la misma operación |
| R-P01 productos limpios | 21 | Grupo unitario + PHP unívoco + sin conflicto | Asignar marca por `STRONG_INFERENCE` |
| R-T03 marcas | 11 | Página propia + logo referenciado literalmente | Asignar productos en la misma operación |
| R-G02 institucionales/contacto | 5 | No existe otro propietario posible | — |

**La condición que mantiene LOW a R-P01, R-T01 y R-T03 es la misma: crear la entidad sin
asignar las relaciones dudosas.** Si una implementación futura asignara marca por inferencia
o productos a términos, esas tres reglas pasarían a MEDIUM o HIGH.

## Reglas MEDIUM — 784 filas

| Regla | Filas | Por qué no es LOW |
|---|---:|---|
| R-M04b/01b/05b propietario pendiente | 467 | Correcto hoy, pero cambia al decidirse el propietario |
| R-G01 páginas que reexpresan entidades | 164 | Depende de que la fase de URLs/SEO exista: sin ella se perderían 164 rutas públicas |
| R-M07 medios sin uso demostrable | 89 | Se preserva por prudencia, no por certeza |
| R-S01 fichas estáticas | 31 | Categoría y marca `UNKNOWN` |
| R-P03 vacíos y prueba | 15 | Reversible, pero podrían ser altas pendientes |
| R-G02 landings SEO | 9 | `STRONG_INFERENCE`; solapan productos |
| R-M06 tipo fuera de política | 6 | Requiere revisión técnica |
| R-G03 utilitarias | 3 | Depende de D12 |

## Reglas HIGH — 134 filas

| Regla | Filas | Por qué permanece humana |
|---|---:|---|
| R-P02 duplicados | 126 | Ningún ganador determinista; fusionar mal crea fichas inexistentes |
| R-X01 recursos ausentes | 4 | No hay evidencia; sustituir sería inventar |
| R-T02 categorías 25/26/33 | 3 | Fusionar por nombre está explícitamente prohibido |
| R-P05 conflicto de fabricante | 1 | Requiere catálogo autorizado |

## Los errores más peligrosos si se automatizara de más

Ordenados por daño y por dificultad de detección.

1. **Fusionar los 53 grupos canónicos eligiendo un ganador por regla.** Produciría fichas que
   ningún registro legacy describe, con imágenes de un contexto y categoría de otro. Sería
   casi indetectable tras publicar, porque el resultado *parece* un producto válido. Es el
   error que la regla de ganadores mixtos del importador ya previene; relajarla para bajar el
   conteo sería el peor cambio posible.

2. **Asignar marca por `STRONG_INFERENCE`.** Afecta a 20 de las 21 fichas limpias. El sitio
   actual ya contiene atribuciones contradictorias; copiarlas las convertiría en dato
   estructurado y las volvería más difíciles de corregir que hoy.

3. **Descartar los 89 medios sin referencia.** Reduciría el conteo un 4,6 % y es la
   tentación más directa. La ausencia de referencia local no demuestra orfandad: el sitio
   pudo enlazarlos desde contenido que no está en la copia, o recibir tráfico directo.

4. **Aplicar R-G01 sin la fase de URLs.** `SKIP` de 164 páginas es correcto como decisión de
   contenido y catastrófico como decisión de URL. El riesgo no está en la regla sino en
   ejecutarla fuera de orden.

5. **Consolidar las categorías 26 y 33 por nombre idéntico.** Dos filas con el mismo nombre y
   distinto padre pueden ser un duplicado o dos ramas legítimas. Sólo afecta a 3 filas, pero
   altera la navegación pública.

6. **Tratar los derivados como duplicados eliminables.** R-M02 propone no importarlos, no
   borrarlos. Si una implementación futura interpretara `SKIP` como permiso de borrado,
   destruiría 561 archivos que aún sirven URLs históricas.

## Criterio de parada

El objetivo no es llegar a 0 REVIEW. Los 730 `KEEP_REVIEW` del escenario base no son deuda
pendiente: **596 son ambigüedad correctamente preservada y 134 son decisiones editoriales
reales**. Una propuesta que los redujera significativamente más estaría, casi con certeza,
inventando información.

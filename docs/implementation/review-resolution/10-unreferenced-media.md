# Medios sin referencia local

89 filas. **El principio se mantiene sin cambios: la ausencia de referencia no demuestra
orfandad.** Ninguna se propone para SKIP. Lo que sí se puede hacer es subdividirlas por la
evidencia que sí existe, para que la decisión externa (Q08) sea más barata.

## Subdivisión por evidencia

| Subgrupo | Filas | Ubicación | Evidencia que lo distingue |
|---|---:|---|---|
| `likely_historical` | 63 | `images/` | en el directorio público histórico, sin duplicado binario y sin original registrado |
| `duplicate_binary` | 23 | `images/` (15), `fichas/` (3), `system/` (3), `multimedia/` (2) | su contenido existe en otra ruta del inventario |
| `source_original` | 2 | `system/` | marcados `ORIGINAL` en el inventario |
| `genuinely_unknown` | 1 | `multimedia/` | sin ninguna señal adicional |
| **total** | **89** | | |

## Qué cambia esto para la decisión

Los tres subgrupos tienen implicaciones distintas:

### 63 `likely_historical` — el grupo que realmente necesita evidencia externa
Están en `images/`, el directorio desde el que el sitio servía sus imágenes públicas. Que
hoy no aparezca una referencia literal en el HTML copiado puede significar tres cosas muy
distintas, y el repositorio no distingue entre ellas:
- la imagen se usaba desde contenido que no está en la copia local;
- la imagen se usaba desde una versión anterior del sitio y quedó huérfana;
- la imagen nunca se publicó (subida y descartada).

**Sólo datos de producción (Search Console, logs de acceso, crawl autorizado) separan estos
tres casos.** Es el núcleo de Q08 y la razón de que siga siendo `EXTERNAL_INFORMATION_REQUIRED`.

### 23 `duplicate_binary` — decisión más barata de lo que parece
Su contenido **ya existe en otra ruta** del inventario. Si esa otra ruta acaba migrando, el
contenido visual estará en WordPress de todas formas; lo único en juego es si además se
conserva *esta* ruta concreta (una cuestión de URLs históricas, no de contenido).

Esto los convierte en candidatos naturales a resolverse **dentro de la fase de URLs/SEO**, no
dentro de la decisión de medios. No requieren datos de tráfico para decidir si el *contenido*
se pierde: no se pierde.

### 2 `source_original` + 1 `genuinely_unknown`
Volumen despreciable (3 filas). Los 2 originales bajo `system/` merecen conservarse por
definición (son la fuente de posibles derivados); el `genuinely_unknown` es un caso
individual.

## Recomendación analítica

| Subgrupo | Recomendación |
|---|---|
| 63 `likely_historical` | `KEEP_REVIEW` — requiere evidencia de producción (Q08) |
| 23 `duplicate_binary` | `KEEP_REVIEW` como medio, pero **trasladar la pregunta a la fase de URLs**: el contenido no está en riesgo |
| 2 `source_original` | `KEEP_REVIEW`; conservar por ser fuente |
| 1 `genuinely_unknown` | `KEEP_REVIEW` |

**Ninguna se elimina, ninguna se marca SKIP.** El único cambio propuesto es reconocer que 23
de las 89 no dependen realmente de información de tráfico para garantizar que no se pierde
contenido, lo que reduce el alcance efectivo de Q08 de 89 a 66 filas críticas.

# Q03-A — consolidación por variante de nombre

7 grupos aprobados, los 7 consolidan. Ninguno tiene divergencia de categoría — la unión
calculada por `q03_consolidate()` para estos grupos es siempre un único valor, un no-op
seguro del mismo mecanismo que usa Q03-GLOBAL, no una regla distinta.

## Cómo se elige el título

`Planner`'s propio `$add()` siempre usa el `name` del **ganador**, tal cual está en
`product-master.csv` — no existe ningún campo separado para "sobrescribir" el título
(verificado leyendo el código antes de diseñar esto). Por tanto, preferir un título más
completo/correcto sólo es posible **eligiendo cuál id es el ganador** — nunca reescribiendo
texto. `editorial-decisions.json` declara `canonical_title_source_id` únicamente en los 5
grupos donde el ganador natural (más antiguo, descripción no vacía) no coincide ya con el
título preferido; en los otros 2 no hace falta ninguna preferencia.

| Grupo | IDs | Título usado | Por qué |
|---|---|---|---|
| energy-series-with-intellicore-37171-3-4.php | 2,17 | el de **17** (más completo: añade 3720/3724/3722) | descripción SQL idéntica entre ambos — ningún contenido se pierde al preferir el más completo; los números de modelo ya estaban documentados en el propio registro legacy, no se inventan |
| cortina-en-aluminio-serie-511-521.php | 5,88 | el de **5** (ganador natural) | diferencia puramente de espaciado, sin preferencia editorial necesaria |
| puerta-seccional-de-acero-thermacore-serie-592.php | 8,62 | el de **62** (más completo) | coincide con el título ya usado en la página dependiente |
| fast-seal-high-performance-door.php | 36,101 | el de **101** (más completo) | coincide con el título ya usado en la página dependiente |
| rapida-apilable.php | 39,104 | el de **39** (ganador natural) | ya es la forma con mayúsculas/minúsculas correctas y más completa |
| rolli-zip.php | 40,105 | el de **105** (ortografía corregida) | "autorreparable" vs. "autorreparabale" — se usa el registro que YA trae la ortografía correcta, no se reescribe nada |
| labio-de-elevacion-mecanico-dockman.php | 51,143 | el de **51** (neutral) | ver más abajo |

## Q03-A7: por qué la marca sí se asigna pero el título no la menciona

La instrucción fue explícita: no introducir un fabricante no confirmado en el TÍTULO. Se
verificó que Dockman **sí** está confirmado como marca para este producto:
`brand_id=10`, `brand_confidence=CONFIRMED` en el registro ganador (id 51) — el mismo umbral
que ya usa Q02 para asignar marca automáticamente. Por tanto:

- **Marca (taxonomía `psi_marca`)**: se asigna, `brand:10` — es un dato estructurado, bien
  evidenciado, no el problema que la instrucción señalaba.
- **Título (texto libre)**: se usa la forma neutral de id 51 ("Labio de elevación
  mecánico"), nunca la de id 143 que añade "Dockman" al final. Esto no es una excepción
  especial: ninguno de los otros 22 grupos consolidados de esta fase repite el nombre de su
  marca en el título tampoco (Doorlock, BFT, Overhead Door, etc. se asignan todos vía
  taxonomía, nunca en el texto) — Q03-A7 simplemente evita que la ÚNICA variante de nombre
  que sí lo hacía se convirtiera en el título elegido.

## La regla no se extiende a otros productos

Verificado explícitamente por test: cualquier producto cuyo `canonical_candidate_group` no
sea uno de estos 7 nunca recibe una decisión `Q03-A`, sin importar cuán parecido sea su
nombre a alguno de los 7 casos (mayúsculas, espacios, erratas). La lista de 7 es cerrada,
leída de `editorial-decisions.json`, nunca inferida por parecido de texto.

## Trazabilidad

[q03-consolidation-audit.csv](q03-consolidation-audit.csv) (filas 17-23) y
[q03-field-provenance.csv](q03-field-provenance.csv).

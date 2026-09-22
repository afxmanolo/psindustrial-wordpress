# Riesgos identificados

Ninguno de estos riesgos bloquea el análisis; se documentan para que la decisión humana (y
cualquier implementación futura) los tenga en cuenta explícitamente.

## 1. Fusionar identidad y marca en la misma pregunta (riesgo principal)

Cuatro grupos (Modern Steel, LiftMaster/Blue Giant, Thermospan, y parcialmente Kelley) tienen
identidad de producto sólida pero marca contradictoria o ausente. El riesgo concreto: si una
futura implementación trata "¿son la misma entidad?" y "¿qué marca tienen?" como una sola
decisión, cualquier respuesta que consolide el producto arrastraría automáticamente una
resolución de marca no autorizada — exactamente lo que Q09 reservó para sí mismo. La
recomendación en [04-ambiguous-groups.md](04-ambiguous-groups.md) separa ambas preguntas
explícitamente para evitar esto: se puede consolidar identidad y migrar sin marca (mismo
mecanismo que Q09 ya ofrece como Opción C), dejando la marca abierta.

## 2. Categorías con el mismo `name` no implican duplicado — verificado, no asumido otra vez

Las categorías 12 y 38 comparten literalmente el nombre "Operadores para puerta abatible"
(usado por los grupos `p45-7-piston...` y `lux-2b...`), igual que 26/33 lo compartían antes de
que Q10 las investigara. Se verificó contra `category-master.csv`: **12 y 38 son ramas reales
distintas** (`parent_legacy_id` 1 vs 7 — la industrial y la residencial de la casa,
respectivamente, cada una con su propia página). No es el mismo patrón que 25/26 — aquí ambas
categorías tienen producto propio, página propia y padre propio. Se documenta explícitamente
para que una futura fase no vuelva a tratar "mismo nombre" como señal de fusión sin volver a
verificarlo — el riesgo no es que esta tarea se equivocara, es que la próxima lo intente sin
mirar.

## 3. El contenido "ganador" no debería heredar ciegamente la regla de Q02

Q02 elige el registro más antiguo (`productos.fecha`) como ganador para **todos** los campos,
incluida la descripción — seguro para Q02 porque, por definición, sus 26 grupos tienen
descripción idéntica en todos los miembros. **Eso no es cierto para varios grupos Q03**: en
`puertas-contra-incendio.php`, `puerta-estandar.php` y `puerta-estandar-reforzada.php`, la fila
**más antigua es precisamente la que tiene la descripción SQL vacía** (0 caracteres — la fila
`STRONG_INFERENCE` con categoría doble, creada el 22-oct, antes de que se clonara en las filas
confirmadas de 29-oct/4-nov). Si una implementación futura reutiliza la regla de Q02 sin
adaptarla, estos 3 productos quedarían con contenido vacío pese a que sus propias filas
hermanas sí tienen descripción. **Recomendación técnica, no implementada aquí:** separar
"ganador de identidad/fecha" (para trazabilidad, puede seguir siendo el más antiguo) de
"ganador de contenido" (debería ser la fila con más contenido sustantivo, cuando la más antigua
está vacía). Verificado con datos reales, no una advertencia genérica:

| Grupo | Fila más antigua | Su descripción | Alternativa con contenido |
|---|---|---:|---:|
| puertas-contra-incendio.php | id=29 (22-oct) | 0 caracteres | id=116 (197) / id=130 (144) |
| puerta-estandar.php | id=31 (22-oct) | 0 caracteres | id=119/131 (144, idénticas entre sí) |
| puerta-estandar-reforzada.php | id=32 (22-oct) | 0 caracteres | id=120/132 (145, idénticas entre sí) |

## 4. Dos grupos category-only se aceptaron sin una tercera fila corroborante

`operador-para-perfilados-comerciales-sel.php` y `cortinas-ventiladas-685.php` sólo tienen 2
filas cada uno (nunca una fila con categoría doble explícita) — a diferencia de los otros 14,
que sí la tienen. Se incluyeron porque superan el mismo criterio de 7 puntos que los demás, no
por relajar el criterio. Se documenta como riesgo de todas formas: si aparece evidencia nueva
(por ejemplo, acceso al dump histórico completo, o a Search Console) que contradiga que estos
dos productos realmente vivían en dos secciones, esta sería la primera hipótesis a revisar.

## 5. Riesgo de alcance: no confundir "identidad resuelta" con "listo para publicar"

Igual que Q07 (páginas en borrador, nunca publicadas automáticamente) y Q02 (fusión, nunca
publicación), cualquier futura resolución de Q03-GLOBAL debe producir productos en
`post_status=draft`, nunca publicados automáticamente — este análisis no propone ni implica lo
contrario, pero se documenta explícitamente porque es el mismo tipo de salvaguarda que el
proyecto ya exige en cada fase anterior.

## 6. Riesgo no encontrado, buscado explícitamente: fabricar contenido para rellenar un hueco

Se revisó específicamente si algún grupo, para poder clasificarse como category-only o como
variante de nombre, requería inventar o adivinar un dato ausente (nombre, marca, imagen,
descripción). **En ningún caso fue necesario**: donde faltaba un dato (descripción vacía en
id=29/31/32, marca ausente en varios miembros de MOOVI/Modern Steel), la recomendación es
usar el dato que sí existe en otra fila del mismo grupo o dejar el campo vacío/pendiente — nunca
inventar uno nuevo.

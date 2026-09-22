# Recursos ausentes

4 filas. Referencias encontradas en el HTML legacy que no resuelven a ningún archivo físico.
**No se fabrica ningún reemplazo.**

## Los cuatro casos

### 1. `images/dura.jpg`
| | |
|---|---|
| Quién la necesita | `dura-glide-20003000-puerta.php`, línea 33 |
| Coincidencia física | ninguna |
| Confianza | `UNKNOWN` |
| ¿Recuperable desde otra ruta? | no se encontró ninguna variante ni duplicado binario |
| Impacto | imagen rota en una ficha de producto |
| ¿Bloquea la migración? | **No.** Bloquea 1 fila de medio; la página puede migrar sin ella |

### 2. `images/magic.jpg`
| | |
|---|---|
| Quién la necesita | `magic-fuerza-del-operador.php`, línea 34 |
| Coincidencia física | ninguna |
| Confianza | `UNKNOWN` |
| ¿Recuperable? | no |
| Impacto | imagen rota en una ficha de producto |
| ¿Bloquea la migración? | No |

### 3. `fichas/INFRACA-RÁPIDA-APILABLE.pdf` — el único con solución técnica identificada
| | |
|---|---|
| Quién lo necesita | `rapida-apilable.php`, línea 32 |
| Coincidencia física | **sí**: `fichas/INFRACA-RÁPIDA-APILABLE.pdf` |
| Confianza | `STRONG_INFERENCE` |
| Causa | discrepancia de normalización Unicode en la `Á` (NFC frente a NFD). El archivo **existe**; el nombre referenciado y el nombre en disco son visualmente idénticos pero difieren en bytes |
| Impacto | ficha técnica no descargable |
| ¿Bloquea la migración? | No |

Es el único de los cuatro donde el contenido **no se ha perdido**: está en disco y el
problema es de codificación del nombre. Resolverlo requiere autorizar explícitamente la
regularización de la referencia (o del nombre), que la fase anterior dejó pendiente por
prohibición expresa de renombrar automáticamente.

### 4. `fichas/FICHATECNICASELLOSSOLMMERS.pdf`
| | |
|---|---|
| Quién lo necesita | `sellos-nacionales.php`, línea 33 |
| Coincidencia física | ninguna con ese nombre exacto |
| Confianza | `UNKNOWN` |
| Observación | existe `fichas/SELLOSSOLMMER.pdf` en el inventario, con nombre parecido pero **no idéntico**; tratarlo como el mismo documento sería una inferencia por similitud de nombre, no evidencia |
| Impacto | ficha técnica no descargable |
| Nota | pertenece a `sellos-nacionales.php`, la misma página del conflicto de marca del ID 150 (ver [06](06-brand-conflicts.md)) |
| ¿Bloquea la migración? | No |

## Conclusión

| Caso | Contenido perdido | Vía de resolución |
|---|---|---|
| `dura.jpg` | sí | recuperar original o aceptar su ausencia |
| `magic.jpg` | sí | recuperar original o aceptar su ausencia |
| `INFRACA-RÁPIDA-APILABLE.pdf` | **no** | autorizar la regularización Unicode de la referencia |
| `FICHATECNICASELLOSSOLMMERS.pdf` | sí (con un candidato de nombre parecido, no confirmado) | confirmar si `SELLOSSOLMMER.pdf` es el mismo documento |

**Ninguno de los cuatro bloquea la migración**: cada uno bloquea exactamente su propia fila.
Su peso en el total es 4 de 932 (0,4 %). Es la decisión de menor impacto de todo el conjunto,
y puede posponerse indefinidamente sin coste.

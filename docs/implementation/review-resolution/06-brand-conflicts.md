# Conflictos de fabricante

Los 11 términos de marca **ya están aprobados y creados** (regla R-T03): crear el término no
atribuye ningún producto. Lo que sigue abierto es **qué marca lleva cada producto en disputa**.

Estado actual: `brand` tiene **0 filas en REVIEW**. Los conflictos afectan a filas de
`product`, y hoy sólo **1** está bloqueada *primariamente* por este motivo (ID 150); las otras
13 están bloqueadas antes por su grupo canónico (Q02/Q03) y heredarán el conflicto de marca
después.

## Conflictos y filas afectadas

| Conflicto | Productos | En REVIEW hoy | `brand_id` guardado | Confianza | Bloqueo primario actual |
|---|---|---|---|---|---|
| Modern Steel | 6, 16, 20, 107 | los 4 | 1, 2, 3, (vacío) | WEAK | Q03 (grupo `coleccion-modern-steel.php`) |
| LiftMaster / Blue Giant | 43, 81 | los 2 | 11, (vacío) | WEAK | Q03 |
| MOOVI | 44, 123, 125, 135 | los 4 | 12, (vacío)×3 | WEAK | Q03 |
| Kelley (ID 139) | 139 | 1 | (vacío) | WEAK | Q03 |
| Thermospan (Wayne/Clopay) | 14, 57 | los 2 | 2, (vacío) | WEAK | Q03 |
| Sellos Nacionales (Dockman/Solmmer) | 150 | 1 | (vacío) | WEAK | **Q09 (este conflicto)** |

**Los 14 productos en disputa tienen `brand_confidence = WEAK_INFERENCE`.** Ninguno tiene una
atribución confirmada: el catálogo SQL no resuelve el conflicto por sí solo.

## Evidencia declarada en `brand-master.csv`

| Marca | Productos en conflicto | Productos con evidencia fuerte |
|---|---|---:|
| 1 Overhead Door | 6, 16, 20, 107 | 36 |
| 2 Wayne Dalton | 6, 14, 16, 20, 57, 107 | 2 |
| 3 Clopay | 6, 14, 16, 20, 57, 107 | 3 |
| 4 Blue Giant | 43, 44, 81, 123, 125, 135 | 4 |
| 5 Kelley | 139 | 2 |
| 10 Dockman | 150 | 10 |
| 11 LiftMaster | 43, 81 | **0** |
| 12 BFT | 44, 123, 125, 135 | 9 |

## Los seis conflictos, en detalle

### Modern Steel — IDs 6, 16, 20, 107
- **Contenido afectado:** la colección Modern Steel (`coleccion-modern-steel.php`), 4 filas SQL + 5 medios.
- **Evidencia SQL:** los cuatro registros guardan marcas distintas entre sí (1, 2, 3 y vacío).
- **Evidencia PHP:** la misma ficha aparece enlazada desde las páginas de **tres** marcas
  (`overhead-door.php`, `wayne-dalton.php`, `clopay.php`).
- **Evidencia de logo:** las tres marcas tienen logo propio y página propia; ninguna es
  descartable por falta de evidencia.
- **Certeza:** baja. Tres fabricantes reivindican visualmente el mismo producto.
- **Alternativas:** (a) atribuir a un fabricante autorizado por el negocio; (b) migrar sin
  marca y decidir después; (c) permitir varias marcas explicadas.
- **REVIEW afectados:** 9 (4 producto + 5 medios), ya contabilizados en Q03.

### LiftMaster / Blue Giant — IDs 43, 81
- **Evidencia PHP:** el título de la ficha dice Blue Giant, pero el enlace la sitúa en LiftMaster.
- **Evidencia de logo:** LiftMaster tiene página y logo, pero **cero** productos con evidencia fuerte.
- **Certeza:** baja. Es el caso donde la marca podría no tener catálogo real en el sitio.
- **REVIEW afectados:** contabilizados en Q03.

### MOOVI — IDs 44, 123, 125, 135
- **Evidencia:** el enlace lleva a Blue Giant; el texto y la ficha apuntan a BFT (fabricante de
  barreras MOOVI). BFT tiene 9 productos con evidencia fuerte; Blue Giant, 4.
- **Certeza:** media a favor de BFT por coherencia de catálogo, pero **no demostrada** por el sitio.
- **REVIEW afectados:** contabilizados en Q03.

### Kelley — ID 139
- **Evidencia:** nombre mixto; el registro aparece en el grupo de rampas Kelley pero sin marca guardada.
- **Certeza:** baja.

### Thermospan — IDs 14, 57
- **Evidencia:** texto que mezcla Wayne Dalton y Clopay en la misma ficha. Ambas marcas
  reivindican los mismos IDs (6, 14, 16, 20, 57, 107).
- **Certeza:** baja.

### Sellos Nacionales — ID 150
- **Evidencia PHP:** título Dockman; texto y PDF apuntan a Solmmer.
- **Nota importante:** **Solmmer no existe como marca** en `brand-master.csv` (sólo hay 12
  marcas y ninguna es Solmmer). Decidir "es Solmmer" implicaría **crear una marca nueva**, no
  sólo reasignar.
- **Certeza:** baja. Es el único de los seis cuyo bloqueo primario es el conflicto de marca.
- **REVIEW afectados:** 1 (Q09).

## Lo que el código no puede determinar

Ninguna de estas atribuciones se deduce del repositorio. La coincidencia de enlaces mide
cómo estaba construida la navegación, no quién fabrica el producto. Resolverlo requiere el
catálogo de fabricante o la confirmación comercial del cliente.

**No se asigna ningún fabricante en esta fase.** La opción de migrar sin marca sigue
disponible y ya está implementada: R-P01 migra productos con la marca deliberadamente vacía
cuando la confianza no es `CONFIRMED`.

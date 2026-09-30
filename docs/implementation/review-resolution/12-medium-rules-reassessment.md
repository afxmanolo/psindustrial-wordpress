# Reevaluación de las reglas MEDIUM

Revisión de cada regla clasificada MEDIUM en
[09-proposed-rules.md](../review-analysis/09-proposed-rules.md), a la luz de todo lo aprendido
al implementar las LOW y auditar los PDF. **No se implementa ninguna.**

| Regla | Filas hoy | Clasificación anterior | Nueva clasificación | Motivo del cambio |
|---|---:|---|---|---|
| R-M01b / R-M04b / R-M05b (propietario pendiente) | 481 | MEDIUM | **Propagación automática** (no es una decisión) | Se resuelven solas al decidir su propietario. No requieren juicio propio |
| R-G01 (página reexpresa otra entidad) | 164 (+250 medios) | MEDIUM | **Decisión humana (Q01)** | Descubierto que arrastra 250 medios sin dueño alternativo; ya no es una regla aplicable en aislamiento |
| R-M07 (medios sin referencia) | 89 | MEDIUM | **MEDIUM, con alcance reducido a 66** | 23 de las 89 tienen su contenido en otra ruta: no hay pérdida de contenido en juego, sólo de URL |
| R-M06 (tipo/límite/hash) | 41 | MEDIUM (6 filas) | **Parcialmente LOW (11) + decisión (30)** | El triaje estructural cambió el cuadro por completo, ver abajo |
| R-S01 (fichas estáticas) | 31 | MEDIUM | **Decisión humana (Q04), acoplada a Q01** | Confirmado que las 31 son contenido adicional real, no duplicados de un producto SQL |
| R-P03 (vacíos y prueba) | 15 (+15 medios) | MEDIUM | **MEDIUM sin cambios (Q06)** | Ninguna evidencia nueva |
| R-G02 landings SEO | 9 (+17 medios) | MEDIUM | **MEDIUM sin cambios (Q07)** | Ninguna evidencia nueva |
| R-G03 (utilitarias) | 3 | MEDIUM | **MEDIUM sin cambios (Q12)** | Ninguna evidencia nueva |

## R-M06: el cambio más importante

La regla cubría "PDFs rechazados por tipo/límite/hash" como un bloque opaco de 41 filas. El
triaje estructural (mismo método seguro de la auditoría aprobada: `pikepdf`, lectura de
estructura, sin ejecutar nada) las separa en perfiles claros:

| Perfil | Archivos | Qué son |
|---|---:|---|
| **A** — gemelo binario de un PDF **ya saneado** (Grupo A) | **9** | mismo SHA-256 que un archivo cuya copia saneada ya existe y está verificada |
| **B** — gemelo binario de un PDF **ya aprobado** (Grupo B) | **2** | mismo SHA-256 que un falso positivo ya auditado y aprobado |
| **C** — sólo `/EmbeddedFile` pasivo, sin acción activa | 1 | perfil idéntico al del Grupo A ya aprobado (`puerta-thermacore-595.pdf`) |
| **D** — `/OpenAction` que es un **destino de vista** | 1 | "abrir en esta página/zoom"; no es una acción ejecutable |
| **E** — acción `/GoTo` declarada | 8 | navegación **dentro del propio documento**; sin JavaScript, sin Launch, sin adjuntos |
| **F** — sin ninguna estructura activa | 11 | falso positivo de bytes, mismo perfil que el Grupo B aprobado |
| **total** | **32** | (las otras 9 filas de R-M06 no son PDF: 3 vídeos externos, 3 SVG/PNG, 3 JPEG) |

**Ninguno de los 32 contiene JavaScript real, acciones `/Launch`, formularios, contenido
auto-ejecutable ni adjuntos ejecutables.** El caso más "activo" es `/GoTo`, que sólo salta a
una página del mismo documento.

### Consecuencia inmediata: 11 filas son degradables a LOW

Los perfiles A (9) y B (2) son **byte-idénticos** a archivos que ya pasaron por la auditoría
completa y tienen aprobación humana vigente. Extender la aprobación a ellos no requiere
ningún juicio nuevo: es la misma decisión ya tomada, aplicada por hash en vez de por ruta.
Para los 9 del perfil A, **la copia saneada ya existe y ya está verificada**; no hay siquiera
que generar nada.

Ejemplos: `fichas/puerta-424.pdf` es el gemelo con nombre humano de
`system/files/images/productos/639b9801…`, que se saneó en la fase anterior.

Esto convierte lo que parecía "32 archivos que auditar" en **"1 extensión trivial (11) + 1
decisión de política sobre 21 archivos con perfil ya conocido"**.

## Reglas que dejan de ser reglas

Dos entradas de la tabla ya no deberían tratarse como "reglas pendientes de implementar":

- **R-M01b/04b/05b (481 filas)** nunca fueron una decisión: son el mecanismo de propagación
  de dependencias del `Planner` funcionando correctamente. Se resolverán sin intervención en
  cuanto se decidan Q01, Q02 y Q03. Presentarlas como "una regla MEDIUM pendiente" infla
  artificialmente el trabajo restante en 481 filas.
- **R-G01 (164 filas)** dejó de ser candidata a automatización: su aplicación cambia el
  destino de 250 medios adicionales que no tienen otro propietario documentado. Debe
  responderse como decisión, no aplicarse como regla.

## Ninguna regla sube a HIGH

Ninguna de las MEDIUM revisadas mostró evidencia nueva que aumente su riesgo. Las HIGH
existentes (duplicados sin ganador determinista, categorías 25/26/33, recursos ausentes,
conflicto de fabricante) siguen exactamente igual — salvo los 26 grupos de identidad
equivalente, que **bajaron** de HIGH a decisión de política única gracias a la evidencia de
[03-identity-groups.md](03-identity-groups.md).

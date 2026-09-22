# Interfaz de administración

Ruta sin cambios: Herramientas → **Migración PS Industrial** (`tools.php?page=psi-migration`).
Mismo guard de capacidad (`manage_options` + `psi_manage_migration`), mismo nonce por POST.

## Lo que ya existía, sin tocar

El bloque de ejecución `subset` (formulario con la frase `IMPORTAR SUBSET EN BORRADOR`) sigue
exactamente igual, en el mismo sitio, con el mismo texto. El selector de "Alcance del
análisis" (FULL DRY RUN / Subset de ensayo) para **generar** un plan tampoco cambió — sigue
siendo puro DRY RUN, nunca ejecuta nada por sí mismo.

## Lo nuevo: el panel FULL LOCAL RESOLVED-ONLY

Aparece **sólo** cuando el plan cargado tiene `scope==='full'` y no está `COMPLETE` — nunca
se mezcla con el bloque subset, nunca se muestra para un plan de otro scope.

1. **Título explícito**: "FULL LOCAL RESOLVED-ONLY", con la frase literal "Esto NO es un DRY
   RUN" antes de cualquier otra cosa.
2. **"Los elementos REVIEW NO serán importados."** — en negrita, tal como pidió la tarea,
   seguido de una frase aclarando que permanecen exactamente como están, pendientes de
   decisión humana.
3. **Tabla de desglose por acción de decisión**: MIGRATE / MERGE / CREATE_FROM_STATIC / SKIP
   / REVIEW (marcado "preservado, no se importa"), con el conteo real de
   `preflight_full_local()`.
4. **Resultado previsto por objeto**: el propio `plan['summary']['actions']` (CREATE/UPDATE/
   UNCHANGED/CONFLICT, lo que exista de cada uno contra el estado real de la base), para que
   quien vaya a confirmar vea también qué le pasará a cada fila concretamente, no sólo el
   desglose por acción de decisión.
5. **Pre-flight completo, visible antes del formulario de confirmación**: las 14
   verificaciones con su estado (✅/❌) y detalle. Si `ok !== true`, el aviso de error se
   muestra y **el formulario de confirmación ni siquiera se renderiza** — no es sólo que el
   backend lo rechazaría si se enviara, la propia interfaz no ofrece la posibilidad.
6. **Aviso de alcance**: "Sólo entorno local... NO está disponible en staging/producción bajo
   ninguna circunstancia, incluso con la frase de confirmación correcta."
7. **Formulario de confirmación**, sólo si el pre-flight pasó: campo de texto obligatorio
   (`required`, sin autocompletar), frase exacta `IMPORTAR FULL LOCAL RESUELTO`, aviso de que
   el backup se toma automáticamente antes del primer lote.

## Ruta HTTP

Nueva operación `operation=execute_full_local` en `admin-post.php` (distinta de `execute`,
que sigue siendo subset-únicamente) → `Runner::batch_full_local_resolved_only()`. Mismo
patrón de nonce/POST/redirect que ya usa `execute`; ningún camino nuevo de autenticación.

## Qué no se añadió

Ningún botón de un solo clic. Ningún JavaScript/AJAX propio. Ninguna forma de saltarse el
pre-flight desde la interfaz. Ningún indicio visual de que esto podría usarse en
staging/producción.

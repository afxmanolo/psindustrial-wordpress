# Límites y decisiones pendientes

1. **Aprobaciones comerciales**: maestros SHOULD/MUST y los77 grupos son evidencia, no aprobación. Salvo ensayo privado, quedan REVIEW;151–164 y165 también. No se convirtió su clasificación en SKIP ni publicación.
2. **MERGE**: implementada identidad múltiple con ganador único explícito para todos los campos y relaciones resueltas. Fusiones de ganadores mixtos se bloquean como REVIEW; necesitan payload editorial validado. Se probó MERGE sintético, no se fusionó catálogo legacy.
3. **Conflictos**: bloqueo conservador del objeto entero; aún no hay interfaz para aceptar diferencias campo por campo. No sobrescribir por force ni borrar ledger para reintentar sin investigar.
4. **Extracción**: cuerpo estático de párrafos/listas/tablas; no ejecuta PHP ni resuelve includes dinámicos, no construye colecciones y conserva enlaces pendientes de remapeo. Toda salida sigue en draft/review. SEO se conserva como evidencia privada, sin Yoast/adaptador/rutas.
5. **Medios**: no se importó el inventario entero. Duplicación binaria compartida sólo cuando la decisión explicita propósito/alias; otros duplicados REVIEW. PDF signature/patrones no sustituyen antimalware. Derivados y URLs históricas requieren decisiones posteriores.
6. **Ausencias**: las4 referencias faltantes/Unicode siguen REVIEW; no se renombró el archivo INFRACA ni se eligió sustituto.
7. **Hosting compartido**: runner/panel son lotes HTTP sin SSH, probados localmente. El gate de esta fase bloquea cualquier DB/entorno remoto. El preparador local necesita árbol de fuentes; los paquetes normalizados/binarios son privados. El canal FTP privado y la validación de paquete ya preparado sin el árbol local deberán habilitarse/probarse en la fase de despliegue, sin subir dumps/PHP al webroot.
8. **Límites de lotes**:5s es presupuesto entre objetos; WordPress puede tardar más en una imagen. No hay pruebas de carga en Hepsia ni garantía de timeout; reducir tamaño/medios si el host lo exige.
9. **Recuperación**: creaciones intactas admiten rollback controlado; modificaciones requieren snapshot y revisión. Una interrupción antes de registrar ID bloquea reinserción y exige inspección humana. No hay transacción filesystem+DB ni restauración completa automática certificada.
10. **Retención**: mantener journals/manifest de corridas aceptadas para auditoría; revisar logs privados a30d y checkpoints antiguos antes de despliegue. No hay borrado automático de backups/logs, para no perder evidencia de recuperación durante estos ensayos.
11. **DB existente**: se conservaron datos del usuario y subset. Las cuentas/fixtures sintéticas se retiraron; un fatal del guard previo fue corregido y documentado. No se borró su historial de log.
12. **Git recibido**:29 cambios previos ya staged sobre develop; ahora feature/legacy-importer sin commit/push. Revisar tanto índice heredado como nuevos cambios. No se resolvió ese estado haciendo commit ni reset.

Los12 asuntos de manual-decisions-required.md siguen sin respuesta automática: vigencia del dump, identidad/fusión, fabricantes, atribuciones, vacíos/prueba, jerarquía, modelo/PDF, ausencias, tráfico, hosting, contactos/accesos y necesidad de exportación. El ensayo no los resuelve.

El siguiente paso requiere revisar FULL DRY RUN y decisiones editoriales. No se habilitó ni ejecutó una importación completa real.

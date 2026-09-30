# Retención conservadora

Estas categorías expresan destino probable del **contenido**, no permiso de borrado. Todos los archivos y filas se mantienen intactos. MUST_MIGRATE no obliga a portar su implementación; sí a conservar la información o función necesaria.

| Conjunto | Clasificación | Motivo y condición |
|---|---|---|
| Texto, metadata, enlaces y URLs de las 179 páginas de contenido | MUST_MIGRATE | Incluye variantes y landings; no suprimir por duplicación aparente |
| 150 registros con nombre/descripción de catálogo | SHOULD_MIGRATE | Reconciliar duplicados y conflictos; preservar IDs y procedencia antes de fusionar |
| IDs151–164 vacíos | REVIEW | 14 filas de categoría25; 13 tienen imagen y 155 no tiene medios; confirmar borradores frente a residuos |
| ID165 Prueba | REVIEW | Nombre y descripción genéricos con medios; no decidir automáticamente que es prescindible |
| 32 fichas/familias estáticas sin correspondencia SQL fuerte | MUST_MIGRATE | Importar sólo SQL perdería contenido; no equivale a 32 entidades únicas verificadas |
| 38 categorías y 12 marcas | SHOULD_MIGRATE | Preservar relación explícita, etiquetas y navegación; revisión especial25/26/33 y fabricantes contradictorios |
| Logos públicos y tres videos incrustados | MUST_MIGRATE | SQL no representa esos datos de forma suficiente |
| PDF/imágenes con relación SQL o referencia pública | SHOULD_MIGRATE | Continuidad de contenido y descarga; depurar propietario/orden sin eliminar alias por hash |
| Originales sin extensión | SHOULD_MIGRATE cuando relacionados; resto REVIEW | Firma identifica 259 binarios; ausencia de extensión no significa archivo temporal |
| Derivados small/medium/large | REVIEW | Pueden ser reconstruibles, pero sus rutas pueden estar enlazadas; no descartar sólo por versión |
| Medios sin referencia detectada | REVIEW | No demuestra orfandad; faltan enlaces externos y carga dinámica |
| Registros file duplicados/históricos | REVIEW | 1.355 IDs →820 rutas; preservar puente ID/ruta mientras se deciden objetos de destino |
| user (una cuenta) y hashes/credenciales | PROBABLY_NOT_REQUIRED | Recrear acceso de responsables autorizados; no importar contraseñas ni datos personales innecesarios |
| permission (sin filas) | PROBABLY_NOT_REQUIRED | Sin datos que migrar y estrategia desactivada; reemplazar necesidad de control de acceso |
| PHP interno, DAO/SDO/Savant, bibliotecas del CMS | PROBABLY_NOT_REQUIRED | Son implementación, no contenido; conservar documentación de comportamientos y URLs relevantes |
| Backoffice y sus recursos visuales propios | PROBABLY_NOT_REQUIRED | No son identidad visual pública; no confundir con recursos compartidos |
| Envío SMTP legacy y credenciales | PROBABLY_NOT_REQUIRED | Conservar finalidad/formulario; gestionar configuración nueva sin trasladar secretos |
| Logs, temporales o cachés candidatos del inventario previo | REVIEW | Un nombre de archivo no prueba que sea temporal; no hay política automática por extensión |
| Dumps SQL | PROBABLY_NOT_REQUIRED como contenido WordPress | Mantener copia privada de referencia; nunca publicarlos ni incorporarlos a una entrega pública |

El maestro de medios aplica deliberadamente REVIEW a recursos no referenciados y variantes. Su conteo de uso es documental, no una certificación de necesidad individual. El maestro de productos aplica REVIEW a grupos duplicados y conflictos aunque su contenido deba conservarse. Son dimensiones distintas y no se deben usar como una orden masiva de exclusión.

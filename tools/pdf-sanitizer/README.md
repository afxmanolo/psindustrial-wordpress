# Saneador de PDF — herramienta local de migración

**Esto es una herramienta de preprocesamiento local para migrar contenido. No es parte
del sitio WordPress, no se despliega a staging/producción, y el plugin
`psindustrial-core` nunca la invoca ni depende de ella en tiempo de ejecución.**

## Qué hace

Implementa la decisión humana **PDF-A** (aprobada en la fase `pdf-security-review`):
para los 5 PDF del Grupo A, genera una copia con el objeto `/EmbeddedFile` (un fichero
`.joboptions` de Adobe Distiller, confirmado benigno) retirado, dejando el original bajo
`/legacy` absolutamente intacto.

Lee `docs/implementation/pdf-security-review/pdf-approvals.json` (la autorización humana,
gateada por SHA-256 exacto) y para cada entrada de `group_a_sanitization`:

1. verifica que el archivo actual bajo `legacy/public/<legacy_path>` tiene exactamente el
   `source_sha256` aprobado — si no coincide, no procesa esa entrada;
2. abre el PDF **en modo lectura**;
3. retira el `Filespec`/`EmbeddedFile` alcanzable desde `/Names/EmbeddedFiles`;
4. guarda el resultado en un directorio privado **fuera de `/legacy` y fuera del
   repositorio Git** (el mismo directorio privado que ya usa el importador PHP:
   `psindustrial-importer-private/pdf-sanitized/`, nombrado por el SHA-256 del archivo
   fuente);
5. vuelve a calcular el hash del archivo original y confirma que sigue siendo idéntico
   (prueba de que nunca se sobrescribió);
6. corre la batería de validación post-sanitización (páginas preservadas, sin
   `/EmbeddedFile`, sin `/OpenAction`/`/AA`/`/AcroForm`/`/Names/JavaScript`, pasa la misma
   expresión regular que usa el importador, texto extraído equivalente);
7. escribe `docs/implementation/pdf-security-review/sanitization-audit.json` — el registro
   auditable que el lado PHP (`PdfApprovals`) consume.

**Si cualquier validación falla, esa entrada queda `KEEP_REVIEW` en el registro y el
importador nunca la usa.** No hay ninguna ruta que relaje una comprobación para forzar un
resultado.

## Requisitos (sólo para quien ejecute esta herramienta)

- Python 3.10+
- `pip install -r tools/pdf-sanitizer/requirements.txt` (instala `pikepdf`)
- Opcional: `pdftotext` (parte de poppler) en el `PATH`, para la comprobación de texto
  equivalente. Si no está disponible, esa comprobación específica se omite (se registra
  como `null`, nunca como aprobada por omisión) y las demás comprobaciones siguen
  aplicando con normalidad.

Ninguno de estos requisitos se añade a `composer.json`, a los requisitos de WordPress, ni
a ningún flujo de despliegue. Son exclusivamente para quien ejecute este script en su
máquina de desarrollo.

## Cómo ejecutarlo

Desde la raíz del repositorio:

```bash
pip install -r tools/pdf-sanitizer/requirements.txt
python tools/pdf-sanitizer/sanitize.py --dry-run   # sólo valida y reporta, no escribe nada
python tools/pdf-sanitizer/sanitize.py             # genera las copias + el registro auditable
```

## Dónde queda la salida

- Copias sanitizadas: `<raíz-privada>/pdf-sanitized/<source_sha256>.pdf` — fuera de
  `/legacy`, fuera del repositorio Git. No se comprometen a Git.
- Registro auditable: `docs/implementation/pdf-security-review/sanitization-audit.json`
  — sí se versiona (es sólo metadata: hashes, resultado de validación, timestamps), sin
  contenido binario.

## Reproducibilidad

Volver a ejecutar el script sobre los mismos 5 archivos originales produce exactamente el
mismo resultado (mismo SHA-256 de salida) porque la operación es determinista: sólo retira
un objeto identificado por su posición estructural, sin aleatoriedad ni marcas de tiempo
dentro del propio PDF.

Si algún PDF original bajo `/legacy` cambiara de bytes (por ejemplo, alguien lo
reemplazara), el hash actual dejaría de coincidir con `source_sha256` en
`pdf-approvals.json`, el script se negaría a procesarlo, y el lado PHP
(`PdfApprovals::resolve()`) tampoco aplicaría la aprobación — el archivo volvería a
`REVIEW` automáticamente, sin necesidad de ningún paso manual adicional.

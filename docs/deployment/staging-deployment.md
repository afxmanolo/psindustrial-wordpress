# Despliegue a staging — procedimiento

Hosting compartido (Hepsia), FTP, **sin SSH garantizado**. Este documento es el
procedimiento; no se ha ejecutado ningún paso contra staging todavía.

El despliegue de código (theme + plugin) está automatizado vía GitHub Actions — ver
[`ci-cd-pipeline.md`](ci-cd-pipeline.md). Este documento cubre lo que el pipeline
deliberadamente NO automatiza: base de datos, uploads, permalinks, HTTPS, noindex, mail,
cache, rollback.

## 0. Qué se despliega

- `wp-content/themes/psindustrial` (sin `node_modules`/caches — no existen, no hay build step)
- `wp-content/plugins/psindustrial-core` **excluyendo `tests/`** (27 archivos de test nunca se suben)
- Base de datos local aprobada (export → import)
- `wp-content/uploads/` ya existentes (copia directa, ver §4)
- WordPress core: instalación estándar en staging (vía Hepsia/instalador), **no se empaqueta
  desde local** — no está versionado en este repo (`.gitignore`)

**Nunca se sube:** `/legacy`, `wp-config.php` local, dumps `.sql`, backups, logs, archivos
privados del importador (fuera del repo, en `psindustrial-importer-private`), `.git`,
`tests/` del plugin. Ver `tools/staging-package/build.php` — construye el paquete
directamente desde `git show`, nunca desde el working tree, así que nunca puede incluir
nada no versionado.

## 1. Backup de staging (antes de escribir nada)

Si staging ya tiene contenido previo: backup completo (DB + `wp-content`) vía el panel
Hepsia antes de sobrescribir. Sin esto no hay rollback posible (§10).

## 2. Archivos — subida FTP

1. Generar el paquete: `php tools/staging-package/build.php develop` → `dist/staging/*.zip` + `manifest.json`.
2. Verificar el SHA-256 del zip contra `manifest.json` antes de subir (íntegridad de transferencia).
3. Subir por FTP y extraer **sobre** `wp-content/` de staging (el zip ya tiene esa
   estructura: `themes/psindustrial/...`, `plugins/psindustrial-core/...`).
4. Activar el plugin y el theme desde wp-admin si no quedan activos automáticamente.

## 3. Base de datos

**Verificado en local (2026-09-21):** ningún `post_content`, `postmeta`, `termmeta` ni
`usermeta` contiene la URL local en ninguna forma, serializada o no — sólo dos opciones
simples (`siteurl`, `home`) la contienen, como cadenas planas, nunca dentro de una
estructura serializada. Esto simplifica el procedimiento real; aun así se documenta la
ruta completa por si contenido futuro llega a incluir URLs absolutas.

1. **Export local:** `mysqldump psindustrial_wp_dev` (vía phpMyAdmin/panel local si no hay
   acceso a shell — Hepsia normalmente ofrece phpMyAdmin en el panel).
2. **Import a staging:** vía phpMyAdmin de Hepsia (import de archivo `.sql`) o la
   herramienta de import del panel. Nunca ejecutar el dump como SQL "a mano" fila por fila.
3. **Cambiar `siteurl`/`home`:** wp-admin → **Ajustes → Generales** → escribir la URL de
   staging en ambos campos → Guardar. Seguro por diseño: ambos son cadenas simples, jamás
   serializadas; WordPress no requiere nada adicional para estos dos campos.
4. **Si en el futuro aparece contenido con URLs absolutas hardcodeadas** (post_content,
   ACF-like meta, widgets serializados): **nunca** UPDATE/REPLACE SQL bruto sobre columnas
   serializadas — rompe el prefijo de longitud de cada `s:N:"..."` y corrompe el dato.
   - **Si WP-CLI está disponible** (confirmar con Hepsia; no asumido): `wp search-replace
     'http://localhost/psindustrial-wordpress/wordpress' 'https://<dominio-staging>'
     --all-tables --precise --dry-run` primero, sin `--dry-run` después. Es la herramienta
     estándar, ya sabe recorrer datos serializados sin romperlos.
   - **Sin WP-CLI:** usar una herramienta de reemplazo consciente de serialización vía
     wp-admin (p. ej. Better Search Replace) — nunca phpMyAdmin "Find & Replace" (opera a
     nivel de texto plano, no entiende `a:N:{...}`). No se instaló ninguna herramienta de
     este tipo en esta fase; queda como paso a decidir si el paso 4 llega a ser necesario.
5. **Verificación post-import:** repetir la consulta de §0 (LIKE '%localhost%' en
   post_content/postmeta/termmeta/usermeta) contra staging — debe seguir dando 0 filas
   fuera de siteurl/home antes de continuar.

## 4. Uploads

Copiar `wp-content/uploads/YYYY/MM/...` completo desde local a staging por FTP,
preservando la estructura exacta de carpetas. La base de datos ya importada (§3) referencia
estas mismas rutas por `_wp_attached_file`/GUID — **no reimportar vía WordPress**, ya que
eso generaría IDs de adjunto nuevos y rompería la asociación existente con productos/páginas.

## 5. Permalinks (sin CLI)

Tras importar la DB: wp-admin → **Ajustes → Enlaces permanentes** → sin cambiar nada →
**Guardar cambios**. Este único guardado regenera `rewrite_rules` para el dominio/ruta de
staging; es la vía sin SSH, no requiere WP-CLI.

## 6. HTTPS / dominio

Antes del QA final: activar HTTPS en staging (Hepsia suele ofrecer certificado gratuito
vía el panel), confirmar que `siteurl`/`home` (§3) ya usan `https://`, revisar que no haya
mixed content (assets del theme usan rutas relativas vía `wp_head`/`enqueue`, no URLs
absolutas hardcodeadas — confirmado en el código). Configurar redirect HTTP→HTTPS a nivel
de panel/hosting, no en código de este repo (evita reglas de dominio duro fuera de lugar).

## 7. noindex / protección de staging

- `blog_public = 0` debe permanecer así en staging (mismo valor que local) hasta
  producción — WordPress emite `noindex, nofollow` automáticamente.
- **No depender sólo de `robots.txt`** (no es una barrera real, sólo una petición a
  crawlers educados). Añadir protección de acceso si Hepsia lo permite: HTTP Basic Auth
  vía el panel (`.htpasswd`) o restricción por IP — confirmar disponibilidad exacta con
  Hepsia antes de asumir cuál aplica.

## 8. Mail

`Contact.php` ya bloquea el envío salvo `wp_get_environment_type() === 'production'`
exacto (ver código, sin cambios necesarios). En el `wp-config.php` de staging:

```php
define( 'WP_ENVIRONMENT_TYPE', 'staging' );
```

Con esto el formulario es probable de extremo a extremo (validación, nonce, honeypot,
límite de intentos) pero `deliver()` siempre devuelve "envío no disponible en este
entorno" — cero correos accidentales, sin tocar código.

## 9. Cache

Durante el QA de staging: cache de plugin/servidor mínima o desactivada si el panel lo
permite (para ver cada cambio inmediatamente). Tras cada nuevo deployment de archivos:
limpiar cualquier cache de objeto/página del hosting y, si se usa, purgar Cloudflare antes
de volver a probar (Cloudflare no se evalúa todavía — ver checklist de producción futura).

## 10. Rollback

Sencillo, sin infraestructura nueva:

1. Restaurar el backup de DB de staging tomado en §1.
2. Restaurar el backup de archivos (`wp-content/themes/psindustrial`,
   `wp-content/plugins/psindustrial-core`) tomado en §1.
3. Si el paquete nuevo ya se extrajo: sobrescribir con el `dist/staging/*.zip` anterior
   (los zips quedan nombrados por commit corto, nunca se sobrescriben entre builds).

## 11. Importador

Permanece incluido, sin cambios: `Storage::guard()` exige
`wp_get_environment_type() === 'local'` — bloquea el panel de migración fuera de local sin
necesidad de ocultar su UI ni tocar código ya probado.

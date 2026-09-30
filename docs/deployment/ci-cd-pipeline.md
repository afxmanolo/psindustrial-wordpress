# Pipeline CI/CD — GitHub Actions

`.github/workflows/ci-cd.yml`. `develop` → entorno **Staging**, `main` → entorno
**production**. Sólo despliega código (`wp-content/themes/psindustrial`,
`wp-content/plugins/psindustrial-core` sin `tests/`) — nunca la base de datos ni
`uploads/`; eso sigue el procedimiento manual de `staging-deployment.md`, conforme a la
regla del proyecto de no sincronizar la base de datos automáticamente.

## Qué corre en cada push/PR (`lint` + `build`)

- **`lint`**: `php -l` sobre cada archivo del theme y del plugin (incluye `tests/` — el
  código de test también debe ser válido, aunque no se despliegue).
- **`build`**: ejecuta `tools/staging-package/build.php` contra el commit exacto probado y
  sube el ZIP + `manifest.json` como artifact del workflow (14 días de retención).

**La suite completa de 27 archivos NO corre aquí.** Está atada por diseño a la base de
datos local `psindustrial_wp_dev` (`Storage::guard()` exige
`wp_get_environment_type()==='local'` y ese nombre de base exacto) y a su estado
acumulado a lo largo de todo el proyecto — no es reproducible en un runner efímero sin
falsear esa base. Sigue siendo la puerta obligatoria **local**, antes de cada push, tal
como se ha hecho durante todo el proyecto.

## Qué corre sólo al hacer push a develop/main (`deploy-staging` / `deploy-production`)

Cada uno depende de `build` y usa un [Environment de
GitHub](https://docs.github.com/en/actions/deployment/targeting-different-environments)
configurado en el propio repositorio (no en este archivo):

| Environment | Rama permitida | Revisor requerido |
|---|---|---|
| `Staging` | sólo `develop` | afxmanolo |
| `production` | sólo `main` | afxmanolo |

Esto significa que **ningún deploy se ejecuta automáticamente**: cada ejecución queda en
"Waiting" en la pestaña Actions hasta que se aprueba manualmente ahí — configurado a nivel
de repositorio (Settings → Environments), no algo que este workflow pueda saltarse por sí
mismo. Aprobar equivale a confirmar "27/27 verificado en local", igual que en las fases
anteriores de este proyecto.

## Secrets requeridos (Settings → Secrets and variables → Actions)

Ninguna credencial vive en este repositorio. Configurar exactamente estos nombres, por
entorno, antes de que un deploy real pueda completarse:

| Secret | Descripción |
|---|---|
| `STAGING_FTP_SERVER` | Host FTP/FTPS de staging (Hepsia) |
| `STAGING_FTP_USERNAME` | Usuario FTP de staging |
| `STAGING_FTP_PASSWORD` | Password FTP de staging |
| `STAGING_FTP_SERVER_DIR` | Ruta remota de `wp-content/` en staging, con `/` final (p. ej. `/public_html/wp-content/` — confirmar la ruta real con Hepsia) |
| `PRODUCTION_FTP_SERVER` | Host FTP/FTPS de producción |
| `PRODUCTION_FTP_USERNAME` | Usuario FTP de producción |
| `PRODUCTION_FTP_PASSWORD` | Password FTP de producción |
| `PRODUCTION_FTP_SERVER_DIR` | Ruta remota de `wp-content/` en producción, con `/` final |

Sin estos secrets configurados, el job de deploy correspondiente fallará al conectar —
nunca falla "en silencio" ni despliega con valores vacíos.

## Seguridad de la acción de despliegue

`SamKirkland/FTP-Deploy-Action` fijada por commit SHA exacto (no por tag flotante) por
cada versión usada; `protocol: ftps`, nunca FTP plano; `dangerous-clean-slate: false`
explícito en ambos jobs — el modo "espejo" de esa acción borraría en el remoto cualquier
archivo ausente en local, lo que destruiría `uploads/` u otros plugins/temas si algún día
compartieran el mismo `wp-content/`. Con `local-dir` limitado a
`themes/psindustrial/`+`plugins/psindustrial-core/`, un despliegue nunca puede tocar nada
fuera de esas dos carpetas.

## Cómo probar el pipeline sin desplegar nada real

`lint`+`build` corren en cualquier Pull Request hacia `develop`/`main` — sirve para
validar el paquete sin acercarse a ningún entorno real ni a ningún secret.

## Qué NO hace este pipeline

No migra la base de datos. No sincroniza `uploads/`. No hace flush de permalinks. No
gestiona HTTPS/DNS. No instala WordPress core. Todo eso permanece en
`staging-deployment.md`, deliberadamente manual y fuera del alcance de un push automático.

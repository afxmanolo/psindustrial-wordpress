# Flujo de desarrollo
Rama de trabajo: feature/wp-bootstrap. No se realizó commit ni push. Main/develop no cambiaron.

## Lo versionable
Únicamente código propio bajo los dos directorios permitidos del theme/plugin y documentación de la fase. Las reglas existentes de .gitignore ya excluyen core, wp-config.php, uploads, idiomas, logs, scripts privados y dumps; no fue necesario modificarlas.

No se añadieron dependencias PHP/JS, archivos compilados ni secretos. Para revisar archivos nuevos, git diff normal no los muestra: consultar git status --short --untracked-files=all y abrir los archivos de created-files.md. Se revisaron también los archivos no rastreados, no sólo el diff vacío de archivos existentes.

## Verificación local
Desde la raíz, PowerShell:
```powershell
$psiPhp = 'C:\laragon\bin\php\php-8.4.24-nts-Win32-vs17-x64\php.exe'
Get-ChildItem wordpress/wp-content/themes/psindustrial,wordpress/wp-content/plugins/psindustrial-core -Recurse -Filter *.php | ForEach-Object { & $psiPhp -l $_.FullName }
& $psiPhp wordpress/wp-content/plugins/psindustrial-core/tests/smoke.php
node --check wordpress/wp-content/plugins/psindustrial-core/assets/admin-fields.js
node --check wordpress/wp-content/themes/psindustrial/assets/js/navigation.js
git diff --check
git status --short --untracked-files=all
```

Apache/MySQL deben estar disponibles y el plugin/theme activos. La suite se niega a ejecutarse por HTTP y exige environment local + base psindustrial_wp_dev. No ejecutarla contra staging/producción. Genera objetos con prefijo aleatorio y elimina únicamente sus fixtures. No migrar/copiar DB legacy.

La base y uploads son estado local, no se reconstruyen con un simple checkout Git. Para otro entorno instalar core, crear configuración privada y DB propias; activar código propio. No sincronizar la DB local directamente a producción.

WP_DEBUG y SCRIPT_DEBUG corresponden al entorno privado. Revisar el log protegido después de cada prueba; no subirlo a Git. Actualizar core/plugins en staging con backups antes de producción, no desde esta fase.

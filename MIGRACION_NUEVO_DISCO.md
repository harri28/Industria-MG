# Migración de IndustriaMG a nuevo disco M.2

Guía para desconectar el disco actual con todo lo imprescindible respaldado
y reinstalar el entorno local de desarrollo en el disco nuevo.

> **Alcance:** solo entorno **local de desarrollo** en Windows (XAMPP).
> Producción corre en el VPS `sistemmg.com` y no se toca con esta migración.

---

## 1. Estado de la migración (rellenar manualmente)

| Campo                          | Valor                                              |
|--------------------------------|----------------------------------------------------|
| Fecha planificada              |                                                    |
| Fecha ejecutada                |                                                    |
| Disco origen (modelo/serie)    |                                                    |
| Disco destino (modelo/serie)   |                                                    |
| Responsable                    | harri28 (petram.control@gmail.com)                 |
| Equipo                         | Laptop Windows 11 Home Single Language (10.0.26200)|
| Nombre del respaldo (carpeta)  | Ej. `D:\respaldo_industria_mg_YYYYMMDD\`           |

---

## 2. Identificación del proyecto

- **Tipo:** sistema de gestión industrial (ERP-lite), raw PHP sin framework.
- **Lenguaje / runtime:** PHP 8.2.12 (ZTS, VC++ 2019 x64), servido por Apache de XAMPP.
- **Base de datos:** PostgreSQL **17.9** (instalación nativa Windows, no la que trae XAMPP).
- **Frontend:** Vanilla JS + CSS propio. Librerías locales ya versionadas en `assets/vendor/` (jQuery 3.7.1, Select2, DataTables + Bootstrap 5 theme). Font Awesome 6.5 por CDN.
- **Dependencias externas vía CDN:** Font Awesome. No hay `composer.json` ni `package.json`: **no hay `node_modules` ni `vendor/` que recrear**.
- **Servicios integrados:** SUNAT (SOAP e-invoicing) — requiere certificado digital `.pfx`.
- **Sin build step, sin tests automatizados, sin gestor de paquetes.**

---

## 3. Herramientas y versiones a instalar en el disco nuevo

| Herramienta                | Versión actual        | Observaciones                                                   |
|----------------------------|-----------------------|-----------------------------------------------------------------|
| **XAMPP**                  | 8.2.12                | Instalar en `C:\xampp\` (misma ruta — simplifica rutas en httpd.conf). |
| **PHP** (viene con XAMPP)  | 8.2.12 ZTS VC++ 2019  | Verificar que la extensión `pdo_pgsql` esté habilitada en `php.ini`. |
| **PostgreSQL**             | 17.9 (x64)            | Instalación nativa, NO la de XAMPP. Puerto 5432. Mantener contraseña `postgres=1234` o actualizar `config/database.php`. |
| **Git for Windows**        | reciente (≥ 2.40)     | Incluye Git Credential Manager (necesario para HTTPS). Mantener user.name=harri28. |
| **Visual Studio Code**     | última                | No es imprescindible pero es tu editor habitual.                 |
| **VC++ 2019 Redistributable** | x64                | Requerido por PHP de XAMPP (lo pide el instalador si falta).     |
| **Visor PDF**              | cualquiera            | Opcional, para abrir los CDR / PDF generados por SUNAT.          |

### Extensiones VS Code (opcional pero recomendado)
No hay `.vscode/` versionado (gitignored). Instala en el disco nuevo lo que uses: PHP IntelliSense, PostgreSQL explorer, EditorConfig, GitLens.

---

## 4. Estrategia de respaldo

**Decidido: copiar completa la carpeta `C:\xampp\` al disco de respaldo.** Eso cubre
sin tener que seleccionar archivos uno a uno:

- Todo el código del proyecto (`htdocs\Industria_mg\`), incluidos los archivos gitignored:
  - `config/database.php` (credenciales DB locales)
  - `facturacion/certs/20610316884.pfx` ← **crítico** (certificado SUNAT)
  - `facturacion/storage/` (XML/CDR emitidos en beta)
  - `assets/uploads/` (6.5 MB de fotos de activos, diseños, capacitación, etc.)
  - `backups/` (dumps SQL antiguos, 952 KB)
  - `storage/sessions/` (irrelevante, se puede dejar)
  - Los 2 `.docx` untracked en la raíz
- Virtualhosts (`apache\conf\extra\httpd-vhosts.conf`) y cualquier edición manual al `httpd.conf`.
- El propio PHP de XAMPP con sus extensiones habilitadas.

Lo que **NO está en `C:\xampp\` y hay que respaldar aparte**:

| Qué | Ruta | Tamaño | Prioridad | Nota |
|---|---|---|---|---|
| **Dump de la base de datos** | A generar — §5.1 | ~? MB | **CRÍTICA** | PostgreSQL vive en `C:\Program Files\PostgreSQL\17\` con su propia carpeta `data`. Copiar esa carpeta cruda es frágil (permisos, servicio, versión). El dump SQL en texto plano es el camino seguro. |
| **Snapshot de git config global** | Generar — §5.2 | <1 KB | Media | Para replicar `user.name`, `user.email`, `credential.helper`. |
| **Nota del remoto actual y estado git** | Generar — §5.2 | <1 KB | Media | Confirma a qué URL apunta `origin` y que no haya commits locales sin pushear. |

> **Importante:** aunque copies `C:\xampp\` entera, **el disco nuevo necesita reinstalar
> XAMPP y PostgreSQL** (para registrar servicios de Windows, PATH, permisos NTFS, etc.).
> Después se reemplaza la carpeta `htdocs\Industria_mg` por la respaldada y se restaura
> el dump de PostgreSQL. Ver §7.

### 4.1. Qué puedes podar del respaldo si quieres ahorrar espacio

Estas rutas dentro de `C:\xampp\` **son prescindibles** si quieres hacer una copia más liviana:

- `C:\xampp\htdocs\Industria_mg\storage\sessions\` — sesiones PHP temporales.
- `C:\xampp\htdocs\Industria_mg\backups\` — dumps viejos (si vas a generar uno fresco en §5.1).
- `C:\xampp\htdocs\dashboard\`, `webalizer\`, `img\`, `applications.html`, demás ejemplos de XAMPP — vienen con la reinstalación.
- `C:\xampp\tmp\`, `C:\xampp\apache\logs\`, `C:\xampp\mysql\data\` (no uso MySQL aquí).

No es obligatorio podar nada — con copiar `C:\xampp\` entera estás cubierto.

---

## 5. Comandos exactos para generar los respaldos

> **NO ejecutar todavía.** Esta sección queda documentada para cuando toque hacerlo.

Elige disco/carpeta destino (ej. `D:\respaldo_industria_mg_YYYYMMDD\`).

### 5.1. Dump de la base de datos (texto plano, portable) — **IMPRESCINDIBLE**
Desde **PowerShell** con Apache aún corriendo:
```powershell
$fecha = Get-Date -Format "yyyyMMdd_HHmm"
$dest  = "D:\respaldo_industria_mg_$fecha"
New-Item -ItemType Directory -Force $dest | Out-Null

# Dump SQL plano (recomendado para portar entre versiones mayores)
& "C:\Program Files\PostgreSQL\17\bin\pg_dump.exe" `
    -U postgres -h localhost -p 5432 `
    --encoding=UTF8 --no-owner --no-privileges `
    -f "$dest\industria_mg_$fecha.sql" industria_mg

# (Redundante opcional) dump binario comprimido
& "C:\Program Files\PostgreSQL\17\bin\pg_dump.exe" `
    -U postgres -h localhost -p 5432 -Fc `
    -f "$dest\industria_mg_$fecha.dump" industria_mg
```
Pide la contraseña (`1234` en este entorno local). Validar que el `.sql` termine con
`-- PostgreSQL database dump complete` y que su tamaño sea > 100 KB (indicador de que no quedó vacío).

### 5.2. Snapshot de git y de ajustes de XAMPP
```powershell
git config --global --list > "$dest\git_config_global.txt"
git -C "C:\xampp\htdocs\Industria_mg" remote -v     > "$dest\git_remote.txt"
git -C "C:\xampp\htdocs\Industria_mg" status        > "$dest\git_status.txt"
git -C "C:\xampp\htdocs\Industria_mg" log --oneline -20                > "$dest\git_log_tail.txt"
git -C "C:\xampp\htdocs\Industria_mg" log --oneline origin/main..HEAD > "$dest\git_log_sin_push.txt"
```
Si el archivo `git_log_sin_push.txt` queda vacío → no hay commits pendientes y es seguro apagar Apache y desconectar.

### 5.3. Copia completa de `C:\xampp\`
**Primero detener servicios** para que no haya archivos abiertos:
```powershell
# Desde el XAMPP Control Panel: Stop Apache (y MySQL si estuviera).
# Confirmar que ningún proceso httpd.exe queda arriba:
Get-Process httpd -ErrorAction SilentlyContinue
```
Después copiar — `robocopy` es más rápido y robusto que `Copy-Item` para esto:
```powershell
robocopy "C:\xampp" "$dest\xampp" /E /COPY:DAT /DCOPY:DAT /R:1 /W:1 /MT:16 /NFL /NDL /NP `
    /XD "C:\xampp\tmp" "C:\xampp\apache\logs" "C:\xampp\mysql\data" `
    /XF "*.log"
```
Flags clave:
- `/E` incluye subcarpetas vacías.
- `/XD` excluye temporales (`tmp`, `logs`, data de MySQL que no usas).
- `/MT:16` paraleliza en 16 hilos.
Fin esperado: código de retorno 1 (copy OK) o 0 (nada que copiar).

### 5.4. Verificación del respaldo antes de desconectar el disco
```powershell
# El dump debe existir y pesar > 100 KB
Get-ChildItem "$dest\industria_mg_*.sql" | Select-Object Name, Length

# El cert SUNAT debe existir en el respaldo (verificación crítica)
Test-Path "$dest\xampp\htdocs\Industria_mg\facturacion\certs\20610316884.pfx"
# Esperado: True

# config/database.php debe estar en el respaldo
Test-Path "$dest\xampp\htdocs\Industria_mg\config\database.php"
# Esperado: True

# Tamaño total del respaldo
"{0:N0} MB" -f ((Get-ChildItem $dest -Recurse | Measure-Object -Property Length -Sum).Sum / 1MB)
```
Abre el `.sql` con VS Code (o `Get-Content -TotalCount 5` y `-Tail 5`) y verifica
que termine con `-- PostgreSQL database dump complete`.

---

## 6. Reconexión al repositorio Git ⚠️

### 6.1. Estado actual detectado
- **URL remoto:** `https://github.com/harri28/Industria-MG.git` (**HTTPS**, no SSH).
- **Git user.name global:** `harri28` → se conserva.
- **Git user.email global:** `harristr13@hotmail.com` → **confirmado**, se conserva en el disco nuevo.
- **Carpeta `.ssh`:** no existe en `C:\Users\harri\.ssh\`. Por lo tanto NO usas SSH; usas **HTTPS con Git Credential Manager** (viene con Git for Windows).
- **Nada pendiente de push:** `git log origin/main..HEAD` sale vacío.
- **2 archivos `.docx` untracked** en la raíz (`MG INDUSTRIA Y COMERCIO.docx`, `Requisitos_Funcionales_TDR_vs_Sistema.docx`): se mantienen en su sitio y viajan con la copia completa de `C:\xampp\`.

### 6.2. Antes de desconectar el disco actual
```powershell
# 1. Verificar que no quede trabajo sin pushear
git -C "C:\xampp\htdocs\Industria_mg" status
git -C "C:\xampp\htdocs\Industria_mg" log --oneline origin/main..HEAD

# 2. Si hay commits locales, pushearlos
#    git -C "C:\xampp\htdocs\Industria_mg" push origin main

# 3. Guardar el nombre del credential helper que está en uso
git config --global --list | Select-String -Pattern "credential"
# Esperado: credential.helper=manager (Git Credential Manager, provisto por Git for Windows)
```

Como el token de GitHub está guardado dentro de **Git Credential Manager** (que a su vez lo guarda en el **Windows Credential Manager** del usuario), ese token **NO se migra con el disco**. En el disco nuevo tendrás que:
- Pegar un **Personal Access Token** nuevo al primer `git push`, O
- Regenerarlo en https://github.com/settings/tokens si el actual venció/lo perdiste.

### 6.3. En el disco nuevo — pasos de reconexión
```powershell
# 1. Instalar Git for Windows (incluye Git Credential Manager)
# 2. Configurar identidad global
git config --global user.name  "harri28"
git config --global user.email "harristr13@hotmail.com"
git config --global credential.helper manager
git config --global init.defaultBranch main

# 3. (Opción recomendada en esta migración) NO clonar — ya tienes la carpeta completa
#    del respaldo con su .git intacto (§7 paso 7). Solo basta con un git status para
#    verificar que apunta a https://github.com/harri28/Industria-MG.git.

# 4. Al primer push/pull privado, Git Credential Manager abrirá un pop-up:
#    - Elegir "Sign in with your browser" → autenticar con la cuenta harri28, O
#    - Pegar un Personal Access Token (scopes requeridos: repo).
#    El token queda guardado en el Credential Manager de Windows y no vuelve a pedirlo.
```

### 6.4. Si decides pasarte a SSH (opcional, no urgente)
```powershell
ssh-keygen -t ed25519 -C "petram.control@gmail.com"
# Enter en todo (deja la ruta por defecto C:\Users\<user>\.ssh\id_ed25519)
Get-Content C:\Users\<user>\.ssh\id_ed25519.pub | clip
# Pegar en https://github.com/settings/keys
# Cambiar el remoto:
git -C "C:\xampp\htdocs\Industria_mg" remote set-url origin git@github.com:harri28/Industria-MG.git
```

---

## 7. Pasos de restauración en el disco nuevo (orden recomendado)

Estrategia: **reinstalar XAMPP + PostgreSQL en limpio, después reemplazar `htdocs\Industria_mg` por la carpeta respaldada y restaurar el dump de la BD.**

1. **Windows limpio arranca** → actualizar al día.
2. **Instalar VC++ 2019 Redistributable (x64)** — lo exige el PHP de XAMPP.
3. **Instalar XAMPP 8.2.12** en `C:\xampp\`.
   - Levantar Apache; probar `http://localhost/` (debe salir el dashboard por defecto de XAMPP).
   - Habilitar `extension=pdo_pgsql` y `extension=pgsql` en `C:\xampp\php\php.ini` si no están.
   - Habilitar extensiones adicionales que necesite el proyecto (`openssl`, `curl`, `gd`, `soap` para SUNAT, `fileinfo`) — generalmente ya vienen activas.
4. **Instalar PostgreSQL 17.9** nativo (x64).
   - Puerto 5432, usuario `postgres`, contraseña `1234` (así no hay que tocar `config/database.php`).
5. **Instalar Git for Windows** + configurar según §6.3.
6. **Detener Apache** (desde el XAMPP Control Panel).
7. **Reemplazar la carpeta del proyecto por la del respaldo:**
   ```powershell
   $src = "D:\respaldo_industria_mg_YYYYMMDD\xampp\htdocs\Industria_mg"
   $dst = "C:\xampp\htdocs\Industria_mg"

   # Si XAMPP recién instalado dejó una carpeta Industria_mg vacía, bórrala primero
   if (Test-Path $dst) { Remove-Item -Recurse -Force $dst }

   robocopy $src $dst /E /COPY:DAT /DCOPY:DAT /R:1 /W:1 /MT:16 /NFL /NDL /NP
   ```
   Esto trae de vuelta el working copy entero (con `.git`, `config/database.php`,
   `facturacion/certs/20610316884.pfx`, `assets/uploads/`, etc.).
8. **(Opcional)** Restaurar también virtualhosts o cambios manuales a Apache:
   ```powershell
   Copy-Item "D:\respaldo_industria_mg_YYYYMMDD\xampp\apache\conf\extra\httpd-vhosts.conf" `
             "C:\xampp\apache\conf\extra\httpd-vhosts.conf"
   # (Solo si lo habías editado — por defecto XAMPP viene con ese archivo vacío.)
   ```
9. **Crear la base y restaurar el dump:**
   ```powershell
   & "C:\Program Files\PostgreSQL\17\bin\psql.exe" -U postgres -c "CREATE DATABASE industria_mg;"
   & "C:\Program Files\PostgreSQL\17\bin\psql.exe" -U postgres -d industria_mg `
       -f "D:\respaldo_industria_mg_YYYYMMDD\industria_mg_YYYYMMDD_HHmm.sql"
   ```
   > El dump trae todas las tablas con datos. **No** correr `schema.sql` ni las migraciones 001–035 cuando restauras desde dump.
10. **Levantar Apache** desde el XAMPP Control Panel y abrir `http://localhost/industria_mg/`.
11. **Instalar VS Code** y las extensiones habituales.
12. **Reconectar Git** (abrir una terminal en `C:\xampp\htdocs\Industria_mg` y correr `git status`; §6.3 explica el pop-up del Credential Manager en el primer push).

---

## 8. Checklist de verificación post-migración

Marcar manualmente:

- [ ] `http://localhost/industria_mg/` carga el login sin error de conexión a DB.
- [ ] Login con `admin@industria-mg.com` / `admin123` entra al dashboard.
- [ ] El módulo **Producción** lista los proyectos con sus BOM, piezas y partes (verifica que el dump trajo las tablas `proyectos`, `proyecto_areas`, `proyecto_piezas`, `proyecto_partes`).
- [ ] El módulo **Inventarios** muestra productos con stock y precio promedio (`kardex`).
- [ ] El módulo **Ventas** → emisor de comprobantes carga datos de `empresa_emisora` y hay series en `series_comprobantes`.
- [ ] El certificado SUNAT está en `facturacion/certs/20610316884.pfx` y la UI de Configuración lo detecta.
- [ ] Las fotos de activos / diseños cargan (prueba un activo con imagen en `/modules/activos/`).
- [ ] `git status` dentro del repo responde correctamente.
- [ ] `git pull origin main` **no pide usuario/contraseña** (el Credential Manager ya tiene el token).
- [ ] Un commit de prueba + `git push origin main` sube sin pedir credenciales. (Revertir el commit después si fue solo prueba.)
- [ ] El bell de notificaciones carga avances recientes sin error 500.
- [ ] Las sesiones persisten entre páginas (no te desloguea solo) → confirma que `storage/sessions/` exista y tenga permisos de escritura.
- [ ] Verificar logs de Apache en `C:\xampp\apache\logs\error.log` por si hay warnings de extensiones faltantes.

---

## 9. Historial de migraciones

| Fecha        | Disco origen → destino | Observaciones |
|--------------|------------------------|---------------|
|              |                        |               |
|              |                        |               |

---

## 10. Notas y riesgos

- **Dump en texto plano** es más portable que `-Fc` entre versiones de PostgreSQL. Si saltas de 17 a una versión mayor en el disco nuevo, el `.sql` plano es el seguro.
- El **certificado `.pfx`** es la pieza más delicada. Si se pierde, hay que tramitar otro con SUNAT. Confirmar que el respaldo lo contiene antes de desconectar el disco.
- El entorno local usa **SUNAT beta**, nunca producción. Si el disco nuevo apunta a otra `sunat_server`, revisarlo en `empresa_emisora`.
- La configuración del **VPS no se toca** con esta migración; sigue viva en `sistemmg.com` con su propia `config/database.php` (ignorada por git) y su propio dump si hace falta.
- Si cambias la contraseña de PostgreSQL en el disco nuevo, acuérdate de editar `C:\xampp\htdocs\Industria_mg\config\database.php`.

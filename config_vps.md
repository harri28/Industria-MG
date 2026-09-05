# Configuración para VPS (Producción) — IndustriaMG

Notas de despliegue para pasar el sistema de XAMPP local a un VPS de producción. El código está preparado para ambos entornos, pero varios valores son **específicos de cada entorno y no viajan en git** — hay que configurarlos a mano en el servidor.

## 1. Requisitos del servidor

- PHP 8.2+ con extensiones: `pdo_pgsql`, `pgsql`, `curl`, `zip`, `dom`, `mbstring`, `openssl`.
- PostgreSQL (versión usada en desarrollo: 17).
- Servidor web (Apache recomendado, mismo stack que XAMPP) con `mod_rewrite`/`mod_proxy` si el VPS comparte host con otros proyectos.
- `display_errors` debe quedar en `Off` en producción (ya es el comportamiento actual) — el debug se hace por logs de Apache/PHP, no en pantalla.

## 2. Archivos que NO viajan en git (por `.gitignore`)

Hay que crearlos/copiarlos manualmente en el VPS, uno por entorno:

| Ruta | Contenido |
|---|---|
| `config/database.php` | Credenciales de conexión reales del VPS (host, puerto, usuario, password, nombre de BD). |
| `storage/sessions/` | Carpeta de sesiones PHP — debe existir y ser escribible por el usuario del servidor web. |
| `backups/` | Carpeta de backups generados desde el módulo Seguridad. |
| `assets/uploads/` | Imágenes subidas de materiales/otros. |
| `facturacion/storage/` | XML, CDR y PDF generados por SUNAT. |
| `*.pfx`, `*.p12`, `*.pem` | Certificado digital SUNAT — sensible, subir directo al servidor, nunca a git. |

## 3. Constantes de entorno en `config/database.php`

```php
define('DB_HOST', 'localhost');   // ajustar si Postgres corre en otro host
define('DB_PORT', '5432');
define('DB_NAME', 'industria_mg');
define('DB_USER', 'postgres');
define('DB_PASS', '...');         // credencial real del VPS
define('APP_BASE', '/industria_mg/'); // XAMPP: '/industria_mg/'  |  VPS: '/'
```

**`APP_BASE` es la variable que más suele romperse al migrar.** En XAMPP local la app cuelga de un subdirectorio (`/industria_mg/`), pero en el VPS normalmente el dominio apunta directo al docroot del proyecto, así que `APP_BASE` debe ser `'/'`. Todos los enlaces del sidebar, redirecciones de login y llamadas a `api.php` usan esta constante — si no se actualiza, los links quedan rotos o duplicados.

## 4. Facturación electrónica (SUNAT)

- El toggle **beta / producción** vive en la tabla `empresa_emisora` (columna `sunat_server`), no en código — se configura desde el módulo Configuración una vez desplegado. `config/sunat.php` resuelve el endpoint SOAP correcto según ese valor.
- `certificate_path` (columna de `empresa_emisora`) debe apuntar a una ruta válida **en el VPS**, no a la ruta de desarrollo local. Si se guarda como ruta relativa, se resuelve contra la raíz del proyecto o contra `facturacion/`.
- **Ojo con `sunat_public_base_url()`** en `config/sunat.php`: está *hardcodeada* a `/Industria-MG-main/facturacion/storage` (un nombre de carpeta que no coincide con `industria_mg`). Si el VPS sirve el proyecto bajo otra ruta, hay que actualizar esta función a mano — de lo contrario los enlaces a XML/CDR generados en los comprobantes quedarán rotos.
- Las credenciales SOL (`sunat_username`, `sunat_password`) y la clave del certificado también se configuran vía `empresa_emisora`, no en archivos de config.

## 5. Pasos de despliegue (resumen)

1. Clonar el repositorio en el VPS.
2. Crear `config/database.php` con las credenciales reales (no existe en git).
3. Crear la base de datos y aplicar schema + migraciones en orden:
   ```bash
   psql -U postgres -c "CREATE DATABASE industria_mg;"
   psql -U postgres -d industria_mg -f database/schema.sql
   # 001 a 032 en orden (hay un gap en 031)
   ```
4. Crear `storage/sessions/`, `backups/`, `assets/uploads/`, `facturacion/storage/` con permisos de escritura para el usuario del servidor web.
5. Subir el certificado SUNAT (`.pfx`/`.p12`) fuera de git, directo al servidor.
6. Ajustar `APP_BASE` en `config/database.php` según la ruta real de publicación.
7. Configurar el vhost del servidor web apuntando al docroot del proyecto.
8. Configurar `empresa_emisora` desde el módulo Configuración: RUC, credenciales SOL, ruta del certificado, y `sunat_server = 'beta'` para probar antes de pasar a `'produccion'`.
9. Verificar que `display_errors` esté en `Off` y que los logs de Apache/PHP sean accesibles para debug.
10. Recomendado: servir con HTTPS — las cookies de sesión (`PHPSESSID`) y el login no tienen protección adicional (no hay CSRF, ver `CLAUDE.md` § Known Limitations), así que el transporte cifrado es la única defensa mínima en producción.

## 6. Cosas que el código asume que NO cambian entre entornos

- Nombres de tablas/columnas, rutas de módulos (`modules/<mod>/`), convención de `action` en `api.php`.
- La zona horaria y el locale de moneda (Soles peruanos, `S/`) están fijos en `formatMoney()` — no hay soporte multi-moneda ni multi-locale.

## 7. Referencias cruzadas

- Arquitectura y convenciones de código: `CLAUDE.md`.
- Reglas de negocio del dominio: `reglas_negocio.md`.

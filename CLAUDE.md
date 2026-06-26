# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**IndustriaMG** is a full industrial management system (ERP-lite) for a manufacturing company. Stack: PHP + PostgreSQL + Vanilla JS + CSS custom design system. Runs on XAMPP.

## Setup

```bash
psql -U postgres -c "CREATE DATABASE industria_mg;"
psql -U postgres -d industria_mg -f database/schema.sql
# Apply all migrations in order (001–032, gap at 031):
psql -U postgres -d industria_mg -f database/migrations/001_proyecto_areas.sql
# ... through 032_maquinarias_venta.sql
```

Access: `http://localhost/industria_mg/`
Default user: `admin@industria-mg.com` / `admin123`
DB credentials in `config/database.php` (host: localhost:5432, user: postgres, pass: 1234).

There are no build steps, package managers, or test runners — this is a raw PHP/XAMPP application. Debug blank API responses by checking Apache/PHP logs (`display_errors` is off).

## Architecture

### Request Flow
```
Browser → PHP page (modules/<mod>/index.php) → includes header/footer
              ↓
         Vanilla JS fetch()
              ↓
         modules/<mod>/api.php?action=<name>  (JSON body for POST)
              ↓
         PDO via config/database.php getDB()
              ↓
         JSON response
```

### Key Files
- **`config/database.php`** — PDO singleton `getDB()`, `jsonResponse()`, `formatMoney()`, `generarCodigo()`, `tienePermiso()`
- **`config/events.php`** — Synchronous in-process event dispatcher: `listen($event, $fn)` and `dispatch($event, $payload)`. Used for side effects triggered by writes (e.g., `proyecto.creado` auto-creates `proyecto_areas` rows for all active areas).
- **`config/session.php`** — `startAppSession()` helper that sets a custom session save path (`storage/sessions/`) and calls `session_start()` safely. Use this instead of calling `session_start()` directly.
- **`config/sunat.php`** — Adapter for SUNAT SOAP e-invoicing: path helpers, XML signing, endpoint resolution (`beta` vs `produccion`), and `sunat_estado_db()` truncator.
- **`facturacion/`** — XML signing library (`signature.php` + `api_signature/XMLSec*.php`) used by the ventas e-invoicing flow.
- **`includes/header.php`** — Sidebar + topbar + bell notification system; requires `$page_title` (and optionally `$page_breadcrumb`) set **before** including
- **`includes/footer.php`** — Closes HTML, loads `assets/js/main.js`, renders optional `$extra_js`
- **`assets/css/style.css`** — Complete design system: CSS variables, sidebar, topbar, cards, tables, badges, buttons, forms, modals, toasts
- **`assets/js/main.js`** — Global utilities: `Toast`, `Modal`, `apiGet()`, `apiPost()`, `formatMoney()`, `formatDate()`, `badge()`, `initTabs()`
- **`assets/vendor/`** — Locally bundled: jQuery 3.7.1, Select2, DataTables + Bootstrap 5 theme. Some modules use these via `<script src="../../assets/vendor/...">`.
- **Font Awesome 6.5** — loaded via CDN in `header.php`; all icons use `fa fa-*` classes. No local copy.

### Module Status

| Module | Status | Key files |
|--------|--------|-----------|
| Producción | ✅ Complete | `modules/produccion/index.php`, `proyecto.php`, `area.php`, `bandeja.php`, `workflow.php`, `api.php` |
| Compras | ✅ Complete | `modules/compras/index.php`, `api.php` |
| Inventarios | ✅ Complete | `modules/inventarios/index.php`, `api.php` |
| Ventas/CRM | ✅ Complete | `modules/ventas/index.php`, `api.php` (includes e-invoicing via comprobantes electrónicos) |
| Comercialización | ✅ Complete | `modules/comercializacion/index.php`, `api.php` |
| Reportes | ✅ Complete | `modules/reportes/index.php`, `api.php` |
| Seguridad | ✅ Complete | `modules/seguridad/index.php`, `api.php` |
| Capacitación | ✅ Complete | `modules/capacitacion/index.php`, `api.php` (includes exam system, migration 022) |
| Activos | ✅ Complete | `modules/activos/index.php`, `api.php` |
| Clientes | ✅ Complete | `modules/clientes/index.php`, `api.php` |
| Caja | ✅ Complete | `modules/caja/index.php`, `api.php` |
| Préstamos | ✅ Complete | `modules/prestamos/index.php`, `api.php` |
| Cuentas por Cobrar | ✅ Complete | `modules/cuentas_cobrar/index.php`, `api.php` (migration 023) |
| Configuración | ⚠️ Partial | `modules/configuracion/index.php`, `api.php` (ubigeo resolver + empresa_emisora CRUD; UI settings not persisted) |
| Cuentas por Pagar | ⚠️ Stub | `modules/cuentas_pagar/index.php` only (no api.php; shows "próximamente") |

### Production Workflow (Producción Module)

Projects flow through two parallel status systems:
1. **`proyectos.estado`** — top-level: `fabricacion → pruebas → entrega → completado`
2. **`proyecto_areas`** — per-area status cross-table (project × area): `pendiente → en_proceso → completado | bloqueado`

The `areas` table has `orden_workflow` (integer, execution order) and `es_externa` (boolean, marks outsourced steps like Corte or Torno). When a project is created, the `proyecto.creado` event auto-populates `proyecto_areas` rows for all active areas via `config/events.php`.

### Áreas de Trabajo como puntos de transformación

Las áreas (`Soldadura`, `Torno`, `Taladro`, `Corte`, `Rolado`, etc.) no son solo etapas de un workflow secuencial — son **puntos de transformación física** donde piezas/materiales se consumen y se genera una pieza nueva con identidad propia.

Ejemplo: en `Soldadura` ingresan un tubo rectangular 2"×3"×2.5mm + un tubo de otra medida → salen como una pieza soldada (ej. "Bastidor soldado"). Las piezas de entrada **dejan de existir** como tales; la pieza resultante puede:

- **Continuar en el proyecto**: ser ensamblada con otras piezas o entrar a una transformación posterior dentro de la misma máquina.
- **Ir a inventario como stock**: se convierte en un producto disponible para otros proyectos, registrado vía `kardex`.

Las tres tablas (`transformaciones`, `transformacion_inputs`, `transformacion_outputs`) existen en el schema desde migration 019 y registran qué entró, qué salió, en qué área, y a dónde fue el output. Cuando el destino es inventario, la transformación dispara una entrada en `kardex` que actualiza `precio_promedio`. **El schema está modelado pero ningún `api.php` implementa aún acciones que escriban en estas tablas.**

### Database Key Tables

- **`proyectos`** — Core production entity. Has `costo_estimado` and `costo_real` (auto-recalculated from BOM + extra costs).
- **`proyecto_areas`** — Per-area workflow state for each project (created by migration 001).
- **`proyecto_bom`** — Bill of Materials per project: planned qty/cost vs real qty/cost. Has optional FKs `pieza_id → proyecto_piezas` (migration 011) and `parte_id → proyecto_partes` (migration 014). The legacy column `pieza` (text Detalle) is consolidated into `proyecto_partes.nombre` by migration 020 — kept for backward compat but should not be used in new code.
- **`proyecto_piezas`** — Top-level structural component (e.g. "Caja", "Marco"). Has `estado` (`planificada`/`en_proceso`/`disponible`/`consumida`/`integrada`/`stock`) and `area_actual_id` (migration 019).
- **`proyecto_partes`** — Sub-piece of a Pieza, the minimum unit of work assigned to an área (e.g. "Travesaño", "Cruz 1"). Has `estado_fabricacion` (`sin_asignar`/`pendiente`/`en_proceso`/`completada`/`integrada`/`stock`), `area_id` (assigned manually by operator after loading plantilla), `fecha_inicio`, `fecha_completada`, `usuario_completo_id` (migration 019).
- **`transformaciones`** — Event log of work done in an área. `tipo` is `combinacion` (N inputs → 1 new piece, e.g. Soldadura), `procesamiento` (1 piece refined, e.g. Pintura), or `finalizacion` (last step of chain, decides if output goes to project or stock). External áreas (`areas.es_externa = true`) use `fecha_envio_externo`/`fecha_recepcion_externo`/`proveedor` (migration 019).
- **`transformacion_inputs`** — What a transformation consumed. Each row references EITHER `proyecto_bom_id` (raw material) OR `proyecto_pieza_id` (already-transformed piece), enforced by CHECK constraint (migration 019).
- **`transformacion_outputs`** — What a transformation generated. If destino=proyecto: `proyecto_pieza_id` filled. If destino=stock: `producto_id` + `kardex_id` filled (kardex entry created at the same time, updates `precio_promedio`). Migration 019.
- **`proyecto_costos`** — Additional costs per project (labor, overhead, etc.).
- **`proyecto_avances`** — Progress log entries by area/user; updates `porcentaje_avance` on parent project. Uses `fecha` timestamp column (not `created_at`).
- **`kardex`** — Inventory movements (entrada/salida/ajuste) with running balance. Costing is weighted average (`precio_promedio` recalculated on every entry).
- **`ordenes_compra`** → **`recepciones`** — Purchase order lifecycle feeding inventory.
- **`ordenes_venta`** / **`orden_venta_items`** — Sales order linked to `clientes`, `proyectos`, `cotizaciones` (migration 019). States: `borrador → confirmada → facturada → cobrada → cancelada`.
- **`facturas`** / **`cobros`** — Accounts receivable: invoice + payment records (migration 023). `facturas` also has FK to `comprobantes_electronicos` when an e-invoice is generated.
- **`empresa_emisora`** — Single-row company profile for e-invoicing: RUC, SUNAT credentials, certificate path, WhatsApp instance, etc. (migrations 024, 028).
- **`series_comprobantes`** — Active document series per type (F001 for facturas, B001 for boletas, NV01 for notas de venta, FC01/BC01 for notas de crédito, etc.) with sequential `ultimo_numero` (migration 028).
- **`comprobantes_electronicos`** / **`comprobante_detalles`** — Full e-invoice record including SUNAT response JSON, XML/CDR/PDF paths, QR, hash, SOAP payloads, and credit/debit note references (migrations 028, 029).
- **`fe_*` catalog tables** — SUNAT lookup catalogs: `fe_tipos_documento`, `fe_tipos_documento_identidad`, `fe_unidades`, `fe_formas_pago`, `fe_tipos_operacion`, `fe_monedas`, `fe_tipos_afectacion_igv`, `fe_tipos_nota_credito`, `fe_tipos_nota_debito` (migrations 025, 026).
- **`maquinarias`** — Now has `producto_id`, `precio_venta`, `costo_total`, `estado_venta`, `orden_venta_id`, `comprobante_electronico_id` — machines can be sold directly from the Ventas module (migration 032).
- **`alertas`** — System-wide alerts (delay, low stock, warranty expiry).

Schema migrations in `database/migrations/` (001–032, gap at 031) document all schema evolution after the base `schema.sql`.

### Database Naming Conventions
- Tables: plural snake_case (`proyectos`, `proyecto_avances`)
- PKs: always `id SERIAL PRIMARY KEY`
- FKs: `{singular_table}_id` (e.g. `proyecto_id`, `usuario_id`)
- Status: `estado` (enum string) or `activo` (boolean — soft-delete pattern used across all tables)
- Money: `DECIMAL(12,2)`; quantities: `DECIMAL(12,3)`
- Most tables have `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`; only some also have `updated_at`

## API Conventions

### Action Routing
The `action` parameter can arrive via GET query string **or** inside the POST JSON body (POST body takes precedence):
```php
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $action;
} else {
    $input = $_GET;
}
```
Always returns `{"ok": true, ...}` or `{"error": "message"}` via `jsonResponse()`.

APIs that trigger side effects must `require_once '../../config/events.php'` in addition to `database.php`. APIs that need SUNAT e-invoicing must also `require_once '../../config/sunat.php'`.

### Transactions
Use `beginTransaction()` / `commit()` / `rollBack()` only for multi-table writes (e.g. order + line items, inventory movement + stock update). Single-table operations use no transaction.

### SQL Patterns
- Text search: `ILIKE` (case-insensitive, PostgreSQL)
- Dynamic WHERE: build `$conditions[]` array, join with `implode(' AND ', $conditions)`
- Inventory race conditions: `SELECT ... FOR UPDATE` before stock updates
- Detail tables use `ON DELETE CASCADE` from their parent

### Helpers
- `generarCodigo($prefix, $table, $field)` — generates `SC-2026-0001` codes, **resets counter per year**. `$table` and `$field` are interpolated directly into SQL (not parameterized); always pass literal string constants, never user-supplied values.
- `formatMoney($n)` — returns `'S/ 1,234.56'` (Peruvian sol)
- `getUsuarioActual()` — returns `$_SESSION['usuario']` or placeholder `{id:null, nombre:'Sistema', rol:'Administrador'}`. Requires session to be active. Use the pattern below.
- `tienePermiso()` exists but **is never called**; all routes are currently open to any logged-in user
- `IGV` constant = `0.18` (18% VAT); defined in `database.php` alongside DB credentials

### Session Pattern for New api.php Files
New `api.php` files that need the real logged-in user must start the session correctly:
```php
require_once __DIR__ . '/../../config/session.php';
startAppSession();
// then:
$usuario_id = $_SESSION['usuario']['id'] ?? null;
```
Older `api.php` files skip `session_start()` and always get a null user ID in audit logs. The `config/session.php` helper sets a custom session save path (`storage/sessions/`) which must exist.

## Module UI Patterns

Every `modules/<mod>/index.php` follows the same structure:
1. Set `$page_title` and `$page_breadcrumb` before `include 'header.php'`
2. HTML layout: toolbar (`toolbar-left` for filters, `toolbar-right` for action buttons) + table skeleton
3. All logic in an inline `<script>` block at the bottom — no separate JS files per module

Each module defines its own JS functions following these naming conventions:
- `cargarXXX()` — fetch data from API, render into `<tbody>`
- `abrirModalXXX()` — reset form + `Modal.open('modalXXX')`
- `guardarXXX()` — collect form, POST to API, reload
- `badgeEstadoXXX(estado)` — returns badge HTML string for module-specific enums

State is kept in module-level JS globals (e.g. `let proyectoActual = null`), not in DOM attributes.

The `initTabs()` call (from `main.js`) auto-wires elements with `data-group` and `data-target` attributes; call it after rendering tab content.

Some modules (Ventas) load Select2 and DataTables from `assets/vendor/` for enhanced client/product search and table pagination.

## Bell Notification System

Implemented entirely in `includes/header.php`. On every page load it fetches `proyecto_avances` from the last 7 days; client-side JS compares the max ID against `localStorage` to detect new entries, plays a synthetic bell sound, and shows a badge count (capped at 99+). Clicking a notification navigates to the project's Avances tab.

## E-Invoicing (Facturación Electrónica)

The Ventas module generates SUNAT-compliant electronic documents (facturas, boletas, notas de venta, notas de crédito/débito). The flow is:

1. A sale creates an `ordenes_venta` record.
2. The operator selects document type and series from `series_comprobantes`.
3. `modules/ventas/api.php` calls `config/sunat.php` to build and sign the XML (via `facturacion/signature.php`), then sends it to SUNAT via SOAP.
4. The response is stored in `comprobantes_electronicos` (`sunat_response_json`, `hash_cdr`, `xml_path`, `cdr_path`, `pdf_path`).
5. `facturas.comprobante_electronico_id` links the invoice to the e-document.

`empresa_emisora` holds all SUNAT credentials (`sunat_username`, `sunat_password`, `certificate_path`, etc.) and the `sunat_server` toggle (`beta` or `produccion`).

## CSS Design Tokens
Classic ERP look. Primary colors via CSS variables in `style.css`: `--sidebar-bg: #1a2c4e`, `--primary: #1d4ed8`. Status badge classes follow the pattern `badge-<estado>` (e.g. `badge-fabricacion`, `badge-retrasado`).

## Known Limitations
- **Session user in APIs**: Older `api.php` files only `require_once 'database.php'` and never start the session, so `getUsuarioActual()` returns placeholder defaults and audit logs record null user IDs. New files should use `startAppSession()` from `config/session.php`.
- **Permissions**: `tienePermiso()` is defined but unused — no access control is enforced at the route level.
- **No CSRF protection**: All POST endpoints accept requests without token verification.
- **Error visibility**: `display_errors` is off; PHP errors are swallowed. Check Apache/PHP logs when debugging blank responses.
- **`transformaciones` unimplemented**: The area transformation tables (`transformaciones`, `transformacion_inputs`, `transformacion_outputs`) are fully modeled in the schema but no `api.php` writes to them yet.

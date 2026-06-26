# AGENTS.md

This file provides guidance to Codex (Codex.ai/code) when working with code in this repository.

## Project Overview

**IndustriaMG** is a full industrial management system (ERP-lite) for a manufacturing company. Stack: PHP + PostgreSQL + Vanilla JS + CSS custom design system. Runs on XAMPP.

## Setup

```bash
psql -U postgres -c "CREATE DATABASE industria_mg;"
psql -U postgres -d industria_mg -f database/schema.sql
# Apply all migrations after base schema (001–020):
psql -U postgres -d industria_mg -f database/migrations/001_proyecto_areas.sql
# ... through 020_consolidar_detalle_a_parte.sql
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
- **`includes/header.php`** — Sidebar + topbar + bell notification system; requires `$page_title` (and optionally `$page_breadcrumb`) set **before** including
- **`includes/footer.php`** — Closes HTML, loads `assets/js/main.js`, renders optional `$extra_js`
- **`assets/css/style.css`** — Complete design system: CSS variables, sidebar, topbar, cards, tables, badges, buttons, forms, modals, toasts
- **`assets/js/main.js`** — Global utilities: `Toast`, `Modal`, `apiGet()`, `apiPost()`, `formatMoney()`, `formatDate()`, `badge()`, `initTabs()`
- **Font Awesome 6.5** — loaded via CDN in `header.php`; all icons use `fa fa-*` classes. No local copy.

### Module Status

| Module | Status | Key files |
|--------|--------|-----------|
| Producción | ✅ Complete | `modules/produccion/index.php`, `proyecto.php`, `area.php`, `bandeja.php`, `workflow.php`, `api.php` |
| Compras | ✅ Complete | `modules/compras/index.php`, `api.php` |
| Inventarios | ✅ Complete | `modules/inventarios/index.php`, `api.php` |
| Ventas/CRM | ✅ Complete | `modules/ventas/index.php`, `api.php` |
| Comercialización | ✅ Complete | `modules/comercializacion/index.php`, `api.php` |
| Reportes | ✅ Complete | `modules/reportes/index.php`, `api.php` |
| Seguridad | ✅ Complete | `modules/seguridad/index.php`, `api.php` |
| Capacitación | ✅ Complete | `modules/capacitacion/index.php`, `api.php` |
| Activos | ✅ Complete | `modules/activos/index.php`, `api.php` |
| Clientes | ✅ Complete | `modules/clientes/index.php`, `api.php` |
| Caja | ✅ Complete | `modules/caja/index.php`, `api.php` |
| Préstamos | ✅ Complete | `modules/prestamos/index.php`, `api.php` |
| Configuración | ⚠️ UI only | `modules/configuracion/index.php` (no api.php) |
| Cuentas por Cobrar | ⚠️ UI only | `modules/cuentas_cobrar/index.php` (no api.php) |
| Cuentas por Pagar | ⚠️ UI only | `modules/cuentas_pagar/index.php` (no api.php) |

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
- **`alertas`** — System-wide alerts (delay, low stock, warranty expiry).

Schema migrations in `database/migrations/` (001–020) document all schema evolution after the base `schema.sql`.

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

APIs that trigger side effects must `require_once '../../config/events.php'` in addition to `database.php`.

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
- `getUsuarioActual()` — returns `$_SESSION['usuario']` or placeholder `{id:null, nombre:'Sistema', rol:'Administrador'}`. Login sets the session correctly, but **API files that don't call `session_start()` will always get the placeholder**, which is why audit logs record null user IDs. Fix: add `session_start()` at the top of any `api.php` that needs the real user.
- `tienePermiso()` exists but **is never called**; all routes are currently open to any logged-in user
- `IGV` constant = `0.18` (18% VAT); defined in `database.php` alongside DB credentials

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

## Bell Notification System

Implemented entirely in `includes/header.php`. On every page load it fetches `proyecto_avances` from the last 7 days; client-side JS compares the max ID against `localStorage` to detect new entries, plays a synthetic bell sound, and shows a badge count (capped at 99+). Clicking a notification navigates to the project's Avances tab.

## CSS Design Tokens
Classic ERP look. Primary colors via CSS variables in `style.css`: `--sidebar-bg: #1a2c4e`, `--primary: #1d4ed8`. Status badge classes follow the pattern `badge-<estado>` (e.g. `badge-fabricacion`, `badge-retrasado`).

## Known Limitations
- **Session user in APIs**: `login.php` correctly populates `$_SESSION['usuario']`. However, `api.php` files only `require_once 'database.php'` and do not call `session_start()`, so `getUsuarioActual()` returns placeholder defaults in API context. Audit logs record null user IDs as a result.
- **Permissions**: `tienePermiso()` is defined but unused — no access control is enforced at the route level.
- **No CSRF protection**: All POST endpoints accept requests without token verification.
- **Error visibility**: `display_errors` is off; PHP errors are swallowed. Check Apache/PHP logs when debugging blank responses.

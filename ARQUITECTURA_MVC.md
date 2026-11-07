# ARQUITECTURA MVC — IndustriaMG

> El sistema se está migrando a **MVC de forma incremental, módulo por módulo** (Nivel
> Model + Controller; la `index.php` sigue siendo la Vista). El resto de módulos aún usan el
> estilo plano (`api.php` con PDO inline). Ver `CLAUDE.md` para la arquitectura global.

## Estado de la migración
- **Inventario** — ✅ migrado (2026-11). `api.php` es un router delgado que delega en
  `modules/inventarios/mvc/controllers/InventarioController.php` + `mvc/models/*`
  (`Producto`, `Kardex`, `Categoria`, `UnidadMedida`, `Catalogo`). El sub-feature **Estante**
  (`estante/`) ya era MVC y se conserva. La importación (`import/importador.php`) se mantiene.
- Resto de módulos — pendientes.

**Patrón por módulo:**
```
modules/<mod>/
├── index.php                         ← Vista (página; HTML + su JS)
├── api.php                           ← Router delgado: sesión/permisos → delega al controlador
└── mvc/
    ├── controllers/<Mod>Controller.php   ← 1 acción = 1 método; responde jsonResponse()
    └── models/<Entidad>.php              ← TODO el SQL/PDO + reglas de negocio
```
Regla de oro: **comportamiento idéntico** en cada paso; solo se reorganiza dónde vive el código.

---

## Anexo — Feature "Estante" (sub-módulo de Inventario)

> Documento original de la funcionalidad **Estante / Ubicación visual de productos**, el primer
> MVC localizado del sistema (sirvió de plantilla para migrar el módulo completo).

---

## 1. Objetivo

Al abrir la ficha de un producto, mostrar un **estante dibujado en SVG** (marco gris/negro) que
**resalta en naranja** (color del sistema) la celda donde está guardada la pieza. El tamaño del
estante (columnas y filas) es **configurable**. El código de celda usa el formato
`columna+fila` → `B3` = columna **B**, fila **3** (`B12` = columna B, fila 12).

Decisiones acordadas con el usuario:

- **Un solo estante global** para todo el almacén (no hay racks con nombre).
- **Letra = columna, número = fila.** Columnas: A, B, C… · Filas: 1 … N.
- Se **mantiene** el campo `productos.ubicacion` (texto libre) y se **agrega** un campo nuevo
  para la celda estructurada.

---

## 2. Principio MVC aplicado

| Capa | Responsabilidad | Prohibido |
|------|-----------------|-----------|
| **Model** | Acceso a datos (SQL/PDO), validación y reglas (parseo de celda, rango válido) | HTML, `echo`, routing |
| **Controller** | Leer la `action`, invocar el Model, devolver `jsonResponse()` | SQL directo, HTML |
| **View** | Dibujar el SVG del estante a partir de `{config, celda}` | SQL, lógica de negocio |

Separación estricta: la Vista nunca consulta la BD; el Modelo nunca imprime HTML; el Controlador
no arma SQL ni HTML.

---

## 3. Estructura de carpetas

```
modules/inventarios/estante/
├── models/
│   └── Estante.php            # Modelo: datos + reglas
├── controllers/
│   └── EstanteController.php  # Controlador: action → model → JSON
└── views/
    └── estante.view.php       # Vista: render SVG (gris + celda naranja)
```

---

## 4. Contratos de cada capa

### 4.1 Model — `Estante.php` (clase `Estante`)
Métodos (todos estáticos, reciben `$db` de `getDB()`):

- `obtenerConfig(): array` → `['columnas' => int, 'filas' => int]` (crea/devuelve la fila única).
- `guardarConfig(int $columnas, int $filas): void` — valida rangos (1..26 columnas, 1..99 filas).
- `parseCelda(string $codigo): ?array` → `['col' => 'B', 'col_num' => 2, 'fila' => 3]` o `null` si inválido.
- `celdaValida(string $codigo): bool` — dentro del tamaño configurado.
- `guardarCeldaProducto(int $productoId, ?string $celda): void`
- `obtenerCeldaProducto(int $productoId): ?string`

Reglas de parseo: letras iniciales = columna (A→1, B→2, …), dígitos finales = fila.
`strtoupper`, sin espacios. Celda fuera del tamaño configurado = inválida.

### 4.2 Controller — `EstanteController.php` (clase `EstanteController`)
Enrutado desde `modules/inventarios/api.php` vía `action`:

| Action | Método | Entrada | Salida |
|--------|--------|---------|--------|
| `estante_config` | `configGet()` | — | `{ok, columnas, filas}` |
| `estante_config_guardar` | `configGuardar()` | `{columnas, filas}` | `{ok}` |
| `estante_celda_guardar` | `celdaGuardar()` | `{producto_id, celda}` | `{ok}` |

Devuelve siempre `{"ok": true, ...}` o `{"error": "..."}` vía `jsonResponse()`
(convención del sistema, ver `CLAUDE.md` → API Conventions).

### 4.3 View — `estante.view.php`
Función `renderEstanteSVG(array $config, ?string $celdaActiva): string`.
- Dibuja rejilla: columnas rotuladas (A, B…) arriba, filas numeradas (1…N) a la izquierda.
- Celda activa → relleno **naranja del sistema**; resto → gris claro.
- Sin dependencias externas; SVG puro embebible en `producto.php` y en el modal de edición.

---

## 5. Flujo de petición

```
Ficha de producto (producto.php)
    └── include estante/views/estante.view.php
            renderEstanteSVG(config, celda_del_producto)  → SVG en línea

Editar producto / configurar tamaño (index.php)
    └── JS fetch → api.php?action=estante_*
            └── EstanteController::<accion>()
                    └── Estante::<metodo>()  → PDO → BD
                    └── jsonResponse(...)
```

El SVG del selector clickeable reutiliza la **misma Vista**; el JS solo alterna la celda
seleccionada y hace POST a `estante_celda_guardar`.

---

## 6. Base de datos — migración 037

- Tabla nueva `estante_config` (una sola fila): `columnas INT`, `filas INT`.
- Columna nueva en `productos` para la celda estructurada (p. ej. `celda_estante VARCHAR`).
- `productos.ubicacion` (texto libre) **se conserva** sin cambios.

---

## 7. Convenciones respetadas del sistema

- Routing por `action` (GET o POST JSON, POST tiene precedencia) — ver `CLAUDE.md`.
- Respuestas `jsonResponse()`; búsqueda `ILIKE`; soft-delete con `activo` donde aplique.
- Sin pasos de build; SVG y JS inline, acorde al stack PHP/XAMPP.

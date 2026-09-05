# Reglas de Negocio — IndustriaMG

Este documento describe las reglas de negocio del sistema: cómo se comporta el dominio (producción, inventario, ventas, permisos), independientemente de la implementación técnica. Para arquitectura de código ver `CLAUDE.md`.

## 1. Producción

### 1.1 Proyectos
Un proyecto de producción avanza por dos sistemas de estado en paralelo:

- **Estado general del proyecto** (`proyectos.estado`): `fabricacion → pruebas → entrega → completado`.
- **Estado por área** (`proyecto_areas`): cada área activa tiene su propio estado por proyecto: `pendiente → en_proceso → completado | bloqueado`.

Al crear un proyecto, se generan automáticamente filas de `proyecto_areas` para **todas las áreas activas** — no hace falta asignarlas manualmente.

### 1.2 Áreas de trabajo como puntos de transformación
Las áreas (Soldadura, Torno, Taladro, Corte, Rolado, etc.) no son solo pasos de un flujo — son **puntos de transformación física**: entran piezas/materiales, salen piezas nuevas con identidad propia. Las piezas de entrada dejan de existir como tales una vez transformadas.

La pieza resultante de una transformación tiene dos destinos posibles:
- **Continúa en el proyecto**: se ensambla con otras piezas o pasa a otra transformación.
- **Va a inventario como stock**: se convierte en producto disponible para otros proyectos (genera una entrada en kardex y actualiza el precio promedio).

Tipos de transformación:
- `combinacion` — N piezas/materiales de entrada → 1 pieza nueva (ej. Soldadura).
- `procesamiento` — 1 pieza se refina (ej. Pintura).
- `finalizacion` — último paso de la cadena; decide si el output va al proyecto o a stock.

**Áreas externas** (`es_externa = true`, ej. Corte Láser) representan trabajo tercerizado — se registra envío/recepción con proveedor en vez de ejecución interna. La asignación de qué área trabaja cada parte la decide el operador caso por caso, no hay una regla fija de enrutamiento (excepto Corte Láser, que siempre es externo porque la planta no tiene máquina propia).

### 1.3 Jerarquía de piezas
- **Pieza** (`proyecto_piezas`) — componente estructural de alto nivel (ej. "Caja", "Marco"). Estados: `planificada / en_proceso / disponible / consumida / integrada / stock`.
- **Parte** (`proyecto_partes`) — subunidad de una pieza, la unidad mínima de trabajo asignable a un área (ej. "Travesaño", "Cruz 1"). Estados: `sin_asignar / pendiente / en_proceso / completada / integrada / stock`.

El área de una parte se asigna manualmente por el operador después de cargar la plantilla del proyecto — no es automático.

### 1.4 Costos
`proyectos.costo_estimado` se define al planificar; `costo_real` se recalcula automáticamente a partir del BOM (Bill of Materials) real consumido más los costos adicionales (`proyecto_costos`, ej. mano de obra, overhead).

## 2. Inventario / Almacén

### 2.1 Kardex y valuación
Todo movimiento de stock (entrada, salida, ajuste) genera una fila en `kardex` con saldo corriente. El método de valuación es **promedio ponderado** por defecto (también existe la opción FIFO por producto, aunque el sistema usa mayormente promedio):

- **Entrada**: el nuevo precio promedio pondera el stock anterior contra la cantidad y precio entrantes.
- **Salida**: no cambia el precio promedio, solo descuenta stock (debe validarse que el stock alcance).
- **Ajuste**: fija el stock exactamente en la cantidad indicada (no suma ni resta), útil para conciliaciones.

### 2.2 Stock crítico
Un producto está en estado crítico cuando `stock_actual <= stock_minimo` y `stock_minimo > 0`. El sistema genera alertas automáticamente y las resuelve (marca como leídas) cuando el stock vuelve a superar el mínimo.

### 2.3 Materiales vs. Repuestos
Un mismo catálogo de productos (`productos`) distingue materiales de repuestos mediante el flag `es_repuesto`. Los repuestos se reservan conceptualmente para maquinaria vendida (post-venta), pero comparten el mismo control de stock/kardex que los materiales de producción.

### 2.4 Roles y permisos de Almacén
El acceso al módulo Almacén se controla por rol (ver sección 5). Un usuario con permiso de **solo lectura** puede consultar inventario, kardex, entradas y salidas, pero no puede registrar nuevos materiales, ajustes de stock, entradas/salidas ni categorías. Un usuario **sin acceso** no puede ni siquiera ver el módulo.

## 3. Compras

El flujo de abastecimiento es: **Orden de Compra → Recepción → Inventario**. Una recepción parcial o total de una orden de compra genera automáticamente movimientos de entrada en kardex para los productos recibidos, actualizando el stock y el precio promedio.

## 4. Ventas y Facturación Electrónica

### 4.1 Ciclo de una orden de venta
`borrador → confirmada → facturada → cobrada → cancelada`.

### 4.2 Comprobantes electrónicos
Cada venta puede generar un comprobante electrónico ante SUNAT: factura, boleta, nota de venta (no fiscal), o notas de crédito/débito para corregir comprobantes ya emitidos. Reglas clave:

- **Factura**: requiere que el cliente tenga RUC. Sin RUC no se puede emitir.
- **Boleta**: requiere un cliente seleccionado (RUC o DNI de 8 dígitos); si no hay documento válido, se usa el genérico "Clientes Varios" (`00000000`).
- Cada tipo de documento usa una **serie** independiente con numeración correlativa que nunca se reinicia ni se reutiliza (F001 facturas, B001 boletas, NV01 notas de venta, FC01/BC01 notas de crédito/débito).
- El comprobante se firma digitalmente (XML + certificado) y se envía a SUNAT por SOAP; la respuesta (aceptado/observado/rechazado) queda registrada junto con el CDR.
- El ambiente de envío (`beta` vs `producción`) es una configuración global de la empresa emisora — todas las ventas usan el mismo ambiente hasta que se cambie explícitamente.

### 4.3 Maquinarias como productos vendibles
Las máquinas registradas en `maquinarias` pueden venderse directamente desde Ventas, generando su propio comprobante electrónico y quedando enlazadas a la orden de venta correspondiente.

## 5. Roles y Permisos

Cada rol define permisos por módulo con tres niveles posibles:

| Nivel | Significado |
|---|---|
| **Sin acceso** | El módulo no aparece ni es alcanzable — el usuario recibe error de permiso. |
| **Solo lectura** | Puede ver/listar/consultar, pero no crear, editar, ni eliminar. |
| **Acceso completo** | Puede ver y escribir sin restricciones. |

Un rol marcado **Administrador total** (`{"all": true}`) tiene acceso completo a todos los módulos sin excepción, sin importar la configuración individual por módulo.

La aplicación de estos niveles debe implementarse módulo por módulo (backend + UI); actualmente está implementada de forma completa en **Almacén** (Inventarios). Otros módulos aún no aplican estas reglas de forma activa — ver `CLAUDE.md` § Known Limitations para el estado real y actualizado.

## 6. Auditoría

Toda acción de creación, edición, eliminación y login relevante debe quedar registrada con: usuario, módulo, acción, datos anteriores/nuevos (cuando aplica), IP y fecha. Esto es la base de la trazabilidad del sistema, especialmente relevante para movimientos de inventario y comprobantes electrónicos.

# Orden de migraciones para facturacion electronica

Aplicar estas migraciones en este orden sobre la base `industria_mg`:

1. `024_facturacion_empresa_base.sql`
2. `025_facturacion_catalogos_base.sql`
3. `026_facturacion_catalogos_seed.sql`
4. `027_facturacion_campos_clientes_productos.sql`
5. `028_facturacion_series_y_comprobantes.sql`
6. `029_facturacion_notas_credito.sql`
7. `030_facturacion_seed_productos_prueba.sql`

## Que aporta cada una

- `024`: crea `empresa_emisora` para los datos fiscales y de emision.
- `025`: crea catalogos base SUNAT y tablas de ubigeo.
- `026`: carga tipos de documento, identidad, formas de pago, monedas, afectacion IGV, notas y unidades.
- `027`: adapta `clientes` y `productos` con campos tributarios y crea `CLIENTES VARIOS`.
- `028`: crea `series_comprobantes`, `comprobantes_electronicos`, `comprobante_detalles` y enlaza `facturas`.
- `029`: agrega soporte para notas de credito y debito.
- `030`: deja datos minimos de empresa y productos de prueba listos para pruebas de facturacion.

## Ejemplo de ejecucion en Windows

Si tu PostgreSQL esta instalado en una ruta distinta, ajusta el ejecutable:

```bat
"C:\Program Files\PostgreSQL\17\bin\psql.exe" -U postgres -d industria_mg -f "C:\laragon\www\Industria-MG-main\database\migrations\024_facturacion_empresa_base.sql"
"C:\Program Files\PostgreSQL\17\bin\psql.exe" -U postgres -d industria_mg -f "C:\laragon\www\Industria-MG-main\database\migrations\025_facturacion_catalogos_base.sql"
"C:\Program Files\PostgreSQL\17\bin\psql.exe" -U postgres -d industria_mg -f "C:\laragon\www\Industria-MG-main\database\migrations\026_facturacion_catalogos_seed.sql"
"C:\Program Files\PostgreSQL\17\bin\psql.exe" -U postgres -d industria_mg -f "C:\laragon\www\Industria-MG-main\database\migrations\027_facturacion_campos_clientes_productos.sql"
"C:\Program Files\PostgreSQL\17\bin\psql.exe" -U postgres -d industria_mg -f "C:\laragon\www\Industria-MG-main\database\migrations\028_facturacion_series_y_comprobantes.sql"
"C:\Program Files\PostgreSQL\17\bin\psql.exe" -U postgres -d industria_mg -f "C:\laragon\www\Industria-MG-main\database\migrations\029_facturacion_notas_credito.sql"
"C:\Program Files\PostgreSQL\17\bin\psql.exe" -U postgres -d industria_mg -f "C:\laragon\www\Industria-MG-main\database\migrations\030_facturacion_seed_productos_prueba.sql"
```

## Siguiente capa recomendada

Despues de estas migraciones, lo siguiente es implementar:

1. configuracion de empresa emisora en el modulo `configuracion`
2. mantenimiento tributario de clientes y productos
3. emision de boletas/facturas desde ventas
4. generacion y persistencia del payload SUNAT o Nubefact
5. notas de credito operativas

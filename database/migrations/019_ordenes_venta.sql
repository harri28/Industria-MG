-- Órdenes de venta vinculadas a proyectos e inventario
CREATE TABLE ordenes_venta (
    id SERIAL PRIMARY KEY,
    numero VARCHAR(30) UNIQUE NOT NULL,
    cliente_id INTEGER REFERENCES clientes(id),
    proyecto_id INTEGER REFERENCES proyectos(id),
    cotizacion_id INTEGER REFERENCES cotizaciones(id),
    estado VARCHAR(20) DEFAULT 'borrador', -- borrador, confirmada, facturada, cobrada, cancelada
    fecha_emision DATE DEFAULT CURRENT_DATE,
    fecha_entrega DATE,
    observaciones TEXT,
    subtotal DECIMAL(12,2) DEFAULT 0,
    igv DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orden_venta_items (
    id SERIAL PRIMARY KEY,
    orden_venta_id INTEGER REFERENCES ordenes_venta(id) ON DELETE CASCADE,
    producto_id INTEGER REFERENCES productos(id),
    descripcion VARCHAR(200),
    cantidad DECIMAL(12,3) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(5,2) DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL
);

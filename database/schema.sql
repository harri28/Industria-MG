-- ============================================================
-- ARCHIVO: industria_mg/database/schema.sql
-- DESCRIPCIÓN: Esquema PostgreSQL - Sistema de Gestión Industrial MG
-- EJECUTAR:
--   psql -U postgres -c "CREATE DATABASE industria_mg;"
--   psql -U postgres -d industria_mg -f schema.sql
-- ============================================================

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ============================================================
-- SEGURIDAD: ROLES, ÁREAS Y USUARIOS
-- ============================================================

CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    permisos JSONB DEFAULT '{}',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE areas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol_id INTEGER REFERENCES roles(id),
    area_id INTEGER REFERENCES areas(id),
    activo BOOLEAN DEFAULT TRUE,
    ultimo_acceso TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE auditoria (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER REFERENCES usuarios(id),
    accion VARCHAR(50),       -- crear, editar, eliminar, login, logout
    modulo VARCHAR(50),
    tabla VARCHAR(100),
    registro_id INTEGER,
    datos_anteriores JSONB,
    datos_nuevos JSONB,
    ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE backups (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200),
    archivo VARCHAR(255),
    tamanio BIGINT,
    estado VARCHAR(30) DEFAULT 'completado',
    usuario_id INTEGER REFERENCES usuarios(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- CLIENTES Y CRM
-- ============================================================

CREATE TABLE clientes (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(20) DEFAULT 'empresa',   -- empresa, persona
    razon_social VARCHAR(200) NOT NULL,
    ruc_dni VARCHAR(20),
    contacto_nombre VARCHAR(150),
    email VARCHAR(100),
    telefono VARCHAR(20),
    whatsapp VARCHAR(20),
    direccion TEXT,
    estado_crm VARCHAR(30) DEFAULT 'prospecto', -- prospecto, activo, inactivo
    notas TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE maquinarias (
    id SERIAL PRIMARY KEY,
    cliente_id INTEGER REFERENCES clientes(id),
    codigo VARCHAR(50),
    modelo VARCHAR(100),
    numero_serie VARCHAR(100),
    descripcion TEXT,
    proyecto_id INTEGER,   -- FK se agrega después
    fecha_venta DATE,
    garantia_meses INTEGER DEFAULT 12,
    garantia_hasta DATE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cotizaciones (
    id SERIAL PRIMARY KEY,
    numero VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INTEGER REFERENCES clientes(id),
    estado VARCHAR(30) DEFAULT 'borrador', -- borrador, enviada, aprobada, rechazada, vencida
    subtotal DECIMAL(12,2) DEFAULT 0,
    igv DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    vigencia_dias INTEGER DEFAULT 30,
    fecha_emision DATE DEFAULT CURRENT_DATE,
    fecha_vigencia DATE,
    observaciones TEXT,
    usuario_id INTEGER REFERENCES usuarios(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cotizacion_detalles (
    id SERIAL PRIMARY KEY,
    cotizacion_id INTEGER REFERENCES cotizaciones(id) ON DELETE CASCADE,
    descripcion VARCHAR(200) NOT NULL,
    cantidad DECIMAL(12,3) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL
);

CREATE TABLE garantias (
    id SERIAL PRIMARY KEY,
    maquinaria_id INTEGER REFERENCES maquinarias(id),
    tipo VARCHAR(50),   -- garantia, postventa, mantenimiento
    descripcion TEXT,
    estado VARCHAR(30) DEFAULT 'activa',  -- activa, vencida, cancelada
    fecha_inicio DATE,
    fecha_fin DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE servicios_tecnicos (
    id SERIAL PRIMARY KEY,
    maquinaria_id INTEGER REFERENCES maquinarias(id),
    tipo VARCHAR(50),   -- correctivo, preventivo, garantia
    descripcion TEXT,
    diagnostico TEXT,
    solucion TEXT,
    estado VARCHAR(30) DEFAULT 'pendiente', -- pendiente, en_proceso, completado, cancelado
    tecnico_id INTEGER REFERENCES usuarios(id),
    fecha_solicitud DATE DEFAULT CURRENT_DATE,
    fecha_inicio DATE,
    fecha_fin DATE,
    costo DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- INVENTARIOS
-- ============================================================

CREATE TABLE categorias_producto (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE
);

CREATE TABLE productos (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    categoria_id INTEGER REFERENCES categorias_producto(id),
    unidad VARCHAR(50) DEFAULT 'unidad',
    stock_actual DECIMAL(12,3) DEFAULT 0,
    stock_minimo DECIMAL(12,3) DEFAULT 0,
    stock_maximo DECIMAL(12,3) DEFAULT 0,
    precio_promedio DECIMAL(10,2) DEFAULT 0,
    metodo_valuacion VARCHAR(20) DEFAULT 'promedio', -- promedio, fifo
    es_repuesto BOOLEAN DEFAULT FALSE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lotes_producto (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER REFERENCES productos(id),
    numero_lote VARCHAR(50),
    cantidad_inicial DECIMAL(12,3) NOT NULL,
    cantidad_actual DECIMAL(12,3) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    fecha_ingreso DATE DEFAULT CURRENT_DATE,
    recepcion_id INTEGER,  -- FK se agrega después
    activo BOOLEAN DEFAULT TRUE
);

CREATE TABLE kardex (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER REFERENCES productos(id),
    tipo VARCHAR(20) NOT NULL,          -- entrada, salida, ajuste
    referencia_tipo VARCHAR(50),        -- recepcion, bom_proyecto, ajuste_manual
    referencia_id INTEGER,
    proyecto_id INTEGER,                -- FK se agrega después
    cantidad DECIMAL(12,3) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    saldo_cantidad DECIMAL(12,3) NOT NULL,
    saldo_valor DECIMAL(14,2) NOT NULL,
    usuario_id INTEGER REFERENCES usuarios(id),
    observaciones TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- PROVEEDORES Y COMPRAS
-- ============================================================

CREATE TABLE proveedores (
    id SERIAL PRIMARY KEY,
    razon_social VARCHAR(200) NOT NULL,
    ruc VARCHAR(20),
    contacto_nombre VARCHAR(150),
    email VARCHAR(100),
    telefono VARCHAR(20),
    direccion TEXT,
    calificacion DECIMAL(3,1) DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE proveedor_productos (
    id SERIAL PRIMARY KEY,
    proveedor_id INTEGER REFERENCES proveedores(id),
    producto_id INTEGER REFERENCES productos(id),
    precio_referencial DECIMAL(10,2),
    tiempo_entrega_dias INTEGER,
    UNIQUE(proveedor_id, producto_id)
);

CREATE TABLE solicitudes_compra (
    id SERIAL PRIMARY KEY,
    numero VARCHAR(20) UNIQUE NOT NULL,
    proyecto_id INTEGER,               -- FK se agrega después
    tipo VARCHAR(30) DEFAULT 'manual', -- manual, automatica_bom, automatica_stock
    solicitante_id INTEGER REFERENCES usuarios(id),
    estado VARCHAR(30) DEFAULT 'pendiente', -- pendiente, aprobada, rechazada, procesada
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE solicitud_detalles (
    id SERIAL PRIMARY KEY,
    solicitud_id INTEGER REFERENCES solicitudes_compra(id) ON DELETE CASCADE,
    producto_id INTEGER REFERENCES productos(id),
    cantidad DECIMAL(12,3) NOT NULL,
    precio_referencial DECIMAL(10,2),
    justificacion TEXT
);

CREATE TABLE ordenes_compra (
    id SERIAL PRIMARY KEY,
    numero VARCHAR(20) UNIQUE NOT NULL,
    proveedor_id INTEGER REFERENCES proveedores(id),
    solicitud_id INTEGER REFERENCES solicitudes_compra(id),
    estado VARCHAR(30) DEFAULT 'emitida', -- emitida, enviada, recibida_parcial, recibida, anulada
    fecha_emision DATE DEFAULT CURRENT_DATE,
    fecha_entrega_estimada DATE,
    subtotal DECIMAL(12,2) DEFAULT 0,
    igv DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    observaciones TEXT,
    usuario_id INTEGER REFERENCES usuarios(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE oc_detalles (
    id SERIAL PRIMARY KEY,
    oc_id INTEGER REFERENCES ordenes_compra(id) ON DELETE CASCADE,
    producto_id INTEGER REFERENCES productos(id),
    cantidad DECIMAL(12,3) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL
);

CREATE TABLE recepciones (
    id SERIAL PRIMARY KEY,
    numero VARCHAR(20) UNIQUE NOT NULL,
    oc_id INTEGER REFERENCES ordenes_compra(id),
    fecha DATE DEFAULT CURRENT_DATE,
    usuario_id INTEGER REFERENCES usuarios(id),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE recepcion_detalles (
    id SERIAL PRIMARY KEY,
    recepcion_id INTEGER REFERENCES recepciones(id) ON DELETE CASCADE,
    oc_detalle_id INTEGER REFERENCES oc_detalles(id),
    cantidad_recibida DECIMAL(12,3) NOT NULL,
    estado VARCHAR(30) DEFAULT 'conforme' -- conforme, disconforme, devuelto
);

CREATE TABLE evaluaciones_proveedor (
    id SERIAL PRIMARY KEY,
    proveedor_id INTEGER REFERENCES proveedores(id),
    oc_id INTEGER REFERENCES ordenes_compra(id),
    calidad INTEGER CHECK(calidad BETWEEN 1 AND 5),
    tiempo_entrega INTEGER CHECK(tiempo_entrega BETWEEN 1 AND 5),
    precio INTEGER CHECK(precio BETWEEN 1 AND 5),
    servicio INTEGER CHECK(servicio BETWEEN 1 AND 5),
    comentario TEXT,
    evaluador_id INTEGER REFERENCES usuarios(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- PRODUCCIÓN
-- ============================================================

CREATE TABLE proyectos (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(30) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    cliente_id INTEGER REFERENCES clientes(id),
    estado VARCHAR(30) DEFAULT 'fabricacion', -- fabricacion, pruebas, entrega, completado, cancelado
    prioridad VARCHAR(20) DEFAULT 'normal',   -- baja, normal, alta, urgente
    fecha_inicio DATE,
    fecha_entrega_estimada DATE,
    fecha_entrega_real DATE,
    costo_estimado DECIMAL(12,2) DEFAULT 0,
    costo_real DECIMAL(12,2) DEFAULT 0,
    porcentaje_avance INTEGER DEFAULT 0,
    responsable_id INTEGER REFERENCES usuarios(id),
    observaciones TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agregar FK diferida a maquinarias
ALTER TABLE maquinarias ADD CONSTRAINT fk_maquinaria_proyecto
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id);

CREATE TABLE proyecto_bom (
    id SERIAL PRIMARY KEY,
    proyecto_id INTEGER REFERENCES proyectos(id) ON DELETE CASCADE,
    producto_id INTEGER REFERENCES productos(id),
    cantidad_planificada DECIMAL(12,3) NOT NULL,
    cantidad_real DECIMAL(12,3) DEFAULT 0,
    costo_unitario_estimado DECIMAL(10,2) DEFAULT 0,
    costo_unitario_real DECIMAL(10,2) DEFAULT 0,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE proyecto_avances (
    id SERIAL PRIMARY KEY,
    proyecto_id INTEGER REFERENCES proyectos(id) ON DELETE CASCADE,
    area_id INTEGER REFERENCES areas(id),
    usuario_id INTEGER REFERENCES usuarios(id),
    descripcion TEXT NOT NULL,
    porcentaje INTEGER DEFAULT 0,
    evidencia VARCHAR(255),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE proyecto_costos (
    id SERIAL PRIMARY KEY,
    proyecto_id INTEGER REFERENCES proyectos(id) ON DELETE CASCADE,
    tipo VARCHAR(50),   -- mano_obra, material, overhead, otros
    descripcion VARCHAR(200),
    monto DECIMAL(10,2) NOT NULL,
    fecha DATE DEFAULT CURRENT_DATE,
    usuario_id INTEGER REFERENCES usuarios(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cerrar FK diferida de kardex y solicitudes
ALTER TABLE kardex ADD CONSTRAINT fk_kardex_proyecto
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id);
ALTER TABLE solicitudes_compra ADD CONSTRAINT fk_sc_proyecto
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id);
ALTER TABLE lotes_producto ADD CONSTRAINT fk_lote_recepcion
    FOREIGN KEY (recepcion_id) REFERENCES recepciones(id);

-- ============================================================
-- ALERTAS
-- ============================================================

CREATE TABLE alertas (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(50),           -- retraso_proyecto, stock_minimo, garantia_vence, etc.
    referencia_tipo VARCHAR(50),
    referencia_id INTEGER,
    mensaje TEXT,
    leida BOOLEAN DEFAULT FALSE,
    usuario_id INTEGER REFERENCES usuarios(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- COMERCIALIZACIÓN DIGITAL
-- ============================================================

CREATE TABLE catalogo_productos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    descripcion_corta VARCHAR(500),
    categoria VARCHAR(100),
    precio_referencial DECIMAL(10,2),
    imagen_principal VARCHAR(255),
    imagenes JSONB DEFAULT '[]',
    publicado BOOLEAN DEFAULT FALSE,
    destacado BOOLEAN DEFAULT FALSE,
    orden INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE leads (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(150),
    email VARCHAR(100),
    telefono VARCHAR(20),
    empresa VARCHAR(150),
    origen VARCHAR(50),  -- web, facebook, instagram, tiktok, youtube, directo
    mensaje TEXT,
    estado VARCHAR(30) DEFAULT 'nuevo', -- nuevo, contactado, calificado, convertido, descartado
    cliente_id INTEGER REFERENCES clientes(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- CAPACITACIÓN
-- ============================================================

CREATE TABLE materiales_capacitacion (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    tipo VARCHAR(30),   -- pdf, video, texto, enlace
    archivo VARCHAR(255),
    url VARCHAR(500),
    categoria VARCHAR(100),
    rol_id INTEGER REFERENCES roles(id),
    area_id INTEGER REFERENCES areas(id),
    orden INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE progreso_capacitacion (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER REFERENCES usuarios(id),
    material_id INTEGER REFERENCES materiales_capacitacion(id),
    completado BOOLEAN DEFAULT FALSE,
    fecha_completado TIMESTAMP,
    UNIQUE(usuario_id, material_id)
);

-- ============================================================
-- DATOS INICIALES
-- ============================================================

INSERT INTO roles (nombre, descripcion, permisos) VALUES
('Administrador',    'Acceso completo al sistema',              '{"all": true}'),
('Producción',       'Gestión de proyectos y producción',       '{"produccion": true, "inventarios": "lectura"}'),
('Compras',          'Gestión de compras y proveedores',        '{"compras": true, "inventarios": true}'),
('Ventas',           'Gestión de clientes y cotizaciones',      '{"clientes": true, "catalogo": true}'),
('Almacén',          'Control de inventarios',                  '{"inventarios": true}'),
('Técnico',          'Servicio técnico y producción',           '{"produccion": "lectura", "clientes": "lectura"}');

INSERT INTO areas (nombre, descripcion) VALUES
('Administración',      'Área administrativa y gerencia'),
('Soldadura',           'Área de soldadura y metalmecánica'),
('Pintura',             'Área de pintura y acabados'),
('Control de Calidad',  'Área de control y aseguramiento de calidad'),
('Ensamble',            'Área de ensamble y montaje'),
('Almacén',             'Área de almacén e inventarios');

-- Contraseña: admin123 (hash bcrypt)
INSERT INTO usuarios (nombre, email, password, rol_id, area_id) VALUES
('Administrador', 'admin@industria-mg.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);

INSERT INTO categorias_producto (nombre, descripcion) VALUES
('Acero y Metales',          'Planchas, perfiles, tubos y materiales metálicos'),
('Insumos Eléctricos',       'Cables, conectores, tableros y componentes eléctricos'),
('Pinturas y Acabados',      'Pinturas, solventes y materiales de acabado'),
('Herramientas',             'Herramientas manuales y eléctricas'),
('Consumibles Soldadura',    'Electrodos, alambre MIG, gases y consumibles'),
('Repuestos',                'Repuestos para maquinaria vendida'),
('EPP',                      'Equipos de protección personal');

INSERT INTO clientes (tipo, razon_social, ruc_dni, contacto_nombre, email, telefono, estado_crm) VALUES
('empresa', 'Agroindustrial del Norte S.A.C.', '20480123456', 'Roberto Sánchez', 'rsanchez@agronorte.com', '987001001', 'activo'),
('empresa', 'Minera Los Andes E.I.R.L.',       '20501234567', 'Carmen Villanueva', 'cvillanueva@losandes.com', '987002002', 'activo'),
('empresa', 'Constructora Pacífico S.A.',       '20456789012', 'Miguel Torres', 'mtorres@pacifico.com', '987003003', 'prospecto');

INSERT INTO proveedores (razon_social, ruc, contacto_nombre, email, telefono, calificacion) VALUES
('Aceros Arequipa S.A.',       '20100007024', 'Juan Pérez',    'jperez@acerosarequipa.com',   '054-123456', 4.5),
('Indeco S.A.',                '20100065970', 'Ana Gómez',     'agomez@indeco.com.pe',         '01-2345678', 4.2),
('Sherwin Williams Perú S.A.', '20112273922', 'Luis Quispe',   'lquispe@sherwin.com.pe',       '01-3456789', 4.8);

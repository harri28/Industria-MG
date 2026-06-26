-- ============================================================
-- Migración 005: Tablas de autenticación + usuario admin
-- Ejecutar: "C:/Program Files/PostgreSQL/17/bin/psql.exe" -U postgres -d industria_mg -f database/migrations/005_auth.sql
-- ============================================================

-- Crear tablas si no existen
CREATE TABLE IF NOT EXISTS roles (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    descripcion TEXT,
    permisos    JSONB DEFAULT '{}',
    activo      BOOLEAN DEFAULT TRUE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS areas (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo      BOOLEAN DEFAULT TRUE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuarios (
    id           SERIAL PRIMARY KEY,
    nombre       VARCHAR(150) NOT NULL,
    email        VARCHAR(100) UNIQUE NOT NULL,
    password     VARCHAR(255) NOT NULL,
    rol_id       INTEGER REFERENCES roles(id),
    area_id      INTEGER REFERENCES areas(id),
    activo       BOOLEAN DEFAULT TRUE,
    ultimo_acceso TIMESTAMP,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS auditoria (
    id           SERIAL PRIMARY KEY,
    usuario_id   INTEGER REFERENCES usuarios(id),
    accion       VARCHAR(50),
    modulo       VARCHAR(50),
    tabla        VARCHAR(100),
    registro_id  INTEGER,
    datos_anteriores JSONB,
    datos_nuevos     JSONB,
    ip           VARCHAR(45),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rol administrador
INSERT INTO roles (nombre, descripcion, permisos)
SELECT 'Administrador', 'Acceso completo al sistema', '{"all": true}'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE nombre = 'Administrador');

INSERT INTO roles (nombre, descripcion, permisos)
SELECT 'Producción', 'Gestión de proyectos y producción', '{"produccion": true, "inventarios": "lectura"}'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE nombre = 'Producción');

INSERT INTO roles (nombre, descripcion, permisos)
SELECT 'Compras', 'Gestión de compras y proveedores', '{"compras": true, "inventarios": true}'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE nombre = 'Compras');

INSERT INTO roles (nombre, descripcion, permisos)
SELECT 'Almacén', 'Control de inventarios', '{"inventarios": true}'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE nombre = 'Almacén');

-- Área administración
INSERT INTO areas (nombre, descripcion)
SELECT 'Administración', 'Área administrativa y gerencia'
WHERE NOT EXISTS (SELECT 1 FROM areas WHERE nombre = 'Administración');

-- Admin: email=admin  password=admin123
-- Hash generado con password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO usuarios (nombre, email, password, rol_id, area_id)
SELECT 'Administrador',
       'admin',
       '$2y$10$EjcCH/A8h8ig0HFf9VvHXeZWvLtuvSO8xRPnkBYg8OsPS1BeKFK0q',
       (SELECT id FROM roles WHERE nombre = 'Administrador' LIMIT 1),
       (SELECT id FROM areas  WHERE nombre = 'Administración' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE email = 'admin');

-- Si ya existía el admin con email antiguo, actualizar contraseña
UPDATE usuarios
SET password = '$2y$10$EjcCH/A8h8ig0HFf9VvHXeZWvLtuvSO8xRPnkBYg8OsPS1BeKFK0q',
    activo   = TRUE
WHERE email IN ('admin@industria-mg.com', 'admin');

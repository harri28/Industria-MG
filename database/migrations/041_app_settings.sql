-- Migration 041: Ajustes de aplicacion (clave/valor) para la pestana Personalizacion.
-- Guarda preferencias globales de UI. Primer ajuste: color de fondo del sidebar.

CREATE TABLE IF NOT EXISTS app_settings (
    clave      VARCHAR(60) PRIMARY KEY,
    valor      TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO app_settings (clave, valor) VALUES
    ('sidebar_bg',      '#ffffff'),
    ('login_bg',        'assets/iconos/fondo.png'),
    ('login_logo',      'assets/iconos/logo.jpeg'),
    ('login_nombre',    'IndustriaMG'),
    ('login_subtitulo', 'Sistema de Gestion Industrial')
ON CONFLICT (clave) DO NOTHING;

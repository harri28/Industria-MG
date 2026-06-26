-- Módulo Capacitación: exámenes, preguntas, opciones, intentos y respuestas

CREATE TABLE IF NOT EXISTS examenes (
    id              SERIAL PRIMARY KEY,
    material_id     INTEGER NOT NULL REFERENCES materiales_capacitacion(id) ON DELETE CASCADE,
    titulo          VARCHAR(200) NOT NULL,
    puntaje_minimo  INTEGER NOT NULL DEFAULT 70,
    activo          BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS preguntas_examen (
    id          SERIAL PRIMARY KEY,
    examen_id   INTEGER NOT NULL REFERENCES examenes(id) ON DELETE CASCADE,
    texto       TEXT NOT NULL,
    orden       INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS opciones_pregunta (
    id          SERIAL PRIMARY KEY,
    pregunta_id INTEGER NOT NULL REFERENCES preguntas_examen(id) ON DELETE CASCADE,
    texto       TEXT NOT NULL,
    es_correcta BOOLEAN NOT NULL DEFAULT FALSE,
    orden       INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS intentos_examen (
    id           SERIAL PRIMARY KEY,
    examen_id    INTEGER NOT NULL REFERENCES examenes(id),
    usuario_id   INTEGER NOT NULL REFERENCES usuarios(id),
    puntaje      INTEGER,
    aprobado     BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_inicio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin    TIMESTAMP
);

CREATE TABLE IF NOT EXISTS respuestas_intento (
    id          SERIAL PRIMARY KEY,
    intento_id  INTEGER NOT NULL REFERENCES intentos_examen(id) ON DELETE CASCADE,
    pregunta_id INTEGER NOT NULL REFERENCES preguntas_examen(id),
    opcion_id   INTEGER REFERENCES opciones_pregunta(id)
);

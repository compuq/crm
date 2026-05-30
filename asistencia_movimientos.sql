CREATE TABLE asistencia_movimientos (
    id BIGSERIAL PRIMARY KEY,

    usuario_id BIGINT NOT NULL,

    tipo_movimiento VARCHAR(20) NOT NULL
        CHECK (tipo_movimiento IN ('entrada', 'salida')),

    motivo VARCHAR(20) NOT NULL
        CHECK (motivo IN (
            'laboral',
            'almuerzo',
            'refaccion',
            'permiso',
            'otro'
        )),

    comentario TEXT NULL,

    fecha_hora TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    CONSTRAINT fk_asistencia_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
);
CREATE INDEX idx_asistencia_usuario
ON asistencia_movimientos(usuario_id);

CREATE INDEX idx_asistencia_fecha
ON asistencia_movimientos(fecha_hora);

CREATE INDEX idx_asistencia_usuario_fecha
ON asistencia_movimientos(usuario_id, fecha_hora);
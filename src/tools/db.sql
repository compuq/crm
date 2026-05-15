-- ==========================================
-- LEX 360 CRM - Estructura de Base de Datos (v2)
-- Motor: PostgreSQL 14+
-- ==========================================

-- Habilitar extensiones útiles
CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS "unaccent";

-- ==========================================
-- 1. TABLAS MAESTRAS (CONFIGURACIÓN)
-- ==========================================

CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    clave_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(30) NOT NULL CHECK (rol IN ('gestor', 'supervisor', 'supervisor_general', 'admin')),
    supervisor_id INT REFERENCES usuarios(id),
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMPTZ DEFAULT NOW(),
    fecha_ultimo_login TIMESTAMPTZ
);

CREATE TABLE carteras (
    id SERIAL PRIMARY KEY,
    nombre_cartera VARCHAR(100) NOT NULL,
    cuenta_nombre VARCHAR(50),
    identificacion_nombre VARCHAR(50),
    nombre_cliente_label VARCHAR(50),
    saldo_label VARCHAR(50),
    activa BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMPTZ DEFAULT NOW()
);

CREATE TABLE extras (
    id SERIAL PRIMARY KEY,
    id_cartera INT REFERENCES carteras(id) ON DELETE CASCADE,
    nombre_campo VARCHAR(50) NOT NULL,
    etiqueta_display VARCHAR(100) NOT NULL,
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('dato', 'telefono', 'fecha', 'moneda')),
    orden_visual INT DEFAULT 0
);

CREATE TABLE tipologias (
    id SERIAL PRIMARY KEY,
    clase CHAR(1) NOT NULL CHECK (clase IN ('T', 'S')),
    padre_id INT REFERENCES tipologias(id),
    nombre VARCHAR(100) NOT NULL,
    codigo_origen VARCHAR(20)
);

-- ==========================================
-- 2. LOTES (Movida ANTES de historial para evitar error 42P01)
-- ==========================================

CREATE TABLE lotes (
    id SERIAL PRIMARY KEY,
    fecha_ejecucion TIMESTAMPTZ DEFAULT NOW(),
    usuario_id INT REFERENCES usuarios(id),
    tipo_operacion VARCHAR(20),
    cantidad_registros INT DEFAULT 0,
    observaciones TEXT,
    estado VARCHAR(20) DEFAULT 'completado'
);

-- ==========================================
-- 3. TABLAS OPERATIVAS (DATOS REALES)
-- ==========================================

CREATE TABLE clientes (
    id SERIAL PRIMARY KEY,
    id_cartera INT REFERENCES carteras(id),
    id_gestor_asignado INT REFERENCES usuarios(id),
    id_supervisor_cadena INT REFERENCES usuarios(id),
    
    cuenta VARCHAR(100), 
    identificacion VARCHAR(50),
    nombre VARCHAR(200),
    saldo NUMERIC(15, 2) DEFAULT 0.00,
    
    telefono_1 VARCHAR(20),
    telefono_2 VARCHAR(20),
    
    estado VARCHAR(20) DEFAULT 'activo' CHECK (estado IN ('activo', 'moroso', 'pagado', 'historico')),
    fecha_ultima_gestion TIMESTAMPTZ,
    fecha_asignacion TIMESTAMPTZ DEFAULT NOW(),
    
    search_vector TSVECTOR
);

CREATE TRIGGER tsvector_update_clientes
BEFORE INSERT OR UPDATE ON clientes
FOR EACH ROW EXECUTE FUNCTION tsvector_update_trigger(
    'search_vector', 'pg_catalog.spanish', 'nombre', 'identificacion', 'cuenta', 'telefono_1'
);

CREATE INDEX idx_clientes_cartera ON clientes(id_cartera);
CREATE INDEX idx_clientes_gestor ON clientes(id_gestor_asignado);
CREATE INDEX idx_clientes_supervisor ON clientes(id_supervisor_cadena);
CREATE INDEX idx_clientes_search ON clientes USING GIN(search_vector);
CREATE INDEX idx_clientes_identificacion ON clientes(identificacion);

CREATE TABLE data_extras (
    id SERIAL PRIMARY KEY,
    id_cliente INT REFERENCES clientes(id) ON DELETE CASCADE,
    id_extra INT REFERENCES extras(id),
    valor TEXT,
    UNIQUE(id_cliente, id_extra)
);

-- ==========================================
-- 4. GESTIÓN, HISTORIAL Y PAGOS
-- ==========================================

CREATE TABLE historial (
    id SERIAL PRIMARY KEY,
    id_cliente INT REFERENCES clientes(id),
    id_usuario INT REFERENCES usuarios(id),
    fecha_gestion TIMESTAMPTZ DEFAULT NOW(),
    
    estatus VARCHAR(4) NOT NULL CHECK (estatus IN ('SINC', 'COMP', 'PAGG', 'PAGO')),
    telefono_utilizado VARCHAR(20),
    id_tipologia INT REFERENCES tipologias(id),
    comentario TEXT NOT NULL,
    lote_origen_id INT REFERENCES lotes(id) -- ✅ Ahora lotes ya existe
);

CREATE TABLE extras_historial (
    id SERIAL PRIMARY KEY,
    id_historial INT REFERENCES historial(id) ON DELETE CASCADE,
    nombre_campo VARCHAR(50),
    valor TEXT
);

CREATE TABLE promesas (
    id SERIAL PRIMARY KEY,
    id_cliente INT REFERENCES clientes(id),
    id_usuario INT REFERENCES usuarios(id),
    monto_prometido NUMERIC(15, 2),
    fecha_compromiso DATE,
    fecha_registro TIMESTAMPTZ DEFAULT NOW(),
    estatus VARCHAR(20) DEFAULT 'pendiente' CHECK (estatus IN ('pendiente', 'cumplida', 'incumplida'))
);

CREATE TABLE pagos (
    id SERIAL PRIMARY KEY,
    id_cliente INT REFERENCES clientes(id),
    monto NUMERIC(15, 2),
    fecha_pago DATE,
    referencia_bancaria VARCHAR(100),
    estatus VARCHAR(4) CHECK (estatus IN ('PAGG', 'PAGO')),
    validado_por INT REFERENCES usuarios(id),
    fecha_validacion TIMESTAMPTZ
);

-- ==========================================
-- 5. BACKUP Y AUDITORÍA
-- ==========================================

CREATE TABLE clientes_bk (
    id_bk SERIAL PRIMARY KEY,
    id_original INT,
    lote_id INT REFERENCES lotes(id),
    fecha_migracion TIMESTAMPTZ DEFAULT NOW(),
    id_cartera INT,
    id_gestor_asignado INT,
    id_supervisor_cadena INT,
    cuenta VARCHAR(100),
    identificacion VARCHAR(50),
    nombre VARCHAR(200),
    saldo NUMERIC(15, 2),
    telefono_1 VARCHAR(20),
    telefono_2 VARCHAR(20),
    estado VARCHAR(20),
    fecha_ultima_gestion TIMESTAMPTZ
);

CREATE TABLE historial_bk (
    id_bk SERIAL PRIMARY KEY,
    id_original INT,
    lote_id INT REFERENCES lotes(id),
    fecha_migracion TIMESTAMPTZ DEFAULT NOW(),
    id_cliente INT,
    id_usuario INT,
    fecha_gestion TIMESTAMPTZ,
    estatus VARCHAR(4),
    telefono_utilizado VARCHAR(20),
    id_tipologia INT,
    comentario TEXT
);

CREATE TABLE logs_auditoria (
    id SERIAL PRIMARY KEY,
    usuario_id INT REFERENCES usuarios(id),
    accion VARCHAR(50),
    tabla_afectada VARCHAR(50),
    registro_id INT,
    ip VARCHAR(45),
    datos_anteriores JSONB,
    datos_nuevos JSONB,
    fecha TIMESTAMP DEFAULT NOW()
);

CREATE TABLE logs_asistencia (
    id SERIAL PRIMARY KEY,
    usuario_id INT REFERENCES usuarios(id),
    entrada TIMESTAMPTZ,
    salida TIMESTAMPTZ,
    horas_trabajadas NUMERIC(5, 2),
    fecha DATE DEFAULT CURRENT_DATE
);

-- ==========================================
-- 6. DATOS INICIALES (SEED)
-- ==========================================

-- ⚠️ Cambia este hash por uno generado con password_hash('tu_clave_segura', PASSWORD_DEFAULT) en PHP
INSERT INTO usuarios (nombre, usuario, clave_hash, rol, activo) VALUES 
('Administrador General', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', true);

INSERT INTO carteras (nombre_cartera, cuenta_nombre, identificacion_nombre, nombre_cliente_label, saldo_label) VALUES
('Cartera Demo', 'Tarjeta', 'DPI', 'Deudor', 'Deuda Total');
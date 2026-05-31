--
-- PostgreSQL database dump
--

-- Dumped from database version 17.2
-- Dumped by pg_dump version 17.2

-- Started on 2026-05-30 22:38:12

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 2 (class 3079 OID 24933)
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- TOC entry 5172 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- TOC entry 3 (class 3079 OID 24970)
-- Name: unaccent; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS unaccent WITH SCHEMA public;


--
-- TOC entry 5173 (class 0 OID 0)
-- Dependencies: 3
-- Name: EXTENSION unaccent; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION unaccent IS 'text search dictionary that removes accents';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 256 (class 1259 OID 65580)
-- Name: asistencia_movimientos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asistencia_movimientos (
    id bigint NOT NULL,
    usuario_id bigint NOT NULL,
    tipo_movimiento character varying(20) NOT NULL,
    motivo character varying(20) NOT NULL,
    comentario text,
    fecha_hora timestamp with time zone DEFAULT now() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT asistencia_movimientos_motivo_check CHECK (((motivo)::text = ANY ((ARRAY['laboral'::character varying, 'almuerzo'::character varying, 'refaccion'::character varying, 'permiso'::character varying, 'otro'::character varying])::text[]))),
    CONSTRAINT asistencia_movimientos_tipo_movimiento_check CHECK (((tipo_movimiento)::text = ANY ((ARRAY['entrada'::character varying, 'salida'::character varying])::text[])))
);


ALTER TABLE public.asistencia_movimientos OWNER TO postgres;

--
-- TOC entry 255 (class 1259 OID 65579)
-- Name: asistencia_movimientos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asistencia_movimientos_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asistencia_movimientos_id_seq OWNER TO postgres;

--
-- TOC entry 5174 (class 0 OID 0)
-- Dependencies: 255
-- Name: asistencia_movimientos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asistencia_movimientos_id_seq OWNED BY public.asistencia_movimientos.id;


--
-- TOC entry 222 (class 1259 OID 24995)
-- Name: carteras; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.carteras (
    id integer NOT NULL,
    nombre_cartera character varying(100) NOT NULL,
    cuenta_nombre character varying(50),
    identificacion_nombre character varying(50),
    nombre_cliente_label character varying(50),
    saldo_label character varying(50),
    activa boolean DEFAULT true,
    fecha_creacion timestamp with time zone DEFAULT now(),
    lbl_nombre character varying(50) DEFAULT 'Nombre'::character varying,
    lbl_saldo character varying(50) DEFAULT 'Saldo'::character varying,
    lbl_telefono character varying(50) DEFAULT 'Teléfono'::character varying,
    lbl_estado character varying(50) DEFAULT 'Estado'::character varying
);


ALTER TABLE public.carteras OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 24994)
-- Name: carteras_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.carteras_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.carteras_id_seq OWNER TO postgres;

--
-- TOC entry 5175 (class 0 OID 0)
-- Dependencies: 221
-- Name: carteras_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.carteras_id_seq OWNED BY public.carteras.id;


--
-- TOC entry 230 (class 1259 OID 25048)
-- Name: clientes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.clientes (
    id integer NOT NULL,
    id_cartera integer,
    id_gestor_asignado integer,
    id_supervisor_cadena integer,
    cuenta character varying(100),
    identificacion character varying(50),
    nombre character varying(200),
    saldo numeric(15,2) DEFAULT 0.00,
    telefono_1 character varying(20),
    telefono_2 character varying(20),
    estado character varying(20) DEFAULT 'activo'::character varying,
    fecha_ultima_gestion timestamp with time zone,
    fecha_asignacion timestamp with time zone DEFAULT now(),
    search_vector tsvector,
    data_extras jsonb DEFAULT '{}'::jsonb,
    fecha_actualizacion timestamp without time zone,
    saldo_inicial numeric(15,2),
    CONSTRAINT clientes_estado_check CHECK (((estado)::text = ANY ((ARRAY['activo'::character varying, 'moroso'::character varying, 'pagado'::character varying, 'historico'::character varying])::text[])))
);


ALTER TABLE public.clientes OWNER TO postgres;

--
-- TOC entry 242 (class 1259 OID 25186)
-- Name: clientes_bk; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.clientes_bk (
    id_bk integer NOT NULL,
    id_original integer,
    lote_id integer,
    fecha_migracion timestamp with time zone DEFAULT now(),
    id_cartera integer,
    id_gestor_asignado integer,
    id_supervisor_cadena integer,
    cuenta character varying(100),
    identificacion character varying(50),
    nombre character varying(200),
    saldo numeric(15,2),
    telefono_1 character varying(20),
    telefono_2 character varying(20),
    estado character varying(20),
    fecha_ultima_gestion timestamp with time zone,
    data_extras jsonb DEFAULT '{}'::jsonb,
    saldo_inicial numeric(15,2)
);


ALTER TABLE public.clientes_bk OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 25185)
-- Name: clientes_bk_id_bk_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.clientes_bk_id_bk_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.clientes_bk_id_bk_seq OWNER TO postgres;

--
-- TOC entry 5176 (class 0 OID 0)
-- Dependencies: 241
-- Name: clientes_bk_id_bk_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.clientes_bk_id_bk_seq OWNED BY public.clientes_bk.id_bk;


--
-- TOC entry 229 (class 1259 OID 25047)
-- Name: clientes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.clientes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.clientes_id_seq OWNER TO postgres;

--
-- TOC entry 5177 (class 0 OID 0)
-- Dependencies: 229
-- Name: clientes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.clientes_id_seq OWNED BY public.clientes.id;


--
-- TOC entry 232 (class 1259 OID 25082)
-- Name: data_extras; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.data_extras (
    id integer NOT NULL,
    id_cliente integer,
    id_extra integer,
    valor text
);


ALTER TABLE public.data_extras OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 25081)
-- Name: data_extras_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.data_extras_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.data_extras_id_seq OWNER TO postgres;

--
-- TOC entry 5178 (class 0 OID 0)
-- Dependencies: 231
-- Name: data_extras_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.data_extras_id_seq OWNED BY public.data_extras.id;


--
-- TOC entry 224 (class 1259 OID 25004)
-- Name: extras; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.extras (
    id integer NOT NULL,
    id_cartera integer,
    nombre_campo character varying(50) NOT NULL,
    etiqueta_display character varying(100) NOT NULL,
    tipo character varying(20) NOT NULL,
    orden_visual integer DEFAULT 0,
    CONSTRAINT extras_tipo_check CHECK (((tipo)::text = ANY ((ARRAY['dato'::character varying, 'telefono'::character varying, 'fecha'::character varying, 'moneda'::character varying])::text[])))
);


ALTER TABLE public.extras OWNER TO postgres;

--
-- TOC entry 250 (class 1259 OID 32777)
-- Name: extras_cartera; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.extras_cartera (
    id integer NOT NULL,
    id_cartera integer NOT NULL,
    nombre_campo character varying(100) NOT NULL,
    etiqueta character varying(100),
    tipo character varying(50) DEFAULT 'texto'::character varying,
    orden integer DEFAULT 0,
    activo boolean DEFAULT true,
    modulo character varying(20) DEFAULT 'clientes'::character varying
);


ALTER TABLE public.extras_cartera OWNER TO postgres;

--
-- TOC entry 249 (class 1259 OID 32776)
-- Name: extras_cartera_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.extras_cartera_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.extras_cartera_id_seq OWNER TO postgres;

--
-- TOC entry 5179 (class 0 OID 0)
-- Dependencies: 249
-- Name: extras_cartera_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.extras_cartera_id_seq OWNED BY public.extras_cartera.id;


--
-- TOC entry 236 (class 1259 OID 25134)
-- Name: extras_historial; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.extras_historial (
    id integer NOT NULL,
    id_historial integer,
    nombre_campo character varying(50),
    valor text
);


ALTER TABLE public.extras_historial OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 25133)
-- Name: extras_historial_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.extras_historial_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.extras_historial_id_seq OWNER TO postgres;

--
-- TOC entry 5180 (class 0 OID 0)
-- Dependencies: 235
-- Name: extras_historial_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.extras_historial_id_seq OWNED BY public.extras_historial.id;


--
-- TOC entry 223 (class 1259 OID 25003)
-- Name: extras_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.extras_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.extras_id_seq OWNER TO postgres;

--
-- TOC entry 5181 (class 0 OID 0)
-- Dependencies: 223
-- Name: extras_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.extras_id_seq OWNED BY public.extras.id;


--
-- TOC entry 234 (class 1259 OID 25103)
-- Name: historial; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.historial (
    id integer NOT NULL,
    id_cliente integer,
    id_usuario integer,
    fecha_gestion timestamp with time zone DEFAULT now(),
    estatus character varying(4) NOT NULL,
    telefono_utilizado character varying(20),
    id_tipologia integer,
    comentario text NOT NULL,
    lote_origen_id integer,
    data_extras jsonb DEFAULT '{}'::jsonb,
    fecha_proxima_llamada timestamp without time zone,
    CONSTRAINT historial_estatus_check CHECK (((estatus)::text = ANY ((ARRAY['SINC'::character varying, 'COMP'::character varying, 'PAGG'::character varying, 'PAGO'::character varying])::text[])))
);


ALTER TABLE public.historial OWNER TO postgres;

--
-- TOC entry 244 (class 1259 OID 25199)
-- Name: historial_bk; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.historial_bk (
    id_bk integer NOT NULL,
    id_original integer,
    lote_id integer,
    fecha_migracion timestamp with time zone DEFAULT now(),
    id_cliente integer,
    id_usuario integer,
    fecha_gestion timestamp with time zone,
    estatus character varying(4),
    telefono_utilizado character varying(20),
    id_tipologia integer,
    comentario text,
    data_extras jsonb DEFAULT '{}'::jsonb,
    fecha_proxima_llamada timestamp without time zone
);


ALTER TABLE public.historial_bk OWNER TO postgres;

--
-- TOC entry 243 (class 1259 OID 25198)
-- Name: historial_bk_id_bk_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.historial_bk_id_bk_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.historial_bk_id_bk_seq OWNER TO postgres;

--
-- TOC entry 5182 (class 0 OID 0)
-- Dependencies: 243
-- Name: historial_bk_id_bk_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.historial_bk_id_bk_seq OWNED BY public.historial_bk.id_bk;


--
-- TOC entry 233 (class 1259 OID 25102)
-- Name: historial_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.historial_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.historial_id_seq OWNER TO postgres;

--
-- TOC entry 5183 (class 0 OID 0)
-- Dependencies: 233
-- Name: historial_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.historial_id_seq OWNED BY public.historial.id;


--
-- TOC entry 248 (class 1259 OID 25229)
-- Name: logs_asistencia; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.logs_asistencia (
    id integer NOT NULL,
    usuario_id integer,
    entrada timestamp with time zone,
    salida timestamp with time zone,
    horas_trabajadas numeric(5,2),
    fecha date DEFAULT CURRENT_DATE
);


ALTER TABLE public.logs_asistencia OWNER TO postgres;

--
-- TOC entry 247 (class 1259 OID 25228)
-- Name: logs_asistencia_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.logs_asistencia_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.logs_asistencia_id_seq OWNER TO postgres;

--
-- TOC entry 5184 (class 0 OID 0)
-- Dependencies: 247
-- Name: logs_asistencia_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.logs_asistencia_id_seq OWNED BY public.logs_asistencia.id;


--
-- TOC entry 246 (class 1259 OID 25214)
-- Name: logs_auditoria; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.logs_auditoria (
    id integer NOT NULL,
    usuario_id integer,
    accion character varying(50),
    tabla_afectada character varying(50),
    registro_id integer,
    ip character varying(45),
    datos_anteriores jsonb,
    datos_nuevos jsonb,
    fecha timestamp without time zone DEFAULT now()
);


ALTER TABLE public.logs_auditoria OWNER TO postgres;

--
-- TOC entry 245 (class 1259 OID 25213)
-- Name: logs_auditoria_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.logs_auditoria_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.logs_auditoria_id_seq OWNER TO postgres;

--
-- TOC entry 5185 (class 0 OID 0)
-- Dependencies: 245
-- Name: logs_auditoria_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.logs_auditoria_id_seq OWNED BY public.logs_auditoria.id;


--
-- TOC entry 228 (class 1259 OID 25031)
-- Name: lotes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.lotes (
    id integer NOT NULL,
    fecha_ejecucion timestamp with time zone DEFAULT now(),
    usuario_id integer,
    tipo_operacion character varying(20),
    cantidad_registros integer DEFAULT 0,
    observaciones text,
    estado character varying(20) DEFAULT 'completado'::character varying
);


ALTER TABLE public.lotes OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 25030)
-- Name: lotes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.lotes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.lotes_id_seq OWNER TO postgres;

--
-- TOC entry 5186 (class 0 OID 0)
-- Dependencies: 227
-- Name: lotes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.lotes_id_seq OWNED BY public.lotes.id;


--
-- TOC entry 240 (class 1259 OID 25168)
-- Name: pagos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pagos (
    id integer NOT NULL,
    id_cliente integer,
    monto numeric(15,2),
    fecha_pago timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    referencia_bancaria character varying(100),
    estatus character varying(4),
    validado_por integer,
    fecha_validacion timestamp with time zone,
    id_historial integer,
    CONSTRAINT pagos_estatus_check CHECK (((estatus)::text = ANY ((ARRAY['PAGG'::character varying, 'PAGO'::character varying])::text[])))
);


ALTER TABLE public.pagos OWNER TO postgres;

--
-- TOC entry 252 (class 1259 OID 65548)
-- Name: pagos_bk; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pagos_bk (
    id_bk integer NOT NULL,
    id_original integer,
    lote_id integer,
    fecha_migracion timestamp with time zone DEFAULT now(),
    id_cliente integer,
    monto numeric(15,2),
    fecha_pago timestamp without time zone,
    referencia_bancaria character varying(100),
    estatus character varying(4),
    validado_por integer,
    fecha_validacion timestamp with time zone,
    id_historial integer
);


ALTER TABLE public.pagos_bk OWNER TO postgres;

--
-- TOC entry 251 (class 1259 OID 65547)
-- Name: pagos_bk_id_bk_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pagos_bk_id_bk_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pagos_bk_id_bk_seq OWNER TO postgres;

--
-- TOC entry 5187 (class 0 OID 0)
-- Dependencies: 251
-- Name: pagos_bk_id_bk_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pagos_bk_id_bk_seq OWNED BY public.pagos_bk.id_bk;


--
-- TOC entry 239 (class 1259 OID 25167)
-- Name: pagos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pagos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pagos_id_seq OWNER TO postgres;

--
-- TOC entry 5188 (class 0 OID 0)
-- Dependencies: 239
-- Name: pagos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pagos_id_seq OWNED BY public.pagos.id;


--
-- TOC entry 238 (class 1259 OID 25148)
-- Name: promesas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.promesas (
    id integer NOT NULL,
    id_cliente integer,
    id_usuario integer,
    monto_prometido numeric(15,2),
    fecha_compromiso timestamp without time zone,
    fecha_registro timestamp with time zone DEFAULT now(),
    estatus character varying(20) DEFAULT 'pendiente'::character varying,
    id_historial integer,
    CONSTRAINT promesas_estatus_check CHECK (((estatus)::text = ANY ((ARRAY['pendiente'::character varying, 'cumplida'::character varying, 'incumplida'::character varying])::text[])))
);


ALTER TABLE public.promesas OWNER TO postgres;

--
-- TOC entry 254 (class 1259 OID 65556)
-- Name: promesas_bk; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.promesas_bk (
    id_bk integer NOT NULL,
    id_original integer,
    lote_id integer,
    fecha_migracion timestamp with time zone DEFAULT now(),
    id_cliente integer,
    id_usuario integer,
    monto_prometido numeric(15,2),
    fecha_compromiso timestamp without time zone,
    fecha_registro timestamp with time zone,
    estatus character varying(20),
    id_historial integer
);


ALTER TABLE public.promesas_bk OWNER TO postgres;

--
-- TOC entry 253 (class 1259 OID 65555)
-- Name: promesas_bk_id_bk_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.promesas_bk_id_bk_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.promesas_bk_id_bk_seq OWNER TO postgres;

--
-- TOC entry 5189 (class 0 OID 0)
-- Dependencies: 253
-- Name: promesas_bk_id_bk_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.promesas_bk_id_bk_seq OWNED BY public.promesas_bk.id_bk;


--
-- TOC entry 237 (class 1259 OID 25147)
-- Name: promesas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.promesas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.promesas_id_seq OWNER TO postgres;

--
-- TOC entry 5190 (class 0 OID 0)
-- Dependencies: 237
-- Name: promesas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.promesas_id_seq OWNED BY public.promesas.id;


--
-- TOC entry 226 (class 1259 OID 25018)
-- Name: tipologias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tipologias (
    id integer NOT NULL,
    clase character(1) NOT NULL,
    padre_id integer,
    nombre character varying(100) NOT NULL,
    codigo_origen character varying(20),
    id_cartera integer,
    requiere_proxima_fecha boolean DEFAULT false,
    requiere_monto boolean DEFAULT false,
    estatus_default character varying(4) DEFAULT 'SINC'::character varying,
    CONSTRAINT tipologias_clase_check CHECK ((clase = ANY (ARRAY['T'::bpchar, 'S'::bpchar]))),
    CONSTRAINT tipologias_estatus_default_check CHECK (((estatus_default)::text = ANY ((ARRAY['SINC'::character varying, 'COMP'::character varying, 'PAGG'::character varying, 'PAGO'::character varying])::text[])))
);


ALTER TABLE public.tipologias OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 25017)
-- Name: tipologias_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tipologias_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tipologias_id_seq OWNER TO postgres;

--
-- TOC entry 5191 (class 0 OID 0)
-- Dependencies: 225
-- Name: tipologias_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tipologias_id_seq OWNED BY public.tipologias.id;


--
-- TOC entry 220 (class 1259 OID 24978)
-- Name: usuarios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.usuarios (
    id integer NOT NULL,
    nombre character varying(150) NOT NULL,
    usuario character varying(50) NOT NULL,
    clave_hash character varying(255) NOT NULL,
    rol character varying(30) NOT NULL,
    supervisor_id integer,
    activo boolean DEFAULT true,
    fecha_creacion timestamp with time zone DEFAULT now(),
    fecha_ultimo_login timestamp with time zone,
    CONSTRAINT usuarios_rol_check CHECK (((rol)::text = ANY ((ARRAY['gestor'::character varying, 'supervisor'::character varying, 'supervisor_general'::character varying, 'admin'::character varying])::text[])))
);


ALTER TABLE public.usuarios OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 24977)
-- Name: usuarios_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.usuarios_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.usuarios_id_seq OWNER TO postgres;

--
-- TOC entry 5192 (class 0 OID 0)
-- Dependencies: 219
-- Name: usuarios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.usuarios_id_seq OWNED BY public.usuarios.id;


--
-- TOC entry 4883 (class 2604 OID 65583)
-- Name: asistencia_movimientos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asistencia_movimientos ALTER COLUMN id SET DEFAULT nextval('public.asistencia_movimientos_id_seq'::regclass);


--
-- TOC entry 4832 (class 2604 OID 24998)
-- Name: carteras id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.carteras ALTER COLUMN id SET DEFAULT nextval('public.carteras_id_seq'::regclass);


--
-- TOC entry 4849 (class 2604 OID 25051)
-- Name: clientes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes ALTER COLUMN id SET DEFAULT nextval('public.clientes_id_seq'::regclass);


--
-- TOC entry 4864 (class 2604 OID 25189)
-- Name: clientes_bk id_bk; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes_bk ALTER COLUMN id_bk SET DEFAULT nextval('public.clientes_bk_id_bk_seq'::regclass);


--
-- TOC entry 4854 (class 2604 OID 25085)
-- Name: data_extras id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.data_extras ALTER COLUMN id SET DEFAULT nextval('public.data_extras_id_seq'::regclass);


--
-- TOC entry 4839 (class 2604 OID 25007)
-- Name: extras id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras ALTER COLUMN id SET DEFAULT nextval('public.extras_id_seq'::regclass);


--
-- TOC entry 4874 (class 2604 OID 32780)
-- Name: extras_cartera id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_cartera ALTER COLUMN id SET DEFAULT nextval('public.extras_cartera_id_seq'::regclass);


--
-- TOC entry 4858 (class 2604 OID 25137)
-- Name: extras_historial id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_historial ALTER COLUMN id SET DEFAULT nextval('public.extras_historial_id_seq'::regclass);


--
-- TOC entry 4855 (class 2604 OID 25106)
-- Name: historial id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial ALTER COLUMN id SET DEFAULT nextval('public.historial_id_seq'::regclass);


--
-- TOC entry 4867 (class 2604 OID 25202)
-- Name: historial_bk id_bk; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial_bk ALTER COLUMN id_bk SET DEFAULT nextval('public.historial_bk_id_bk_seq'::regclass);


--
-- TOC entry 4872 (class 2604 OID 25232)
-- Name: logs_asistencia id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logs_asistencia ALTER COLUMN id SET DEFAULT nextval('public.logs_asistencia_id_seq'::regclass);


--
-- TOC entry 4870 (class 2604 OID 25217)
-- Name: logs_auditoria id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logs_auditoria ALTER COLUMN id SET DEFAULT nextval('public.logs_auditoria_id_seq'::regclass);


--
-- TOC entry 4845 (class 2604 OID 25034)
-- Name: lotes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lotes ALTER COLUMN id SET DEFAULT nextval('public.lotes_id_seq'::regclass);


--
-- TOC entry 4862 (class 2604 OID 25171)
-- Name: pagos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos ALTER COLUMN id SET DEFAULT nextval('public.pagos_id_seq'::regclass);


--
-- TOC entry 4879 (class 2604 OID 65551)
-- Name: pagos_bk id_bk; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos_bk ALTER COLUMN id_bk SET DEFAULT nextval('public.pagos_bk_id_bk_seq'::regclass);


--
-- TOC entry 4859 (class 2604 OID 25151)
-- Name: promesas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas ALTER COLUMN id SET DEFAULT nextval('public.promesas_id_seq'::regclass);


--
-- TOC entry 4881 (class 2604 OID 65559)
-- Name: promesas_bk id_bk; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas_bk ALTER COLUMN id_bk SET DEFAULT nextval('public.promesas_bk_id_bk_seq'::regclass);


--
-- TOC entry 4841 (class 2604 OID 25021)
-- Name: tipologias id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipologias ALTER COLUMN id SET DEFAULT nextval('public.tipologias_id_seq'::regclass);


--
-- TOC entry 4829 (class 2604 OID 24981)
-- Name: usuarios id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id SET DEFAULT nextval('public.usuarios_id_seq'::regclass);


--
-- TOC entry 5166 (class 0 OID 65580)
-- Dependencies: 256
-- Data for Name: asistencia_movimientos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asistencia_movimientos (id, usuario_id, tipo_movimiento, motivo, comentario, fecha_hora, created_at) FROM stdin;
1	1	entrada	laboral	\N	2026-05-26 23:27:21.178177+00	2026-05-26 23:27:21.178177+00
2	1	salida	otro	ir a la farmacia autoriza jefe regional 2	2026-05-26 23:29:10.431144+00	2026-05-26 23:29:10.431144+00
3	1	entrada	otro	llegada	2026-05-26 23:30:44.883375+00	2026-05-26 23:30:44.883375+00
5	1	salida	refaccion	\N	2026-05-27 03:36:19.446594+00	2026-05-27 03:36:19.446594+00
8	1	entrada	refaccion	\N	2026-05-27 04:43:24.969618+00	2026-05-27 04:43:24.969618+00
9	1	salida	refaccion	\N	2026-05-27 04:43:56.022736+00	2026-05-27 04:43:56.022736+00
10	1	entrada	refaccion	\N	2026-05-27 04:49:55.437613+00	2026-05-27 04:49:55.437613+00
15	1	salida	refaccion	\N	2026-05-27 04:52:01.379506+00	2026-05-27 04:52:01.379506+00
16	1	entrada	refaccion	\N	2026-05-27 04:53:40.707144+00	2026-05-27 04:53:40.707144+00
17	1	salida	permiso	permiso para comprar repuestos según jefe 3	2026-05-27 04:54:03.522767+00	2026-05-27 04:54:03.522767+00
18	1	entrada	permiso	se regresa rápido, ya que estaba lloviendo, saldremos ya sea más tarde o mañana	2026-05-27 04:54:28.295612+00	2026-05-27 04:54:28.295612+00
19	36	entrada	laboral	\N	2026-05-27 04:55:24.534226+00	2026-05-27 04:55:24.534226+00
20	36	entrada	laboral	\N	2026-05-27 11:19:42.415868+00	2026-05-27 11:19:42.415868+00
21	36	salida	refaccion	Permiso del jefe	2026-05-27 11:21:58.168661+00	2026-05-27 11:21:58.168661+00
\.


--
-- TOC entry 5132 (class 0 OID 24995)
-- Dependencies: 222
-- Data for Name: carteras; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.carteras (id, nombre_cartera, cuenta_nombre, identificacion_nombre, nombre_cliente_label, saldo_label, activa, fecha_creacion, lbl_nombre, lbl_saldo, lbl_telefono, lbl_estado) FROM stdin;
34	LEX	ID_Credito	Codigo_Cliente	\N	\N	t	2026-05-25 08:54:12.81393+00	Nombre_Deudor	Saldo_Total_Exigible	Teléfono	Estado
\.


--
-- TOC entry 5140 (class 0 OID 25048)
-- Dependencies: 230
-- Data for Name: clientes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.clientes (id, id_cartera, id_gestor_asignado, id_supervisor_cadena, cuenta, identificacion, nombre, saldo, telefono_1, telefono_2, estado, fecha_ultima_gestion, fecha_asignacion, search_vector, data_extras, fecha_actualizacion, saldo_inicial) FROM stdin;
17	34	38	37	CRE-88555	CLI-002	David Ernesto Santos	35000.00	55556666	\N	activo	2026-05-29 00:28:06.102188+00	2026-05-28 02:54:42.347186+00	'-002':5 '-88555':7 '55556666':8 'cli':4 'cre':6 'dav':1 'ernest':2 'sant':3	{"_oneda": "GTQ", "_______": "3310 12345 0202", "_ias__ora": "60", "_tapa__obro": "Administrativa", "_apital__ora": "6500", "_tros__astos": "0.00", "_echa__raslado": "5/1/2024", "_gencia__rigen": "Agencia Central", "_egmento__roducto": "Microcrédito", "_oleta__eferencia": "B-99821", "_ntereses__oratorios": "50.00", "_ntereses__orrientes": "1050"}	\N	35000.00
16	34	36	34	CRE-88291	CLI-001	Juan Pérez García	40000.00	44445555	\N	pagado	2026-05-31 03:14:12.061778+00	2026-05-28 04:00:06.661114+00	'-001':5 '-88291':7 '44445555':8 'cli':4 'cre':6 'garc':3 'juan':1 'perez':2	{"_oneda": "GTQ", "_______": "2510 12345 0101", "_ias__ora": "45", "_tapa__obro": "Administrativa", "_apital__ora": "5000.00", "_tros__astos": "0.00", "_echa__raslado": "2024-04-15", "_gencia__rigen": "Agencia Central", "_egmento__roducto": "Microcrédito", "_oleta__eferencia": "B-99821", "_ntereses__oratorios": "50.00", "_ntereses__orrientes": "250.00"}	\N	40000.00
\.


--
-- TOC entry 5152 (class 0 OID 25186)
-- Dependencies: 242
-- Data for Name: clientes_bk; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.clientes_bk (id_bk, id_original, lote_id, fecha_migracion, id_cartera, id_gestor_asignado, id_supervisor_cadena, cuenta, identificacion, nombre, saldo, telefono_1, telefono_2, estado, fecha_ultima_gestion, data_extras, saldo_inicial) FROM stdin;
\.


--
-- TOC entry 5142 (class 0 OID 25082)
-- Dependencies: 232
-- Data for Name: data_extras; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.data_extras (id, id_cliente, id_extra, valor) FROM stdin;
\.


--
-- TOC entry 5134 (class 0 OID 25004)
-- Dependencies: 224
-- Data for Name: extras; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.extras (id, id_cartera, nombre_campo, etiqueta_display, tipo, orden_visual) FROM stdin;
\.


--
-- TOC entry 5160 (class 0 OID 32777)
-- Dependencies: 250
-- Data for Name: extras_cartera; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.extras_cartera (id, id_cartera, nombre_campo, etiqueta, tipo, orden, activo, modulo) FROM stdin;
4	34	_______	DPI_NIT	texto	1	t	clientes
5	34	_egmento__roducto	Segmento_Producto	texto	2	t	clientes
6	34	_ias__ora	Dias_Mora	texto	3	t	clientes
7	34	_tapa__obro	Etapa_Cobro	texto	4	t	clientes
8	34	_echa__raslado	Fecha_Traslado	texto	5	t	clientes
9	34	_gencia__rigen	Agencia_Origen	texto	6	t	clientes
10	34	_oneda	Moneda	texto	7	t	clientes
11	34	_apital__ora	Capital_Mora	texto	8	t	clientes
12	34	_ntereses__orrientes	Intereses_Corrientes	texto	9	t	clientes
13	34	_ntereses__oratorios	Intereses_Moratorios	texto	10	t	clientes
14	34	_tros__astos	Otros_Gastos	texto	11	t	clientes
15	34	_oleta__eferencia	Boleta_Referencia	texto	12	t	gestiones
\.


--
-- TOC entry 5146 (class 0 OID 25134)
-- Dependencies: 236
-- Data for Name: extras_historial; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.extras_historial (id, id_historial, nombre_campo, valor) FROM stdin;
\.


--
-- TOC entry 5144 (class 0 OID 25103)
-- Dependencies: 234
-- Data for Name: historial; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.historial (id, id_cliente, id_usuario, fecha_gestion, estatus, telefono_utilizado, id_tipologia, comentario, lote_origen_id, data_extras, fecha_proxima_llamada) FROM stdin;
126	16	36	2026-05-30 15:22:47.26673+00	COMP	44444444	43	prueba compromiso Q100	\N	[]	2026-05-30 09:22:00
127	16	36	2026-05-31 03:14:12.061778+00	PAGG	24242424	44	prueba de pago Q100 boleta 12345 BANCOR	\N	{"id_promesa_aplicada": 26}	2026-05-30 21:15:00
\.


--
-- TOC entry 5154 (class 0 OID 25199)
-- Dependencies: 244
-- Data for Name: historial_bk; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.historial_bk (id_bk, id_original, lote_id, fecha_migracion, id_cliente, id_usuario, fecha_gestion, estatus, telefono_utilizado, id_tipologia, comentario, data_extras, fecha_proxima_llamada) FROM stdin;
\.


--
-- TOC entry 5158 (class 0 OID 25229)
-- Dependencies: 248
-- Data for Name: logs_asistencia; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.logs_asistencia (id, usuario_id, entrada, salida, horas_trabajadas, fecha) FROM stdin;
\.


--
-- TOC entry 5156 (class 0 OID 25214)
-- Dependencies: 246
-- Data for Name: logs_auditoria; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.logs_auditoria (id, usuario_id, accion, tabla_afectada, registro_id, ip, datos_anteriores, datos_nuevos, fecha) FROM stdin;
1	1	login	usuarios	1	::1	\N	\N	2026-05-01 02:23:17.205636
2	1	login	usuarios	1	::1	\N	\N	2026-05-01 03:05:09.126003
3	1	login	usuarios	1	::1	\N	\N	2026-05-01 03:05:44.084017
4	34	login	usuarios	34	::1	\N	\N	2026-05-01 03:30:42.382159
5	35	login	usuarios	35	::1	\N	\N	2026-05-01 03:31:00.534802
6	1	login	usuarios	1	::1	\N	\N	2026-05-01 03:37:08.408972
7	1	login	usuarios	1	::1	\N	\N	2026-05-01 03:40:07.145183
8	1	update	carteras	1	::1	\N	{"lbl_saldo": "DEUDA_ACTUAL", "lbl_estado": "ESTADO", "lbl_nombre": "PRESTAMO", "lbl_telefono": "CELULAR", "cuenta_nombre": "Tarjeta", "nombre_cartera": "Cartera Demo", "identificacion_nombre": "DPI"}	2026-05-01 04:44:58.364297
9	35	login	usuarios	35	::1	\N	\N	2026-05-01 06:53:31.088793
10	1	login	usuarios	1	::1	\N	\N	2026-05-01 06:54:39.519687
11	34	login	usuarios	34	::1	\N	\N	2026-05-09 04:49:07.225592
12	35	login	usuarios	35	::1	\N	\N	2026-05-12 03:21:56.084609
13	1	login	usuarios	1	::1	\N	\N	2026-05-12 03:30:10.055271
14	34	login	usuarios	34	::1	\N	\N	2026-05-12 03:30:28.361537
15	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 02:04:57.405723
16	35	login	usuarios	35	::1	\N	\N	2026-05-13 02:05:30.671125
17	34	login	usuarios	34	::1	\N	\N	2026-05-13 02:05:45.474717
18	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 02:06:12.954683
19	1	login	usuarios	1	::1	\N	\N	2026-05-13 02:34:06.813217
20	35	login	usuarios	35	::1	\N	\N	2026-05-13 02:43:16.320964
21	34	login	usuarios	34	::1	\N	\N	2026-05-13 02:43:40.616074
22	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 02:43:59.968168
23	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 02:57:17.053014
24	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 02:58:54.249994
25	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 04:40:14.94097
26	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 04:41:43.700872
27	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 04:42:57.382811
28	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 04:50:02.926437
29	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 04:57:29.121582
30	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 04:59:46.289992
31	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 05:10:01.214817
32	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-13 05:23:11.512654
33	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 2}	2026-05-13 05:29:17.968344
34	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 1}	2026-05-13 05:42:37.765181
35	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 1}	2026-05-13 05:43:44.308617
36	34	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 1}	2026-05-13 06:49:14.690522
37	35	login	usuarios	35	::1	\N	\N	2026-05-13 06:49:50.792578
38	35	gestion_cliente	historial	1	::1	\N	{"estatus": "SINC", "tipologia": "7", "comentario": "Cliente solicita le llamemos mañana a las 14:00 horas"}	2026-05-14 02:55:50.746794
39	34	login	usuarios	34	::1	\N	\N	2026-05-14 02:56:10.252433
40	35	login	usuarios	35	::1	\N	\N	2026-05-14 03:00:31.45054
41	35	login	usuarios	35	::1	\N	\N	2026-05-14 03:04:33.572996
42	34	login	usuarios	34	::1	\N	\N	2026-05-14 03:04:50.958022
43	1	login	usuarios	1	::1	\N	\N	2026-05-14 07:03:46.688958
44	35	login	usuarios	35	::1	\N	\N	2026-05-16 01:11:08.466239
45	35	gestion_cliente	historial	3	::1	\N	{"estatus": "COMP", "tipologia": 20, "comentario": "Cliente se compromete a pagar 500 mañana, quedó que le llamemos a las 3 pm para confirmar pago"}	2026-05-16 07:06:47.854918
46	34	login	usuarios	34	::1	\N	\N	2026-05-16 07:07:02.90092
47	35	login	usuarios	35	::1	\N	\N	2026-05-16 07:17:58.950888
48	34	login	usuarios	34	::1	\N	\N	2026-05-17 00:27:09.627579
49	1	login	usuarios	1	::1	\N	\N	2026-05-17 00:29:27.63898
50	1	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 1}	2026-05-17 00:34:08.189272
51	36	login	usuarios	36	::1	\N	\N	2026-05-17 00:34:29.785091
52	1	login	usuarios	1	::1	\N	\N	2026-05-17 00:43:08.534461
53	36	login	usuarios	36	::1	\N	\N	2026-05-17 01:47:19.984353
54	34	login	usuarios	34	::1	\N	\N	2026-05-17 03:05:48.354399
55	1	login	usuarios	1	::1	\N	\N	2026-05-17 03:17:56.300821
56	36	login	usuarios	36	::1	\N	\N	2026-05-17 05:17:55.557429
57	35	login	usuarios	35	::1	\N	\N	2026-05-17 05:19:19.129474
58	34	login	usuarios	34	::1	\N	\N	2026-05-17 05:19:33.284896
59	1	login	usuarios	1	::1	\N	\N	2026-05-17 05:19:54.137412
60	36	login	usuarios	36	::1	\N	\N	2026-05-17 05:43:00.145147
61	1	login	usuarios	1	::1	\N	\N	2026-05-17 05:43:47.147996
62	34	login	usuarios	34	::1	\N	\N	2026-05-17 06:18:34.092946
63	1	login	usuarios	1	::1	\N	\N	2026-05-17 06:19:46.931982
64	36	login	usuarios	36	::1	\N	\N	2026-05-17 23:04:28.126385
65	1	login	usuarios	1	::1	\N	\N	2026-05-18 02:59:59.775783
66	35	login	usuarios	35	::1	\N	\N	2026-05-18 05:03:30.237957
67	1	login	usuarios	1	::1	\N	\N	2026-05-18 06:52:09.856252
68	35	login	usuarios	35	::1	\N	\N	2026-05-18 06:53:51.016417
69	1	login	usuarios	1	::1	\N	\N	2026-05-18 06:55:13.934591
70	35	login	usuarios	35	::1	\N	\N	2026-05-18 06:55:43.173746
71	1	login	usuarios	1	::1	\N	\N	2026-05-19 01:15:31.536189
72	35	login	usuarios	35	::1	\N	\N	2026-05-19 01:16:20.548366
73	35	login	usuarios	35	::1	\N	\N	2026-05-19 01:19:23.945828
74	1	login	usuarios	1	::1	\N	\N	2026-05-19 01:25:45.774546
75	34	login	usuarios	34	::1	\N	\N	2026-05-19 01:26:30.987574
76	35	login	usuarios	35	::1	\N	\N	2026-05-19 01:34:26.449262
77	1	login	usuarios	1	::1	\N	\N	2026-05-19 01:59:46.893214
78	35	login	usuarios	35	::1	\N	\N	2026-05-19 02:01:25.732858
79	1	login	usuarios	1	::1	\N	\N	2026-05-19 02:07:39.806167
80	34	login	usuarios	34	::1	\N	\N	2026-05-19 02:10:23.087773
81	35	login	usuarios	35	::1	\N	\N	2026-05-19 02:11:26.563343
82	1	login	usuarios	1	::1	\N	\N	2026-05-19 02:16:28.418298
83	35	login	usuarios	35	::1	\N	\N	2026-05-19 02:17:48.610389
84	1	login	usuarios	1	::1	\N	\N	2026-05-19 03:22:31.0598
85	34	login	usuarios	34	::1	\N	\N	2026-05-19 03:39:26.950609
86	35	login	usuarios	35	::1	\N	\N	2026-05-19 03:46:36.920306
87	1	login	usuarios	1	::1	\N	\N	2026-05-20 03:16:50.15395
88	35	login	usuarios	35	::1	\N	\N	2026-05-20 07:52:13.004769
89	1	login	usuarios	1	::1	\N	\N	2026-05-20 07:54:50.791702
90	37	login	usuarios	37	::1	\N	\N	2026-05-23 20:16:51.873906
91	1	login	usuarios	1	::1	\N	\N	2026-05-23 20:40:32.222746
92	37	login	usuarios	37	::1	\N	\N	2026-05-23 20:42:15.978035
93	34	login	usuarios	34	::1	\N	\N	2026-05-23 20:42:39.761425
94	1	login	usuarios	1	::1	\N	\N	2026-05-23 20:43:25.049049
95	1	login	usuarios	1	::1	\N	\N	2026-05-23 20:43:25.295534
96	1	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 1}	2026-05-23 20:49:53.159286
97	37	login	usuarios	37	::1	\N	\N	2026-05-23 20:52:20.168586
98	38	login	usuarios	38	::1	\N	\N	2026-05-23 20:53:01.28629
99	35	login	usuarios	35	::1	\N	\N	2026-05-23 20:54:22.247822
100	37	login	usuarios	37	::1	\N	\N	2026-05-23 20:56:02.963061
101	34	login	usuarios	34	::1	\N	\N	2026-05-23 20:56:43.740643
102	37	login	usuarios	37	::1	\N	\N	2026-05-23 20:57:22.351405
103	34	login	usuarios	34	::1	\N	\N	2026-05-23 21:14:40.928717
104	38	login	usuarios	38	::1	\N	\N	2026-05-23 21:42:12.417826
105	34	login	usuarios	34	::1	\N	\N	2026-05-23 22:44:57.409197
106	1	login	usuarios	1	::1	\N	\N	2026-05-23 23:00:04.934636
107	35	login	usuarios	35	::1	\N	\N	2026-05-23 23:01:29.148567
108	35	login	usuarios	35	::1	\N	\N	2026-05-23 23:42:45.721217
109	1	login	usuarios	1	::1	\N	\N	2026-05-23 23:47:07.406692
110	1	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-24 00:38:26.377256
111	1	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 0}	2026-05-24 00:39:58.320004
112	1	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 1}	2026-05-24 00:43:03.193324
113	36	login	usuarios	36	::1	\N	\N	2026-05-24 00:43:55.339897
114	1	login	usuarios	1	::1	\N	\N	2026-05-24 01:53:15.744068
115	35	login	usuarios	35	::1	\N	\N	2026-05-24 02:58:39.538289
116	1	login	usuarios	1	::1	\N	\N	2026-05-24 03:00:21.755581
117	1	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 1}	2026-05-24 05:28:54.71398
118	38	login	usuarios	38	::1	\N	\N	2026-05-24 05:57:15.820075
119	1	login	usuarios	1	::1	\N	\N	2026-05-24 05:58:01.843921
120	1	carga_csv	clientes	\N	::1	\N	{"cartera_id": "1", "registros_importados": 1}	2026-05-24 06:02:50.559251
121	38	login	usuarios	38	::1	\N	\N	2026-05-24 06:03:07.501432
122	1	login	usuarios	1	::1	\N	\N	2026-05-24 06:04:54.107736
123	35	login	usuarios	35	::1	\N	\N	2026-05-25 02:19:48.441658
124	38	login	usuarios	38	::1	\N	\N	2026-05-25 02:20:15.15857
125	1	login	usuarios	1	::1	\N	\N	2026-05-25 02:22:51.187758
126	1	login	usuarios	1	::1	\N	\N	2026-05-25 05:46:09.504212
127	35	login	usuarios	35	::1	\N	\N	2026-05-25 08:10:58.259428
128	1	login	usuarios	1	::1	\N	\N	2026-05-25 08:16:11.986921
129	1	insert	carteras	34	::1	\N	{"lbl_saldo": "Saldo_Total_Exigible", "lbl_estado": "Estado", "lbl_nombre": "Nombre_Deudor", "lbl_telefono": "Teléfono", "cuenta_nombre": "ID_Credito", "nombre_cartera": "LEX", "identificacion_nombre": "Codigo_Cliente"}	2026-05-25 08:54:12.816224
130	1	carga_csv	clientes	\N	::1	\N	{"cartera_id": "34", "registros_importados": 2}	2026-05-25 09:06:06.655174
131	1	carga_csv	clientes	\N	::1	\N	{"cartera_id": "34", "registros_importados": 2}	2026-05-25 09:18:40.423769
132	1	carga_csv	clientes	\N	::1	\N	{"cartera_id": "34", "registros_importados": 1}	2026-05-25 09:25:17.694094
133	1	carga_csv	clientes	\N	::1	\N	{"cartera_id": "34", "registros_importados": 1}	2026-05-25 09:26:07.96244
134	35	login	usuarios	35	::1	\N	\N	2026-05-25 10:04:17.332306
135	1	login	usuarios	1	::1	\N	\N	2026-05-25 10:04:32.760629
136	36	login	usuarios	36	::1	\N	\N	2026-05-25 10:04:59.923843
137	1	login	usuarios	1	::1	\N	\N	2026-05-25 10:06:30.244741
138	1	login	usuarios	1	::1	\N	\N	2026-05-25 10:07:03.484373
139	39	login	usuarios	39	::1	\N	\N	2026-05-25 10:10:43.590258
140	36	login	usuarios	36	::1	\N	\N	2026-05-25 10:11:44.194078
141	34	login	usuarios	34	::1	\N	\N	2026-05-25 10:12:06.354026
142	39	login	usuarios	39	::1	\N	\N	2026-05-25 10:13:13.545148
143	1	login	usuarios	1	::1	\N	\N	2026-05-25 20:25:26.283821
144	38	login	usuarios	38	::1	\N	\N	2026-05-25 20:25:58.024188
145	36	login	usuarios	36	::1	\N	\N	2026-05-25 20:26:11.323516
146	1	login	usuarios	1	::1	\N	\N	2026-05-25 21:46:06.615758
147	1	login	usuarios	1	::1	\N	\N	2026-05-25 22:06:13.124894
148	36	login	usuarios	36	::1	\N	\N	2026-05-25 22:07:25.917607
149	36	login	usuarios	36	::1	\N	\N	2026-05-25 22:31:36.068119
150	39	login	usuarios	39	::1	\N	\N	2026-05-25 22:34:45.249792
151	36	login	usuarios	36	::1	\N	\N	2026-05-25 22:37:52.505188
152	39	login	usuarios	39	::1	\N	\N	2026-05-25 22:40:52.599391
153	36	login	usuarios	36	::1	\N	\N	2026-05-25 22:42:44.286098
154	39	login	usuarios	39	::1	\N	\N	2026-05-25 22:44:12.456396
155	36	login	usuarios	36	::1	\N	\N	2026-05-26 20:57:31.247491
156	1	login	usuarios	1	::1	\N	\N	2026-05-26 22:52:52.572816
157	36	login	usuarios	36	::1	\N	\N	2026-05-26 22:55:18.16289
158	1	login	usuarios	1	::1	\N	\N	2026-05-26 22:56:02.356247
159	1	login	usuarios	1	::1	\N	\N	2026-05-27 00:49:27.6607
160	36	login	usuarios	36	::1	\N	\N	2026-05-27 04:44:05.494231
161	39	login	usuarios	39	::1	\N	\N	2026-05-27 05:24:15.251917
162	1	login	usuarios	1	::1	\N	\N	2026-05-27 14:58:13.390793
163	36	login	usuarios	36	::1	\N	\N	2026-05-27 15:00:29.94951
164	1	login	usuarios	1	::1	\N	\N	2026-05-27 20:53:54.724026
165	1	carga_csv	clientes	\N	::1	\N	{"cartera_id": "34", "registros_importados": 1}	2026-05-27 20:54:44.331012
166	1	login	usuarios	1	::1	\N	\N	2026-05-27 20:55:28.080266
167	38	login	usuarios	38	::1	\N	\N	2026-05-27 20:55:43.894566
168	1	login	usuarios	1	::1	\N	\N	2026-05-27 21:35:21.245655
169	36	login	usuarios	36	::1	\N	\N	2026-05-27 22:00:46.542181
170	1	login	usuarios	1	::1	\N	\N	2026-05-27 22:42:59.501139
171	36	login	usuarios	36	::1	\N	\N	2026-05-27 22:53:47.464145
172	38	login	usuarios	38	::1	\N	\N	2026-05-27 22:57:01.773229
173	1	login	usuarios	1	::1	\N	\N	2026-05-28 01:09:06.630322
174	34	login	usuarios	34	::1	\N	\N	2026-05-28 01:09:37.185589
175	1	login	usuarios	1	::1	\N	\N	2026-05-28 01:12:57.308603
176	38	login	usuarios	38	::1	\N	\N	2026-05-28 01:15:14.997712
177	39	login	usuarios	39	::1	\N	\N	2026-05-28 01:36:31.212651
178	38	login	usuarios	38	::1	\N	\N	2026-05-28 01:48:43.572096
179	1	login	usuarios	1	::1	\N	\N	2026-05-28 11:55:06.740264
180	34	login	usuarios	34	::1	\N	\N	2026-05-28 12:03:17.005035
181	36	login	usuarios	36	::1	\N	\N	2026-05-28 12:53:14.071633
182	1	login	usuarios	1	::1	\N	\N	2026-05-28 16:30:31.51137
183	39	login	usuarios	39	::1	\N	\N	2026-05-28 17:37:04.493112
184	36	login	usuarios	36	::1	\N	\N	2026-05-28 17:47:20.749691
185	38	login	usuarios	38	::1	\N	\N	2026-05-28 18:08:27.992229
186	39	login	usuarios	39	::1	\N	\N	2026-05-28 18:11:13.635877
187	39	login	usuarios	39	::1	\N	\N	2026-05-28 18:25:15.120416
188	38	login	usuarios	38	::1	\N	\N	2026-05-28 18:26:44.182795
189	39	login	usuarios	39	::1	\N	\N	2026-05-28 18:53:01.886868
190	36	login	usuarios	36	::1	\N	\N	2026-05-28 23:55:39.579801
191	36	login	usuarios	36	::1	\N	\N	2026-05-28 23:57:20.94436
192	36	login	usuarios	36	::1	\N	\N	2026-05-29 15:47:49.119098
193	36	login	usuarios	36	::1	\N	\N	2026-05-29 22:16:48.876329
194	36	login	usuarios	36	::1	\N	\N	2026-05-29 22:44:09.109611
195	1	login	usuarios	1	::1	\N	\N	2026-05-30 22:20:48.015332
196	36	login	usuarios	36	::1	\N	\N	2026-05-30 22:23:11.280842
197	1	login	usuarios	1	::1	\N	\N	2026-05-30 22:31:11.542521
\.


--
-- TOC entry 5138 (class 0 OID 25031)
-- Dependencies: 228
-- Data for Name: lotes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.lotes (id, fecha_ejecucion, usuario_id, tipo_operacion, cantidad_registros, observaciones, estado) FROM stdin;
3	2026-05-24 05:23:52.871933+00	1	migracion_backup	1	\N	completado
4	2026-05-24 06:06:01.209413+00	1	migracion_backup	1	\N	completado
5	2026-05-25 03:48:35.679317+00	1	migracion_backup	1	\N	completado
6	2026-05-25 04:38:59.585116+00	1	migracion_backup	1	\N	completado
7	2026-05-25 04:42:32.202385+00	1	migracion_backup	1	\N	completado
8	2026-05-25 04:42:34.713433+00	1	migracion_backup	1	\N	completado
9	2026-05-25 04:42:37.960888+00	1	migracion_backup	1	\N	completado
10	2026-05-25 04:42:38.464765+00	1	migracion_backup	1	\N	completado
11	2026-05-25 04:42:38.705057+00	1	migracion_backup	1	\N	completado
12	2026-05-25 04:42:38.997309+00	1	migracion_backup	1	\N	completado
13	2026-05-25 04:42:47.441029+00	1	migracion_backup	1	\N	completado
14	2026-05-25 04:43:58.038118+00	1	migracion_backup	1	\N	completado
15	2026-05-25 04:45:25.905244+00	1	migracion_backup	1	\N	completado
16	2026-05-25 04:46:50.333357+00	1	migracion_backup	1	\N	completado
17	2026-05-25 04:46:51.972889+00	1	migracion_backup	1	\N	completado
18	2026-05-25 04:47:31.720159+00	1	migracion_backup	1	\N	completado
19	2026-05-25 04:53:00.904252+00	1	migracion_backup	1	\N	completado
20	2026-05-25 08:36:40.414838+00	1	migracion_backup	3	\N	completado
21	2026-05-25 08:37:48.159773+00	1	migracion_backup	1	\N	completado
24	2026-05-28 03:39:22.593019+00	1	migracion_backup	1	\N	completado
\.


--
-- TOC entry 5150 (class 0 OID 25168)
-- Dependencies: 240
-- Data for Name: pagos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pagos (id, id_cliente, monto, fecha_pago, referencia_bancaria, estatus, validado_por, fecha_validacion, id_historial) FROM stdin;
62	16	100.00	2026-05-30 21:14:12.061778		PAGG	\N	\N	127
\.


--
-- TOC entry 5162 (class 0 OID 65548)
-- Dependencies: 252
-- Data for Name: pagos_bk; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pagos_bk (id_bk, id_original, lote_id, fecha_migracion, id_cliente, monto, fecha_pago, referencia_bancaria, estatus, validado_por, fecha_validacion, id_historial) FROM stdin;
\.


--
-- TOC entry 5148 (class 0 OID 25148)
-- Dependencies: 238
-- Data for Name: promesas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.promesas (id, id_cliente, id_usuario, monto_prometido, fecha_compromiso, fecha_registro, estatus, id_historial) FROM stdin;
26	16	36	100.00	2026-05-30 09:22:00	2026-05-30 15:22:47.26673+00	cumplida	126
\.


--
-- TOC entry 5164 (class 0 OID 65556)
-- Dependencies: 254
-- Data for Name: promesas_bk; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.promesas_bk (id_bk, id_original, lote_id, fecha_migracion, id_cliente, id_usuario, monto_prometido, fecha_compromiso, fecha_registro, estatus, id_historial) FROM stdin;
\.


--
-- TOC entry 5136 (class 0 OID 25018)
-- Dependencies: 226
-- Data for Name: tipologias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tipologias (id, clase, padre_id, nombre, codigo_origen, id_cartera, requiere_proxima_fecha, requiere_monto, estatus_default) FROM stdin;
60	S	42	Migro	21	34	f	f	SINC
61	S	\N	Fallecido	22	34	f	f	SINC
62	S	61	Fallecido	23	34	f	f	SINC
40	T	\N	Contacto Efectivo	1	34	t	f	SINC
41	T	\N	Contacto No Efectivo	2	34	t	f	SINC
42	T	\N	Datos Incorrectos	3	34	t	f	SINC
45	T	\N	Plan de Pagos (PLP)	6	34	t	f	SINC
46	S	40	Negativa de Pago (NP)	7	34	t	f	SINC
47	S	40	Incapacidad de Pago (IP)	8	34	t	f	SINC
48	S	40	solicita descuento	9	34	t	f	SINC
49	S	40	cliente solicita estado de cuenta	10	34	t	f	SINC
50	S	40	solicitud de no contacto	11	34	t	f	SINC
51	S	41	No Contesta / Buzón	12	34	t	f	SINC
52	S	41	Promesa Incumplida (PI)	13	34	t	f	SINC
53	S	41	Tercero Informado	14	34	t	f	SINC
54	S	41	Cita Programada	15	34	t	f	SINC
55	S	41	teléfono apagado o numero bloqueado	16	34	t	f	SINC
56	S	41	fuera de servicio	17	34	t	f	SINC
57	S	41	buzon de voz	18	34	t	f	SINC
58	S	42	Número Equivocado	19	34	t	f	SINC
59	S	42	Llamar en otro horario	20	34	t	f	SINC
43	T	\N	Promesa de Pago (PP)	4	34	t	t	COMP
44	T	\N	Ya Pagó (YP)	5	34	t	t	PAGG
\.


--
-- TOC entry 5130 (class 0 OID 24978)
-- Dependencies: 220
-- Data for Name: usuarios; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuarios (id, nombre, usuario, clave_hash, rol, supervisor_id, activo, fecha_creacion, fecha_ultimo_login) FROM stdin;
34	Juan Pérez	jperez	$2y$10$8kPJWtWR1gMR.wVitIxDZ..CZG47y.POwlnNGQNl9eKxCHANcdSNC	supervisor	\N	t	2026-05-01 03:30:04.919826+00	\N
35	Gerson Solis	gsolis	$2y$10$8kPJWtWR1gMR.wVitIxDZ..CZG47y.POwlnNGQNl9eKxCHANcdSNC	gestor	34	t	2026-05-01 03:30:29.898543+00	\N
36	edgar ricardo arjona	earjona	$2y$10$34oFnbmOEPG7tPxjRKsn.uMmdEJ58PajayJdQRMZyg21aumuqAUUO	gestor	34	t	2026-05-17 00:30:30.57159+00	\N
37	Luis Cruz	lcruz	$2y$10$KBqcIOi1h9vzz78Z3uOtne6jakp9B5ObkJ69HijReusBNKJQK7h9C	supervisor	\N	t	2026-05-23 20:16:26.962794+00	\N
38	Pablo Marmol	pmarmol	$2y$10$JVQ5q5jixCNkxfF8zJt0KOfC3Q9uOJ.obbGKOC9mx2AtRgU2QvcDO	gestor	37	t	2026-05-23 20:42:08.191858+00	\N
1	Administrador General	admin	$2y$10$KqFqwWWbrP8tCJK4hjaJ5OiJ/7rEMmEWhemNciN4EAjlVpjn5ZqB2	admin	\N	t	2026-04-23 09:17:16.871377+00	\N
39	Supervisor General	supervisor	$2y$10$h1kJX55TK2sbo1HW2S.ine7uTSLYa9wWiv4aPQvTABwPx9TYB/Vem	supervisor_general	\N	t	2026-05-25 10:07:50.600986+00	\N
\.


--
-- TOC entry 5193 (class 0 OID 0)
-- Dependencies: 255
-- Name: asistencia_movimientos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asistencia_movimientos_id_seq', 21, true);


--
-- TOC entry 5194 (class 0 OID 0)
-- Dependencies: 221
-- Name: carteras_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.carteras_id_seq', 34, true);


--
-- TOC entry 5195 (class 0 OID 0)
-- Dependencies: 241
-- Name: clientes_bk_id_bk_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.clientes_bk_id_bk_seq', 17, true);


--
-- TOC entry 5196 (class 0 OID 0)
-- Dependencies: 229
-- Name: clientes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.clientes_id_seq', 17, true);


--
-- TOC entry 5197 (class 0 OID 0)
-- Dependencies: 231
-- Name: data_extras_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.data_extras_id_seq', 1, false);


--
-- TOC entry 5198 (class 0 OID 0)
-- Dependencies: 249
-- Name: extras_cartera_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.extras_cartera_id_seq', 15, true);


--
-- TOC entry 5199 (class 0 OID 0)
-- Dependencies: 235
-- Name: extras_historial_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.extras_historial_id_seq', 1, false);


--
-- TOC entry 5200 (class 0 OID 0)
-- Dependencies: 223
-- Name: extras_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.extras_id_seq', 1, false);


--
-- TOC entry 5201 (class 0 OID 0)
-- Dependencies: 243
-- Name: historial_bk_id_bk_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.historial_bk_id_bk_seq', 372, true);


--
-- TOC entry 5202 (class 0 OID 0)
-- Dependencies: 233
-- Name: historial_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.historial_id_seq', 127, true);


--
-- TOC entry 5203 (class 0 OID 0)
-- Dependencies: 247
-- Name: logs_asistencia_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.logs_asistencia_id_seq', 1, false);


--
-- TOC entry 5204 (class 0 OID 0)
-- Dependencies: 245
-- Name: logs_auditoria_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.logs_auditoria_id_seq', 197, true);


--
-- TOC entry 5205 (class 0 OID 0)
-- Dependencies: 227
-- Name: lotes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.lotes_id_seq', 24, true);


--
-- TOC entry 5206 (class 0 OID 0)
-- Dependencies: 251
-- Name: pagos_bk_id_bk_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pagos_bk_id_bk_seq', 138, true);


--
-- TOC entry 5207 (class 0 OID 0)
-- Dependencies: 239
-- Name: pagos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pagos_id_seq', 62, true);


--
-- TOC entry 5208 (class 0 OID 0)
-- Dependencies: 253
-- Name: promesas_bk_id_bk_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.promesas_bk_id_bk_seq', 110, true);


--
-- TOC entry 5209 (class 0 OID 0)
-- Dependencies: 237
-- Name: promesas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.promesas_id_seq', 26, true);


--
-- TOC entry 5210 (class 0 OID 0)
-- Dependencies: 225
-- Name: tipologias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tipologias_id_seq', 62, true);


--
-- TOC entry 5211 (class 0 OID 0)
-- Dependencies: 219
-- Name: usuarios_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.usuarios_id_seq', 39, true);


--
-- TOC entry 4953 (class 2606 OID 65591)
-- Name: asistencia_movimientos asistencia_movimientos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asistencia_movimientos
    ADD CONSTRAINT asistencia_movimientos_pkey PRIMARY KEY (id);


--
-- TOC entry 4901 (class 2606 OID 25002)
-- Name: carteras carteras_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.carteras
    ADD CONSTRAINT carteras_pkey PRIMARY KEY (id);


--
-- TOC entry 4936 (class 2606 OID 25192)
-- Name: clientes_bk clientes_bk_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes_bk
    ADD CONSTRAINT clientes_bk_pkey PRIMARY KEY (id_bk);


--
-- TOC entry 4912 (class 2606 OID 25059)
-- Name: clientes clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_pkey PRIMARY KEY (id);


--
-- TOC entry 4921 (class 2606 OID 25091)
-- Name: data_extras data_extras_id_cliente_id_extra_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.data_extras
    ADD CONSTRAINT data_extras_id_cliente_id_extra_key UNIQUE (id_cliente, id_extra);


--
-- TOC entry 4923 (class 2606 OID 25089)
-- Name: data_extras data_extras_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.data_extras
    ADD CONSTRAINT data_extras_pkey PRIMARY KEY (id);


--
-- TOC entry 4944 (class 2606 OID 32787)
-- Name: extras_cartera extras_cartera_id_cartera_nombre_campo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_cartera
    ADD CONSTRAINT extras_cartera_id_cartera_nombre_campo_key UNIQUE (id_cartera, nombre_campo);


--
-- TOC entry 4946 (class 2606 OID 32785)
-- Name: extras_cartera extras_cartera_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_cartera
    ADD CONSTRAINT extras_cartera_pkey PRIMARY KEY (id);


--
-- TOC entry 4929 (class 2606 OID 25141)
-- Name: extras_historial extras_historial_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_historial
    ADD CONSTRAINT extras_historial_pkey PRIMARY KEY (id);


--
-- TOC entry 4903 (class 2606 OID 25011)
-- Name: extras extras_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras
    ADD CONSTRAINT extras_pkey PRIMARY KEY (id);


--
-- TOC entry 4938 (class 2606 OID 25207)
-- Name: historial_bk historial_bk_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial_bk
    ADD CONSTRAINT historial_bk_pkey PRIMARY KEY (id_bk);


--
-- TOC entry 4925 (class 2606 OID 25112)
-- Name: historial historial_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial
    ADD CONSTRAINT historial_pkey PRIMARY KEY (id);


--
-- TOC entry 4942 (class 2606 OID 25235)
-- Name: logs_asistencia logs_asistencia_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logs_asistencia
    ADD CONSTRAINT logs_asistencia_pkey PRIMARY KEY (id);


--
-- TOC entry 4940 (class 2606 OID 25222)
-- Name: logs_auditoria logs_auditoria_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logs_auditoria
    ADD CONSTRAINT logs_auditoria_pkey PRIMARY KEY (id);


--
-- TOC entry 4910 (class 2606 OID 25041)
-- Name: lotes lotes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lotes
    ADD CONSTRAINT lotes_pkey PRIMARY KEY (id);


--
-- TOC entry 4949 (class 2606 OID 65554)
-- Name: pagos_bk pagos_bk_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos_bk
    ADD CONSTRAINT pagos_bk_pkey PRIMARY KEY (id_bk);


--
-- TOC entry 4934 (class 2606 OID 25174)
-- Name: pagos pagos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_pkey PRIMARY KEY (id);


--
-- TOC entry 4951 (class 2606 OID 65562)
-- Name: promesas_bk promesas_bk_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas_bk
    ADD CONSTRAINT promesas_bk_pkey PRIMARY KEY (id_bk);


--
-- TOC entry 4932 (class 2606 OID 25156)
-- Name: promesas promesas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas
    ADD CONSTRAINT promesas_pkey PRIMARY KEY (id);


--
-- TOC entry 4906 (class 2606 OID 25024)
-- Name: tipologias tipologias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipologias
    ADD CONSTRAINT tipologias_pkey PRIMARY KEY (id);


--
-- TOC entry 4908 (class 2606 OID 32775)
-- Name: tipologias uq_tipologia_cartera; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipologias
    ADD CONSTRAINT uq_tipologia_cartera UNIQUE (codigo_origen, id_cartera);


--
-- TOC entry 4897 (class 2606 OID 24986)
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id);


--
-- TOC entry 4899 (class 2606 OID 24988)
-- Name: usuarios usuarios_usuario_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_usuario_key UNIQUE (usuario);


--
-- TOC entry 4954 (class 1259 OID 65598)
-- Name: idx_asistencia_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asistencia_fecha ON public.asistencia_movimientos USING btree (fecha_hora);


--
-- TOC entry 4955 (class 1259 OID 65597)
-- Name: idx_asistencia_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asistencia_usuario ON public.asistencia_movimientos USING btree (usuario_id);


--
-- TOC entry 4956 (class 1259 OID 65599)
-- Name: idx_asistencia_usuario_fecha; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asistencia_usuario_fecha ON public.asistencia_movimientos USING btree (usuario_id, fecha_hora);


--
-- TOC entry 4913 (class 1259 OID 25076)
-- Name: idx_clientes_cartera; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_cartera ON public.clientes USING btree (id_cartera);


--
-- TOC entry 4914 (class 1259 OID 65542)
-- Name: idx_clientes_cuenta_unico; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_clientes_cuenta_unico ON public.clientes USING btree (cuenta);


--
-- TOC entry 4915 (class 1259 OID 32801)
-- Name: idx_clientes_extras; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_extras ON public.clientes USING gin (data_extras);


--
-- TOC entry 4916 (class 1259 OID 25077)
-- Name: idx_clientes_gestor; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_gestor ON public.clientes USING btree (id_gestor_asignado);


--
-- TOC entry 4917 (class 1259 OID 25080)
-- Name: idx_clientes_identificacion; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_identificacion ON public.clientes USING btree (identificacion);


--
-- TOC entry 4918 (class 1259 OID 25079)
-- Name: idx_clientes_search; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_search ON public.clientes USING gin (search_vector);


--
-- TOC entry 4919 (class 1259 OID 25078)
-- Name: idx_clientes_supervisor; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_supervisor ON public.clientes USING btree (id_supervisor_cadena);


--
-- TOC entry 4947 (class 1259 OID 32793)
-- Name: idx_extras_cartera; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_extras_cartera ON public.extras_cartera USING btree (id_cartera);


--
-- TOC entry 4926 (class 1259 OID 65600)
-- Name: idx_historial_cliente; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_historial_cliente ON public.historial USING btree (id_cliente, id DESC);


--
-- TOC entry 4927 (class 1259 OID 40970)
-- Name: idx_historial_cliente_fecha_desc; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_historial_cliente_fecha_desc ON public.historial USING btree (id_cliente, fecha_gestion DESC) WHERE ((estatus)::text = ANY ((ARRAY['SINC'::character varying, 'COMP'::character varying])::text[]));


--
-- TOC entry 4930 (class 1259 OID 57350)
-- Name: idx_promesas_historial; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_promesas_historial ON public.promesas USING btree (id_historial);


--
-- TOC entry 4904 (class 1259 OID 32773)
-- Name: idx_tipologias_cartera; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_tipologias_cartera ON public.tipologias USING btree (id_cartera);


--
-- TOC entry 4983 (class 2620 OID 25075)
-- Name: clientes tsvector_update_clientes; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER tsvector_update_clientes BEFORE INSERT OR UPDATE ON public.clientes FOR EACH ROW EXECUTE FUNCTION tsvector_update_trigger('search_vector', 'pg_catalog.spanish', 'nombre', 'identificacion', 'cuenta', 'telefono_1');


--
-- TOC entry 4977 (class 2606 OID 25193)
-- Name: clientes_bk clientes_bk_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes_bk
    ADD CONSTRAINT clientes_bk_lote_id_fkey FOREIGN KEY (lote_id) REFERENCES public.lotes(id);


--
-- TOC entry 4962 (class 2606 OID 25060)
-- Name: clientes clientes_id_cartera_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_id_cartera_fkey FOREIGN KEY (id_cartera) REFERENCES public.carteras(id);


--
-- TOC entry 4963 (class 2606 OID 25065)
-- Name: clientes clientes_id_gestor_asignado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_id_gestor_asignado_fkey FOREIGN KEY (id_gestor_asignado) REFERENCES public.usuarios(id);


--
-- TOC entry 4964 (class 2606 OID 25070)
-- Name: clientes clientes_id_supervisor_cadena_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_id_supervisor_cadena_fkey FOREIGN KEY (id_supervisor_cadena) REFERENCES public.usuarios(id);


--
-- TOC entry 4965 (class 2606 OID 25092)
-- Name: data_extras data_extras_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.data_extras
    ADD CONSTRAINT data_extras_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id) ON DELETE CASCADE;


--
-- TOC entry 4966 (class 2606 OID 25097)
-- Name: data_extras data_extras_id_extra_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.data_extras
    ADD CONSTRAINT data_extras_id_extra_fkey FOREIGN KEY (id_extra) REFERENCES public.extras(id);


--
-- TOC entry 4981 (class 2606 OID 32788)
-- Name: extras_cartera extras_cartera_id_cartera_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_cartera
    ADD CONSTRAINT extras_cartera_id_cartera_fkey FOREIGN KEY (id_cartera) REFERENCES public.carteras(id) ON DELETE CASCADE;


--
-- TOC entry 4971 (class 2606 OID 25142)
-- Name: extras_historial extras_historial_id_historial_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_historial
    ADD CONSTRAINT extras_historial_id_historial_fkey FOREIGN KEY (id_historial) REFERENCES public.historial(id) ON DELETE CASCADE;


--
-- TOC entry 4958 (class 2606 OID 25012)
-- Name: extras extras_id_cartera_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras
    ADD CONSTRAINT extras_id_cartera_fkey FOREIGN KEY (id_cartera) REFERENCES public.carteras(id) ON DELETE CASCADE;


--
-- TOC entry 4982 (class 2606 OID 65592)
-- Name: asistencia_movimientos fk_asistencia_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asistencia_movimientos
    ADD CONSTRAINT fk_asistencia_usuario FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE;


--
-- TOC entry 4974 (class 2606 OID 49180)
-- Name: pagos fk_pagos_historial; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT fk_pagos_historial FOREIGN KEY (id_historial) REFERENCES public.historial(id);


--
-- TOC entry 4978 (class 2606 OID 25208)
-- Name: historial_bk historial_bk_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial_bk
    ADD CONSTRAINT historial_bk_lote_id_fkey FOREIGN KEY (lote_id) REFERENCES public.lotes(id);


--
-- TOC entry 4967 (class 2606 OID 25113)
-- Name: historial historial_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial
    ADD CONSTRAINT historial_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id);


--
-- TOC entry 4968 (class 2606 OID 25123)
-- Name: historial historial_id_tipologia_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial
    ADD CONSTRAINT historial_id_tipologia_fkey FOREIGN KEY (id_tipologia) REFERENCES public.tipologias(id);


--
-- TOC entry 4969 (class 2606 OID 25118)
-- Name: historial historial_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial
    ADD CONSTRAINT historial_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id);


--
-- TOC entry 4970 (class 2606 OID 25128)
-- Name: historial historial_lote_origen_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial
    ADD CONSTRAINT historial_lote_origen_id_fkey FOREIGN KEY (lote_origen_id) REFERENCES public.lotes(id);


--
-- TOC entry 4980 (class 2606 OID 25236)
-- Name: logs_asistencia logs_asistencia_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logs_asistencia
    ADD CONSTRAINT logs_asistencia_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- TOC entry 4979 (class 2606 OID 25223)
-- Name: logs_auditoria logs_auditoria_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logs_auditoria
    ADD CONSTRAINT logs_auditoria_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- TOC entry 4961 (class 2606 OID 25042)
-- Name: lotes lotes_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lotes
    ADD CONSTRAINT lotes_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- TOC entry 4975 (class 2606 OID 25175)
-- Name: pagos pagos_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id);


--
-- TOC entry 4976 (class 2606 OID 25180)
-- Name: pagos pagos_validado_por_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_validado_por_fkey FOREIGN KEY (validado_por) REFERENCES public.usuarios(id);


--
-- TOC entry 4972 (class 2606 OID 25157)
-- Name: promesas promesas_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas
    ADD CONSTRAINT promesas_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id);


--
-- TOC entry 4973 (class 2606 OID 25162)
-- Name: promesas promesas_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas
    ADD CONSTRAINT promesas_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id);


--
-- TOC entry 4959 (class 2606 OID 32768)
-- Name: tipologias tipologias_id_cartera_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipologias
    ADD CONSTRAINT tipologias_id_cartera_fkey FOREIGN KEY (id_cartera) REFERENCES public.carteras(id) ON DELETE CASCADE;


--
-- TOC entry 4960 (class 2606 OID 25025)
-- Name: tipologias tipologias_padre_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipologias
    ADD CONSTRAINT tipologias_padre_id_fkey FOREIGN KEY (padre_id) REFERENCES public.tipologias(id);


--
-- TOC entry 4957 (class 2606 OID 24989)
-- Name: usuarios usuarios_supervisor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_supervisor_id_fkey FOREIGN KEY (supervisor_id) REFERENCES public.usuarios(id);


-- Completed on 2026-05-30 22:38:12

--
-- PostgreSQL database dump complete
--


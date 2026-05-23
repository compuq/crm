--
-- PostgreSQL database dump
--

-- Dumped from database version 17.2
-- Dumped by pg_dump version 17.2

-- Started on 2026-05-20 22:28:59

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
-- TOC entry 5128 (class 0 OID 0)
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
-- TOC entry 5129 (class 0 OID 0)
-- Dependencies: 3
-- Name: EXTENSION unaccent; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION unaccent IS 'text search dictionary that removes accents';


SET default_tablespace = '';

SET default_table_access_method = heap;

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
-- TOC entry 5130 (class 0 OID 0)
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
    fecha_ultima_gestion timestamp with time zone
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
-- TOC entry 5131 (class 0 OID 0)
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
-- TOC entry 5132 (class 0 OID 0)
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
-- TOC entry 5133 (class 0 OID 0)
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
-- TOC entry 5134 (class 0 OID 0)
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
-- TOC entry 5135 (class 0 OID 0)
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
-- TOC entry 5136 (class 0 OID 0)
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
    comentario text
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
-- TOC entry 5137 (class 0 OID 0)
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
-- TOC entry 5138 (class 0 OID 0)
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
-- TOC entry 5139 (class 0 OID 0)
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
-- TOC entry 5140 (class 0 OID 0)
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
-- TOC entry 5141 (class 0 OID 0)
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
-- TOC entry 5142 (class 0 OID 0)
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
-- TOC entry 5143 (class 0 OID 0)
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
-- TOC entry 5144 (class 0 OID 0)
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
-- TOC entry 5145 (class 0 OID 0)
-- Dependencies: 219
-- Name: usuarios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.usuarios_id_seq OWNED BY public.usuarios.id;


--
-- TOC entry 4817 (class 2604 OID 24998)
-- Name: carteras id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.carteras ALTER COLUMN id SET DEFAULT nextval('public.carteras_id_seq'::regclass);


--
-- TOC entry 4834 (class 2604 OID 25051)
-- Name: clientes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes ALTER COLUMN id SET DEFAULT nextval('public.clientes_id_seq'::regclass);


--
-- TOC entry 4849 (class 2604 OID 25189)
-- Name: clientes_bk id_bk; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes_bk ALTER COLUMN id_bk SET DEFAULT nextval('public.clientes_bk_id_bk_seq'::regclass);


--
-- TOC entry 4839 (class 2604 OID 25085)
-- Name: data_extras id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.data_extras ALTER COLUMN id SET DEFAULT nextval('public.data_extras_id_seq'::regclass);


--
-- TOC entry 4824 (class 2604 OID 25007)
-- Name: extras id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras ALTER COLUMN id SET DEFAULT nextval('public.extras_id_seq'::regclass);


--
-- TOC entry 4857 (class 2604 OID 32780)
-- Name: extras_cartera id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_cartera ALTER COLUMN id SET DEFAULT nextval('public.extras_cartera_id_seq'::regclass);


--
-- TOC entry 4843 (class 2604 OID 25137)
-- Name: extras_historial id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_historial ALTER COLUMN id SET DEFAULT nextval('public.extras_historial_id_seq'::regclass);


--
-- TOC entry 4840 (class 2604 OID 25106)
-- Name: historial id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial ALTER COLUMN id SET DEFAULT nextval('public.historial_id_seq'::regclass);


--
-- TOC entry 4851 (class 2604 OID 25202)
-- Name: historial_bk id_bk; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial_bk ALTER COLUMN id_bk SET DEFAULT nextval('public.historial_bk_id_bk_seq'::regclass);


--
-- TOC entry 4855 (class 2604 OID 25232)
-- Name: logs_asistencia id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logs_asistencia ALTER COLUMN id SET DEFAULT nextval('public.logs_asistencia_id_seq'::regclass);


--
-- TOC entry 4853 (class 2604 OID 25217)
-- Name: logs_auditoria id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logs_auditoria ALTER COLUMN id SET DEFAULT nextval('public.logs_auditoria_id_seq'::regclass);


--
-- TOC entry 4830 (class 2604 OID 25034)
-- Name: lotes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lotes ALTER COLUMN id SET DEFAULT nextval('public.lotes_id_seq'::regclass);


--
-- TOC entry 4847 (class 2604 OID 25171)
-- Name: pagos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos ALTER COLUMN id SET DEFAULT nextval('public.pagos_id_seq'::regclass);


--
-- TOC entry 4844 (class 2604 OID 25151)
-- Name: promesas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas ALTER COLUMN id SET DEFAULT nextval('public.promesas_id_seq'::regclass);


--
-- TOC entry 4826 (class 2604 OID 25021)
-- Name: tipologias id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipologias ALTER COLUMN id SET DEFAULT nextval('public.tipologias_id_seq'::regclass);


--
-- TOC entry 4814 (class 2604 OID 24981)
-- Name: usuarios id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id SET DEFAULT nextval('public.usuarios_id_seq'::regclass);


--
-- TOC entry 5094 (class 0 OID 24995)
-- Dependencies: 222
-- Data for Name: carteras; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.carteras (id, nombre_cartera, cuenta_nombre, identificacion_nombre, nombre_cliente_label, saldo_label, activa, fecha_creacion, lbl_nombre, lbl_saldo, lbl_telefono, lbl_estado) FROM stdin;
1	Cartera Demo	Tarjeta	DPI	Deudor	Deuda Total	t	2026-04-23 09:17:16.871377+00	PRESTAMO	DEUDA_ACTUAL	CELULAR	ESTADO
\.


--
-- TOC entry 5102 (class 0 OID 25048)
-- Dependencies: 230
-- Data for Name: clientes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.clientes (id, id_cartera, id_gestor_asignado, id_supervisor_cadena, cuenta, identificacion, nombre, saldo, telefono_1, telefono_2, estado, fecha_ultima_gestion, fecha_asignacion, search_vector, data_extras) FROM stdin;
6	1	36	34	55554444111133302784	1234567890101	Consuelo Porras	65000.00	42224444	22224444	activo	2026-05-18 02:59:36.999446+00	2026-05-17 00:34:07.687864+00	'1234567890101':3 '42224444':5 '55554444111133302784':4 'consuel':1 'porr':2	{"direccion": "Barrio Gerona", "tasa_inter__s": "14.5", "direccion_trabajo": "Barrio Gerona"}
5	1	35	34	4444555566661110	1234567890123	Miguel Angel Asturias	3500.03	44445555	\N	activo	2026-05-20 07:53:35.829706+00	2026-05-13 06:49:14.589522+00	'1234567890123':4 '44445555':6 '4444555566661110':5 'angel':2 'asturi':3 'miguel':1	{"direccion": "2a avenida 3-33 zona 2 Escuintla", "tasa_inter__s": "12", "direccion_trabajo": "n/a"}
\.


--
-- TOC entry 5114 (class 0 OID 25186)
-- Dependencies: 242
-- Data for Name: clientes_bk; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.clientes_bk (id_bk, id_original, lote_id, fecha_migracion, id_cartera, id_gestor_asignado, id_supervisor_cadena, cuenta, identificacion, nombre, saldo, telefono_1, telefono_2, estado, fecha_ultima_gestion) FROM stdin;
\.


--
-- TOC entry 5104 (class 0 OID 25082)
-- Dependencies: 232
-- Data for Name: data_extras; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.data_extras (id, id_cliente, id_extra, valor) FROM stdin;
\.


--
-- TOC entry 5096 (class 0 OID 25004)
-- Dependencies: 224
-- Data for Name: extras; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.extras (id, id_cartera, nombre_campo, etiqueta_display, tipo, orden_visual) FROM stdin;
\.


--
-- TOC entry 5122 (class 0 OID 32777)
-- Dependencies: 250
-- Data for Name: extras_cartera; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.extras_cartera (id, id_cartera, nombre_campo, etiqueta, tipo, orden, activo, modulo) FROM stdin;
1	1	direccion	Dirección	texto	0	t	clientes
2	1	direccion_trabajo	Dirección Trabajo	texto	0	t	clientes
3	1	tasa_inter__s	Tasa interes	texto	0	t	clientes
\.


--
-- TOC entry 5108 (class 0 OID 25134)
-- Dependencies: 236
-- Data for Name: extras_historial; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.extras_historial (id, id_historial, nombre_campo, valor) FROM stdin;
\.


--
-- TOC entry 5106 (class 0 OID 25103)
-- Dependencies: 234
-- Data for Name: historial; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.historial (id, id_cliente, id_usuario, fecha_gestion, estatus, telefono_utilizado, id_tipologia, comentario, lote_origen_id, data_extras, fecha_proxima_llamada) FROM stdin;
1	5	35	2026-05-14 02:55:50.746794+00	SINC	44440606	7	Cliente solicita le llamemos mañana a las 14:00 horas	\N	{}	\N
2	5	35	2026-05-16 06:55:26.448198+00	COMP		20	Cliente se compromete a pagar mañana a las 3 pm	\N	{}	2026-05-16 15:00:00
3	5	35	2026-05-16 07:06:47.854918+00	COMP		20	Cliente se compromete a pagar 500 mañana, quedó que le llamemos a las 3 pm para confirmar pago	\N	{}	2026-05-16 15:00:00
4	5	35	2026-05-16 07:19:22.99475+00	COMP	44444444	22	Compromiso de pago para mañana a las 15:00 pide confirmar pago	\N	{}	2026-05-17 15:00:00
5	5	35	2026-05-16 07:29:19.81636+00	COMP	44444444	20	prueba de compromiso	\N	{}	2026-05-17 15:00:00
6	5	35	2026-05-16 07:32:49.895295+00	COMP	44444444	20	prueba compromiso	\N	{}	2026-05-18 15:00:00
7	5	35	2026-05-16 07:34:43.410311+00	COMP	44444444	20	nueva prueba compromiso de 100 pesos	\N	{}	2026-05-17 15:00:00
12	5	35	2026-05-16 07:38:00.718796+00	COMP	44444444	20	va de nuez 100 pesitos a ver si caen	\N	{}	2026-05-17 15:00:00
14	5	35	2026-05-16 07:43:11.14591+00	COMP	44444444	20	otro paguito para el 18	\N	{}	2026-05-18 13:00:00
15	5	35	2026-05-16 07:52:23.250275+00	COMP	44444444	20	otros 100 compromiso	\N	{}	2026-05-18 15:00:00
16	5	35	2026-05-16 07:53:44.598533+00	COMP	44444444	20	otra vez	\N	{}	2026-05-18 15:00:00
17	5	35	2026-05-16 07:57:12.167989+00	COMP	44444444	20	van 5000 pesitos de promesa, a ver si se guardan	\N	{}	2026-05-18 15:00:00
18	5	35	2026-05-16 07:59:23.864319+00	COMP	44444444	20	a ver si graba 5000	\N	{}	2026-05-25 15:00:00
22	5	35	2026-05-17 00:26:46.396119+00	COMP	44444444	16	probando 2500	\N	{}	\N
23	6	36	2026-05-17 00:37:27.384206+00	COMP	44444444	20	Pueba grabación 5000	\N	{}	2026-03-25 11:00:00
27	6	36	2026-05-17 02:59:57.34925+00	COMP	44444444	8	preuba de compromiso de pago	\N	{}	2026-05-25 15:00:00
30	6	36	2026-05-17 05:43:35.447427+00	PAGO	44444444	9	Se recibe pago parcial de Q5000.00	\N	{}	2026-05-25 13:00:00
31	5	34	2026-05-17 06:19:34.697902+00	PAGO	44444444	9	prueba de pago de 2000	\N	{}	2026-05-25 13:00:00
32	6	36	2026-05-18 02:59:36.999446+00	PAGO	22224444	9	prueba 5000\r\nse realiza la prueba para poder comprobar si el nuevo formulario realiza la transacción	\N	{}	2025-06-02 10:00:00
36	5	35	2026-05-18 06:51:14.608771+00	PAGO	44445555	9	promesa cumplida por Q5000 del 25/052026	\N	{"id_promesa_aplicada": 1}	2026-06-15 15:00:00
35	5	35	2026-05-18 06:48:35.795441+00	PAGO	44444444	9	prueba promesa cumplida de 2500	\N	{"id_promesa_aplicada": 2}	2026-06-15 15:00:00
34	5	35	2026-05-18 06:40:12.98246+00	PAGO	44444444	9	promesa 19/05/2026 por Q2500 cumplida boleta 12315 Industrial	\N	[]	2026-06-15 13:00:00
33	5	35	2026-05-18 06:10:59.261957+00	PAGO	44445555	9	promesa cumplida del 19/05/2026 por Q2500, lo llamaremos el otro mes el 15/05/2026 a las 11:00 horas	\N	[]	2026-05-19 11:00:00
37	5	35	2026-05-18 06:54:57.761619+00	PAGO	44445555	9	prueba de paso sin promesa por 2500	\N	[]	2026-06-25 13:00:00
38	5	35	2026-05-18 07:00:10.188058+00	COMP	44444444	8	Cliente se compromete a pagar, realizará el pago hoy y notificará de boleta gyt por Q1000	\N	[]	2026-05-18 01:00:00
39	5	35	2026-05-18 20:54:58.353521+00	COMP	44445555	17	Cliente se compromete a pagar 100 hoy	\N	[]	2026-05-18 19:06:00
41	5	35	2026-05-19 01:15:12.817324+00	PAGO	44444444	9	Prueba de tipología pendiente	\N	{"id_promesa_aplicada": 5}	2026-06-25 15:00:00
40	5	35	2026-05-19 01:02:39.226268+00	PAGO		9	pago para hoy Q100	\N	{"id_promesa_aplicada": 6}	2026-06-25 13:00:00
42	5	35	2026-05-19 01:22:25.512535+00	COMP	44445555	17	Cliente se compromete a pagar Q100 y que le llamemos en 5 minutos	\N	[]	2026-01-18 19:22:00
43	5	35	2026-05-19 01:25:18.672738+00	PAGO	44444444	9	prueba de pago de 100	\N	{"id_promesa_aplicada": 7}	2026-06-25 13:00:00
45	5	35	2026-05-19 01:58:37.709883+00	COMP	44444444	8	prueba de compromiso Q100	\N	[]	2026-05-18 20:00:00
46	5	35	2026-05-19 01:59:23.506233+00	PAGO	44444444	9	prueba de pago Q100	\N	{"id_promesa_aplicada": 9}	2026-06-26 11:00:00
47	5	35	2026-05-19 02:06:16.81838+00	COMP	44445555	8	Cliente se compromete hoy para pagar Q100, se le llamará en 5 munutos	\N	[]	2026-05-18 20:09:00
48	5	35	2026-05-19 02:07:26.506682+00	PAGO	44444444	9	prueba pago 100 exitosa	\N	{"id_promesa_aplicada": 10}	2026-05-31 13:00:00
49	5	35	2026-05-19 02:14:13.382898+00	COMP	44445555	8	Cliente se compromete hoy a pagar Q100 en el BI	\N	[]	2026-05-18 20:15:00
50	5	35	2026-05-19 02:16:06.352943+00	PAGO	44445555	9	Cliente realiza pago de Q100 en el BI boleta 123456, se llamará a fin de més	\N	{"id_promesa_aplicada": 11}	2026-05-31 11:00:00
51	5	35	2026-05-19 04:06:02.226365+00	COMP	44444444	8	Prueba promesa 100	\N	[]	2026-05-31 15:00:00
52	5	35	2026-05-19 04:11:43.921273+00	PAGG	44444444	9	aplicación pago 100	\N	{"id_promesa_aplicada": 12}	2026-05-31 13:00:00
85	5	35	2026-05-20 07:52:55.64751+00	COMP	44444444	20	prueba 100 quetzales	\N	[]	2026-05-20 01:55:00
86	5	35	2026-05-20 07:53:35.829706+00	PAGG	44444444	9	prueba de grabación pago 100	\N	{"id_promesa_aplicada": 13}	2026-06-15 13:00:00
\.


--
-- TOC entry 5116 (class 0 OID 25199)
-- Dependencies: 244
-- Data for Name: historial_bk; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.historial_bk (id_bk, id_original, lote_id, fecha_migracion, id_cliente, id_usuario, fecha_gestion, estatus, telefono_utilizado, id_tipologia, comentario) FROM stdin;
\.


--
-- TOC entry 5120 (class 0 OID 25229)
-- Dependencies: 248
-- Data for Name: logs_asistencia; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.logs_asistencia (id, usuario_id, entrada, salida, horas_trabajadas, fecha) FROM stdin;
\.


--
-- TOC entry 5118 (class 0 OID 25214)
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
\.


--
-- TOC entry 5100 (class 0 OID 25031)
-- Dependencies: 228
-- Data for Name: lotes; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.lotes (id, fecha_ejecucion, usuario_id, tipo_operacion, cantidad_registros, observaciones, estado) FROM stdin;
\.


--
-- TOC entry 5112 (class 0 OID 25168)
-- Dependencies: 240
-- Data for Name: pagos; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pagos (id, id_cliente, monto, fecha_pago, referencia_bancaria, estatus, validado_por, fecha_validacion, id_historial) FROM stdin;
3	6	5000.00	2026-05-17 05:43:35.447427	Pago boleto 123456789 banco industrial confirma gerente	PAGO	1	2026-05-17 06:16:25.350892+00	30
4	5	2000.00	2026-05-17 06:19:34.697902	confirmación con pago aplicado con gestor	PAGO	1	2026-05-17 06:20:47.604868+00	31
5	6	5000.00	2026-05-18 02:59:36.999446	confirmado banco azteca boleta 2221231	PAGO	1	2026-05-18 03:00:20.087732+00	32
9	5	5000.00	2026-05-18 06:51:14.608771	boleta 1231231 BI	PAGO	1	2026-05-18 06:52:47.409432+00	36
8	5	2500.00	2026-05-18 06:48:35.795441	boleta 13213213 banrural	PAGO	1	2026-05-18 06:52:59.454908+00	35
7	5	2500.00	2026-05-18 06:40:12.98246	boelta 6513132 gyt	PAGO	1	2026-05-18 06:53:11.979656+00	34
6	5	2500.00	2026-05-18 06:10:59.261957	boleta 131312 BAM	PAGO	1	2026-05-18 06:53:30.222811+00	33
10	5	2500.00	2026-05-18 06:54:57.761619	Pago boleto 123456789 banco industrial confirma gerente	PAGO	1	2026-05-18 06:55:29.51951+00	37
12	5	1000.00	2026-05-19 01:15:12.817324	Pago boleto 123456789 banco industrial confirma gerente	PAGO	1	2026-05-19 01:15:51.393915+00	41
11	5	99.97	2026-05-19 01:02:39.226268	confirmación con pago aplicado con gestor	PAGO	1	2026-05-19 01:16:08.427402+00	40
13	5	100.00	2026-05-19 01:25:18.672738	boleta 1231231 BI	PAGO	1	2026-05-19 01:26:05.587334+00	43
14	5	100.00	2026-05-19 01:59:23.506233	Pago boleto 123456789 banco industrial confirma gerente	PAGO	1	2026-05-19 02:00:16.143535+00	46
15	5	100.00	2026-05-19 02:07:26.506682	Pago boleto 123456789 banco industrial confirma gerente	PAGO	1	2026-05-19 02:07:48.750749+00	48
16	5	100.00	2026-05-19 02:16:06.352943	confirmación boleta 123456 por Q100 BI, confirma Oficina Central BAC	PAGO	1	2026-05-19 02:17:35.359613+00	50
17	5	100.00	2026-05-19 04:11:43.921273		PAGG	\N	\N	52
50	5	100.00	2026-05-20 07:53:35.829706		PAGG	\N	\N	86
\.


--
-- TOC entry 5110 (class 0 OID 25148)
-- Dependencies: 238
-- Data for Name: promesas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.promesas (id, id_cliente, id_usuario, monto_prometido, fecha_compromiso, fecha_registro, estatus, id_historial) FROM stdin;
3	6	36	5000.00	2026-05-19 00:00:00	2026-05-17 00:37:27.384206+00	pendiente	\N
4	6	36	500.00	2026-05-19 00:00:00	2026-05-17 02:59:57.34925+00	pendiente	\N
2	5	35	2500.00	2026-05-19 00:00:00	2026-05-17 00:26:46.396119+00	cumplida	\N
1	5	35	5000.00	2026-05-25 15:00:00	2026-05-16 07:59:23.864319+00	cumplida	\N
6	5	35	100.00	2026-05-21 00:00:00	2026-05-18 20:54:58.353521+00	cumplida	\N
5	5	35	1000.00	2026-05-18 19:23:00	2026-05-18 07:00:10.188058+00	cumplida	\N
7	5	35	100.00	2026-05-21 00:00:00	2026-05-19 01:22:25.512535+00	cumplida	\N
9	5	35	100.00	2026-05-18 20:00:00	2026-05-19 01:58:37.709883+00	cumplida	\N
10	5	35	100.00	2026-05-18 20:09:00	2026-05-19 02:06:16.81838+00	cumplida	\N
11	5	35	100.00	2026-05-18 20:15:00	2026-05-19 02:14:13.382898+00	cumplida	\N
12	5	35	100.00	2026-05-18 22:09:00	2026-05-19 04:06:02.226365+00	cumplida	\N
13	5	35	100.00	2026-05-20 01:55:00	2026-05-20 07:52:55.64751+00	cumplida	85
\.


--
-- TOC entry 5098 (class 0 OID 25018)
-- Dependencies: 226
-- Data for Name: tipologias; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.tipologias (id, clase, padre_id, nombre, codigo_origen, id_cartera, requiere_proxima_fecha, requiere_monto, estatus_default) FROM stdin;
6	T	\N	Descartado	105	1	f	f	SINC
9	S	1	Pago Parcial Realizado	202	1	t	t	PAGG
1	T	\N	Contacto Exitoso	100	1	t	f	SINC
2	T	\N	Sin Contacto	101	1	t	f	SINC
4	T	\N	Negativa de Pago	103	1	t	f	SINC
5	T	\N	Gestión Judicial	104	1	t	f	SINC
7	S	1	Cliente Colaborativo	200	1	t	f	SINC
10	S	1	Solicita Reestructuración	203	1	t	f	SINC
11	S	1	Reconoce Deuda	204	1	t	f	SINC
12	S	2	Teléfono No Existe	210	1	t	f	SINC
13	S	2	Teléfono Apagado	211	1	t	f	SINC
14	S	2	Sin Respuesta (3 intentos)	212	1	t	f	SINC
15	S	2	Número Equivocado	213	1	t	f	SINC
16	S	2	Cliente No Localizado	214	1	t	f	SINC
21	S	4	No Reconoce Deuda	230	1	t	f	SINC
22	S	4	Disputa el Monto	231	1	t	f	SINC
23	S	4	Sin Capacidad de Pago	232	1	t	f	SINC
24	S	4	Niega Obligación	233	1	t	f	SINC
25	S	4	Se Niega a Pagar	234	1	t	f	SINC
26	S	5	Proceso Legal Iniciado	240	1	t	f	SINC
27	S	5	Embargo en Proceso	241	1	t	f	SINC
28	S	5	Demanda Judicial	242	1	t	f	SINC
3	T	\N	Promesa de Pago	102	1	t	f	SINC
29	S	6	Fallecido	250	1	f	f	SINC
30	S	6	Cliente Irrelocalizable	251	1	f	f	SINC
31	S	6	Cuenta Cancelada	252	1	f	f	SINC
32	S	6	Prescripción	253	1	f	f	SINC
8	S	1	Se Compromete a Pagar	201	1	t	t	COMP
17	S	3	Compromiso Hoy	220	1	t	t	COMP
18	S	3	Compromiso Esta Semana	221	1	t	t	COMP
19	S	3	Compromiso Próximo Mes	222	1	t	t	COMP
20	S	3	Compromiso con Fecha Específica	223	1	t	t	COMP
\.


--
-- TOC entry 5092 (class 0 OID 24978)
-- Dependencies: 220
-- Data for Name: usuarios; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.usuarios (id, nombre, usuario, clave_hash, rol, supervisor_id, activo, fecha_creacion, fecha_ultimo_login) FROM stdin;
34	Juan Pérez	jperez	$2y$10$8kPJWtWR1gMR.wVitIxDZ..CZG47y.POwlnNGQNl9eKxCHANcdSNC	supervisor	\N	t	2026-05-01 03:30:04.919826+00	\N
35	Gerson Solis	gsolis	$2y$10$8kPJWtWR1gMR.wVitIxDZ..CZG47y.POwlnNGQNl9eKxCHANcdSNC	gestor	34	t	2026-05-01 03:30:29.898543+00	\N
1	Administrador General	admin	$2y$10$8kPJWtWR1gMR.wVitIxDZ..CZG47y.POwlnNGQNl9eKxCHANcdSNC	admin	\N	t	2026-04-23 09:17:16.871377+00	\N
36	edgar ricardo arjona	earjona	$2y$10$34oFnbmOEPG7tPxjRKsn.uMmdEJ58PajayJdQRMZyg21aumuqAUUO	gestor	34	t	2026-05-17 00:30:30.57159+00	\N
\.


--
-- TOC entry 5146 (class 0 OID 0)
-- Dependencies: 221
-- Name: carteras_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.carteras_id_seq', 33, true);


--
-- TOC entry 5147 (class 0 OID 0)
-- Dependencies: 241
-- Name: clientes_bk_id_bk_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.clientes_bk_id_bk_seq', 1, false);


--
-- TOC entry 5148 (class 0 OID 0)
-- Dependencies: 229
-- Name: clientes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.clientes_id_seq', 6, true);


--
-- TOC entry 5149 (class 0 OID 0)
-- Dependencies: 231
-- Name: data_extras_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.data_extras_id_seq', 1, false);


--
-- TOC entry 5150 (class 0 OID 0)
-- Dependencies: 249
-- Name: extras_cartera_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.extras_cartera_id_seq', 3, true);


--
-- TOC entry 5151 (class 0 OID 0)
-- Dependencies: 235
-- Name: extras_historial_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.extras_historial_id_seq', 1, false);


--
-- TOC entry 5152 (class 0 OID 0)
-- Dependencies: 223
-- Name: extras_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.extras_id_seq', 1, false);


--
-- TOC entry 5153 (class 0 OID 0)
-- Dependencies: 243
-- Name: historial_bk_id_bk_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.historial_bk_id_bk_seq', 1, false);


--
-- TOC entry 5154 (class 0 OID 0)
-- Dependencies: 233
-- Name: historial_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.historial_id_seq', 86, true);


--
-- TOC entry 5155 (class 0 OID 0)
-- Dependencies: 247
-- Name: logs_asistencia_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.logs_asistencia_id_seq', 1, false);


--
-- TOC entry 5156 (class 0 OID 0)
-- Dependencies: 245
-- Name: logs_auditoria_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.logs_auditoria_id_seq', 89, true);


--
-- TOC entry 5157 (class 0 OID 0)
-- Dependencies: 227
-- Name: lotes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.lotes_id_seq', 1, false);


--
-- TOC entry 5158 (class 0 OID 0)
-- Dependencies: 239
-- Name: pagos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pagos_id_seq', 50, true);


--
-- TOC entry 5159 (class 0 OID 0)
-- Dependencies: 237
-- Name: promesas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.promesas_id_seq', 13, true);


--
-- TOC entry 5160 (class 0 OID 0)
-- Dependencies: 225
-- Name: tipologias_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.tipologias_id_seq', 32, true);


--
-- TOC entry 5161 (class 0 OID 0)
-- Dependencies: 219
-- Name: usuarios_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.usuarios_id_seq', 36, true);


--
-- TOC entry 4875 (class 2606 OID 25002)
-- Name: carteras carteras_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.carteras
    ADD CONSTRAINT carteras_pkey PRIMARY KEY (id);


--
-- TOC entry 4908 (class 2606 OID 25192)
-- Name: clientes_bk clientes_bk_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes_bk
    ADD CONSTRAINT clientes_bk_pkey PRIMARY KEY (id_bk);


--
-- TOC entry 4886 (class 2606 OID 25059)
-- Name: clientes clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_pkey PRIMARY KEY (id);


--
-- TOC entry 4894 (class 2606 OID 25091)
-- Name: data_extras data_extras_id_cliente_id_extra_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.data_extras
    ADD CONSTRAINT data_extras_id_cliente_id_extra_key UNIQUE (id_cliente, id_extra);


--
-- TOC entry 4896 (class 2606 OID 25089)
-- Name: data_extras data_extras_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.data_extras
    ADD CONSTRAINT data_extras_pkey PRIMARY KEY (id);


--
-- TOC entry 4916 (class 2606 OID 32787)
-- Name: extras_cartera extras_cartera_id_cartera_nombre_campo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_cartera
    ADD CONSTRAINT extras_cartera_id_cartera_nombre_campo_key UNIQUE (id_cartera, nombre_campo);


--
-- TOC entry 4918 (class 2606 OID 32785)
-- Name: extras_cartera extras_cartera_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_cartera
    ADD CONSTRAINT extras_cartera_pkey PRIMARY KEY (id);


--
-- TOC entry 4901 (class 2606 OID 25141)
-- Name: extras_historial extras_historial_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_historial
    ADD CONSTRAINT extras_historial_pkey PRIMARY KEY (id);


--
-- TOC entry 4877 (class 2606 OID 25011)
-- Name: extras extras_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras
    ADD CONSTRAINT extras_pkey PRIMARY KEY (id);


--
-- TOC entry 4910 (class 2606 OID 25207)
-- Name: historial_bk historial_bk_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial_bk
    ADD CONSTRAINT historial_bk_pkey PRIMARY KEY (id_bk);


--
-- TOC entry 4898 (class 2606 OID 25112)
-- Name: historial historial_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial
    ADD CONSTRAINT historial_pkey PRIMARY KEY (id);


--
-- TOC entry 4914 (class 2606 OID 25235)
-- Name: logs_asistencia logs_asistencia_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logs_asistencia
    ADD CONSTRAINT logs_asistencia_pkey PRIMARY KEY (id);


--
-- TOC entry 4912 (class 2606 OID 25222)
-- Name: logs_auditoria logs_auditoria_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logs_auditoria
    ADD CONSTRAINT logs_auditoria_pkey PRIMARY KEY (id);


--
-- TOC entry 4884 (class 2606 OID 25041)
-- Name: lotes lotes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lotes
    ADD CONSTRAINT lotes_pkey PRIMARY KEY (id);


--
-- TOC entry 4906 (class 2606 OID 25174)
-- Name: pagos pagos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_pkey PRIMARY KEY (id);


--
-- TOC entry 4904 (class 2606 OID 25156)
-- Name: promesas promesas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas
    ADD CONSTRAINT promesas_pkey PRIMARY KEY (id);


--
-- TOC entry 4880 (class 2606 OID 25024)
-- Name: tipologias tipologias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipologias
    ADD CONSTRAINT tipologias_pkey PRIMARY KEY (id);


--
-- TOC entry 4882 (class 2606 OID 32775)
-- Name: tipologias uq_tipologia_cartera; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipologias
    ADD CONSTRAINT uq_tipologia_cartera UNIQUE (codigo_origen, id_cartera);


--
-- TOC entry 4871 (class 2606 OID 24986)
-- Name: usuarios usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id);


--
-- TOC entry 4873 (class 2606 OID 24988)
-- Name: usuarios usuarios_usuario_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_usuario_key UNIQUE (usuario);


--
-- TOC entry 4887 (class 1259 OID 25076)
-- Name: idx_clientes_cartera; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_cartera ON public.clientes USING btree (id_cartera);


--
-- TOC entry 4888 (class 1259 OID 32801)
-- Name: idx_clientes_extras; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_extras ON public.clientes USING gin (data_extras);


--
-- TOC entry 4889 (class 1259 OID 25077)
-- Name: idx_clientes_gestor; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_gestor ON public.clientes USING btree (id_gestor_asignado);


--
-- TOC entry 4890 (class 1259 OID 25080)
-- Name: idx_clientes_identificacion; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_identificacion ON public.clientes USING btree (identificacion);


--
-- TOC entry 4891 (class 1259 OID 25079)
-- Name: idx_clientes_search; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_search ON public.clientes USING gin (search_vector);


--
-- TOC entry 4892 (class 1259 OID 25078)
-- Name: idx_clientes_supervisor; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_supervisor ON public.clientes USING btree (id_supervisor_cadena);


--
-- TOC entry 4919 (class 1259 OID 32793)
-- Name: idx_extras_cartera; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_extras_cartera ON public.extras_cartera USING btree (id_cartera);


--
-- TOC entry 4899 (class 1259 OID 40970)
-- Name: idx_historial_cliente_fecha_desc; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_historial_cliente_fecha_desc ON public.historial USING btree (id_cliente, fecha_gestion DESC) WHERE ((estatus)::text = ANY ((ARRAY['SINC'::character varying, 'COMP'::character varying])::text[]));


--
-- TOC entry 4902 (class 1259 OID 57350)
-- Name: idx_promesas_historial; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_promesas_historial ON public.promesas USING btree (id_historial);


--
-- TOC entry 4878 (class 1259 OID 32773)
-- Name: idx_tipologias_cartera; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_tipologias_cartera ON public.tipologias USING btree (id_cartera);


--
-- TOC entry 4945 (class 2620 OID 25075)
-- Name: clientes tsvector_update_clientes; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER tsvector_update_clientes BEFORE INSERT OR UPDATE ON public.clientes FOR EACH ROW EXECUTE FUNCTION tsvector_update_trigger('search_vector', 'pg_catalog.spanish', 'nombre', 'identificacion', 'cuenta', 'telefono_1');


--
-- TOC entry 4940 (class 2606 OID 25193)
-- Name: clientes_bk clientes_bk_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes_bk
    ADD CONSTRAINT clientes_bk_lote_id_fkey FOREIGN KEY (lote_id) REFERENCES public.lotes(id);


--
-- TOC entry 4925 (class 2606 OID 25060)
-- Name: clientes clientes_id_cartera_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_id_cartera_fkey FOREIGN KEY (id_cartera) REFERENCES public.carteras(id);


--
-- TOC entry 4926 (class 2606 OID 25065)
-- Name: clientes clientes_id_gestor_asignado_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_id_gestor_asignado_fkey FOREIGN KEY (id_gestor_asignado) REFERENCES public.usuarios(id);


--
-- TOC entry 4927 (class 2606 OID 25070)
-- Name: clientes clientes_id_supervisor_cadena_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.clientes
    ADD CONSTRAINT clientes_id_supervisor_cadena_fkey FOREIGN KEY (id_supervisor_cadena) REFERENCES public.usuarios(id);


--
-- TOC entry 4928 (class 2606 OID 25092)
-- Name: data_extras data_extras_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.data_extras
    ADD CONSTRAINT data_extras_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id) ON DELETE CASCADE;


--
-- TOC entry 4929 (class 2606 OID 25097)
-- Name: data_extras data_extras_id_extra_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.data_extras
    ADD CONSTRAINT data_extras_id_extra_fkey FOREIGN KEY (id_extra) REFERENCES public.extras(id);


--
-- TOC entry 4944 (class 2606 OID 32788)
-- Name: extras_cartera extras_cartera_id_cartera_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_cartera
    ADD CONSTRAINT extras_cartera_id_cartera_fkey FOREIGN KEY (id_cartera) REFERENCES public.carteras(id) ON DELETE CASCADE;


--
-- TOC entry 4934 (class 2606 OID 25142)
-- Name: extras_historial extras_historial_id_historial_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras_historial
    ADD CONSTRAINT extras_historial_id_historial_fkey FOREIGN KEY (id_historial) REFERENCES public.historial(id) ON DELETE CASCADE;


--
-- TOC entry 4921 (class 2606 OID 25012)
-- Name: extras extras_id_cartera_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.extras
    ADD CONSTRAINT extras_id_cartera_fkey FOREIGN KEY (id_cartera) REFERENCES public.carteras(id) ON DELETE CASCADE;


--
-- TOC entry 4937 (class 2606 OID 49180)
-- Name: pagos fk_pagos_historial; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT fk_pagos_historial FOREIGN KEY (id_historial) REFERENCES public.historial(id);


--
-- TOC entry 4941 (class 2606 OID 25208)
-- Name: historial_bk historial_bk_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial_bk
    ADD CONSTRAINT historial_bk_lote_id_fkey FOREIGN KEY (lote_id) REFERENCES public.lotes(id);


--
-- TOC entry 4930 (class 2606 OID 25113)
-- Name: historial historial_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial
    ADD CONSTRAINT historial_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id);


--
-- TOC entry 4931 (class 2606 OID 25123)
-- Name: historial historial_id_tipologia_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial
    ADD CONSTRAINT historial_id_tipologia_fkey FOREIGN KEY (id_tipologia) REFERENCES public.tipologias(id);


--
-- TOC entry 4932 (class 2606 OID 25118)
-- Name: historial historial_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial
    ADD CONSTRAINT historial_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id);


--
-- TOC entry 4933 (class 2606 OID 25128)
-- Name: historial historial_lote_origen_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.historial
    ADD CONSTRAINT historial_lote_origen_id_fkey FOREIGN KEY (lote_origen_id) REFERENCES public.lotes(id);


--
-- TOC entry 4943 (class 2606 OID 25236)
-- Name: logs_asistencia logs_asistencia_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logs_asistencia
    ADD CONSTRAINT logs_asistencia_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- TOC entry 4942 (class 2606 OID 25223)
-- Name: logs_auditoria logs_auditoria_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.logs_auditoria
    ADD CONSTRAINT logs_auditoria_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- TOC entry 4924 (class 2606 OID 25042)
-- Name: lotes lotes_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lotes
    ADD CONSTRAINT lotes_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id);


--
-- TOC entry 4938 (class 2606 OID 25175)
-- Name: pagos pagos_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id);


--
-- TOC entry 4939 (class 2606 OID 25180)
-- Name: pagos pagos_validado_por_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pagos
    ADD CONSTRAINT pagos_validado_por_fkey FOREIGN KEY (validado_por) REFERENCES public.usuarios(id);


--
-- TOC entry 4935 (class 2606 OID 25157)
-- Name: promesas promesas_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas
    ADD CONSTRAINT promesas_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.clientes(id);


--
-- TOC entry 4936 (class 2606 OID 25162)
-- Name: promesas promesas_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.promesas
    ADD CONSTRAINT promesas_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuarios(id);


--
-- TOC entry 4922 (class 2606 OID 32768)
-- Name: tipologias tipologias_id_cartera_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipologias
    ADD CONSTRAINT tipologias_id_cartera_fkey FOREIGN KEY (id_cartera) REFERENCES public.carteras(id) ON DELETE CASCADE;


--
-- TOC entry 4923 (class 2606 OID 25025)
-- Name: tipologias tipologias_padre_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tipologias
    ADD CONSTRAINT tipologias_padre_id_fkey FOREIGN KEY (padre_id) REFERENCES public.tipologias(id);


--
-- TOC entry 4920 (class 2606 OID 24989)
-- Name: usuarios usuarios_supervisor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_supervisor_id_fkey FOREIGN KEY (supervisor_id) REFERENCES public.usuarios(id);


-- Completed on 2026-05-20 22:29:00

--
-- PostgreSQL database dump complete
--


--
-- PostgreSQL database dump
--

-- Dumped from database version 16.9 (Ubuntu 16.9-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.9 (Ubuntu 16.9-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: ai_decision_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_decision_logs (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    tenant_id character varying(255) NOT NULL,
    symbol character varying(20) NOT NULL,
    decision_type character varying(255) NOT NULL,
    ai_provider character varying(50) NOT NULL,
    decision character varying(20),
    confidence smallint,
    leverage numeric(8,2),
    stop_loss numeric(16,8),
    take_profit numeric(16,8),
    reason text,
    market_price numeric(16,8),
    coingecko_score numeric(5,2),
    market_sentiment numeric(5,2),
    risk_profile character varying(20) DEFAULT 'moderate'::character varying NOT NULL,
    context_data json,
    ai_response json,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT ai_decision_logs_decision_type_check CHECK (((decision_type)::text = ANY ((ARRAY['position_open'::character varying, 'position_manage'::character varying, 'position_close'::character varying, 'scan'::character varying])::text[])))
);


--
-- Name: ai_decision_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ai_decision_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ai_decision_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ai_decision_logs_id_seq OWNED BY public.ai_decision_logs.id;


--
-- Name: ai_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_logs (
    id bigint NOT NULL,
    cycle_uuid uuid NOT NULL,
    symbol character varying(255) NOT NULL,
    provider character varying(255) NOT NULL,
    stage character varying(255) NOT NULL,
    action character varying(255),
    confidence smallint,
    input_ctx json NOT NULL,
    raw_output json,
    latency_ms integer,
    reason text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT ai_logs_action_check CHECK (((action)::text = ANY ((ARRAY['LONG'::character varying, 'SHORT'::character varying, 'HOLD'::character varying, 'CLOSE'::character varying, 'NO_TRADE'::character varying])::text[]))),
    CONSTRAINT ai_logs_stage_check CHECK (((stage)::text = ANY ((ARRAY['STAGE1'::character varying, 'STAGE2'::character varying, 'FINAL'::character varying])::text[])))
);


--
-- Name: ai_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ai_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ai_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ai_logs_id_seq OWNED BY public.ai_logs.id;


--
-- Name: ai_providers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ai_providers (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    enabled boolean DEFAULT true NOT NULL,
    model character varying(255),
    timeout_ms integer DEFAULT 30000 NOT NULL,
    max_tokens integer DEFAULT 2048 NOT NULL,
    priority smallint DEFAULT '10'::smallint NOT NULL,
    weight numeric(3,2) DEFAULT '1'::numeric NOT NULL,
    cost_per_1k_tokens numeric(8,4) DEFAULT '0'::numeric NOT NULL,
    meta json,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


--
-- Name: ai_providers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ai_providers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ai_providers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ai_providers_id_seq OWNED BY public.ai_providers.id;


--
-- Name: alerts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.alerts (
    id bigint NOT NULL,
    type character varying(255) NOT NULL,
    message text NOT NULL,
    severity character varying(255) DEFAULT 'info'::character varying NOT NULL,
    context json,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    acknowledged_by bigint,
    acknowledged_at timestamp(0) without time zone,
    resolved_by bigint,
    resolved_at timestamp(0) without time zone,
    resolution text,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT alerts_severity_check CHECK (((severity)::text = ANY ((ARRAY['info'::character varying, 'warning'::character varying, 'error'::character varying, 'critical'::character varying])::text[])))
);


--
-- Name: alerts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.alerts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: alerts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.alerts_id_seq OWNED BY public.alerts.id;


--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    user_id bigint,
    tenant_id bigint,
    action character varying(255) NOT NULL,
    resource_type character varying(255) NOT NULL,
    resource_id bigint,
    old_values json,
    new_values json,
    ip_address inet,
    user_agent text,
    session_id character varying(255),
    request_id character varying(255),
    metadata json,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: backtest_data; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.backtest_data (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    tenant_id character varying(255) NOT NULL,
    run_id character varying(50) NOT NULL,
    symbol character varying(20) NOT NULL,
    trade_time timestamp(0) without time zone NOT NULL,
    open_price numeric(16,8) NOT NULL,
    high_price numeric(16,8) NOT NULL,
    low_price numeric(16,8) NOT NULL,
    close_price numeric(16,8) NOT NULL,
    volume numeric(20,8) NOT NULL,
    ai_decisions json NOT NULL,
    final_decision character varying(20) NOT NULL,
    consensus_confidence smallint NOT NULL,
    position_side character varying(255),
    entry_price numeric(16,8),
    exit_price numeric(16,8),
    pnl numeric(16,8),
    is_winner boolean,
    coingecko_reliability numeric(5,2),
    coingecko_sentiment numeric(5,2),
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT backtest_data_position_side_check CHECK (((position_side)::text = ANY ((ARRAY['LONG'::character varying, 'SHORT'::character varying])::text[])))
);


--
-- Name: backtest_data_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.backtest_data_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: backtest_data_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.backtest_data_id_seq OWNED BY public.backtest_data.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: consensus_decisions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.consensus_decisions (
    id bigint NOT NULL,
    cycle_uuid uuid NOT NULL,
    symbol character varying(255) NOT NULL,
    round1 json,
    round2 json,
    final_action character varying(255) NOT NULL,
    final_confidence smallint NOT NULL,
    meta json,
    majority_lock boolean DEFAULT true NOT NULL,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT consensus_decisions_final_action_check CHECK (((final_action)::text = ANY ((ARRAY['LONG'::character varying, 'SHORT'::character varying, 'HOLD'::character varying, 'CLOSE'::character varying, 'NO_TRADE'::character varying])::text[])))
);


--
-- Name: consensus_decisions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.consensus_decisions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: consensus_decisions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.consensus_decisions_id_seq OWNED BY public.consensus_decisions.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: fills; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.fills (
    id uuid NOT NULL,
    order_id uuid NOT NULL,
    trade_id bigint,
    quantity numeric(20,8) NOT NULL,
    price numeric(20,8) NOT NULL,
    commission numeric(20,8) DEFAULT '0'::numeric NOT NULL,
    commission_asset character varying(10),
    exchange_fill_id character varying(255) NOT NULL,
    filled_at timestamp(0) with time zone NOT NULL,
    created_at timestamp(0) with time zone NOT NULL,
    updated_at timestamp(0) with time zone NOT NULL
);


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at timestamp with time zone NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at timestamp with time zone NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: lab_metrics; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lab_metrics (
    id bigint NOT NULL,
    as_of date NOT NULL,
    pf numeric(10,4),
    maxdd_pct numeric(10,4),
    sharpe numeric(10,4),
    meta json,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    lab_run_id bigint,
    equity numeric(15,2),
    win_rate numeric(5,2),
    avg_trade_pct numeric(8,4)
);


--
-- Name: lab_metrics_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lab_metrics_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lab_metrics_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lab_metrics_id_seq OWNED BY public.lab_metrics.id;


--
-- Name: lab_runs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lab_runs (
    id bigint NOT NULL,
    symbols json NOT NULL,
    initial_equity numeric(15,2) NOT NULL,
    final_equity numeric(15,2),
    risk_pct numeric(5,2) NOT NULL,
    max_leverage integer NOT NULL,
    total_trades integer DEFAULT 0 NOT NULL,
    winning_trades integer DEFAULT 0 NOT NULL,
    losing_trades integer DEFAULT 0 NOT NULL,
    final_pf numeric(10,6),
    start_date timestamp(0) without time zone NOT NULL,
    end_date timestamp(0) without time zone,
    status character varying(255) DEFAULT 'RUNNING'::character varying NOT NULL,
    meta json,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT lab_runs_status_check CHECK (((status)::text = ANY ((ARRAY['RUNNING'::character varying, 'COMPLETED'::character varying, 'FAILED'::character varying])::text[])))
);


--
-- Name: lab_runs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lab_runs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lab_runs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lab_runs_id_seq OWNED BY public.lab_runs.id;


--
-- Name: lab_trades; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.lab_trades (
    id bigint NOT NULL,
    symbol character varying(255) NOT NULL,
    side character varying(255) NOT NULL,
    qty numeric(24,10) DEFAULT '0'::numeric NOT NULL,
    entry_price numeric(24,10) NOT NULL,
    exit_price numeric(24,10),
    opened_at timestamp(0) without time zone,
    closed_at timestamp(0) without time zone,
    pnl_quote numeric(24,10),
    pnl_pct numeric(12,6),
    cycle_uuid character varying(255),
    meta json,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT lab_trades_side_check CHECK (((side)::text = ANY ((ARRAY['LONG'::character varying, 'SHORT'::character varying])::text[])))
);


--
-- Name: lab_trades_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.lab_trades_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: lab_trades_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.lab_trades_id_seq OWNED BY public.lab_trades.id;


--
-- Name: market_data; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.market_data (
    id bigint NOT NULL,
    "timestamp" timestamp(0) with time zone NOT NULL,
    symbol character varying(32) NOT NULL,
    open numeric(20,8),
    high numeric(20,8),
    low numeric(20,8),
    close numeric(20,8),
    volume numeric(32,8),
    indicators json,
    source character varying(255) DEFAULT 'unknown'::character varying NOT NULL
);


--
-- Name: market_data_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.market_data_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: market_data_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.market_data_id_seq OWNED BY public.market_data.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: orders; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.orders (
    id uuid NOT NULL,
    tenant_id bigint NOT NULL,
    user_id bigint NOT NULL,
    symbol character varying(20) NOT NULL,
    side character varying(255) NOT NULL,
    order_type character varying(255) NOT NULL,
    quantity numeric(20,8) NOT NULL,
    limit_price numeric(20,8),
    stop_price numeric(20,8),
    idempotency_key character varying(255) NOT NULL,
    order_link_id character varying(255),
    correlation_id character varying(255),
    status character varying(255) NOT NULL,
    exchange_order_id character varying(255),
    exchange_response json,
    created_at timestamp(0) with time zone NOT NULL,
    updated_at timestamp(0) with time zone NOT NULL,
    CONSTRAINT orders_order_type_check CHECK (((order_type)::text = ANY ((ARRAY['MARKET'::character varying, 'LIMIT'::character varying, 'STOP'::character varying, 'STOP_LIMIT'::character varying])::text[]))),
    CONSTRAINT orders_side_check CHECK (((side)::text = ANY ((ARRAY['BUY'::character varying, 'SELL'::character varying])::text[]))),
    CONSTRAINT orders_status_check CHECK (((status)::text = ANY ((ARRAY['PENDING'::character varying, 'FILLED'::character varying, 'PARTIALLY_FILLED'::character varying, 'CANCELLED'::character varying, 'REJECTED'::character varying])::text[])))
);


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp with time zone
);


--
-- Name: performance_summaries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.performance_summaries (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    tenant_id character varying(255) NOT NULL,
    period_type character varying(255) NOT NULL,
    period_date date NOT NULL,
    risk_profile character varying(20) DEFAULT 'moderate'::character varying NOT NULL,
    total_trades integer DEFAULT 0 NOT NULL,
    winning_trades integer DEFAULT 0 NOT NULL,
    losing_trades integer DEFAULT 0 NOT NULL,
    win_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    total_pnl numeric(16,8) DEFAULT '0'::numeric NOT NULL,
    total_fees numeric(16,8) DEFAULT '0'::numeric NOT NULL,
    net_pnl numeric(16,8) DEFAULT '0'::numeric NOT NULL,
    profit_factor numeric(8,4) DEFAULT '0'::numeric NOT NULL,
    max_drawdown numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    avg_trade_duration numeric(8,2) DEFAULT '0'::numeric NOT NULL,
    ai_decisions integer DEFAULT 0 NOT NULL,
    avg_ai_confidence numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    high_confidence_decisions integer DEFAULT 0 NOT NULL,
    low_confidence_decisions integer DEFAULT 0 NOT NULL,
    symbol_stats json,
    ai_provider_stats json,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT performance_summaries_period_type_check CHECK (((period_type)::text = ANY ((ARRAY['daily'::character varying, 'weekly'::character varying, 'monthly'::character varying])::text[])))
);


--
-- Name: performance_summaries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.performance_summaries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: performance_summaries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.performance_summaries_id_seq OWNED BY public.performance_summaries.id;


--
-- Name: plans; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.plans (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    currency character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    billing_cycle character varying(255) DEFAULT 'monthly'::character varying NOT NULL,
    features json,
    limits json,
    active boolean DEFAULT true NOT NULL,
    meta json,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


--
-- Name: plans_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.plans_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: plans_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.plans_id_seq OWNED BY public.plans.id;


--
-- Name: position_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.position_logs (
    id bigint NOT NULL,
    trade_id bigint,
    user_id bigint NOT NULL,
    tenant_id character varying(255) NOT NULL,
    symbol character varying(20) NOT NULL,
    action character varying(255) NOT NULL,
    side character varying(255),
    entry_price numeric(16,8),
    exit_price numeric(16,8),
    qty numeric(20,8),
    leverage numeric(8,2),
    stop_loss numeric(16,8),
    take_profit numeric(16,8),
    old_stop_loss numeric(16,8),
    new_stop_loss numeric(16,8),
    old_take_profit numeric(16,8),
    new_take_profit numeric(16,8),
    pnl numeric(16,8),
    pnl_percentage numeric(10,4),
    ai_confidence smallint,
    ai_reason text,
    execution_price numeric(16,8),
    execution_fee numeric(16,8) DEFAULT '0'::numeric NOT NULL,
    total_fees numeric(16,8) DEFAULT '0'::numeric NOT NULL,
    slippage numeric(8,4),
    close_reason character varying(100),
    update_reason character varying(100),
    duration_minutes integer,
    bybit_order_id character varying(100),
    risk_profile character varying(20) DEFAULT 'moderate'::character varying NOT NULL,
    market_conditions json,
    market_price numeric(16,8),
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT position_logs_action_check CHECK (((action)::text = ANY ((ARRAY['OPEN'::character varying, 'CLOSE'::character varying, 'UPDATE'::character varying])::text[]))),
    CONSTRAINT position_logs_side_check CHECK (((side)::text = ANY ((ARRAY['LONG'::character varying, 'SHORT'::character varying])::text[])))
);


--
-- Name: position_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.position_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: position_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.position_logs_id_seq OWNED BY public.position_logs.id;


--
-- Name: positions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.positions (
    id bigint NOT NULL,
    user_id bigint,
    trade_id bigint,
    symbol character varying(255) NOT NULL,
    side character varying(255) NOT NULL,
    entry_price numeric(20,8) NOT NULL,
    qty numeric(20,8) NOT NULL,
    leverage integer DEFAULT 1 NOT NULL,
    take_profit numeric(20,8),
    stop_loss numeric(20,8),
    status character varying(255) DEFAULT 'OPEN'::character varying NOT NULL,
    opened_at timestamp(0) without time zone,
    closed_at timestamp(0) without time zone,
    meta json,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT positions_side_check CHECK (((side)::text = ANY ((ARRAY['Long'::character varying, 'Short'::character varying])::text[]))),
    CONSTRAINT positions_status_check CHECK (((status)::text = ANY ((ARRAY['OPEN'::character varying, 'CLOSED'::character varying, 'CANCELLED'::character varying])::text[])))
);


--
-- Name: positions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.positions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: positions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.positions_id_seq OWNED BY public.positions.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.settings (
    id bigint NOT NULL,
    user_id bigint,
    param_name character varying(128) NOT NULL,
    param_value text,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.settings_id_seq OWNED BY public.settings.id;


--
-- Name: subscriptions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.subscriptions (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    plan_id bigint NOT NULL,
    status character varying(255) DEFAULT 'active'::character varying NOT NULL,
    starts_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    meta json,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    CONSTRAINT subscriptions_status_check CHECK (((status)::text = ANY ((ARRAY['active'::character varying, 'cancelled'::character varying, 'expired'::character varying, 'suspended'::character varying])::text[])))
);


--
-- Name: subscriptions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.subscriptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: subscriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.subscriptions_id_seq OWNED BY public.subscriptions.id;


--
-- Name: tenants; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tenants (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    domain character varying(255) NOT NULL,
    database character varying(255),
    settings json,
    active boolean DEFAULT true NOT NULL,
    meta json,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


--
-- Name: tenants_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tenants_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tenants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tenants_id_seq OWNED BY public.tenants.id;


--
-- Name: trades; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.trades (
    id bigint NOT NULL,
    symbol character varying(255) NOT NULL,
    side character varying(255) NOT NULL,
    status character varying(255) NOT NULL,
    margin_mode character varying(255) DEFAULT 'CROSS'::character varying NOT NULL,
    leverage smallint DEFAULT '1'::smallint NOT NULL,
    qty numeric(18,8) NOT NULL,
    entry_price numeric(18,8) NOT NULL,
    take_profit numeric(18,8),
    stop_loss numeric(18,8),
    pnl numeric(18,8),
    pnl_realized numeric(18,8),
    fees_total numeric(18,8) DEFAULT '0'::numeric NOT NULL,
    bybit_order_id character varying(255),
    opened_at timestamp(0) without time zone,
    closed_at timestamp(0) without time zone,
    meta json,
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    tenant_id bigint,
    CONSTRAINT trades_margin_mode_check CHECK (((margin_mode)::text = ANY ((ARRAY['CROSS'::character varying, 'ISOLATED'::character varying])::text[]))),
    CONSTRAINT trades_side_check CHECK (((side)::text = ANY ((ARRAY['LONG'::character varying, 'SHORT'::character varying])::text[]))),
    CONSTRAINT trades_status_check CHECK (((status)::text = ANY ((ARRAY['OPEN'::character varying, 'CLOSED'::character varying, 'CANCELLED'::character varying])::text[])))
);


--
-- Name: trades_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.trades_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: trades_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.trades_id_seq OWNED BY public.trades.id;


--
-- Name: usage_counters; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usage_counters (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    service character varying(255) NOT NULL,
    count integer DEFAULT 0 NOT NULL,
    period character varying(255) NOT NULL,
    reset_at timestamp(0) without time zone,
    created_at timestamp with time zone,
    updated_at timestamp with time zone
);


--
-- Name: usage_counters_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usage_counters_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usage_counters_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usage_counters_id_seq OWNED BY public.usage_counters.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp with time zone,
    updated_at timestamp with time zone,
    tenant_id bigint,
    role character varying(255) DEFAULT 'user'::character varying NOT NULL,
    meta json
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: ai_decision_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_decision_logs ALTER COLUMN id SET DEFAULT nextval('public.ai_decision_logs_id_seq'::regclass);


--
-- Name: ai_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_logs ALTER COLUMN id SET DEFAULT nextval('public.ai_logs_id_seq'::regclass);


--
-- Name: ai_providers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_providers ALTER COLUMN id SET DEFAULT nextval('public.ai_providers_id_seq'::regclass);


--
-- Name: alerts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alerts ALTER COLUMN id SET DEFAULT nextval('public.alerts_id_seq'::regclass);


--
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: backtest_data id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.backtest_data ALTER COLUMN id SET DEFAULT nextval('public.backtest_data_id_seq'::regclass);


--
-- Name: consensus_decisions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consensus_decisions ALTER COLUMN id SET DEFAULT nextval('public.consensus_decisions_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: lab_metrics id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_metrics ALTER COLUMN id SET DEFAULT nextval('public.lab_metrics_id_seq'::regclass);


--
-- Name: lab_runs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_runs ALTER COLUMN id SET DEFAULT nextval('public.lab_runs_id_seq'::regclass);


--
-- Name: lab_trades id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_trades ALTER COLUMN id SET DEFAULT nextval('public.lab_trades_id_seq'::regclass);


--
-- Name: market_data id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.market_data ALTER COLUMN id SET DEFAULT nextval('public.market_data_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: performance_summaries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.performance_summaries ALTER COLUMN id SET DEFAULT nextval('public.performance_summaries_id_seq'::regclass);


--
-- Name: plans id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plans ALTER COLUMN id SET DEFAULT nextval('public.plans_id_seq'::regclass);


--
-- Name: position_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.position_logs ALTER COLUMN id SET DEFAULT nextval('public.position_logs_id_seq'::regclass);


--
-- Name: positions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.positions ALTER COLUMN id SET DEFAULT nextval('public.positions_id_seq'::regclass);


--
-- Name: settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings ALTER COLUMN id SET DEFAULT nextval('public.settings_id_seq'::regclass);


--
-- Name: subscriptions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscriptions ALTER COLUMN id SET DEFAULT nextval('public.subscriptions_id_seq'::regclass);


--
-- Name: tenants id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenants ALTER COLUMN id SET DEFAULT nextval('public.tenants_id_seq'::regclass);


--
-- Name: trades id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trades ALTER COLUMN id SET DEFAULT nextval('public.trades_id_seq'::regclass);


--
-- Name: usage_counters id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_counters ALTER COLUMN id SET DEFAULT nextval('public.usage_counters_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: ai_decision_logs ai_decision_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_decision_logs
    ADD CONSTRAINT ai_decision_logs_pkey PRIMARY KEY (id);


--
-- Name: ai_logs ai_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_logs
    ADD CONSTRAINT ai_logs_pkey PRIMARY KEY (id);


--
-- Name: ai_providers ai_providers_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_providers
    ADD CONSTRAINT ai_providers_name_unique UNIQUE (name);


--
-- Name: ai_providers ai_providers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ai_providers
    ADD CONSTRAINT ai_providers_pkey PRIMARY KEY (id);


--
-- Name: alerts alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alerts
    ADD CONSTRAINT alerts_pkey PRIMARY KEY (id);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: backtest_data backtest_data_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.backtest_data
    ADD CONSTRAINT backtest_data_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: consensus_decisions consensus_decisions_cycle_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consensus_decisions
    ADD CONSTRAINT consensus_decisions_cycle_uuid_unique UNIQUE (cycle_uuid);


--
-- Name: consensus_decisions consensus_decisions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consensus_decisions
    ADD CONSTRAINT consensus_decisions_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: fills fills_exchange_fill_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fills
    ADD CONSTRAINT fills_exchange_fill_id_unique UNIQUE (exchange_fill_id);


--
-- Name: fills fills_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fills
    ADD CONSTRAINT fills_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: lab_metrics lab_metrics_lab_run_id_as_of_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_metrics
    ADD CONSTRAINT lab_metrics_lab_run_id_as_of_unique UNIQUE (lab_run_id, as_of);


--
-- Name: lab_metrics lab_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_metrics
    ADD CONSTRAINT lab_metrics_pkey PRIMARY KEY (id);


--
-- Name: lab_runs lab_runs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_runs
    ADD CONSTRAINT lab_runs_pkey PRIMARY KEY (id);


--
-- Name: lab_trades lab_trades_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.lab_trades
    ADD CONSTRAINT lab_trades_pkey PRIMARY KEY (id);


--
-- Name: market_data market_data_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.market_data
    ADD CONSTRAINT market_data_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: orders orders_correlation_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_correlation_id_unique UNIQUE (correlation_id);


--
-- Name: orders orders_idempotency_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_idempotency_key_unique UNIQUE (idempotency_key);


--
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: performance_summaries performance_summaries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.performance_summaries
    ADD CONSTRAINT performance_summaries_pkey PRIMARY KEY (id);


--
-- Name: performance_summaries performance_summaries_user_id_period_type_period_date_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.performance_summaries
    ADD CONSTRAINT performance_summaries_user_id_period_type_period_date_unique UNIQUE (user_id, period_type, period_date);


--
-- Name: plans plans_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plans
    ADD CONSTRAINT plans_name_unique UNIQUE (name);


--
-- Name: plans plans_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plans
    ADD CONSTRAINT plans_pkey PRIMARY KEY (id);


--
-- Name: position_logs position_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.position_logs
    ADD CONSTRAINT position_logs_pkey PRIMARY KEY (id);


--
-- Name: positions positions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.positions
    ADD CONSTRAINT positions_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (id);


--
-- Name: subscriptions subscriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscriptions
    ADD CONSTRAINT subscriptions_pkey PRIMARY KEY (id);


--
-- Name: tenants tenants_domain_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenants
    ADD CONSTRAINT tenants_domain_unique UNIQUE (domain);


--
-- Name: tenants tenants_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tenants
    ADD CONSTRAINT tenants_pkey PRIMARY KEY (id);


--
-- Name: trades trades_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trades
    ADD CONSTRAINT trades_pkey PRIMARY KEY (id);


--
-- Name: usage_counters usage_counters_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_counters
    ADD CONSTRAINT usage_counters_pkey PRIMARY KEY (id);


--
-- Name: usage_counters usage_counters_user_id_service_period_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_counters
    ADD CONSTRAINT usage_counters_user_id_service_period_unique UNIQUE (user_id, service, period);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: ai_decision_logs_ai_provider_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ai_decision_logs_ai_provider_created_at_index ON public.ai_decision_logs USING btree (ai_provider, created_at);


--
-- Name: ai_decision_logs_decision_type_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ai_decision_logs_decision_type_created_at_index ON public.ai_decision_logs USING btree (decision_type, created_at);


--
-- Name: ai_decision_logs_symbol_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ai_decision_logs_symbol_created_at_index ON public.ai_decision_logs USING btree (symbol, created_at);


--
-- Name: ai_decision_logs_symbol_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ai_decision_logs_symbol_index ON public.ai_decision_logs USING btree (symbol);


--
-- Name: ai_decision_logs_tenant_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ai_decision_logs_tenant_id_created_at_index ON public.ai_decision_logs USING btree (tenant_id, created_at);


--
-- Name: ai_decision_logs_tenant_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ai_decision_logs_tenant_id_index ON public.ai_decision_logs USING btree (tenant_id);


--
-- Name: ai_decision_logs_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ai_decision_logs_user_id_created_at_index ON public.ai_decision_logs USING btree (user_id, created_at);


--
-- Name: ai_logs_cycle_symbol_stage_created_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ai_logs_cycle_symbol_stage_created_idx ON public.ai_logs USING btree (cycle_uuid, symbol, stage, created_at);


--
-- Name: ai_logs_cycle_uuid_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ai_logs_cycle_uuid_index ON public.ai_logs USING btree (cycle_uuid);


--
-- Name: ai_logs_cycle_uuid_provider_stage_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ai_logs_cycle_uuid_provider_stage_index ON public.ai_logs USING btree (cycle_uuid, provider, stage);


--
-- Name: ai_logs_symbol_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ai_logs_symbol_index ON public.ai_logs USING btree (symbol);


--
-- Name: alerts_severity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX alerts_severity_index ON public.alerts USING btree (severity);


--
-- Name: alerts_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX alerts_status_index ON public.alerts USING btree (status);


--
-- Name: alerts_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX alerts_type_index ON public.alerts USING btree (type);


--
-- Name: audit_logs_action_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_action_created_at_index ON public.audit_logs USING btree (action, created_at);


--
-- Name: audit_logs_action_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_action_index ON public.audit_logs USING btree (action);


--
-- Name: audit_logs_request_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_request_id_index ON public.audit_logs USING btree (request_id);


--
-- Name: audit_logs_resource_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_resource_id_index ON public.audit_logs USING btree (resource_id);


--
-- Name: audit_logs_resource_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_resource_type_index ON public.audit_logs USING btree (resource_type);


--
-- Name: audit_logs_resource_type_resource_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_resource_type_resource_id_index ON public.audit_logs USING btree (resource_type, resource_id);


--
-- Name: audit_logs_session_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_session_id_index ON public.audit_logs USING btree (session_id);


--
-- Name: audit_logs_tenant_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_tenant_id_created_at_index ON public.audit_logs USING btree (tenant_id, created_at);


--
-- Name: audit_logs_tenant_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_tenant_id_index ON public.audit_logs USING btree (tenant_id);


--
-- Name: audit_logs_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_user_id_created_at_index ON public.audit_logs USING btree (user_id, created_at);


--
-- Name: audit_logs_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_user_id_index ON public.audit_logs USING btree (user_id);


--
-- Name: backtest_data_final_decision_trade_time_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX backtest_data_final_decision_trade_time_index ON public.backtest_data USING btree (final_decision, trade_time);


--
-- Name: backtest_data_run_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX backtest_data_run_id_index ON public.backtest_data USING btree (run_id);


--
-- Name: backtest_data_run_id_trade_time_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX backtest_data_run_id_trade_time_index ON public.backtest_data USING btree (run_id, trade_time);


--
-- Name: backtest_data_symbol_trade_time_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX backtest_data_symbol_trade_time_index ON public.backtest_data USING btree (symbol, trade_time);


--
-- Name: backtest_data_tenant_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX backtest_data_tenant_id_index ON public.backtest_data USING btree (tenant_id);


--
-- Name: backtest_data_tenant_id_run_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX backtest_data_tenant_id_run_id_index ON public.backtest_data USING btree (tenant_id, run_id);


--
-- Name: backtest_data_user_id_run_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX backtest_data_user_id_run_id_index ON public.backtest_data USING btree (user_id, run_id);


--
-- Name: consensus_decisions_symbol_created_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX consensus_decisions_symbol_created_idx ON public.consensus_decisions USING btree (symbol, created_at);


--
-- Name: consensus_decisions_symbol_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX consensus_decisions_symbol_index ON public.consensus_decisions USING btree (symbol);


--
-- Name: fills_order_id_filled_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX fills_order_id_filled_at_index ON public.fills USING btree (order_id, filled_at);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: lab_runs_start_date_end_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_runs_start_date_end_date_index ON public.lab_runs USING btree (start_date, end_date);


--
-- Name: lab_runs_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_runs_status_index ON public.lab_runs USING btree (status);


--
-- Name: lab_trades_closed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_trades_closed_at_index ON public.lab_trades USING btree (closed_at);


--
-- Name: lab_trades_symbol_opened_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX lab_trades_symbol_opened_at_index ON public.lab_trades USING btree (symbol, opened_at);


--
-- Name: market_data_symbol_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX market_data_symbol_index ON public.market_data USING btree (symbol);


--
-- Name: market_data_timestamp_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX market_data_timestamp_index ON public.market_data USING btree ("timestamp");


--
-- Name: orders_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_status_created_at_index ON public.orders USING btree (status, created_at);


--
-- Name: orders_symbol_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_symbol_created_at_index ON public.orders USING btree (symbol, created_at);


--
-- Name: orders_tenant_id_symbol_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX orders_tenant_id_symbol_index ON public.orders USING btree (tenant_id, symbol);


--
-- Name: performance_summaries_period_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX performance_summaries_period_type_index ON public.performance_summaries USING btree (period_type);


--
-- Name: performance_summaries_period_type_period_date_profit_factor_ind; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX performance_summaries_period_type_period_date_profit_factor_ind ON public.performance_summaries USING btree (period_type, period_date, profit_factor);


--
-- Name: performance_summaries_period_type_period_date_win_rate_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX performance_summaries_period_type_period_date_win_rate_index ON public.performance_summaries USING btree (period_type, period_date, win_rate);


--
-- Name: performance_summaries_risk_profile_period_type_net_pnl_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX performance_summaries_risk_profile_period_type_net_pnl_index ON public.performance_summaries USING btree (risk_profile, period_type, net_pnl);


--
-- Name: performance_summaries_tenant_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX performance_summaries_tenant_id_index ON public.performance_summaries USING btree (tenant_id);


--
-- Name: performance_summaries_tenant_id_period_type_period_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX performance_summaries_tenant_id_period_type_period_date_index ON public.performance_summaries USING btree (tenant_id, period_type, period_date);


--
-- Name: plans_active_price_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX plans_active_price_index ON public.plans USING btree (active, price);


--
-- Name: position_logs_action_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX position_logs_action_index ON public.position_logs USING btree (action);


--
-- Name: position_logs_close_reason_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX position_logs_close_reason_created_at_index ON public.position_logs USING btree (close_reason, created_at);


--
-- Name: position_logs_side_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX position_logs_side_created_at_index ON public.position_logs USING btree (side, created_at);


--
-- Name: position_logs_symbol_action_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX position_logs_symbol_action_created_at_index ON public.position_logs USING btree (symbol, action, created_at);


--
-- Name: position_logs_symbol_action_pnl_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX position_logs_symbol_action_pnl_index ON public.position_logs USING btree (symbol, action, pnl);


--
-- Name: position_logs_symbol_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX position_logs_symbol_index ON public.position_logs USING btree (symbol);


--
-- Name: position_logs_tenant_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX position_logs_tenant_id_created_at_index ON public.position_logs USING btree (tenant_id, created_at);


--
-- Name: position_logs_tenant_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX position_logs_tenant_id_index ON public.position_logs USING btree (tenant_id);


--
-- Name: position_logs_trade_id_action_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX position_logs_trade_id_action_index ON public.position_logs USING btree (trade_id, action);


--
-- Name: position_logs_user_id_action_pnl_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX position_logs_user_id_action_pnl_index ON public.position_logs USING btree (user_id, action, pnl);


--
-- Name: position_logs_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX position_logs_user_id_created_at_index ON public.position_logs USING btree (user_id, created_at);


--
-- Name: positions_symbol_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX positions_symbol_index ON public.positions USING btree (symbol);


--
-- Name: positions_symbol_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX positions_symbol_status_index ON public.positions USING btree (symbol, status);


--
-- Name: positions_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX positions_user_id_status_index ON public.positions USING btree (user_id, status);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: settings_param_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX settings_param_name_index ON public.settings USING btree (param_name);


--
-- Name: settings_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX settings_user_id_index ON public.settings USING btree (user_id);


--
-- Name: subscriptions_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subscriptions_expires_at_index ON public.subscriptions USING btree (expires_at);


--
-- Name: subscriptions_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX subscriptions_user_id_status_index ON public.subscriptions USING btree (user_id, status);


--
-- Name: tenants_active_domain_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tenants_active_domain_index ON public.tenants USING btree (active, domain);


--
-- Name: trades_bybit_order_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trades_bybit_order_id_index ON public.trades USING btree (bybit_order_id);


--
-- Name: trades_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trades_status_index ON public.trades USING btree (status);


--
-- Name: trades_status_symbol_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trades_status_symbol_idx ON public.trades USING btree (status, symbol);


--
-- Name: trades_symbol_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trades_symbol_status_index ON public.trades USING btree (symbol, status);


--
-- Name: trades_tenant_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX trades_tenant_id_status_index ON public.trades USING btree (tenant_id, status);


--
-- Name: usage_counters_period_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX usage_counters_period_index ON public.usage_counters USING btree (period);


--
-- Name: usage_counters_service_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX usage_counters_service_index ON public.usage_counters USING btree (service);


--
-- Name: usage_counters_service_period_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX usage_counters_service_period_index ON public.usage_counters USING btree (service, period);


--
-- Name: users_role_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_role_index ON public.users USING btree (role);


--
-- Name: users_tenant_id_role_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_tenant_id_role_index ON public.users USING btree (tenant_id, role);


--
-- Name: alerts alerts_acknowledged_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alerts
    ADD CONSTRAINT alerts_acknowledged_by_foreign FOREIGN KEY (acknowledged_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: alerts alerts_resolved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.alerts
    ADD CONSTRAINT alerts_resolved_by_foreign FOREIGN KEY (resolved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: fills fills_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fills
    ADD CONSTRAINT fills_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: fills fills_trade_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.fills
    ADD CONSTRAINT fills_trade_id_foreign FOREIGN KEY (trade_id) REFERENCES public.trades(id) ON DELETE SET NULL;


--
-- Name: orders orders_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: orders orders_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: positions positions_trade_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.positions
    ADD CONSTRAINT positions_trade_id_foreign FOREIGN KEY (trade_id) REFERENCES public.trades(id) ON DELETE SET NULL;


--
-- Name: positions positions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.positions
    ADD CONSTRAINT positions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: subscriptions subscriptions_plan_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscriptions
    ADD CONSTRAINT subscriptions_plan_id_foreign FOREIGN KEY (plan_id) REFERENCES public.plans(id) ON DELETE CASCADE;


--
-- Name: subscriptions subscriptions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subscriptions
    ADD CONSTRAINT subscriptions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: trades trades_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.trades
    ADD CONSTRAINT trades_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: usage_counters usage_counters_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usage_counters
    ADD CONSTRAINT usage_counters_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: users users_tenant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_tenant_id_foreign FOREIGN KEY (tenant_id) REFERENCES public.tenants(id) ON DELETE CASCADE;


--
-- Name: trades trade_tenant_isolation; Type: POLICY; Schema: public; Owner: -
--

CREATE POLICY trade_tenant_isolation ON public.trades USING ((tenant_id = (current_setting('app.tenant_id'::text, true))::bigint));


--
-- Name: trades; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.trades ENABLE ROW LEVEL SECURITY;

--
-- PostgreSQL database dump complete
--

--
-- PostgreSQL database dump
--

-- Dumped from database version 16.9 (Ubuntu 16.9-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.9 (Ubuntu 16.9-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_01_20_120000_optimize_database_for_saas	1
5	2025_01_20_150000_create_trading_logs_tables	1
6	2025_08_08_190800_alter_ai_logs_fix_schema	1
7	2025_08_09_000004_create_market_data_table	1
8	2025_08_09_000005_create_settings_table	1
9	2025_08_10_000012_alter_ai_logs_add_round_cycle_latency	1
10	2025_08_11_000001_create_trades_table	1
11	2025_08_11_000002_create_ai_logs_table	1
12	2025_08_11_000003_create_consensus_decisions_table	1
13	2025_08_11_000004_create_alerts_table	1
14	2025_08_11_000005_create_ai_providers_table	1
15	2025_08_11_120000_add_testing_columns_to_trades	1
16	2025_08_13_031132_add_performance_indexes_to_tables	1
17	2025_08_13_031852_update_ai_logs_stage_enum_values	1
18	2025_08_13_100000_create_lab_trades_table	1
19	2025_08_13_100100_create_lab_metrics_table	1
20	2025_08_14_010655_create_lab_runs_table	1
21	2025_08_14_010719_create_usage_counters_table	1
22	2025_08_14_010747_create_plans_table	1
23	2025_08_14_010748_create_subscriptions_table	1
24	2025_08_14_010850_create_tenants_table	1
25	2025_08_14_010953_add_tenant_fields_to_users_table	1
26	2025_08_14_130000_add_plan_to_tenants	1
27	2025_08_20_000001_create_audit_logs_table	1
28	2025_08_21_064148_fix_postgresql_ai_logs_enum	1
29	2025_08_21_082537_add_tenant_id_to_trades_table	1
30	2025_08_25_083150_update_lab_metrics_table_add_missing_columns	1
31	2025_08_25_083301_fix_lab_metrics_unique_constraint	1
32	2025_08_27_132545_create_positions_table	1
33	2025_08_27_132618_add_source_to_market_data_table	1
34	2025_08_27_193259_convert_timestamps_to_timestamptz	2
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 34, true);


--
-- PostgreSQL database dump complete
--


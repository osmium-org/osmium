--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.3
-- Dumped by pg_dump version 9.1.3
-- Started on 2012-02-28 18:14:13 CET

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 6 (class 2615 OID 17948)
-- Name: osmium; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA osmium;


SET search_path = osmium, pg_catalog;

SET default_with_oids = false;

--
-- TOC entry 244 (class 1259 OID 17984)
-- Dependencies: 6
-- Name: accounts; Type: TABLE; Schema: osmium; Owner: -
--

CREATE TABLE accounts (
    account_name character varying(255) NOT NULL,
    password_hash character varying(255) NOT NULL,
    key_id integer NOT NULL,
    verification_code character varying(255) NOT NULL,
    creation_date integer NOT NULL,
    last_login_date integer NOT NULL,
    account_id integer NOT NULL,
    character_id integer NOT NULL,
    character_name character varying(255) NOT NULL,
    corporation_id integer NOT NULL,
    corporation_name character varying(255) NOT NULL,
    alliance_id integer,
    alliance_name character varying(255)
);


--
-- TOC entry 247 (class 1259 OID 18000)
-- Dependencies: 6 244
-- Name: accounts_account_id_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE accounts_account_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2166 (class 0 OID 0)
-- Dependencies: 247
-- Name: accounts_account_id_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE accounts_account_id_seq OWNED BY accounts.account_id;


--
-- TOC entry 248 (class 1259 OID 18017)
-- Dependencies: 6
-- Name: cookie_tokens; Type: TABLE; Schema: osmium; Owner: -
--

CREATE TABLE cookie_tokens (
    token character varying(255) NOT NULL,
    account_id integer NOT NULL,
    client_attributes character varying(255) NOT NULL,
    expiration_date integer NOT NULL
);


--
-- TOC entry 242 (class 1259 OID 17953)
-- Dependencies: 6
-- Name: fittings; Type: TABLE; Schema: osmium; Owner: -
--

CREATE TABLE fittings (
    id integer NOT NULL,
    hull_id integer NOT NULL,
    creation_date integer NOT NULL,
    name character varying(255) NOT NULL,
    description text
);


--
-- TOC entry 241 (class 1259 OID 17951)
-- Dependencies: 6 242
-- Name: fittings_id_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE fittings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2167 (class 0 OID 0)
-- Dependencies: 241
-- Name: fittings_id_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE fittings_id_seq OWNED BY fittings.id;


--
-- TOC entry 246 (class 1259 OID 17994)
-- Dependencies: 6
-- Name: meta_fittings; Type: TABLE; Schema: osmium; Owner: -
--

CREATE TABLE meta_fittings (
    id integer NOT NULL,
    access_level integer NOT NULL,
    owner_id integer NOT NULL
);


--
-- TOC entry 245 (class 1259 OID 17992)
-- Dependencies: 246 6
-- Name: meta_fittings_id_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE meta_fittings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2168 (class 0 OID 0)
-- Dependencies: 245
-- Name: meta_fittings_id_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE meta_fittings_id_seq OWNED BY meta_fittings.id;


--
-- TOC entry 243 (class 1259 OID 17964)
-- Dependencies: 6
-- Name: slots; Type: TABLE; Schema: osmium; Owner: -
--

CREATE TABLE slots (
    fitting_id integer NOT NULL,
    "position" integer NOT NULL,
    type integer NOT NULL,
    module_id integer NOT NULL,
    charge_id integer
);


--
-- TOC entry 2144 (class 2604 OID 18002)
-- Dependencies: 247 244
-- Name: account_id; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accounts ALTER COLUMN account_id SET DEFAULT nextval('accounts_account_id_seq'::regclass);


--
-- TOC entry 2143 (class 2604 OID 17956)
-- Dependencies: 242 241 242
-- Name: id; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittings ALTER COLUMN id SET DEFAULT nextval('fittings_id_seq'::regclass);


--
-- TOC entry 2145 (class 2604 OID 17997)
-- Dependencies: 245 246 246
-- Name: id; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY meta_fittings ALTER COLUMN id SET DEFAULT nextval('meta_fittings_id_seq'::regclass);


--
-- TOC entry 2152 (class 2606 OID 18013)
-- Dependencies: 244 244
-- Name: accounts_account_name_uniq; Type: CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_account_name_uniq UNIQUE (account_name);


--
-- TOC entry 2155 (class 2606 OID 18011)
-- Dependencies: 244 244
-- Name: accounts_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (account_id);


--
-- TOC entry 2159 (class 2606 OID 18024)
-- Dependencies: 248 248
-- Name: cookie_tokens_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY cookie_tokens
    ADD CONSTRAINT cookie_tokens_pkey PRIMARY KEY (token);


--
-- TOC entry 2147 (class 2606 OID 17958)
-- Dependencies: 242 242
-- Name: fittings_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittings
    ADD CONSTRAINT fittings_pkey PRIMARY KEY (id);


--
-- TOC entry 2157 (class 2606 OID 17999)
-- Dependencies: 246 246
-- Name: meta_fittings_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY meta_fittings
    ADD CONSTRAINT meta_fittings_pkey PRIMARY KEY (id);


--
-- TOC entry 2149 (class 2606 OID 17968)
-- Dependencies: 243 243 243 243
-- Name: slots_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY slots
    ADD CONSTRAINT slots_pkey PRIMARY KEY (fitting_id, type, "position");


--
-- TOC entry 2150 (class 1259 OID 18037)
-- Dependencies: 244
-- Name: accounts_account_name_idx; Type: INDEX; Schema: osmium; Owner: -
--

CREATE INDEX accounts_account_name_idx ON accounts USING btree (account_name);


--
-- TOC entry 2153 (class 1259 OID 18038)
-- Dependencies: 244
-- Name: accounts_character_id_idx; Type: INDEX; Schema: osmium; Owner: -
--

CREATE INDEX accounts_character_id_idx ON accounts USING btree (character_id);


--
-- TOC entry 2163 (class 2606 OID 18025)
-- Dependencies: 248 2154 244
-- Name: cookie_tokens_account_id_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY cookie_tokens
    ADD CONSTRAINT cookie_tokens_account_id_fkey FOREIGN KEY (account_id) REFERENCES accounts(account_id);


--
-- TOC entry 2160 (class 2606 OID 17979)
-- Dependencies: 242 204
-- Name: fittings_hull_id_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittings
    ADD CONSTRAINT fittings_hull_id_fkey FOREIGN KEY (hull_id) REFERENCES eve.invtypes(typeid);


--
-- TOC entry 2161 (class 2606 OID 17974)
-- Dependencies: 204 243
-- Name: slots_charge_id_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY slots
    ADD CONSTRAINT slots_charge_id_fkey FOREIGN KEY (charge_id) REFERENCES eve.invtypes(typeid);


--
-- TOC entry 2162 (class 2606 OID 17969)
-- Dependencies: 204 243
-- Name: slots_module_id_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY slots
    ADD CONSTRAINT slots_module_id_fkey FOREIGN KEY (module_id) REFERENCES eve.invtypes(typeid);


-- Completed on 2012-02-28 18:14:13 CET

--
-- PostgreSQL database dump complete
--


--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.3
-- Dumped by pg_dump version 9.1.3
-- Started on 2012-03-08 21:55:50 CET

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 7 (class 2615 OID 17946)
-- Name: osmium; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA osmium;


SET search_path = osmium, pg_catalog;

SET default_with_oids = false;

--
-- TOC entry 241 (class 1259 OID 17947)
-- Dependencies: 7
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
-- TOC entry 242 (class 1259 OID 17953)
-- Dependencies: 7 241
-- Name: accounts_account_id_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE accounts_account_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2187 (class 0 OID 0)
-- Dependencies: 242
-- Name: accounts_account_id_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE accounts_account_id_seq OWNED BY accounts.account_id;


--
-- TOC entry 243 (class 1259 OID 17955)
-- Dependencies: 7
-- Name: cookie_tokens; Type: TABLE; Schema: osmium; Owner: -
--

CREATE TABLE cookie_tokens (
    token character varying(255) NOT NULL,
    account_id integer NOT NULL,
    client_attributes character varying(255) NOT NULL,
    expiration_date integer NOT NULL
);


--
-- TOC entry 246 (class 1259 OID 17969)
-- Dependencies: 2157 7
-- Name: invships; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invships AS
    SELECT invtypes.typeid, invtypes.typename, invtypes.groupid, invgroups.groupname FROM (eve.invtypes JOIN eve.invgroups ON ((invtypes.groupid = invgroups.groupid))) WHERE ((invgroups.categoryid = 6) AND (invtypes.published = 1));


--
-- TOC entry 250 (class 1259 OID 18047)
-- Dependencies: 2158 7
-- Name: dgmslots; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW dgmslots AS
    SELECT invships.typeid, GREATEST((lowslots.valuefloat)::integer, lowslots.valueint, 0) AS lowslots, GREATEST((medslots.valuefloat)::integer, medslots.valueint, 0) AS medslots, GREATEST((hislots.valuefloat)::integer, hislots.valueint, 0) AS hislots, GREATEST((rigslots.valuefloat)::integer, rigslots.valueint, 0) AS rigslots, GREATEST((subsystemslots.valuefloat)::integer, subsystemslots.valueint, 0) AS subsystemslots FROM (((((invships LEFT JOIN eve.dgmtypeattributes lowslots ON (((lowslots.typeid = invships.typeid) AND (lowslots.attributeid = 12)))) LEFT JOIN eve.dgmtypeattributes medslots ON (((medslots.typeid = invships.typeid) AND (medslots.attributeid = 13)))) LEFT JOIN eve.dgmtypeattributes hislots ON (((hislots.typeid = invships.typeid) AND (hislots.attributeid = 14)))) LEFT JOIN eve.dgmtypeattributes rigslots ON (((rigslots.typeid = invships.typeid) AND (rigslots.attributeid = 1137)))) LEFT JOIN eve.dgmtypeattributes subsystemslots ON (((subsystemslots.typeid = invships.typeid) AND (subsystemslots.attributeid = 1367))));


--
-- TOC entry 244 (class 1259 OID 17961)
-- Dependencies: 7
-- Name: fittings; Type: TABLE; Schema: osmium; Owner: -
--

CREATE TABLE fittings (
    id integer NOT NULL,
    hull_id integer NOT NULL,
    creation_date integer NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    meta_fitting_id integer NOT NULL,
    copied_from_id integer
);


--
-- TOC entry 245 (class 1259 OID 17967)
-- Dependencies: 244 7
-- Name: fittings_id_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE fittings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2188 (class 0 OID 0)
-- Dependencies: 245
-- Name: fittings_id_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE fittings_id_seq OWNED BY fittings.id;


--
-- TOC entry 252 (class 1259 OID 18058)
-- Dependencies: 2160 7
-- Name: invcharges; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invcharges AS
    SELECT modattribs.typeid AS moduleid, invtypes.typeid AS chargeid, invtypes.typename AS chargename FROM (((eve.dgmtypeattributes modattribs LEFT JOIN eve.dgmtypeattributes modchargesize ON (((modchargesize.attributeid = 128) AND (modchargesize.typeid = modattribs.typeid)))) LEFT JOIN eve.invtypes ON ((modattribs.valueint = invtypes.groupid))) LEFT JOIN eve.dgmtypeattributes chargesize ON (((chargesize.attributeid = 128) AND (chargesize.typeid = invtypes.typeid)))) WHERE (((modattribs.attributeid = ANY (ARRAY[604, 605, 606, 609, 610])) AND ((chargesize.valueint IS NULL) OR (chargesize.valueint = modchargesize.valueint))) AND (invtypes.published = 1));


--
-- TOC entry 251 (class 1259 OID 18052)
-- Dependencies: 2159 7
-- Name: invmodules; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invmodules AS
    SELECT invtypes.typeid, invtypes.typename, GREATEST(0, lowslotmodifier.valueint, (lowslotmodifier.valuefloat)::integer) AS extralowslots, GREATEST(0, medslotmodifier.valueint, (medslotmodifier.valuefloat)::integer) AS extramedslots, GREATEST(0, highslotmodifier.valueint, (highslotmodifier.valuefloat)::integer) AS extrahighslots FROM ((((eve.invtypes JOIN eve.invgroups ON ((invtypes.groupid = invgroups.groupid))) LEFT JOIN eve.dgmtypeattributes lowslotmodifier ON (((lowslotmodifier.typeid = invtypes.typeid) AND (lowslotmodifier.attributeid = 1376)))) LEFT JOIN eve.dgmtypeattributes medslotmodifier ON (((medslotmodifier.typeid = invtypes.typeid) AND (medslotmodifier.attributeid = 1375)))) LEFT JOIN eve.dgmtypeattributes highslotmodifier ON (((highslotmodifier.typeid = invtypes.typeid) AND (highslotmodifier.attributeid = 1374)))) WHERE ((invgroups.categoryid = ANY (ARRAY[7, 32])) AND (invtypes.published = 1));


--
-- TOC entry 247 (class 1259 OID 17973)
-- Dependencies: 7
-- Name: meta_fittings; Type: TABLE; Schema: osmium; Owner: -
--

CREATE TABLE meta_fittings (
    id integer NOT NULL,
    access_level integer NOT NULL,
    owner_id integer NOT NULL
);


--
-- TOC entry 248 (class 1259 OID 17976)
-- Dependencies: 7 247
-- Name: meta_fittings_id_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE meta_fittings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2189 (class 0 OID 0)
-- Dependencies: 248
-- Name: meta_fittings_id_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE meta_fittings_id_seq OWNED BY meta_fittings.id;


--
-- TOC entry 249 (class 1259 OID 17978)
-- Dependencies: 7
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
-- TOC entry 2161 (class 2604 OID 17981)
-- Dependencies: 242 241
-- Name: account_id; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accounts ALTER COLUMN account_id SET DEFAULT nextval('accounts_account_id_seq'::regclass);


--
-- TOC entry 2162 (class 2604 OID 17982)
-- Dependencies: 245 244
-- Name: id; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittings ALTER COLUMN id SET DEFAULT nextval('fittings_id_seq'::regclass);


--
-- TOC entry 2163 (class 2604 OID 17983)
-- Dependencies: 248 247
-- Name: id; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY meta_fittings ALTER COLUMN id SET DEFAULT nextval('meta_fittings_id_seq'::regclass);


--
-- TOC entry 2166 (class 2606 OID 17985)
-- Dependencies: 241 241
-- Name: accounts_account_name_uniq; Type: CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_account_name_uniq UNIQUE (account_name);


--
-- TOC entry 2169 (class 2606 OID 17987)
-- Dependencies: 241 241
-- Name: accounts_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (account_id);


--
-- TOC entry 2171 (class 2606 OID 17989)
-- Dependencies: 243 243
-- Name: cookie_tokens_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY cookie_tokens
    ADD CONSTRAINT cookie_tokens_pkey PRIMARY KEY (token);


--
-- TOC entry 2173 (class 2606 OID 17991)
-- Dependencies: 244 244
-- Name: fittings_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittings
    ADD CONSTRAINT fittings_pkey PRIMARY KEY (id);


--
-- TOC entry 2175 (class 2606 OID 17993)
-- Dependencies: 247 247
-- Name: meta_fittings_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY meta_fittings
    ADD CONSTRAINT meta_fittings_pkey PRIMARY KEY (id);


--
-- TOC entry 2177 (class 2606 OID 17995)
-- Dependencies: 249 249 249 249
-- Name: slots_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY slots
    ADD CONSTRAINT slots_pkey PRIMARY KEY (fitting_id, type, "position");


--
-- TOC entry 2164 (class 1259 OID 17996)
-- Dependencies: 241
-- Name: accounts_account_name_idx; Type: INDEX; Schema: osmium; Owner: -
--

CREATE INDEX accounts_account_name_idx ON accounts USING btree (account_name);


--
-- TOC entry 2167 (class 1259 OID 17997)
-- Dependencies: 241
-- Name: accounts_character_id_idx; Type: INDEX; Schema: osmium; Owner: -
--

CREATE INDEX accounts_character_id_idx ON accounts USING btree (character_id);


--
-- TOC entry 2178 (class 2606 OID 17998)
-- Dependencies: 2168 243 241
-- Name: cookie_tokens_account_id_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY cookie_tokens
    ADD CONSTRAINT cookie_tokens_account_id_fkey FOREIGN KEY (account_id) REFERENCES accounts(account_id);


--
-- TOC entry 2181 (class 2606 OID 18003)
-- Dependencies: 244 2172 244
-- Name: fittings_copied_from_id_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittings
    ADD CONSTRAINT fittings_copied_from_id_fkey FOREIGN KEY (copied_from_id) REFERENCES fittings(id);


--
-- TOC entry 2180 (class 2606 OID 18008)
-- Dependencies: 244 204
-- Name: fittings_hull_id_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittings
    ADD CONSTRAINT fittings_hull_id_fkey FOREIGN KEY (hull_id) REFERENCES eve.invtypes(typeid);


--
-- TOC entry 2179 (class 2606 OID 18013)
-- Dependencies: 244 2174 247
-- Name: fittings_meta_fitting_id_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittings
    ADD CONSTRAINT fittings_meta_fitting_id_fkey FOREIGN KEY (meta_fitting_id) REFERENCES meta_fittings(id);


--
-- TOC entry 2182 (class 2606 OID 18018)
-- Dependencies: 2168 247 241
-- Name: meta_fittings_owner_id_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY meta_fittings
    ADD CONSTRAINT meta_fittings_owner_id_fkey FOREIGN KEY (id) REFERENCES accounts(account_id);


--
-- TOC entry 2184 (class 2606 OID 18023)
-- Dependencies: 249 204
-- Name: slots_charge_id_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY slots
    ADD CONSTRAINT slots_charge_id_fkey FOREIGN KEY (charge_id) REFERENCES eve.invtypes(typeid);


--
-- TOC entry 2183 (class 2606 OID 18028)
-- Dependencies: 249 204
-- Name: slots_module_id_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY slots
    ADD CONSTRAINT slots_module_id_fkey FOREIGN KEY (module_id) REFERENCES eve.invtypes(typeid);


-- Completed on 2012-03-08 21:55:50 CET

--
-- PostgreSQL database dump complete
--


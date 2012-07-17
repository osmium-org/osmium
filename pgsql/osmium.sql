--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.4
-- Dumped by pg_dump version 9.1.4
-- Started on 2012-07-17 22:13:31 CEST

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 7 (class 2615 OID 27094)
-- Name: osmium; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA osmium;


SET search_path = osmium, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 173 (class 1259 OID 27095)
-- Dependencies: 7
-- Name: accountfavorites; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE accountfavorites (
    accountid integer NOT NULL,
    loadoutid integer NOT NULL,
    favoritedate integer NOT NULL
);


--
-- TOC entry 174 (class 1259 OID 27098)
-- Dependencies: 7
-- Name: accounts; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE accounts (
    accountid integer NOT NULL,
    accountname character varying(255) NOT NULL,
    passwordhash character varying(255) NOT NULL,
    nickname character varying(255) NOT NULL,
    creationdate integer NOT NULL,
    lastlogindate integer NOT NULL,
    keyid integer,
    verificationcode character varying(255),
    apiverified boolean NOT NULL,
    characterid integer,
    charactername character varying(255),
    corporationid integer,
    corporationname character varying(255),
    allianceid integer,
    alliancename character varying(255),
    isfittingmanager boolean NOT NULL,
    ismoderator boolean NOT NULL,
    flagweight integer NOT NULL
);


--
-- TOC entry 175 (class 1259 OID 27104)
-- Dependencies: 7 174
-- Name: accounts_accountid_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE accounts_accountid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2253 (class 0 OID 0)
-- Dependencies: 175
-- Name: accounts_accountid_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE accounts_accountid_seq OWNED BY accounts.accountid;


--
-- TOC entry 176 (class 1259 OID 27106)
-- Dependencies: 7
-- Name: accountsettings; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE accountsettings (
    accountid integer NOT NULL,
    key character varying(255) NOT NULL,
    value text
);


--
-- TOC entry 177 (class 1259 OID 27112)
-- Dependencies: 7
-- Name: loadouts_loadoutid_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE loadouts_loadoutid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 178 (class 1259 OID 27114)
-- Dependencies: 2084 2085 7
-- Name: loadouts; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE loadouts (
    loadoutid integer DEFAULT nextval('loadouts_loadoutid_seq'::regclass) NOT NULL,
    accountid integer NOT NULL,
    viewpermission integer NOT NULL,
    editpermission integer NOT NULL,
    visibility integer NOT NULL,
    passwordhash text,
    allowcomments boolean DEFAULT true NOT NULL
);


--
-- TOC entry 179 (class 1259 OID 27122)
-- Dependencies: 2067 7
-- Name: allowedloadoutsanonymous; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW allowedloadoutsanonymous AS
    SELECT loadouts.loadoutid FROM loadouts WHERE ((loadouts.viewpermission = 0) OR (loadouts.viewpermission = 1));


--
-- TOC entry 180 (class 1259 OID 27126)
-- Dependencies: 2068 7
-- Name: allowedloadoutsbyaccount; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW allowedloadoutsbyaccount AS
    SELECT accounts.accountid, loadouts.loadoutid FROM ((loadouts JOIN accounts author ON ((author.accountid = loadouts.accountid))) JOIN accounts ON ((((((loadouts.viewpermission = 0) OR (loadouts.viewpermission = 1)) OR ((((loadouts.viewpermission = 2) AND (accounts.apiverified = true)) AND (author.apiverified = true)) AND (accounts.allianceid = author.allianceid))) OR ((((loadouts.viewpermission = 3) AND (accounts.apiverified = true)) AND (author.apiverified = true)) AND (accounts.corporationid = author.corporationid))) OR (accounts.accountid = author.accountid))));


--
-- TOC entry 181 (class 1259 OID 27131)
-- Dependencies: 7
-- Name: cacheexpressions; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE cacheexpressions (
    expressionid integer NOT NULL,
    exp text NOT NULL
);


--
-- TOC entry 182 (class 1259 OID 27137)
-- Dependencies: 7
-- Name: clients; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE clients (
    clientid integer NOT NULL,
    remoteaddress inet NOT NULL,
    useragent character varying(4095) NOT NULL,
    accept character varying(1023) NOT NULL,
    loggedinaccountid integer
);


--
-- TOC entry 183 (class 1259 OID 27143)
-- Dependencies: 7 182
-- Name: clients_clientid_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE clients_clientid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2254 (class 0 OID 0)
-- Dependencies: 183
-- Name: clients_clientid_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE clients_clientid_seq OWNED BY clients.clientid;


--
-- TOC entry 184 (class 1259 OID 27145)
-- Dependencies: 7
-- Name: cookietokens; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE cookietokens (
    token character varying(255) NOT NULL,
    accountid integer NOT NULL,
    clientattributes character varying(255) NOT NULL,
    expirationdate integer NOT NULL
);


--
-- TOC entry 185 (class 1259 OID 27151)
-- Dependencies: 2069 7
-- Name: editableloadoutsbyaccount; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW editableloadoutsbyaccount AS
    SELECT accounts.accountid, loadouts.loadoutid FROM ((loadouts JOIN accounts author ON ((author.accountid = loadouts.accountid))) JOIN accounts ON (((((((((loadouts.editpermission = 3) AND (accounts.apiverified = true)) AND (author.apiverified = true)) AND (accounts.allianceid = author.allianceid)) OR ((((loadouts.editpermission = 2) AND (accounts.apiverified = true)) AND (author.apiverified = true)) AND (accounts.corporationid = author.corporationid))) OR (((((loadouts.editpermission = 1) AND (accounts.apiverified = true)) AND (author.apiverified = true)) AND (accounts.corporationid = author.corporationid)) AND (accounts.isfittingmanager = true))) OR (accounts.accountid = author.accountid)) OR (accounts.ismoderator = true))));


--
-- TOC entry 186 (class 1259 OID 27156)
-- Dependencies: 7
-- Name: fittingchargepresets; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE fittingchargepresets (
    fittinghash character(40) NOT NULL,
    presetid integer NOT NULL,
    chargepresetid integer NOT NULL,
    name character varying(255) NOT NULL,
    description text
);


--
-- TOC entry 187 (class 1259 OID 27162)
-- Dependencies: 7
-- Name: fittingcharges; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE fittingcharges (
    fittinghash character(40) NOT NULL,
    presetid integer NOT NULL,
    chargepresetid integer NOT NULL,
    slottype character varying(127) NOT NULL,
    index integer NOT NULL,
    typeid integer NOT NULL
);


--
-- TOC entry 188 (class 1259 OID 27165)
-- Dependencies: 7
-- Name: fittingdeltas; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE fittingdeltas (
    fittinghash1 character(40) NOT NULL,
    fittinghash2 character(40) NOT NULL,
    delta text NOT NULL
);


--
-- TOC entry 189 (class 1259 OID 27171)
-- Dependencies: 7
-- Name: fittingdronepresets; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE fittingdronepresets (
    fittinghash character(40) NOT NULL,
    dronepresetid integer NOT NULL,
    name character varying(255) NOT NULL,
    description text
);


--
-- TOC entry 190 (class 1259 OID 27177)
-- Dependencies: 7
-- Name: fittingdrones; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE fittingdrones (
    fittinghash character(40) NOT NULL,
    dronepresetid integer NOT NULL,
    typeid integer NOT NULL,
    quantityinbay integer NOT NULL,
    quantityinspace integer NOT NULL
);


--
-- TOC entry 191 (class 1259 OID 27180)
-- Dependencies: 7
-- Name: fittingmodules; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE fittingmodules (
    fittinghash character(40) NOT NULL,
    presetid integer NOT NULL,
    slottype character varying(127) NOT NULL,
    index integer NOT NULL,
    typeid integer NOT NULL,
    state integer NOT NULL
);


--
-- TOC entry 192 (class 1259 OID 27183)
-- Dependencies: 7
-- Name: fittingpresets; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE fittingpresets (
    fittinghash character(40) NOT NULL,
    presetid integer NOT NULL,
    name character varying(255) NOT NULL,
    description text
);


--
-- TOC entry 193 (class 1259 OID 27189)
-- Dependencies: 7
-- Name: fittings; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE fittings (
    fittinghash character(40) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    hullid integer NOT NULL,
    creationdate integer NOT NULL
);


--
-- TOC entry 194 (class 1259 OID 27195)
-- Dependencies: 7
-- Name: fittingtags; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE fittingtags (
    fittinghash character(40) NOT NULL,
    tagname character varying(127) NOT NULL
);


--
-- TOC entry 195 (class 1259 OID 27198)
-- Dependencies: 2088 7
-- Name: flags; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE flags (
    flagid integer NOT NULL,
    flaggedbyaccountid integer NOT NULL,
    createdat integer NOT NULL,
    type integer NOT NULL,
    subtype integer NOT NULL,
    status integer NOT NULL,
    other text,
    target1 integer,
    target2 integer,
    target3 integer,
    CONSTRAINT flags_notempty_check CHECK ((((target1 IS NOT NULL) OR (target2 IS NOT NULL)) OR (target3 IS NOT NULL)))
);


--
-- TOC entry 196 (class 1259 OID 27205)
-- Dependencies: 195 7
-- Name: flags_flagid_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE flags_flagid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2255 (class 0 OID 0)
-- Dependencies: 196
-- Name: flags_flagid_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE flags_flagid_seq OWNED BY flags.flagid;


--
-- TOC entry 197 (class 1259 OID 27207)
-- Dependencies: 2070 7
-- Name: invcharges; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invcharges AS
    SELECT modattribs.typeid AS moduleid, invtypes.typeid AS chargeid, invtypes.typename AS chargename FROM (((eve.dgmtypeattribs modattribs LEFT JOIN eve.dgmtypeattribs modchargesize ON (((modchargesize.attributeid = 128) AND (modchargesize.typeid = modattribs.typeid)))) JOIN eve.invtypes ON (((modattribs.value)::integer = invtypes.groupid))) LEFT JOIN eve.dgmtypeattribs chargesize ON (((chargesize.attributeid = 128) AND (chargesize.typeid = invtypes.typeid)))) WHERE (((modattribs.attributeid = ANY (ARRAY[604, 605, 606, 609, 610])) AND (((chargesize.value IS NULL) OR (modchargesize.value IS NULL)) OR (chargesize.value = modchargesize.value))) AND (invtypes.published = 1));


--
-- TOC entry 198 (class 1259 OID 27212)
-- Dependencies: 2071 7
-- Name: invdrones; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invdrones AS
    SELECT invtypes.typeid, invtypes.typename, invtypes.volume, invtypes.groupid, invgroups.groupname FROM (eve.invtypes JOIN eve.invgroups ON ((invtypes.groupid = invgroups.groupid))) WHERE (((invgroups.categoryid = 18) AND (invgroups.published = 1)) AND (invtypes.published = 1));


--
-- TOC entry 199 (class 1259 OID 27217)
-- Dependencies: 2072 7
-- Name: invmodules; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invmodules AS
    SELECT invtypes.typeid, invtypes.typename, COALESCE((invmetatypes.metagroupid)::integer, (metagroup.value)::integer, CASE (techlevel.value)::integer WHEN 2 THEN 2 WHEN 3 THEN 14 ELSE 1 END) AS metagroupid, invgroups.groupid, invgroups.groupname FROM ((((eve.invtypes JOIN eve.invgroups ON ((invtypes.groupid = invgroups.groupid))) LEFT JOIN eve.invmetatypes ON ((invtypes.typeid = invmetatypes.typeid))) LEFT JOIN eve.dgmtypeattribs techlevel ON (((techlevel.typeid = invtypes.typeid) AND (techlevel.attributeid = 422)))) LEFT JOIN eve.dgmtypeattribs metagroup ON (((metagroup.typeid = invtypes.typeid) AND (metagroup.attributeid = 1692)))) WHERE (((invgroups.categoryid = ANY (ARRAY[7, 32])) AND (invgroups.published = 1)) AND (invtypes.published = 1));


--
-- TOC entry 200 (class 1259 OID 27222)
-- Dependencies: 2073 7
-- Name: invmetagroups; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invmetagroups AS
    SELECT DISTINCT invmetagroups.metagroupid, invmetagroups.metagroupname FROM (invmodules LEFT JOIN eve.invmetagroups ON ((invmodules.metagroupid = invmetagroups.metagroupid)));


--
-- TOC entry 216 (class 1259 OID 27601)
-- Dependencies: 2081 7
-- Name: invships; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invships AS
    SELECT invtypes.typeid, invtypes.typename, invtypes.groupid, invgroups.groupname, invtypes.marketgroupid, invmarketgroups.marketgroupname FROM ((eve.invtypes JOIN eve.invgroups ON ((invtypes.groupid = invgroups.groupid))) LEFT JOIN eve.invmarketgroups ON ((invtypes.marketgroupid = invmarketgroups.marketgroupid))) WHERE (((invgroups.categoryid = 6) AND (invgroups.published = 1)) AND (invtypes.published = 1));


--
-- TOC entry 201 (class 1259 OID 27231)
-- Dependencies: 2074 7
-- Name: invskills; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invskills AS
    SELECT invtypes.typeid, invtypes.typename, invtypes.groupid, invgroups.groupname FROM (eve.invtypes JOIN eve.invgroups ON ((invtypes.groupid = invgroups.groupid))) WHERE ((invgroups.categoryid = 16) AND (invtypes.published = 1));


--
-- TOC entry 217 (class 1259 OID 27606)
-- Dependencies: 2082 7
-- Name: invusedtypes; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invusedtypes AS
    (((SELECT invships.typeid FROM invships UNION SELECT invmodules.typeid FROM invmodules) UNION SELECT invcharges.chargeid AS typeid FROM invcharges) UNION SELECT invdrones.typeid FROM invdrones) UNION SELECT invskills.typeid FROM invskills;


--
-- TOC entry 202 (class 1259 OID 27240)
-- Dependencies: 7
-- Name: loadoutcommentreplies; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE loadoutcommentreplies (
    commentreplyid integer NOT NULL,
    commentid integer NOT NULL,
    accountid integer NOT NULL,
    creationdate integer NOT NULL,
    replybody text NOT NULL,
    replyformattedbody text NOT NULL,
    updatedate integer,
    updatedbyaccountid integer
);


--
-- TOC entry 203 (class 1259 OID 27246)
-- Dependencies: 202 7
-- Name: loadoutcommentreplies_commentreplyid_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE loadoutcommentreplies_commentreplyid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2256 (class 0 OID 0)
-- Dependencies: 203
-- Name: loadoutcommentreplies_commentreplyid_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE loadoutcommentreplies_commentreplyid_seq OWNED BY loadoutcommentreplies.commentreplyid;


--
-- TOC entry 204 (class 1259 OID 27248)
-- Dependencies: 7
-- Name: loadoutcommentrevisions; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE loadoutcommentrevisions (
    commentid integer NOT NULL,
    revision integer NOT NULL,
    updatedbyaccountid integer NOT NULL,
    updatedate integer NOT NULL,
    commentbody text NOT NULL,
    commentformattedbody text NOT NULL
);


--
-- TOC entry 205 (class 1259 OID 27254)
-- Dependencies: 7
-- Name: loadoutcomments; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE loadoutcomments (
    commentid integer NOT NULL,
    loadoutid integer NOT NULL,
    accountid integer NOT NULL,
    creationdate integer NOT NULL,
    revision integer NOT NULL
);


--
-- TOC entry 206 (class 1259 OID 27257)
-- Dependencies: 7 205
-- Name: loadoutcomments_commentid_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE loadoutcomments_commentid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2257 (class 0 OID 0)
-- Dependencies: 206
-- Name: loadoutcomments_commentid_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE loadoutcomments_commentid_seq OWNED BY loadoutcomments.commentid;


--
-- TOC entry 207 (class 1259 OID 27259)
-- Dependencies: 2075 7
-- Name: loadoutcommentslatestrevision; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW loadoutcommentslatestrevision AS
    SELECT loadoutcomments.commentid, max(loadoutcommentrevisions.revision) AS latestrevision FROM (loadoutcomments JOIN loadoutcommentrevisions ON ((loadoutcommentrevisions.commentid = loadoutcomments.commentid))) GROUP BY loadoutcomments.commentid;


--
-- TOC entry 208 (class 1259 OID 27263)
-- Dependencies: 7
-- Name: loadouthistory; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE loadouthistory (
    loadoutid integer NOT NULL,
    revision integer NOT NULL,
    fittinghash character(40) NOT NULL,
    updatedbyaccountid integer NOT NULL,
    updatedate integer NOT NULL
);


--
-- TOC entry 209 (class 1259 OID 27266)
-- Dependencies: 2076 7
-- Name: loadoutslatestrevision; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW loadoutslatestrevision AS
    SELECT loadouts.loadoutid, max(loadouthistory.revision) AS latestrevision FROM (loadouts JOIN loadouthistory ON ((loadouthistory.loadoutid = loadouts.loadoutid))) GROUP BY loadouts.loadoutid;


--
-- TOC entry 210 (class 1259 OID 27270)
-- Dependencies: 2077 7
-- Name: loadoutsmodulelist; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW loadoutsmodulelist AS
    SELECT loadoutslatestrevision.loadoutid, string_agg(DISTINCT (invtypes.typename)::text, ' '::text) AS modulelist FROM (((loadoutslatestrevision JOIN loadouthistory ON (((loadouthistory.loadoutid = loadoutslatestrevision.loadoutid) AND (loadouthistory.revision = loadoutslatestrevision.latestrevision)))) JOIN fittingmodules ON ((fittingmodules.fittinghash = loadouthistory.fittinghash))) JOIN eve.invtypes ON ((fittingmodules.typeid = invtypes.typeid))) GROUP BY loadoutslatestrevision.loadoutid;


--
-- TOC entry 211 (class 1259 OID 27275)
-- Dependencies: 2078 7
-- Name: loadoutstaglist; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW loadoutstaglist AS
    SELECT loadoutslatestrevision.loadoutid, string_agg(DISTINCT (fittingtags.tagname)::text, ' '::text) AS taglist FROM ((loadoutslatestrevision JOIN loadouthistory ON (((loadouthistory.loadoutid = loadoutslatestrevision.loadoutid) AND (loadouthistory.revision = loadoutslatestrevision.latestrevision)))) JOIN fittingtags ON ((fittingtags.fittinghash = loadouthistory.fittinghash))) GROUP BY loadoutslatestrevision.loadoutid;


--
-- TOC entry 212 (class 1259 OID 27279)
-- Dependencies: 2079 7
-- Name: searchableloadouts; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW searchableloadouts AS
    SELECT allowedloadoutsbyaccount.accountid, allowedloadoutsbyaccount.loadoutid FROM (allowedloadoutsbyaccount JOIN loadouts ON ((allowedloadoutsbyaccount.loadoutid = loadouts.loadoutid))) WHERE ((loadouts.visibility = 0) AND (loadouts.viewpermission <> 0)) UNION SELECT 0 AS accountid, allowedloadoutsanonymous.loadoutid FROM (allowedloadoutsanonymous JOIN loadouts ON ((allowedloadoutsanonymous.loadoutid = loadouts.loadoutid))) WHERE (loadouts.visibility = 0);


--
-- TOC entry 213 (class 1259 OID 27284)
-- Dependencies: 2080 7
-- Name: loadoutssearchdata; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW loadoutssearchdata AS
    SELECT searchableloadouts.loadoutid, CASE loadouts.viewpermission WHEN 4 THEN accounts.accountid ELSE 0 END AS restrictedtoaccountid, CASE loadouts.viewpermission WHEN 3 THEN CASE accounts.apiverified WHEN true THEN accounts.corporationid ELSE 0 END ELSE 0 END AS restrictedtocorporationid, CASE loadouts.viewpermission WHEN 2 THEN CASE accounts.apiverified WHEN true THEN accounts.allianceid ELSE 0 END ELSE 0 END AS restrictedtoallianceid, loadoutstaglist.taglist AS tags, loadoutsmodulelist.modulelist AS modules, CASE accounts.apiverified WHEN true THEN accounts.charactername ELSE accounts.nickname END AS author, fittings.name, fittings.description, fittings.hullid AS shipid, invtypes.typename AS ship, fittings.creationdate, loadouthistory.updatedate FROM ((((((((searchableloadouts JOIN loadoutslatestrevision ON ((searchableloadouts.loadoutid = loadoutslatestrevision.loadoutid))) JOIN loadouts ON ((loadoutslatestrevision.loadoutid = loadouts.loadoutid))) JOIN accounts ON ((loadouts.accountid = accounts.accountid))) JOIN loadouthistory ON (((loadouthistory.loadoutid = loadoutslatestrevision.loadoutid) AND (loadouthistory.revision = loadoutslatestrevision.latestrevision)))) JOIN fittings ON ((fittings.fittinghash = loadouthistory.fittinghash))) LEFT JOIN loadoutstaglist ON ((loadoutstaglist.loadoutid = loadoutslatestrevision.loadoutid))) LEFT JOIN loadoutsmodulelist ON ((loadoutsmodulelist.loadoutid = loadoutslatestrevision.loadoutid))) JOIN eve.invtypes ON ((invtypes.typeid = fittings.hullid)));


--
-- TOC entry 214 (class 1259 OID 27289)
-- Dependencies: 7
-- Name: log; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE log (
    logentryid integer NOT NULL,
    clientid integer NOT NULL,
    creationdate integer NOT NULL,
    type integer NOT NULL,
    subtype integer,
    target1 integer,
    target2 integer,
    target3 integer
);


--
-- TOC entry 215 (class 1259 OID 27292)
-- Dependencies: 7 214
-- Name: log_logentryid_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE log_logentryid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2258 (class 0 OID 0)
-- Dependencies: 215
-- Name: log_logentryid_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE log_logentryid_seq OWNED BY log.logentryid;


--
-- TOC entry 2083 (class 2604 OID 27294)
-- Dependencies: 175 174
-- Name: accountid; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accounts ALTER COLUMN accountid SET DEFAULT nextval('accounts_accountid_seq'::regclass);


--
-- TOC entry 2086 (class 2604 OID 27295)
-- Dependencies: 183 182
-- Name: clientid; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY clients ALTER COLUMN clientid SET DEFAULT nextval('clients_clientid_seq'::regclass);


--
-- TOC entry 2087 (class 2604 OID 27296)
-- Dependencies: 196 195
-- Name: flagid; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY flags ALTER COLUMN flagid SET DEFAULT nextval('flags_flagid_seq'::regclass);


--
-- TOC entry 2089 (class 2604 OID 27297)
-- Dependencies: 203 202
-- Name: commentreplyid; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcommentreplies ALTER COLUMN commentreplyid SET DEFAULT nextval('loadoutcommentreplies_commentreplyid_seq'::regclass);


--
-- TOC entry 2090 (class 2604 OID 27298)
-- Dependencies: 206 205
-- Name: commentid; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcomments ALTER COLUMN commentid SET DEFAULT nextval('loadoutcomments_commentid_seq'::regclass);


--
-- TOC entry 2091 (class 2604 OID 27299)
-- Dependencies: 215 214
-- Name: logentryid; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY log ALTER COLUMN logentryid SET DEFAULT nextval('log_logentryid_seq'::regclass);


--
-- TOC entry 2095 (class 2606 OID 27301)
-- Dependencies: 173 173 173
-- Name: accountfavorites_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accountfavorites
    ADD CONSTRAINT accountfavorites_pkey PRIMARY KEY (accountid, loadoutid);


--
-- TOC entry 2098 (class 2606 OID 27303)
-- Dependencies: 174 174
-- Name: accounts_accountname_uniq; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_accountname_uniq UNIQUE (accountname);


--
-- TOC entry 2103 (class 2606 OID 27305)
-- Dependencies: 174 174
-- Name: accounts_characterid_uniq; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_characterid_uniq UNIQUE (characterid);


--
-- TOC entry 2105 (class 2606 OID 27307)
-- Dependencies: 174 174
-- Name: accounts_charactername_uniq; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_charactername_uniq UNIQUE (charactername);


--
-- TOC entry 2111 (class 2606 OID 27309)
-- Dependencies: 174 174
-- Name: accounts_nickname_uniq; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_nickname_uniq UNIQUE (nickname);


--
-- TOC entry 2113 (class 2606 OID 27311)
-- Dependencies: 174 174
-- Name: accounts_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (accountid);


--
-- TOC entry 2116 (class 2606 OID 27313)
-- Dependencies: 176 176 176
-- Name: accountsettings_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accountsettings
    ADD CONSTRAINT accountsettings_pkey PRIMARY KEY (accountid, key);


--
-- TOC entry 2124 (class 2606 OID 27315)
-- Dependencies: 181 181
-- Name: cacheexpressions_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY cacheexpressions
    ADD CONSTRAINT cacheexpressions_pkey PRIMARY KEY (expressionid);


--
-- TOC entry 2128 (class 2606 OID 27317)
-- Dependencies: 182 182
-- Name: clients_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY clients
    ADD CONSTRAINT clients_pkey PRIMARY KEY (clientid);


--
-- TOC entry 2131 (class 2606 OID 27319)
-- Dependencies: 182 182 182 182 182
-- Name: clients_uniq; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY clients
    ADD CONSTRAINT clients_uniq UNIQUE (remoteaddress, useragent, accept, loggedinaccountid);


--
-- TOC entry 2136 (class 2606 OID 27321)
-- Dependencies: 184 184
-- Name: cookietokens_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY cookietokens
    ADD CONSTRAINT cookietokens_pkey PRIMARY KEY (token);


--
-- TOC entry 2140 (class 2606 OID 27323)
-- Dependencies: 186 186 186 186
-- Name: fittingchargepresets_fittinghash_presetid_name_unique; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingchargepresets
    ADD CONSTRAINT fittingchargepresets_fittinghash_presetid_name_unique UNIQUE (fittinghash, presetid, name);


--
-- TOC entry 2142 (class 2606 OID 27325)
-- Dependencies: 186 186 186 186
-- Name: fittingchargepresets_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingchargepresets
    ADD CONSTRAINT fittingchargepresets_pkey PRIMARY KEY (fittinghash, presetid, chargepresetid);


--
-- TOC entry 2147 (class 2606 OID 27327)
-- Dependencies: 187 187 187 187 187 187
-- Name: fittingcharges_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingcharges
    ADD CONSTRAINT fittingcharges_pkey PRIMARY KEY (fittinghash, presetid, chargepresetid, slottype, index);


--
-- TOC entry 2152 (class 2606 OID 27329)
-- Dependencies: 188 188 188
-- Name: fittingdeltas_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingdeltas
    ADD CONSTRAINT fittingdeltas_pkey PRIMARY KEY (fittinghash1, fittinghash2);


--
-- TOC entry 2155 (class 2606 OID 27331)
-- Dependencies: 189 189 189
-- Name: fittingdronepresets_fittinghash_name_unique; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingdronepresets
    ADD CONSTRAINT fittingdronepresets_fittinghash_name_unique UNIQUE (fittinghash, name);


--
-- TOC entry 2157 (class 2606 OID 27333)
-- Dependencies: 189 189 189
-- Name: fittingdronepresets_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingdronepresets
    ADD CONSTRAINT fittingdronepresets_pkey PRIMARY KEY (fittinghash, dronepresetid);


--
-- TOC entry 2160 (class 2606 OID 27335)
-- Dependencies: 190 190 190 190
-- Name: fittingdrones_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingdrones
    ADD CONSTRAINT fittingdrones_pkey PRIMARY KEY (fittinghash, dronepresetid, typeid);


--
-- TOC entry 2163 (class 2606 OID 27337)
-- Dependencies: 191 191 191 191 191
-- Name: fittingmodules_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingmodules
    ADD CONSTRAINT fittingmodules_pkey PRIMARY KEY (fittinghash, presetid, slottype, index);


--
-- TOC entry 2166 (class 2606 OID 27339)
-- Dependencies: 192 192 192
-- Name: fittingpresets_fittinghash_name_unique; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingpresets
    ADD CONSTRAINT fittingpresets_fittinghash_name_unique UNIQUE (fittinghash, name);


--
-- TOC entry 2168 (class 2606 OID 27341)
-- Dependencies: 192 192 192
-- Name: fittingpresets_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingpresets
    ADD CONSTRAINT fittingpresets_pkey PRIMARY KEY (fittinghash, presetid);


--
-- TOC entry 2171 (class 2606 OID 27343)
-- Dependencies: 193 193
-- Name: fittings_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittings
    ADD CONSTRAINT fittings_pkey PRIMARY KEY (fittinghash);


--
-- TOC entry 2174 (class 2606 OID 27345)
-- Dependencies: 194 194 194
-- Name: fittingtags_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingtags
    ADD CONSTRAINT fittingtags_pkey PRIMARY KEY (fittinghash, tagname);


--
-- TOC entry 2179 (class 2606 OID 27347)
-- Dependencies: 195 195
-- Name: flags_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY flags
    ADD CONSTRAINT flags_pkey PRIMARY KEY (flagid);


--
-- TOC entry 2190 (class 2606 OID 27349)
-- Dependencies: 202 202
-- Name: loadoutcommentreplies_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY loadoutcommentreplies
    ADD CONSTRAINT loadoutcommentreplies_pkey PRIMARY KEY (commentreplyid);


--
-- TOC entry 2194 (class 2606 OID 27351)
-- Dependencies: 204 204 204
-- Name: loadoutcommentrevisions_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY loadoutcommentrevisions
    ADD CONSTRAINT loadoutcommentrevisions_pkey PRIMARY KEY (commentid, revision);


--
-- TOC entry 2201 (class 2606 OID 27353)
-- Dependencies: 205 205
-- Name: loadoutcomments_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY loadoutcomments
    ADD CONSTRAINT loadoutcomments_pkey PRIMARY KEY (commentid);


--
-- TOC entry 2206 (class 2606 OID 27355)
-- Dependencies: 208 208 208
-- Name: loadouthistory_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY loadouthistory
    ADD CONSTRAINT loadouthistory_pkey PRIMARY KEY (loadoutid, revision);


--
-- TOC entry 2120 (class 2606 OID 27357)
-- Dependencies: 178 178
-- Name: loadouts_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY loadouts
    ADD CONSTRAINT loadouts_pkey PRIMARY KEY (loadoutid);


--
-- TOC entry 2212 (class 2606 OID 27359)
-- Dependencies: 214 214
-- Name: log_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY log
    ADD CONSTRAINT log_pkey PRIMARY KEY (logentryid);


--
-- TOC entry 2092 (class 1259 OID 27360)
-- Dependencies: 173
-- Name: accountfavorites_accountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accountfavorites_accountid_idx ON accountfavorites USING btree (accountid);


--
-- TOC entry 2093 (class 1259 OID 27361)
-- Dependencies: 173
-- Name: accountfavorites_loadoutid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accountfavorites_loadoutid_idx ON accountfavorites USING btree (loadoutid);


--
-- TOC entry 2096 (class 1259 OID 27362)
-- Dependencies: 174
-- Name: accounts_accountname_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_accountname_idx ON accounts USING btree (accountname);


--
-- TOC entry 2099 (class 1259 OID 27363)
-- Dependencies: 174
-- Name: accounts_allianceid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_allianceid_idx ON accounts USING btree (allianceid);


--
-- TOC entry 2100 (class 1259 OID 27364)
-- Dependencies: 174
-- Name: accounts_apiverified_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_apiverified_idx ON accounts USING btree (apiverified);


--
-- TOC entry 2101 (class 1259 OID 27365)
-- Dependencies: 174
-- Name: accounts_characterid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_characterid_idx ON accounts USING btree (characterid);


--
-- TOC entry 2106 (class 1259 OID 27366)
-- Dependencies: 174
-- Name: accounts_corporationid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_corporationid_idx ON accounts USING btree (corporationid);


--
-- TOC entry 2107 (class 1259 OID 27367)
-- Dependencies: 174
-- Name: accounts_isfittingmanager_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_isfittingmanager_idx ON accounts USING btree (isfittingmanager);


--
-- TOC entry 2108 (class 1259 OID 27368)
-- Dependencies: 174
-- Name: accounts_ismoderator_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_ismoderator_idx ON accounts USING btree (ismoderator);


--
-- TOC entry 2109 (class 1259 OID 27369)
-- Dependencies: 174
-- Name: accounts_nickname_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_nickname_idx ON accounts USING btree (nickname);


--
-- TOC entry 2114 (class 1259 OID 27370)
-- Dependencies: 176
-- Name: accountsettings_accountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accountsettings_accountid_idx ON accountsettings USING btree (accountid);


--
-- TOC entry 2125 (class 1259 OID 27371)
-- Dependencies: 182
-- Name: clients_accept_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX clients_accept_idx ON clients USING btree (accept);


--
-- TOC entry 2126 (class 1259 OID 27372)
-- Dependencies: 182
-- Name: clients_loggedinaccountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX clients_loggedinaccountid_idx ON clients USING btree (loggedinaccountid);


--
-- TOC entry 2129 (class 1259 OID 27373)
-- Dependencies: 182
-- Name: clients_remoteaddress_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX clients_remoteaddress_idx ON clients USING btree (remoteaddress);


--
-- TOC entry 2132 (class 1259 OID 27374)
-- Dependencies: 182
-- Name: clients_useragent_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX clients_useragent_idx ON clients USING btree (useragent);


--
-- TOC entry 2133 (class 1259 OID 27375)
-- Dependencies: 184
-- Name: cookietokens_accountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX cookietokens_accountid_idx ON cookietokens USING btree (accountid);


--
-- TOC entry 2134 (class 1259 OID 27376)
-- Dependencies: 184
-- Name: cookietokens_expirationdate_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX cookietokens_expirationdate_idx ON cookietokens USING btree (expirationdate);


--
-- TOC entry 2137 (class 1259 OID 27377)
-- Dependencies: 186
-- Name: fittingchargepresets_fittinghash_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingchargepresets_fittinghash_idx ON fittingchargepresets USING btree (fittinghash);


--
-- TOC entry 2138 (class 1259 OID 27378)
-- Dependencies: 186 186
-- Name: fittingchargepresets_fittinghash_presetid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingchargepresets_fittinghash_presetid_idx ON fittingchargepresets USING btree (fittinghash, presetid);


--
-- TOC entry 2143 (class 1259 OID 27379)
-- Dependencies: 187
-- Name: fittingcharges_fittinghash_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingcharges_fittinghash_idx ON fittingcharges USING btree (fittinghash);


--
-- TOC entry 2144 (class 1259 OID 27380)
-- Dependencies: 187 187 187
-- Name: fittingcharges_fittinghash_presetid_chargepresetid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingcharges_fittinghash_presetid_chargepresetid_idx ON fittingcharges USING btree (fittinghash, presetid, chargepresetid);


--
-- TOC entry 2145 (class 1259 OID 27381)
-- Dependencies: 187 187 187 187
-- Name: fittingcharges_fittinghash_presetid_slottype_index_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingcharges_fittinghash_presetid_slottype_index_idx ON fittingcharges USING btree (fittinghash, presetid, slottype, index);


--
-- TOC entry 2148 (class 1259 OID 27382)
-- Dependencies: 187
-- Name: fittingcharges_typeid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingcharges_typeid_idx ON fittingcharges USING btree (typeid);


--
-- TOC entry 2149 (class 1259 OID 27383)
-- Dependencies: 188
-- Name: fittingdeltas_fittinghash1_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingdeltas_fittinghash1_idx ON fittingdeltas USING btree (fittinghash1);


--
-- TOC entry 2150 (class 1259 OID 27384)
-- Dependencies: 188
-- Name: fittingdeltas_fittinghash2_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingdeltas_fittinghash2_idx ON fittingdeltas USING btree (fittinghash2);


--
-- TOC entry 2153 (class 1259 OID 27385)
-- Dependencies: 189
-- Name: fittingdronepresets_fittinghash_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingdronepresets_fittinghash_idx ON fittingdronepresets USING btree (fittinghash);


--
-- TOC entry 2158 (class 1259 OID 27386)
-- Dependencies: 190 190
-- Name: fittingdrones_fittinghash_dronepresetid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingdrones_fittinghash_dronepresetid_idx ON fittingdrones USING btree (fittinghash, dronepresetid);


--
-- TOC entry 2161 (class 1259 OID 27387)
-- Dependencies: 190
-- Name: fittingdrones_typeid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingdrones_typeid_idx ON fittingdrones USING btree (typeid);


--
-- TOC entry 2164 (class 1259 OID 27388)
-- Dependencies: 192
-- Name: fittingpresets_fittinghash_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingpresets_fittinghash_idx ON fittingpresets USING btree (fittinghash);


--
-- TOC entry 2169 (class 1259 OID 27389)
-- Dependencies: 193
-- Name: fittings_hullid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittings_hullid_idx ON fittings USING btree (hullid);


--
-- TOC entry 2172 (class 1259 OID 27390)
-- Dependencies: 194
-- Name: fittingtags_fittinghash_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingtags_fittinghash_idx ON fittingtags USING btree (fittinghash);


--
-- TOC entry 2175 (class 1259 OID 27391)
-- Dependencies: 194
-- Name: fittingtags_tagname_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingtags_tagname_idx ON fittingtags USING btree (tagname);


--
-- TOC entry 2176 (class 1259 OID 27392)
-- Dependencies: 195
-- Name: flags_createdat_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_createdat_idx ON flags USING btree (createdat);


--
-- TOC entry 2177 (class 1259 OID 27393)
-- Dependencies: 195
-- Name: flags_flaggedbyaccountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_flaggedbyaccountid_idx ON flags USING btree (flaggedbyaccountid);


--
-- TOC entry 2180 (class 1259 OID 27394)
-- Dependencies: 195
-- Name: flags_status_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_status_idx ON flags USING btree (status);


--
-- TOC entry 2181 (class 1259 OID 27395)
-- Dependencies: 195
-- Name: flags_subtype_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_subtype_idx ON flags USING btree (subtype);


--
-- TOC entry 2182 (class 1259 OID 27396)
-- Dependencies: 195
-- Name: flags_target1_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_target1_idx ON flags USING btree (target1);


--
-- TOC entry 2183 (class 1259 OID 27397)
-- Dependencies: 195
-- Name: flags_target2_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_target2_idx ON flags USING btree (target2);


--
-- TOC entry 2184 (class 1259 OID 27398)
-- Dependencies: 195
-- Name: flags_target3_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_target3_idx ON flags USING btree (target3);


--
-- TOC entry 2185 (class 1259 OID 27399)
-- Dependencies: 195
-- Name: flags_type_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_type_idx ON flags USING btree (type);


--
-- TOC entry 2186 (class 1259 OID 27400)
-- Dependencies: 202
-- Name: loadoutcommentreplies_accountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentreplies_accountid_idx ON loadoutcommentreplies USING btree (accountid);


--
-- TOC entry 2187 (class 1259 OID 27401)
-- Dependencies: 202
-- Name: loadoutcommentreplies_commentid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentreplies_commentid_idx ON loadoutcommentreplies USING btree (commentid);


--
-- TOC entry 2188 (class 1259 OID 27402)
-- Dependencies: 202
-- Name: loadoutcommentreplies_creationdate_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentreplies_creationdate_idx ON loadoutcommentreplies USING btree (creationdate);


--
-- TOC entry 2191 (class 1259 OID 27403)
-- Dependencies: 202
-- Name: loadoutcommentreplies_updatedbyaccountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentreplies_updatedbyaccountid_idx ON loadoutcommentreplies USING btree (updatedbyaccountid);


--
-- TOC entry 2192 (class 1259 OID 27404)
-- Dependencies: 204
-- Name: loadoutcommentrevisions_commentid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentrevisions_commentid_idx ON loadoutcommentrevisions USING btree (commentid);


--
-- TOC entry 2195 (class 1259 OID 27405)
-- Dependencies: 204
-- Name: loadoutcommentrevisions_revision_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentrevisions_revision_idx ON loadoutcommentrevisions USING btree (revision);


--
-- TOC entry 2196 (class 1259 OID 27406)
-- Dependencies: 204
-- Name: loadoutcommentrevisions_updatedbyaccountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentrevisions_updatedbyaccountid_idx ON loadoutcommentrevisions USING btree (updatedbyaccountid);


--
-- TOC entry 2197 (class 1259 OID 27407)
-- Dependencies: 205
-- Name: loadoutcomments_accountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcomments_accountid_idx ON loadoutcomments USING btree (loadoutid);


--
-- TOC entry 2198 (class 1259 OID 27408)
-- Dependencies: 205
-- Name: loadoutcomments_creationdate_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcomments_creationdate_idx ON loadoutcomments USING btree (creationdate);


--
-- TOC entry 2199 (class 1259 OID 27409)
-- Dependencies: 205
-- Name: loadoutcomments_loadoutid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcomments_loadoutid_idx ON loadoutcomments USING btree (loadoutid);


--
-- TOC entry 2202 (class 1259 OID 27410)
-- Dependencies: 205
-- Name: loadoutcomments_revision_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcomments_revision_idx ON loadoutcomments USING btree (revision);


--
-- TOC entry 2203 (class 1259 OID 27411)
-- Dependencies: 208
-- Name: loadouthistory_fittinghash_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouthistory_fittinghash_idx ON loadouthistory USING btree (fittinghash);


--
-- TOC entry 2204 (class 1259 OID 27412)
-- Dependencies: 208
-- Name: loadouthistory_loadoutid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouthistory_loadoutid_idx ON loadouthistory USING btree (loadoutid);


--
-- TOC entry 2207 (class 1259 OID 27413)
-- Dependencies: 208
-- Name: loadouthistory_updatedate_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouthistory_updatedate_idx ON loadouthistory USING btree (updatedate);


--
-- TOC entry 2208 (class 1259 OID 27414)
-- Dependencies: 208
-- Name: loadouthistory_updatedbyaccountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouthistory_updatedbyaccountid_idx ON loadouthistory USING btree (updatedbyaccountid);


--
-- TOC entry 2117 (class 1259 OID 27415)
-- Dependencies: 178
-- Name: loadouts_accountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouts_accountid_idx ON loadouts USING btree (accountid);


--
-- TOC entry 2118 (class 1259 OID 27416)
-- Dependencies: 178
-- Name: loadouts_editpermission_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouts_editpermission_idx ON loadouts USING btree (editpermission);


--
-- TOC entry 2121 (class 1259 OID 27417)
-- Dependencies: 178
-- Name: loadouts_viewpermission_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouts_viewpermission_idx ON loadouts USING btree (viewpermission);


--
-- TOC entry 2122 (class 1259 OID 27418)
-- Dependencies: 178
-- Name: loadouts_visibility_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouts_visibility_idx ON loadouts USING btree (visibility);


--
-- TOC entry 2209 (class 1259 OID 27419)
-- Dependencies: 214
-- Name: log_clientid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX log_clientid_idx ON log USING btree (clientid);


--
-- TOC entry 2210 (class 1259 OID 27420)
-- Dependencies: 214
-- Name: log_creationdate_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX log_creationdate_idx ON log USING btree (creationdate);


--
-- TOC entry 2213 (class 1259 OID 27421)
-- Dependencies: 214
-- Name: log_subtype_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX log_subtype_idx ON log USING btree (subtype);


--
-- TOC entry 2214 (class 1259 OID 27422)
-- Dependencies: 214
-- Name: log_target1_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX log_target1_idx ON log USING btree (target1);


--
-- TOC entry 2215 (class 1259 OID 27423)
-- Dependencies: 214
-- Name: log_target2_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX log_target2_idx ON log USING btree (target2);


--
-- TOC entry 2216 (class 1259 OID 27424)
-- Dependencies: 214
-- Name: log_target3_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX log_target3_idx ON log USING btree (target3);


--
-- TOC entry 2217 (class 1259 OID 27425)
-- Dependencies: 214
-- Name: log_type_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX log_type_idx ON log USING btree (type);


--
-- TOC entry 2218 (class 2606 OID 27426)
-- Dependencies: 173 174 2112
-- Name: accountfavorites_accountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accountfavorites
    ADD CONSTRAINT accountfavorites_accountid_fkey FOREIGN KEY (accountid) REFERENCES accounts(accountid);


--
-- TOC entry 2219 (class 2606 OID 27431)
-- Dependencies: 178 173 2119
-- Name: accountfavorites_loadoutid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accountfavorites
    ADD CONSTRAINT accountfavorites_loadoutid_fkey FOREIGN KEY (loadoutid) REFERENCES loadouts(loadoutid);


--
-- TOC entry 2220 (class 2606 OID 27436)
-- Dependencies: 2112 174 176
-- Name: accountsettings_accountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accountsettings
    ADD CONSTRAINT accountsettings_accountid_fkey FOREIGN KEY (accountid) REFERENCES accounts(accountid);


--
-- TOC entry 2222 (class 2606 OID 27441)
-- Dependencies: 2112 182 174
-- Name: clients_loggedinaccountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY clients
    ADD CONSTRAINT clients_loggedinaccountid_fkey FOREIGN KEY (loggedinaccountid) REFERENCES accounts(accountid);


--
-- TOC entry 2223 (class 2606 OID 27446)
-- Dependencies: 2112 184 174
-- Name: cookietokens_accountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY cookietokens
    ADD CONSTRAINT cookietokens_accountid_fkey FOREIGN KEY (accountid) REFERENCES accounts(accountid);


--
-- TOC entry 2224 (class 2606 OID 27451)
-- Dependencies: 192 192 2167 186 186
-- Name: fittingchargepresets_fittinghash_presetid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingchargepresets
    ADD CONSTRAINT fittingchargepresets_fittinghash_presetid_fkey FOREIGN KEY (fittinghash, presetid) REFERENCES fittingpresets(fittinghash, presetid);


--
-- TOC entry 2225 (class 2606 OID 27456)
-- Dependencies: 187 186 186 186 2141 187 187
-- Name: fittingcharges_fittinghash_presetid_chargepresetid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingcharges
    ADD CONSTRAINT fittingcharges_fittinghash_presetid_chargepresetid_fkey FOREIGN KEY (fittinghash, presetid, chargepresetid) REFERENCES fittingchargepresets(fittinghash, presetid, chargepresetid);


--
-- TOC entry 2226 (class 2606 OID 27461)
-- Dependencies: 187 187 187 187 191 191 191 191 2162
-- Name: fittingcharges_fittinghash_presetid_slottype_index_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingcharges
    ADD CONSTRAINT fittingcharges_fittinghash_presetid_slottype_index_fkey FOREIGN KEY (fittinghash, presetid, slottype, index) REFERENCES fittingmodules(fittinghash, presetid, slottype, index);


--
-- TOC entry 2227 (class 2606 OID 27466)
-- Dependencies: 187 172
-- Name: fittingcharges_typeid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingcharges
    ADD CONSTRAINT fittingcharges_typeid_fkey FOREIGN KEY (typeid) REFERENCES eve.invtypes(typeid);


--
-- TOC entry 2228 (class 2606 OID 27471)
-- Dependencies: 2170 188 193
-- Name: fittingdeltas_fittinghash1_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingdeltas
    ADD CONSTRAINT fittingdeltas_fittinghash1_fkey FOREIGN KEY (fittinghash1) REFERENCES fittings(fittinghash);


--
-- TOC entry 2229 (class 2606 OID 27476)
-- Dependencies: 193 188 2170
-- Name: fittingdeltas_fittinghash2_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingdeltas
    ADD CONSTRAINT fittingdeltas_fittinghash2_fkey FOREIGN KEY (fittinghash2) REFERENCES fittings(fittinghash);


--
-- TOC entry 2230 (class 2606 OID 27481)
-- Dependencies: 193 189 2170
-- Name: fittingdronepresets_fittinghash_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingdronepresets
    ADD CONSTRAINT fittingdronepresets_fittinghash_fkey FOREIGN KEY (fittinghash) REFERENCES fittings(fittinghash);


--
-- TOC entry 2231 (class 2606 OID 27486)
-- Dependencies: 189 190 190 189 2156
-- Name: fittingdrones_fittinghash_dronepresetid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingdrones
    ADD CONSTRAINT fittingdrones_fittinghash_dronepresetid_fkey FOREIGN KEY (fittinghash, dronepresetid) REFERENCES fittingdronepresets(fittinghash, dronepresetid);


--
-- TOC entry 2232 (class 2606 OID 27491)
-- Dependencies: 172 190
-- Name: fittingdrones_typeid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingdrones
    ADD CONSTRAINT fittingdrones_typeid_fkey FOREIGN KEY (typeid) REFERENCES eve.invtypes(typeid);


--
-- TOC entry 2233 (class 2606 OID 27496)
-- Dependencies: 192 191 191 192 2167
-- Name: fittingmodules_fittinghash_presetid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingmodules
    ADD CONSTRAINT fittingmodules_fittinghash_presetid_fkey FOREIGN KEY (fittinghash, presetid) REFERENCES fittingpresets(fittinghash, presetid);


--
-- TOC entry 2234 (class 2606 OID 27501)
-- Dependencies: 172 191
-- Name: fittingmodules_typeid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingmodules
    ADD CONSTRAINT fittingmodules_typeid_fkey FOREIGN KEY (typeid) REFERENCES eve.invtypes(typeid);


--
-- TOC entry 2235 (class 2606 OID 27506)
-- Dependencies: 193 192 2170
-- Name: fittingpresets_fittinghash_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingpresets
    ADD CONSTRAINT fittingpresets_fittinghash_fkey FOREIGN KEY (fittinghash) REFERENCES fittings(fittinghash);


--
-- TOC entry 2236 (class 2606 OID 27511)
-- Dependencies: 172 193
-- Name: fittings_hullid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittings
    ADD CONSTRAINT fittings_hullid_fkey FOREIGN KEY (hullid) REFERENCES eve.invtypes(typeid);


--
-- TOC entry 2237 (class 2606 OID 27516)
-- Dependencies: 193 194 2170
-- Name: fittingtags_fittinghash_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingtags
    ADD CONSTRAINT fittingtags_fittinghash_fkey FOREIGN KEY (fittinghash) REFERENCES fittings(fittinghash);


--
-- TOC entry 2238 (class 2606 OID 27521)
-- Dependencies: 174 195 2112
-- Name: flags_flaggedbyaccountid; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY flags
    ADD CONSTRAINT flags_flaggedbyaccountid FOREIGN KEY (flaggedbyaccountid) REFERENCES accounts(accountid);


--
-- TOC entry 2239 (class 2606 OID 27526)
-- Dependencies: 2112 174 202
-- Name: loadoutcommentreplies_accountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcommentreplies
    ADD CONSTRAINT loadoutcommentreplies_accountid_fkey FOREIGN KEY (accountid) REFERENCES accounts(accountid);


--
-- TOC entry 2240 (class 2606 OID 27531)
-- Dependencies: 205 202 2200
-- Name: loadoutcommentreplies_commentid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcommentreplies
    ADD CONSTRAINT loadoutcommentreplies_commentid_fkey FOREIGN KEY (commentid) REFERENCES loadoutcomments(commentid);


--
-- TOC entry 2241 (class 2606 OID 27536)
-- Dependencies: 2112 174 202
-- Name: loadoutcommentreplies_updatedbyaccountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcommentreplies
    ADD CONSTRAINT loadoutcommentreplies_updatedbyaccountid_fkey FOREIGN KEY (updatedbyaccountid) REFERENCES accounts(accountid);


--
-- TOC entry 2242 (class 2606 OID 27541)
-- Dependencies: 205 2200 204
-- Name: loadoutcommentrevisions_commentid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcommentrevisions
    ADD CONSTRAINT loadoutcommentrevisions_commentid_fkey FOREIGN KEY (commentid) REFERENCES loadoutcomments(commentid);


--
-- TOC entry 2243 (class 2606 OID 27546)
-- Dependencies: 2112 174 204
-- Name: loadoutcommentrevisions_updatedbyaccountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcommentrevisions
    ADD CONSTRAINT loadoutcommentrevisions_updatedbyaccountid_fkey FOREIGN KEY (updatedbyaccountid) REFERENCES accounts(accountid);


--
-- TOC entry 2244 (class 2606 OID 27551)
-- Dependencies: 205 2112 174
-- Name: loadoutcomments_accountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcomments
    ADD CONSTRAINT loadoutcomments_accountid_fkey FOREIGN KEY (accountid) REFERENCES accounts(accountid);


--
-- TOC entry 2245 (class 2606 OID 27556)
-- Dependencies: 178 2119 205
-- Name: loadoutcomments_loadoutid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcomments
    ADD CONSTRAINT loadoutcomments_loadoutid_fkey FOREIGN KEY (loadoutid) REFERENCES loadouts(loadoutid);


--
-- TOC entry 2246 (class 2606 OID 27561)
-- Dependencies: 208 205 205 208 2205
-- Name: loadoutcomments_loadoutid_revision_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcomments
    ADD CONSTRAINT loadoutcomments_loadoutid_revision_fkey FOREIGN KEY (loadoutid, revision) REFERENCES loadouthistory(loadoutid, revision);


--
-- TOC entry 2247 (class 2606 OID 27566)
-- Dependencies: 208 2170 193
-- Name: loadouthistory_fittinghash_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadouthistory
    ADD CONSTRAINT loadouthistory_fittinghash_fkey FOREIGN KEY (fittinghash) REFERENCES fittings(fittinghash);


--
-- TOC entry 2248 (class 2606 OID 27571)
-- Dependencies: 2119 178 208
-- Name: loadouthistory_loadoutid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadouthistory
    ADD CONSTRAINT loadouthistory_loadoutid_fkey FOREIGN KEY (loadoutid) REFERENCES loadouts(loadoutid);


--
-- TOC entry 2249 (class 2606 OID 27576)
-- Dependencies: 208 2112 174
-- Name: loadouthistory_updatedbyaccountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadouthistory
    ADD CONSTRAINT loadouthistory_updatedbyaccountid_fkey FOREIGN KEY (updatedbyaccountid) REFERENCES accounts(accountid);


--
-- TOC entry 2221 (class 2606 OID 27581)
-- Dependencies: 178 174 2112
-- Name: loadouts_accountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadouts
    ADD CONSTRAINT loadouts_accountid_fkey FOREIGN KEY (accountid) REFERENCES accounts(accountid);


--
-- TOC entry 2250 (class 2606 OID 27586)
-- Dependencies: 214 182 2127
-- Name: log_clientid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY log
    ADD CONSTRAINT log_clientid_fkey FOREIGN KEY (clientid) REFERENCES clients(clientid);


-- Completed on 2012-07-17 22:13:31 CEST

--
-- PostgreSQL database dump complete
--


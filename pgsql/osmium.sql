--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.4
-- Dumped by pg_dump version 9.1.4
-- Started on 2012-07-15 11:22:06 CEST

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 7 (class 2615 OID 17653)
-- Name: osmium; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA osmium;


SET search_path = osmium, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 172 (class 1259 OID 17655)
-- Dependencies: 7
-- Name: accountfavorites; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE accountfavorites (
    accountid integer NOT NULL,
    loadoutid integer NOT NULL,
    favoritedate integer NOT NULL
);


--
-- TOC entry 173 (class 1259 OID 17658)
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
-- TOC entry 174 (class 1259 OID 17664)
-- Dependencies: 7 173
-- Name: accounts_accountid_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE accounts_accountid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2215 (class 0 OID 0)
-- Dependencies: 174
-- Name: accounts_accountid_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE accounts_accountid_seq OWNED BY accounts.accountid;


--
-- TOC entry 175 (class 1259 OID 17666)
-- Dependencies: 7
-- Name: accountsettings; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE accountsettings (
    accountid integer NOT NULL,
    key character varying(255) NOT NULL,
    value text
);


--
-- TOC entry 176 (class 1259 OID 17672)
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
-- TOC entry 177 (class 1259 OID 17674)
-- Dependencies: 2067 2068 7
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
-- TOC entry 178 (class 1259 OID 17682)
-- Dependencies: 2050 7
-- Name: allowedloadoutsanonymous; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW allowedloadoutsanonymous AS
    SELECT loadouts.loadoutid FROM loadouts WHERE ((loadouts.viewpermission = 0) OR (loadouts.viewpermission = 1));


--
-- TOC entry 179 (class 1259 OID 17686)
-- Dependencies: 2051 7
-- Name: allowedloadoutsbyaccount; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW allowedloadoutsbyaccount AS
    SELECT accounts.accountid, loadouts.loadoutid FROM ((loadouts JOIN accounts author ON ((author.accountid = loadouts.accountid))) JOIN accounts ON ((((((loadouts.viewpermission = 0) OR (loadouts.viewpermission = 1)) OR ((((loadouts.viewpermission = 2) AND (accounts.apiverified = true)) AND (author.apiverified = true)) AND (accounts.allianceid = author.allianceid))) OR ((((loadouts.viewpermission = 3) AND (accounts.apiverified = true)) AND (author.apiverified = true)) AND (accounts.corporationid = author.corporationid))) OR (accounts.accountid = author.accountid))));


--
-- TOC entry 180 (class 1259 OID 17691)
-- Dependencies: 7
-- Name: cacheexpressions; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE cacheexpressions (
    expressionid integer NOT NULL,
    exp text NOT NULL
);


--
-- TOC entry 181 (class 1259 OID 17697)
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
-- TOC entry 182 (class 1259 OID 17703)
-- Dependencies: 2052 7
-- Name: editableloadoutsbyaccount; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW editableloadoutsbyaccount AS
    SELECT accounts.accountid, loadouts.loadoutid FROM ((loadouts JOIN accounts author ON ((author.accountid = loadouts.accountid))) JOIN accounts ON (((((((((loadouts.editpermission = 3) AND (accounts.apiverified = true)) AND (author.apiverified = true)) AND (accounts.allianceid = author.allianceid)) OR ((((loadouts.editpermission = 2) AND (accounts.apiverified = true)) AND (author.apiverified = true)) AND (accounts.corporationid = author.corporationid))) OR (((((loadouts.editpermission = 1) AND (accounts.apiverified = true)) AND (author.apiverified = true)) AND (accounts.corporationid = author.corporationid)) AND (accounts.isfittingmanager = true))) OR (accounts.accountid = author.accountid)) OR (accounts.ismoderator = true))));


--
-- TOC entry 183 (class 1259 OID 17708)
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
-- TOC entry 184 (class 1259 OID 17714)
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
-- TOC entry 185 (class 1259 OID 17717)
-- Dependencies: 7
-- Name: fittingdeltas; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE fittingdeltas (
    fittinghash1 character(40) NOT NULL,
    fittinghash2 character(40) NOT NULL,
    delta text NOT NULL
);


--
-- TOC entry 186 (class 1259 OID 17723)
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
-- TOC entry 187 (class 1259 OID 17729)
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
-- TOC entry 188 (class 1259 OID 17732)
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
-- TOC entry 189 (class 1259 OID 17735)
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
-- TOC entry 190 (class 1259 OID 17741)
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
-- TOC entry 191 (class 1259 OID 17747)
-- Dependencies: 7
-- Name: fittingtags; Type: TABLE; Schema: osmium; Owner: -; Tablespace: 
--

CREATE TABLE fittingtags (
    fittinghash character(40) NOT NULL,
    tagname character varying(127) NOT NULL
);


--
-- TOC entry 212 (class 1259 OID 25855)
-- Dependencies: 2072 7
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
-- TOC entry 211 (class 1259 OID 25853)
-- Dependencies: 212 7
-- Name: flags_flagid_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE flags_flagid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2216 (class 0 OID 0)
-- Dependencies: 211
-- Name: flags_flagid_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE flags_flagid_seq OWNED BY flags.flagid;


--
-- TOC entry 192 (class 1259 OID 17753)
-- Dependencies: 2053 7
-- Name: invcharges; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invcharges AS
    SELECT modattribs.typeid AS moduleid, invtypes.typeid AS chargeid, invtypes.typename AS chargename FROM (((eve.dgmtypeattribs modattribs LEFT JOIN eve.dgmtypeattribs modchargesize ON (((modchargesize.attributeid = 128) AND (modchargesize.typeid = modattribs.typeid)))) JOIN eve.invtypes ON (((modattribs.value)::integer = invtypes.groupid))) LEFT JOIN eve.dgmtypeattribs chargesize ON (((chargesize.attributeid = 128) AND (chargesize.typeid = invtypes.typeid)))) WHERE (((modattribs.attributeid = ANY (ARRAY[604, 605, 606, 609, 610])) AND (((chargesize.value IS NULL) OR (modchargesize.value IS NULL)) OR (chargesize.value = modchargesize.value))) AND (invtypes.published = 1));


--
-- TOC entry 193 (class 1259 OID 17758)
-- Dependencies: 2054 7
-- Name: invdrones; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invdrones AS
    SELECT invtypes.typeid, invtypes.typename, invtypes.volume, invtypes.groupid, invgroups.groupname FROM (eve.invtypes JOIN eve.invgroups ON ((invtypes.groupid = invgroups.groupid))) WHERE (((invgroups.categoryid = 18) AND (invgroups.published = 1)) AND (invtypes.published = 1));


--
-- TOC entry 194 (class 1259 OID 17763)
-- Dependencies: 2055 7
-- Name: invmodules; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invmodules AS
    SELECT invtypes.typeid, invtypes.typename, COALESCE((invmetatypes.metagroupid)::integer, (metagroup.value)::integer, CASE (techlevel.value)::integer WHEN 2 THEN 2 WHEN 3 THEN 14 ELSE 1 END) AS metagroupid, invgroups.groupid, invgroups.groupname FROM ((((eve.invtypes JOIN eve.invgroups ON ((invtypes.groupid = invgroups.groupid))) LEFT JOIN eve.invmetatypes ON ((invtypes.typeid = invmetatypes.typeid))) LEFT JOIN eve.dgmtypeattribs techlevel ON (((techlevel.typeid = invtypes.typeid) AND (techlevel.attributeid = 422)))) LEFT JOIN eve.dgmtypeattribs metagroup ON (((metagroup.typeid = invtypes.typeid) AND (metagroup.attributeid = 1692)))) WHERE (((invgroups.categoryid = ANY (ARRAY[7, 32])) AND (invgroups.published = 1)) AND (invtypes.published = 1));


--
-- TOC entry 195 (class 1259 OID 17768)
-- Dependencies: 2056 7
-- Name: invmetagroups; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invmetagroups AS
    SELECT DISTINCT invmetagroups.metagroupid, invmetagroups.metagroupname FROM (invmodules LEFT JOIN eve.invmetagroups ON ((invmodules.metagroupid = invmetagroups.metagroupid)));


--
-- TOC entry 196 (class 1259 OID 17772)
-- Dependencies: 2057 7
-- Name: invships; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invships AS
    SELECT invtypes.typeid, invtypes.typename, invtypes.groupid, invgroups.groupname FROM (eve.invtypes JOIN eve.invgroups ON ((invtypes.groupid = invgroups.groupid))) WHERE ((invgroups.categoryid = 6) AND (invtypes.published = 1));


--
-- TOC entry 197 (class 1259 OID 17777)
-- Dependencies: 2058 7
-- Name: invskills; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invskills AS
    SELECT invtypes.typeid, invtypes.typename, invtypes.groupid, invgroups.groupname FROM (eve.invtypes JOIN eve.invgroups ON ((invtypes.groupid = invgroups.groupid))) WHERE ((invgroups.categoryid = 16) AND (invtypes.published = 1));


--
-- TOC entry 198 (class 1259 OID 17782)
-- Dependencies: 2059 7
-- Name: invusedtypes; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW invusedtypes AS
    (((SELECT invships.typeid FROM invships UNION SELECT invmodules.typeid FROM invmodules) UNION SELECT invcharges.chargeid AS typeid FROM invcharges) UNION SELECT invdrones.typeid FROM invdrones) UNION SELECT invskills.typeid FROM invskills;


--
-- TOC entry 210 (class 1259 OID 18267)
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
-- TOC entry 209 (class 1259 OID 18265)
-- Dependencies: 7 210
-- Name: loadoutcommentreplies_commentreplyid_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE loadoutcommentreplies_commentreplyid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2217 (class 0 OID 0)
-- Dependencies: 209
-- Name: loadoutcommentreplies_commentreplyid_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE loadoutcommentreplies_commentreplyid_seq OWNED BY loadoutcommentreplies.commentreplyid;


--
-- TOC entry 199 (class 1259 OID 17794)
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
-- TOC entry 200 (class 1259 OID 17800)
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
-- TOC entry 201 (class 1259 OID 17803)
-- Dependencies: 7 200
-- Name: loadoutcomments_commentid_seq; Type: SEQUENCE; Schema: osmium; Owner: -
--

CREATE SEQUENCE loadoutcomments_commentid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- TOC entry 2218 (class 0 OID 0)
-- Dependencies: 201
-- Name: loadoutcomments_commentid_seq; Type: SEQUENCE OWNED BY; Schema: osmium; Owner: -
--

ALTER SEQUENCE loadoutcomments_commentid_seq OWNED BY loadoutcomments.commentid;


--
-- TOC entry 208 (class 1259 OID 18246)
-- Dependencies: 2065 7
-- Name: loadoutcommentslatestrevision; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW loadoutcommentslatestrevision AS
    SELECT loadoutcomments.commentid, max(loadoutcommentrevisions.revision) AS latestrevision FROM (loadoutcomments JOIN loadoutcommentrevisions ON ((loadoutcommentrevisions.commentid = loadoutcomments.commentid))) GROUP BY loadoutcomments.commentid;


--
-- TOC entry 202 (class 1259 OID 17805)
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
-- TOC entry 203 (class 1259 OID 17808)
-- Dependencies: 2060 7
-- Name: loadoutslatestrevision; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW loadoutslatestrevision AS
    SELECT loadouts.loadoutid, max(loadouthistory.revision) AS latestrevision FROM (loadouts JOIN loadouthistory ON ((loadouthistory.loadoutid = loadouts.loadoutid))) GROUP BY loadouts.loadoutid;


--
-- TOC entry 204 (class 1259 OID 17812)
-- Dependencies: 2061 7
-- Name: loadoutsmodulelist; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW loadoutsmodulelist AS
    SELECT loadoutslatestrevision.loadoutid, string_agg(DISTINCT (invtypes.typename)::text, ' '::text) AS modulelist FROM (((loadoutslatestrevision JOIN loadouthistory ON (((loadouthistory.loadoutid = loadoutslatestrevision.loadoutid) AND (loadouthistory.revision = loadoutslatestrevision.latestrevision)))) JOIN fittingmodules ON ((fittingmodules.fittinghash = loadouthistory.fittinghash))) JOIN eve.invtypes ON ((fittingmodules.typeid = invtypes.typeid))) GROUP BY loadoutslatestrevision.loadoutid;


--
-- TOC entry 205 (class 1259 OID 17817)
-- Dependencies: 2062 7
-- Name: loadoutstaglist; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW loadoutstaglist AS
    SELECT loadoutslatestrevision.loadoutid, string_agg(DISTINCT (fittingtags.tagname)::text, ' '::text) AS taglist FROM ((loadoutslatestrevision JOIN loadouthistory ON (((loadouthistory.loadoutid = loadoutslatestrevision.loadoutid) AND (loadouthistory.revision = loadoutslatestrevision.latestrevision)))) JOIN fittingtags ON ((fittingtags.fittinghash = loadouthistory.fittinghash))) GROUP BY loadoutslatestrevision.loadoutid;


--
-- TOC entry 206 (class 1259 OID 17821)
-- Dependencies: 2063 7
-- Name: searchableloadouts; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW searchableloadouts AS
    SELECT allowedloadoutsbyaccount.accountid, allowedloadoutsbyaccount.loadoutid FROM (allowedloadoutsbyaccount JOIN loadouts ON ((allowedloadoutsbyaccount.loadoutid = loadouts.loadoutid))) WHERE ((loadouts.visibility = 0) AND (loadouts.viewpermission <> 0)) UNION SELECT 0 AS accountid, allowedloadoutsanonymous.loadoutid FROM (allowedloadoutsanonymous JOIN loadouts ON ((allowedloadoutsanonymous.loadoutid = loadouts.loadoutid))) WHERE (loadouts.visibility = 0);


--
-- TOC entry 207 (class 1259 OID 18097)
-- Dependencies: 2064 7
-- Name: loadoutssearchdata; Type: VIEW; Schema: osmium; Owner: -
--

CREATE VIEW loadoutssearchdata AS
    SELECT searchableloadouts.loadoutid, CASE loadouts.viewpermission WHEN 4 THEN accounts.accountid ELSE 0 END AS restrictedtoaccountid, CASE loadouts.viewpermission WHEN 3 THEN CASE accounts.apiverified WHEN true THEN accounts.corporationid ELSE 0 END ELSE 0 END AS restrictedtocorporationid, CASE loadouts.viewpermission WHEN 2 THEN CASE accounts.apiverified WHEN true THEN accounts.allianceid ELSE 0 END ELSE 0 END AS restrictedtoallianceid, loadoutstaglist.taglist AS tags, loadoutsmodulelist.modulelist AS modules, CASE accounts.apiverified WHEN true THEN accounts.charactername ELSE accounts.nickname END AS author, fittings.name, fittings.description, fittings.hullid AS shipid, invtypes.typename AS ship, fittings.creationdate, loadouthistory.updatedate FROM ((((((((searchableloadouts JOIN loadoutslatestrevision ON ((searchableloadouts.loadoutid = loadoutslatestrevision.loadoutid))) JOIN loadouts ON ((loadoutslatestrevision.loadoutid = loadouts.loadoutid))) JOIN accounts ON ((loadouts.accountid = accounts.accountid))) JOIN loadouthistory ON (((loadouthistory.loadoutid = loadoutslatestrevision.loadoutid) AND (loadouthistory.revision = loadoutslatestrevision.latestrevision)))) JOIN fittings ON ((fittings.fittinghash = loadouthistory.fittinghash))) LEFT JOIN loadoutstaglist ON ((loadoutstaglist.loadoutid = loadoutslatestrevision.loadoutid))) LEFT JOIN loadoutsmodulelist ON ((loadoutsmodulelist.loadoutid = loadoutslatestrevision.loadoutid))) JOIN eve.invtypes ON ((invtypes.typeid = fittings.hullid)));


--
-- TOC entry 2066 (class 2604 OID 17831)
-- Dependencies: 174 173
-- Name: accountid; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accounts ALTER COLUMN accountid SET DEFAULT nextval('accounts_accountid_seq'::regclass);


--
-- TOC entry 2071 (class 2604 OID 25858)
-- Dependencies: 211 212 212
-- Name: flagid; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY flags ALTER COLUMN flagid SET DEFAULT nextval('flags_flagid_seq'::regclass);


--
-- TOC entry 2070 (class 2604 OID 18270)
-- Dependencies: 210 209 210
-- Name: commentreplyid; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcommentreplies ALTER COLUMN commentreplyid SET DEFAULT nextval('loadoutcommentreplies_commentreplyid_seq'::regclass);


--
-- TOC entry 2069 (class 2604 OID 17833)
-- Dependencies: 201 200
-- Name: commentid; Type: DEFAULT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcomments ALTER COLUMN commentid SET DEFAULT nextval('loadoutcomments_commentid_seq'::regclass);


--
-- TOC entry 2076 (class 2606 OID 17835)
-- Dependencies: 172 172 172
-- Name: accountfavorites_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accountfavorites
    ADD CONSTRAINT accountfavorites_pkey PRIMARY KEY (accountid, loadoutid);


--
-- TOC entry 2079 (class 2606 OID 17837)
-- Dependencies: 173 173
-- Name: accounts_accountname_uniq; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_accountname_uniq UNIQUE (accountname);


--
-- TOC entry 2084 (class 2606 OID 17839)
-- Dependencies: 173 173
-- Name: accounts_characterid_uniq; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_characterid_uniq UNIQUE (characterid);


--
-- TOC entry 2086 (class 2606 OID 17841)
-- Dependencies: 173 173
-- Name: accounts_charactername_uniq; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_charactername_uniq UNIQUE (charactername);


--
-- TOC entry 2092 (class 2606 OID 17843)
-- Dependencies: 173 173
-- Name: accounts_nickname_uniq; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_nickname_uniq UNIQUE (nickname);


--
-- TOC entry 2094 (class 2606 OID 17845)
-- Dependencies: 173 173
-- Name: accounts_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (accountid);


--
-- TOC entry 2097 (class 2606 OID 17847)
-- Dependencies: 175 175 175
-- Name: accountsettings_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accountsettings
    ADD CONSTRAINT accountsettings_pkey PRIMARY KEY (accountid, key);


--
-- TOC entry 2105 (class 2606 OID 17849)
-- Dependencies: 180 180
-- Name: cacheexpressions_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY cacheexpressions
    ADD CONSTRAINT cacheexpressions_pkey PRIMARY KEY (expressionid);


--
-- TOC entry 2109 (class 2606 OID 17851)
-- Dependencies: 181 181
-- Name: cookietokens_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY cookietokens
    ADD CONSTRAINT cookietokens_pkey PRIMARY KEY (token);


--
-- TOC entry 2113 (class 2606 OID 17853)
-- Dependencies: 183 183 183 183
-- Name: fittingchargepresets_fittinghash_presetid_name_unique; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingchargepresets
    ADD CONSTRAINT fittingchargepresets_fittinghash_presetid_name_unique UNIQUE (fittinghash, presetid, name);


--
-- TOC entry 2115 (class 2606 OID 17855)
-- Dependencies: 183 183 183 183
-- Name: fittingchargepresets_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingchargepresets
    ADD CONSTRAINT fittingchargepresets_pkey PRIMARY KEY (fittinghash, presetid, chargepresetid);


--
-- TOC entry 2120 (class 2606 OID 17857)
-- Dependencies: 184 184 184 184 184 184
-- Name: fittingcharges_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingcharges
    ADD CONSTRAINT fittingcharges_pkey PRIMARY KEY (fittinghash, presetid, chargepresetid, slottype, index);


--
-- TOC entry 2125 (class 2606 OID 17859)
-- Dependencies: 185 185 185
-- Name: fittingdeltas_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingdeltas
    ADD CONSTRAINT fittingdeltas_pkey PRIMARY KEY (fittinghash1, fittinghash2);


--
-- TOC entry 2128 (class 2606 OID 17861)
-- Dependencies: 186 186 186
-- Name: fittingdronepresets_fittinghash_name_unique; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingdronepresets
    ADD CONSTRAINT fittingdronepresets_fittinghash_name_unique UNIQUE (fittinghash, name);


--
-- TOC entry 2130 (class 2606 OID 17863)
-- Dependencies: 186 186 186
-- Name: fittingdronepresets_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingdronepresets
    ADD CONSTRAINT fittingdronepresets_pkey PRIMARY KEY (fittinghash, dronepresetid);


--
-- TOC entry 2133 (class 2606 OID 17865)
-- Dependencies: 187 187 187 187
-- Name: fittingdrones_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingdrones
    ADD CONSTRAINT fittingdrones_pkey PRIMARY KEY (fittinghash, dronepresetid, typeid);


--
-- TOC entry 2136 (class 2606 OID 17867)
-- Dependencies: 188 188 188 188 188
-- Name: fittingmodules_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingmodules
    ADD CONSTRAINT fittingmodules_pkey PRIMARY KEY (fittinghash, presetid, slottype, index);


--
-- TOC entry 2139 (class 2606 OID 17869)
-- Dependencies: 189 189 189
-- Name: fittingpresets_fittinghash_name_unique; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingpresets
    ADD CONSTRAINT fittingpresets_fittinghash_name_unique UNIQUE (fittinghash, name);


--
-- TOC entry 2141 (class 2606 OID 17871)
-- Dependencies: 189 189 189
-- Name: fittingpresets_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingpresets
    ADD CONSTRAINT fittingpresets_pkey PRIMARY KEY (fittinghash, presetid);


--
-- TOC entry 2144 (class 2606 OID 17873)
-- Dependencies: 190 190
-- Name: fittings_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittings
    ADD CONSTRAINT fittings_pkey PRIMARY KEY (fittinghash);


--
-- TOC entry 2147 (class 2606 OID 17875)
-- Dependencies: 191 191 191
-- Name: fittingtags_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY fittingtags
    ADD CONSTRAINT fittingtags_pkey PRIMARY KEY (fittinghash, tagname);


--
-- TOC entry 2175 (class 2606 OID 25864)
-- Dependencies: 212 212
-- Name: flags_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY flags
    ADD CONSTRAINT flags_pkey PRIMARY KEY (flagid);


--
-- TOC entry 2170 (class 2606 OID 18275)
-- Dependencies: 210 210
-- Name: loadoutcommentreplies_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY loadoutcommentreplies
    ADD CONSTRAINT loadoutcommentreplies_pkey PRIMARY KEY (commentreplyid);


--
-- TOC entry 2151 (class 2606 OID 17881)
-- Dependencies: 199 199 199
-- Name: loadoutcommentrevisions_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY loadoutcommentrevisions
    ADD CONSTRAINT loadoutcommentrevisions_pkey PRIMARY KEY (commentid, revision);


--
-- TOC entry 2158 (class 2606 OID 17883)
-- Dependencies: 200 200
-- Name: loadoutcomments_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY loadoutcomments
    ADD CONSTRAINT loadoutcomments_pkey PRIMARY KEY (commentid);


--
-- TOC entry 2163 (class 2606 OID 17885)
-- Dependencies: 202 202 202
-- Name: loadouthistory_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY loadouthistory
    ADD CONSTRAINT loadouthistory_pkey PRIMARY KEY (loadoutid, revision);


--
-- TOC entry 2101 (class 2606 OID 17887)
-- Dependencies: 177 177
-- Name: loadouts_pkey; Type: CONSTRAINT; Schema: osmium; Owner: -; Tablespace: 
--

ALTER TABLE ONLY loadouts
    ADD CONSTRAINT loadouts_pkey PRIMARY KEY (loadoutid);


--
-- TOC entry 2073 (class 1259 OID 17888)
-- Dependencies: 172
-- Name: accountfavorites_accountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accountfavorites_accountid_idx ON accountfavorites USING btree (accountid);


--
-- TOC entry 2074 (class 1259 OID 17889)
-- Dependencies: 172
-- Name: accountfavorites_loadoutid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accountfavorites_loadoutid_idx ON accountfavorites USING btree (loadoutid);


--
-- TOC entry 2077 (class 1259 OID 17890)
-- Dependencies: 173
-- Name: accounts_accountname_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_accountname_idx ON accounts USING btree (accountname);


--
-- TOC entry 2080 (class 1259 OID 17891)
-- Dependencies: 173
-- Name: accounts_allianceid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_allianceid_idx ON accounts USING btree (allianceid);


--
-- TOC entry 2081 (class 1259 OID 17892)
-- Dependencies: 173
-- Name: accounts_apiverified_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_apiverified_idx ON accounts USING btree (apiverified);


--
-- TOC entry 2082 (class 1259 OID 17893)
-- Dependencies: 173
-- Name: accounts_characterid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_characterid_idx ON accounts USING btree (characterid);


--
-- TOC entry 2087 (class 1259 OID 17894)
-- Dependencies: 173
-- Name: accounts_corporationid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_corporationid_idx ON accounts USING btree (corporationid);


--
-- TOC entry 2088 (class 1259 OID 17895)
-- Dependencies: 173
-- Name: accounts_isfittingmanager_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_isfittingmanager_idx ON accounts USING btree (isfittingmanager);


--
-- TOC entry 2089 (class 1259 OID 17896)
-- Dependencies: 173
-- Name: accounts_ismoderator_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_ismoderator_idx ON accounts USING btree (ismoderator);


--
-- TOC entry 2090 (class 1259 OID 18094)
-- Dependencies: 173
-- Name: accounts_nickname_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accounts_nickname_idx ON accounts USING btree (nickname);


--
-- TOC entry 2095 (class 1259 OID 17897)
-- Dependencies: 175
-- Name: accountsettings_accountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX accountsettings_accountid_idx ON accountsettings USING btree (accountid);


--
-- TOC entry 2106 (class 1259 OID 17898)
-- Dependencies: 181
-- Name: cookietokens_accountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX cookietokens_accountid_idx ON cookietokens USING btree (accountid);


--
-- TOC entry 2107 (class 1259 OID 17899)
-- Dependencies: 181
-- Name: cookietokens_expirationdate_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX cookietokens_expirationdate_idx ON cookietokens USING btree (expirationdate);


--
-- TOC entry 2110 (class 1259 OID 17900)
-- Dependencies: 183
-- Name: fittingchargepresets_fittinghash_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingchargepresets_fittinghash_idx ON fittingchargepresets USING btree (fittinghash);


--
-- TOC entry 2111 (class 1259 OID 17901)
-- Dependencies: 183 183
-- Name: fittingchargepresets_fittinghash_presetid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingchargepresets_fittinghash_presetid_idx ON fittingchargepresets USING btree (fittinghash, presetid);


--
-- TOC entry 2116 (class 1259 OID 17902)
-- Dependencies: 184
-- Name: fittingcharges_fittinghash_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingcharges_fittinghash_idx ON fittingcharges USING btree (fittinghash);


--
-- TOC entry 2117 (class 1259 OID 17903)
-- Dependencies: 184 184 184
-- Name: fittingcharges_fittinghash_presetid_chargepresetid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingcharges_fittinghash_presetid_chargepresetid_idx ON fittingcharges USING btree (fittinghash, presetid, chargepresetid);


--
-- TOC entry 2118 (class 1259 OID 17904)
-- Dependencies: 184 184 184 184
-- Name: fittingcharges_fittinghash_presetid_slottype_index_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingcharges_fittinghash_presetid_slottype_index_idx ON fittingcharges USING btree (fittinghash, presetid, slottype, index);


--
-- TOC entry 2121 (class 1259 OID 17905)
-- Dependencies: 184
-- Name: fittingcharges_typeid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingcharges_typeid_idx ON fittingcharges USING btree (typeid);


--
-- TOC entry 2122 (class 1259 OID 17906)
-- Dependencies: 185
-- Name: fittingdeltas_fittinghash1_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingdeltas_fittinghash1_idx ON fittingdeltas USING btree (fittinghash1);


--
-- TOC entry 2123 (class 1259 OID 17907)
-- Dependencies: 185
-- Name: fittingdeltas_fittinghash2_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingdeltas_fittinghash2_idx ON fittingdeltas USING btree (fittinghash2);


--
-- TOC entry 2126 (class 1259 OID 17908)
-- Dependencies: 186
-- Name: fittingdronepresets_fittinghash_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingdronepresets_fittinghash_idx ON fittingdronepresets USING btree (fittinghash);


--
-- TOC entry 2131 (class 1259 OID 17909)
-- Dependencies: 187 187
-- Name: fittingdrones_fittinghash_dronepresetid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingdrones_fittinghash_dronepresetid_idx ON fittingdrones USING btree (fittinghash, dronepresetid);


--
-- TOC entry 2134 (class 1259 OID 17910)
-- Dependencies: 187
-- Name: fittingdrones_typeid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingdrones_typeid_idx ON fittingdrones USING btree (typeid);


--
-- TOC entry 2137 (class 1259 OID 17911)
-- Dependencies: 189
-- Name: fittingpresets_fittinghash_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingpresets_fittinghash_idx ON fittingpresets USING btree (fittinghash);


--
-- TOC entry 2142 (class 1259 OID 17912)
-- Dependencies: 190
-- Name: fittings_hullid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittings_hullid_idx ON fittings USING btree (hullid);


--
-- TOC entry 2145 (class 1259 OID 17913)
-- Dependencies: 191
-- Name: fittingtags_fittinghash_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingtags_fittinghash_idx ON fittingtags USING btree (fittinghash);


--
-- TOC entry 2148 (class 1259 OID 17914)
-- Dependencies: 191
-- Name: fittingtags_tagname_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX fittingtags_tagname_idx ON fittingtags USING btree (tagname);


--
-- TOC entry 2172 (class 1259 OID 25870)
-- Dependencies: 212
-- Name: flags_createdat_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_createdat_idx ON flags USING btree (createdat);


--
-- TOC entry 2173 (class 1259 OID 25871)
-- Dependencies: 212
-- Name: flags_flaggedbyaccountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_flaggedbyaccountid_idx ON flags USING btree (flaggedbyaccountid);


--
-- TOC entry 2176 (class 1259 OID 25872)
-- Dependencies: 212
-- Name: flags_status_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_status_idx ON flags USING btree (status);


--
-- TOC entry 2177 (class 1259 OID 25873)
-- Dependencies: 212
-- Name: flags_subtype_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_subtype_idx ON flags USING btree (subtype);


--
-- TOC entry 2178 (class 1259 OID 25874)
-- Dependencies: 212
-- Name: flags_target1_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_target1_idx ON flags USING btree (target1);


--
-- TOC entry 2179 (class 1259 OID 25875)
-- Dependencies: 212
-- Name: flags_target2_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_target2_idx ON flags USING btree (target2);


--
-- TOC entry 2180 (class 1259 OID 25876)
-- Dependencies: 212
-- Name: flags_target3_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_target3_idx ON flags USING btree (target3);


--
-- TOC entry 2181 (class 1259 OID 25877)
-- Dependencies: 212
-- Name: flags_type_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX flags_type_idx ON flags USING btree (type);


--
-- TOC entry 2166 (class 1259 OID 18291)
-- Dependencies: 210
-- Name: loadoutcommentreplies_accountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentreplies_accountid_idx ON loadoutcommentreplies USING btree (accountid);


--
-- TOC entry 2167 (class 1259 OID 18292)
-- Dependencies: 210
-- Name: loadoutcommentreplies_commentid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentreplies_commentid_idx ON loadoutcommentreplies USING btree (commentid);


--
-- TOC entry 2168 (class 1259 OID 18293)
-- Dependencies: 210
-- Name: loadoutcommentreplies_creationdate_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentreplies_creationdate_idx ON loadoutcommentreplies USING btree (creationdate);


--
-- TOC entry 2171 (class 1259 OID 18294)
-- Dependencies: 210
-- Name: loadoutcommentreplies_updatedbyaccountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentreplies_updatedbyaccountid_idx ON loadoutcommentreplies USING btree (updatedbyaccountid);


--
-- TOC entry 2149 (class 1259 OID 17923)
-- Dependencies: 199
-- Name: loadoutcommentrevisions_commentid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentrevisions_commentid_idx ON loadoutcommentrevisions USING btree (commentid);


--
-- TOC entry 2152 (class 1259 OID 17924)
-- Dependencies: 199
-- Name: loadoutcommentrevisions_revision_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentrevisions_revision_idx ON loadoutcommentrevisions USING btree (revision);


--
-- TOC entry 2153 (class 1259 OID 17925)
-- Dependencies: 199
-- Name: loadoutcommentrevisions_updatedbyaccountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcommentrevisions_updatedbyaccountid_idx ON loadoutcommentrevisions USING btree (updatedbyaccountid);


--
-- TOC entry 2154 (class 1259 OID 17926)
-- Dependencies: 200
-- Name: loadoutcomments_accountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcomments_accountid_idx ON loadoutcomments USING btree (loadoutid);


--
-- TOC entry 2155 (class 1259 OID 17927)
-- Dependencies: 200
-- Name: loadoutcomments_creationdate_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcomments_creationdate_idx ON loadoutcomments USING btree (creationdate);


--
-- TOC entry 2156 (class 1259 OID 17928)
-- Dependencies: 200
-- Name: loadoutcomments_loadoutid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcomments_loadoutid_idx ON loadoutcomments USING btree (loadoutid);


--
-- TOC entry 2159 (class 1259 OID 17929)
-- Dependencies: 200
-- Name: loadoutcomments_revision_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadoutcomments_revision_idx ON loadoutcomments USING btree (revision);


--
-- TOC entry 2160 (class 1259 OID 17930)
-- Dependencies: 202
-- Name: loadouthistory_fittinghash_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouthistory_fittinghash_idx ON loadouthistory USING btree (fittinghash);


--
-- TOC entry 2161 (class 1259 OID 17931)
-- Dependencies: 202
-- Name: loadouthistory_loadoutid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouthistory_loadoutid_idx ON loadouthistory USING btree (loadoutid);


--
-- TOC entry 2164 (class 1259 OID 17932)
-- Dependencies: 202
-- Name: loadouthistory_updatedate_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouthistory_updatedate_idx ON loadouthistory USING btree (updatedate);


--
-- TOC entry 2165 (class 1259 OID 17933)
-- Dependencies: 202
-- Name: loadouthistory_updatedbyaccountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouthistory_updatedbyaccountid_idx ON loadouthistory USING btree (updatedbyaccountid);


--
-- TOC entry 2098 (class 1259 OID 17934)
-- Dependencies: 177
-- Name: loadouts_accountid_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouts_accountid_idx ON loadouts USING btree (accountid);


--
-- TOC entry 2099 (class 1259 OID 17935)
-- Dependencies: 177
-- Name: loadouts_editpermission_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouts_editpermission_idx ON loadouts USING btree (editpermission);


--
-- TOC entry 2102 (class 1259 OID 17936)
-- Dependencies: 177
-- Name: loadouts_viewpermission_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouts_viewpermission_idx ON loadouts USING btree (viewpermission);


--
-- TOC entry 2103 (class 1259 OID 17937)
-- Dependencies: 177
-- Name: loadouts_visibility_idx; Type: INDEX; Schema: osmium; Owner: -; Tablespace: 
--

CREATE INDEX loadouts_visibility_idx ON loadouts USING btree (visibility);


--
-- TOC entry 2182 (class 2606 OID 17938)
-- Dependencies: 172 2093 173
-- Name: accountfavorites_accountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accountfavorites
    ADD CONSTRAINT accountfavorites_accountid_fkey FOREIGN KEY (accountid) REFERENCES accounts(accountid);


--
-- TOC entry 2183 (class 2606 OID 17943)
-- Dependencies: 2100 177 172
-- Name: accountfavorites_loadoutid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accountfavorites
    ADD CONSTRAINT accountfavorites_loadoutid_fkey FOREIGN KEY (loadoutid) REFERENCES loadouts(loadoutid);


--
-- TOC entry 2184 (class 2606 OID 17948)
-- Dependencies: 2093 173 175
-- Name: accountsettings_accountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY accountsettings
    ADD CONSTRAINT accountsettings_accountid_fkey FOREIGN KEY (accountid) REFERENCES accounts(accountid);


--
-- TOC entry 2186 (class 2606 OID 17953)
-- Dependencies: 2093 181 173
-- Name: cookietokens_accountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY cookietokens
    ADD CONSTRAINT cookietokens_accountid_fkey FOREIGN KEY (accountid) REFERENCES accounts(accountid);


--
-- TOC entry 2187 (class 2606 OID 17958)
-- Dependencies: 189 183 183 2140 189
-- Name: fittingchargepresets_fittinghash_presetid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingchargepresets
    ADD CONSTRAINT fittingchargepresets_fittinghash_presetid_fkey FOREIGN KEY (fittinghash, presetid) REFERENCES fittingpresets(fittinghash, presetid);


--
-- TOC entry 2188 (class 2606 OID 17963)
-- Dependencies: 183 184 184 184 183 183 2114
-- Name: fittingcharges_fittinghash_presetid_chargepresetid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingcharges
    ADD CONSTRAINT fittingcharges_fittinghash_presetid_chargepresetid_fkey FOREIGN KEY (fittinghash, presetid, chargepresetid) REFERENCES fittingchargepresets(fittinghash, presetid, chargepresetid);


--
-- TOC entry 2189 (class 2606 OID 17968)
-- Dependencies: 2135 188 188 184 184 184 184 188 188
-- Name: fittingcharges_fittinghash_presetid_slottype_index_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingcharges
    ADD CONSTRAINT fittingcharges_fittinghash_presetid_slottype_index_fkey FOREIGN KEY (fittinghash, presetid, slottype, index) REFERENCES fittingmodules(fittinghash, presetid, slottype, index);


--
-- TOC entry 2190 (class 2606 OID 17973)
-- Dependencies: 184 171
-- Name: fittingcharges_typeid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingcharges
    ADD CONSTRAINT fittingcharges_typeid_fkey FOREIGN KEY (typeid) REFERENCES eve.invtypes(typeid);


--
-- TOC entry 2191 (class 2606 OID 17978)
-- Dependencies: 2143 190 185
-- Name: fittingdeltas_fittinghash1_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingdeltas
    ADD CONSTRAINT fittingdeltas_fittinghash1_fkey FOREIGN KEY (fittinghash1) REFERENCES fittings(fittinghash);


--
-- TOC entry 2192 (class 2606 OID 17983)
-- Dependencies: 190 185 2143
-- Name: fittingdeltas_fittinghash2_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingdeltas
    ADD CONSTRAINT fittingdeltas_fittinghash2_fkey FOREIGN KEY (fittinghash2) REFERENCES fittings(fittinghash);


--
-- TOC entry 2193 (class 2606 OID 17988)
-- Dependencies: 2143 186 190
-- Name: fittingdronepresets_fittinghash_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingdronepresets
    ADD CONSTRAINT fittingdronepresets_fittinghash_fkey FOREIGN KEY (fittinghash) REFERENCES fittings(fittinghash);


--
-- TOC entry 2194 (class 2606 OID 17993)
-- Dependencies: 186 187 186 2129 187
-- Name: fittingdrones_fittinghash_dronepresetid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingdrones
    ADD CONSTRAINT fittingdrones_fittinghash_dronepresetid_fkey FOREIGN KEY (fittinghash, dronepresetid) REFERENCES fittingdronepresets(fittinghash, dronepresetid);


--
-- TOC entry 2195 (class 2606 OID 17998)
-- Dependencies: 171 187
-- Name: fittingdrones_typeid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingdrones
    ADD CONSTRAINT fittingdrones_typeid_fkey FOREIGN KEY (typeid) REFERENCES eve.invtypes(typeid);


--
-- TOC entry 2196 (class 2606 OID 18003)
-- Dependencies: 188 2140 189 189 188
-- Name: fittingmodules_fittinghash_presetid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingmodules
    ADD CONSTRAINT fittingmodules_fittinghash_presetid_fkey FOREIGN KEY (fittinghash, presetid) REFERENCES fittingpresets(fittinghash, presetid);


--
-- TOC entry 2197 (class 2606 OID 18008)
-- Dependencies: 171 188
-- Name: fittingmodules_typeid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingmodules
    ADD CONSTRAINT fittingmodules_typeid_fkey FOREIGN KEY (typeid) REFERENCES eve.invtypes(typeid);


--
-- TOC entry 2198 (class 2606 OID 18013)
-- Dependencies: 2143 189 190
-- Name: fittingpresets_fittinghash_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingpresets
    ADD CONSTRAINT fittingpresets_fittinghash_fkey FOREIGN KEY (fittinghash) REFERENCES fittings(fittinghash);


--
-- TOC entry 2199 (class 2606 OID 18018)
-- Dependencies: 190 171
-- Name: fittings_hullid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittings
    ADD CONSTRAINT fittings_hullid_fkey FOREIGN KEY (hullid) REFERENCES eve.invtypes(typeid);


--
-- TOC entry 2200 (class 2606 OID 18023)
-- Dependencies: 2143 190 191
-- Name: fittingtags_fittinghash_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY fittingtags
    ADD CONSTRAINT fittingtags_fittinghash_fkey FOREIGN KEY (fittinghash) REFERENCES fittings(fittinghash);


--
-- TOC entry 2212 (class 2606 OID 25865)
-- Dependencies: 2093 212 173
-- Name: flags_flaggedbyaccountid; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY flags
    ADD CONSTRAINT flags_flaggedbyaccountid FOREIGN KEY (flaggedbyaccountid) REFERENCES accounts(accountid);


--
-- TOC entry 2209 (class 2606 OID 18276)
-- Dependencies: 173 2093 210
-- Name: loadoutcommentreplies_accountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcommentreplies
    ADD CONSTRAINT loadoutcommentreplies_accountid_fkey FOREIGN KEY (accountid) REFERENCES accounts(accountid);


--
-- TOC entry 2210 (class 2606 OID 18281)
-- Dependencies: 2157 210 200
-- Name: loadoutcommentreplies_commentid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcommentreplies
    ADD CONSTRAINT loadoutcommentreplies_commentid_fkey FOREIGN KEY (commentid) REFERENCES loadoutcomments(commentid);


--
-- TOC entry 2211 (class 2606 OID 18286)
-- Dependencies: 173 210 2093
-- Name: loadoutcommentreplies_updatedbyaccountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcommentreplies
    ADD CONSTRAINT loadoutcommentreplies_updatedbyaccountid_fkey FOREIGN KEY (updatedbyaccountid) REFERENCES accounts(accountid);


--
-- TOC entry 2201 (class 2606 OID 18048)
-- Dependencies: 199 2157 200
-- Name: loadoutcommentrevisions_commentid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcommentrevisions
    ADD CONSTRAINT loadoutcommentrevisions_commentid_fkey FOREIGN KEY (commentid) REFERENCES loadoutcomments(commentid);


--
-- TOC entry 2202 (class 2606 OID 18053)
-- Dependencies: 2093 173 199
-- Name: loadoutcommentrevisions_updatedbyaccountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcommentrevisions
    ADD CONSTRAINT loadoutcommentrevisions_updatedbyaccountid_fkey FOREIGN KEY (updatedbyaccountid) REFERENCES accounts(accountid);


--
-- TOC entry 2203 (class 2606 OID 18058)
-- Dependencies: 2093 173 200
-- Name: loadoutcomments_accountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcomments
    ADD CONSTRAINT loadoutcomments_accountid_fkey FOREIGN KEY (accountid) REFERENCES accounts(accountid);


--
-- TOC entry 2204 (class 2606 OID 18063)
-- Dependencies: 200 177 2100
-- Name: loadoutcomments_loadoutid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcomments
    ADD CONSTRAINT loadoutcomments_loadoutid_fkey FOREIGN KEY (loadoutid) REFERENCES loadouts(loadoutid);


--
-- TOC entry 2205 (class 2606 OID 18068)
-- Dependencies: 200 200 202 202 2162
-- Name: loadoutcomments_loadoutid_revision_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadoutcomments
    ADD CONSTRAINT loadoutcomments_loadoutid_revision_fkey FOREIGN KEY (loadoutid, revision) REFERENCES loadouthistory(loadoutid, revision);


--
-- TOC entry 2206 (class 2606 OID 18073)
-- Dependencies: 202 190 2143
-- Name: loadouthistory_fittinghash_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadouthistory
    ADD CONSTRAINT loadouthistory_fittinghash_fkey FOREIGN KEY (fittinghash) REFERENCES fittings(fittinghash);


--
-- TOC entry 2207 (class 2606 OID 18078)
-- Dependencies: 202 177 2100
-- Name: loadouthistory_loadoutid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadouthistory
    ADD CONSTRAINT loadouthistory_loadoutid_fkey FOREIGN KEY (loadoutid) REFERENCES loadouts(loadoutid);


--
-- TOC entry 2208 (class 2606 OID 18083)
-- Dependencies: 2093 202 173
-- Name: loadouthistory_updatedbyaccountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadouthistory
    ADD CONSTRAINT loadouthistory_updatedbyaccountid_fkey FOREIGN KEY (updatedbyaccountid) REFERENCES accounts(accountid);


--
-- TOC entry 2185 (class 2606 OID 18088)
-- Dependencies: 177 2093 173
-- Name: loadouts_accountid_fkey; Type: FK CONSTRAINT; Schema: osmium; Owner: -
--

ALTER TABLE ONLY loadouts
    ADD CONSTRAINT loadouts_accountid_fkey FOREIGN KEY (accountid) REFERENCES accounts(accountid);


-- Completed on 2012-07-15 11:22:06 CEST

--
-- PostgreSQL database dump complete
--


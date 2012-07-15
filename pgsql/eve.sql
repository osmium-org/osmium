--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.4
-- Dumped by pg_dump version 9.1.4
-- Started on 2012-07-15 11:22:10 CEST

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 6 (class 2615 OID 16919)
-- Name: eve; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA eve;


SET search_path = eve, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 162 (class 1259 OID 16920)
-- Dependencies: 6
-- Name: dgmattribs; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmattribs (
    attributeid integer NOT NULL,
    attributename character varying(100) NOT NULL,
    attributecategory integer NOT NULL,
    description character varying(1000) NOT NULL,
    maxattributeid integer,
    attributeidx integer,
    chargerechargetimeid integer,
    defaultvalue real NOT NULL,
    published smallint NOT NULL,
    displayname character varying(100) NOT NULL,
    unitid integer,
    stackable smallint NOT NULL,
    highisgood smallint NOT NULL,
    categoryid integer,
    iconid integer,
    displaynameid integer,
    dataid integer
);


--
-- TOC entry 163 (class 1259 OID 16926)
-- Dependencies: 6
-- Name: dgmcacheexpressions; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmcacheexpressions (
    expressionid integer NOT NULL,
    exp text NOT NULL
);


--
-- TOC entry 164 (class 1259 OID 16932)
-- Dependencies: 6
-- Name: dgmeffects; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmeffects (
    effectid integer NOT NULL,
    effectname character varying(300) NOT NULL,
    effectcategory integer NOT NULL,
    preexpression integer NOT NULL,
    postexpression integer NOT NULL,
    description character varying(1000),
    guid character varying(1000),
    isoffensive smallint NOT NULL,
    isassistance smallint NOT NULL,
    durationattributeid integer,
    trackingspeedattributeid integer,
    dischargeattributeid integer,
    rangeattributeid integer,
    falloffattributeid integer,
    disallowautorepeat smallint NOT NULL,
    published smallint NOT NULL,
    displayname character varying(100) NOT NULL,
    iswarpsafe smallint NOT NULL,
    rangechance integer NOT NULL,
    electronicchance integer NOT NULL,
    propulsionchance integer NOT NULL,
    distribution integer,
    sfxname character varying(1000),
    npcusagechanceattributeid integer,
    npcactivationchanceattributeid integer,
    fittingusagechanceattributeid integer,
    iconid integer,
    displaynameid integer,
    descriptionid integer,
    dataid integer
);


--
-- TOC entry 165 (class 1259 OID 16938)
-- Dependencies: 6
-- Name: dgmtypeattribs; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmtypeattribs (
    typeid integer NOT NULL,
    attributeid smallint NOT NULL,
    value double precision NOT NULL
);


--
-- TOC entry 166 (class 1259 OID 16941)
-- Dependencies: 6
-- Name: dgmtypeeffects; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmtypeeffects (
    typeid integer NOT NULL,
    effectid smallint NOT NULL,
    isdefault smallint
);


--
-- TOC entry 167 (class 1259 OID 16944)
-- Dependencies: 2040 2041 6
-- Name: invcategories; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invcategories (
    categoryid integer NOT NULL,
    categoryname character varying(100) DEFAULT NULL::character varying,
    description character varying(3000) DEFAULT NULL::character varying,
    published smallint,
    iconid integer,
    categorynameid integer,
    dataid integer
);


--
-- TOC entry 168 (class 1259 OID 16952)
-- Dependencies: 2042 2043 6
-- Name: invgroups; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invgroups (
    groupid integer NOT NULL,
    categoryid integer,
    groupname character varying(100) DEFAULT NULL::character varying,
    description character varying(3000) DEFAULT NULL::character varying,
    usebaseprice smallint,
    allowmanufacture smallint,
    allowrecycler smallint,
    anchored smallint,
    anchorable smallint,
    fittablenonsingleton smallint,
    published smallint,
    iconid integer,
    groupnameid integer,
    dataid integer
);


--
-- TOC entry 169 (class 1259 OID 16960)
-- Dependencies: 2044 2045 6
-- Name: invmetagroups; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invmetagroups (
    metagroupid smallint NOT NULL,
    metagroupname character varying(100) DEFAULT NULL::character varying,
    description character varying(1000) DEFAULT NULL::character varying,
    iconid smallint,
    metagroupnameid integer,
    descriptionid integer,
    dataid integer
);


--
-- TOC entry 170 (class 1259 OID 16968)
-- Dependencies: 6
-- Name: invmetatypes; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invmetatypes (
    typeid integer NOT NULL,
    parenttypeid integer,
    metagroupid smallint
);


--
-- TOC entry 171 (class 1259 OID 16971)
-- Dependencies: 2046 2047 6
-- Name: invtypes; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invtypes (
    typeid integer NOT NULL,
    groupid integer,
    typename character varying(100) DEFAULT NULL::character varying,
    description character varying(3000) DEFAULT NULL::character varying,
    graphicid smallint,
    radius double precision,
    mass double precision,
    volume double precision,
    capacity double precision,
    portionsize integer,
    raceid smallint,
    baseprice double precision,
    published smallint,
    marketgroupid integer,
    chanceofduplicating double precision,
    soundid integer,
    dataid integer,
    copytypeid integer,
    iconid integer,
    typenameid integer,
    descriptionid integer
);


--
-- TOC entry 2052 (class 2606 OID 16980)
-- Dependencies: 163 163
-- Name: cacheexpressions_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmcacheexpressions
    ADD CONSTRAINT cacheexpressions_pkey PRIMARY KEY (expressionid);


--
-- TOC entry 2050 (class 2606 OID 16982)
-- Dependencies: 162 162
-- Name: dgmattribs_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmattribs
    ADD CONSTRAINT dgmattribs_pkey PRIMARY KEY (attributeid);


--
-- TOC entry 2059 (class 2606 OID 16984)
-- Dependencies: 164 164
-- Name: dgmeffects_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_pkey PRIMARY KEY (effectid);


--
-- TOC entry 2066 (class 2606 OID 16986)
-- Dependencies: 165 165 165
-- Name: dgmtypeattribs_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmtypeattribs
    ADD CONSTRAINT dgmtypeattribs_pkey PRIMARY KEY (typeid, attributeid);


--
-- TOC entry 2070 (class 2606 OID 16988)
-- Dependencies: 166 166 166
-- Name: dgmtypeeffects_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmtypeeffects
    ADD CONSTRAINT dgmtypeeffects_pkey PRIMARY KEY (typeid, effectid);


--
-- TOC entry 2073 (class 2606 OID 16990)
-- Dependencies: 167 167
-- Name: invcategories_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invcategories
    ADD CONSTRAINT invcategories_pkey PRIMARY KEY (categoryid);


--
-- TOC entry 2076 (class 2606 OID 16992)
-- Dependencies: 168 168
-- Name: invgroups_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invgroups
    ADD CONSTRAINT invgroups_pkey PRIMARY KEY (groupid);


--
-- TOC entry 2078 (class 2606 OID 16994)
-- Dependencies: 169 169
-- Name: invmetagroups_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invmetagroups
    ADD CONSTRAINT invmetagroups_pkey PRIMARY KEY (metagroupid);


--
-- TOC entry 2082 (class 2606 OID 16996)
-- Dependencies: 170 170
-- Name: invmetatypes_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invmetatypes
    ADD CONSTRAINT invmetatypes_pkey PRIMARY KEY (typeid);


--
-- TOC entry 2086 (class 2606 OID 16998)
-- Dependencies: 171 171
-- Name: invtypes_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invtypes
    ADD CONSTRAINT invtypes_pkey PRIMARY KEY (typeid);


--
-- TOC entry 2048 (class 1259 OID 16999)
-- Dependencies: 162
-- Name: dgmattribs_attributename_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmattribs_attributename_idx ON dgmattribs USING btree (attributename);


--
-- TOC entry 2053 (class 1259 OID 17000)
-- Dependencies: 164
-- Name: dgmeffects_dischargeattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_dischargeattributeid_idx ON dgmeffects USING btree (dischargeattributeid);


--
-- TOC entry 2054 (class 1259 OID 17001)
-- Dependencies: 164
-- Name: dgmeffects_durationattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_durationattributeid_idx ON dgmeffects USING btree (durationattributeid);


--
-- TOC entry 2055 (class 1259 OID 17002)
-- Dependencies: 164
-- Name: dgmeffects_effectcategory_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_effectcategory_idx ON dgmeffects USING btree (effectcategory);


--
-- TOC entry 2056 (class 1259 OID 17003)
-- Dependencies: 164
-- Name: dgmeffects_effectname_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_effectname_idx ON dgmeffects USING btree (effectname);


--
-- TOC entry 2057 (class 1259 OID 17004)
-- Dependencies: 164
-- Name: dgmeffects_falloffattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_falloffattributeid_idx ON dgmeffects USING btree (falloffattributeid);


--
-- TOC entry 2060 (class 1259 OID 17005)
-- Dependencies: 164
-- Name: dgmeffects_postexpression_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_postexpression_idx ON dgmeffects USING btree (postexpression);


--
-- TOC entry 2061 (class 1259 OID 17006)
-- Dependencies: 164
-- Name: dgmeffects_preexpression_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_preexpression_idx ON dgmeffects USING btree (preexpression);


--
-- TOC entry 2062 (class 1259 OID 17007)
-- Dependencies: 164
-- Name: dgmeffects_rangeattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_rangeattributeid_idx ON dgmeffects USING btree (rangeattributeid);


--
-- TOC entry 2063 (class 1259 OID 17008)
-- Dependencies: 164
-- Name: dgmeffects_trackingspeedattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_trackingspeedattributeid_idx ON dgmeffects USING btree (trackingspeedattributeid);


--
-- TOC entry 2064 (class 1259 OID 17009)
-- Dependencies: 165
-- Name: dgmtypeattribs_attributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmtypeattribs_attributeid_idx ON dgmtypeattribs USING btree (attributeid);


--
-- TOC entry 2067 (class 1259 OID 17010)
-- Dependencies: 165
-- Name: dgmtypeattribs_typeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmtypeattribs_typeid_idx ON dgmtypeattribs USING btree (typeid);


--
-- TOC entry 2068 (class 1259 OID 17011)
-- Dependencies: 166
-- Name: dgmtypeeffects_effectid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmtypeeffects_effectid_idx ON dgmtypeeffects USING btree (effectid);


--
-- TOC entry 2071 (class 1259 OID 17012)
-- Dependencies: 166
-- Name: dgmtypeeffects_typeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmtypeeffects_typeid_idx ON dgmtypeeffects USING btree (typeid);


--
-- TOC entry 2074 (class 1259 OID 17013)
-- Dependencies: 168
-- Name: invgroups_categoryid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invgroups_categoryid_idx ON invgroups USING btree (categoryid);


--
-- TOC entry 2079 (class 1259 OID 17014)
-- Dependencies: 170
-- Name: invmetatypes_metagroupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmetatypes_metagroupid_idx ON invmetatypes USING btree (metagroupid);


--
-- TOC entry 2080 (class 1259 OID 17015)
-- Dependencies: 170
-- Name: invmetatypes_parenttypeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmetatypes_parenttypeid_idx ON invmetatypes USING btree (parenttypeid);


--
-- TOC entry 2083 (class 1259 OID 17016)
-- Dependencies: 170
-- Name: invmetatypes_typeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmetatypes_typeid_idx ON invmetatypes USING btree (typeid);


--
-- TOC entry 2084 (class 1259 OID 17017)
-- Dependencies: 171
-- Name: invtypes_groupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invtypes_groupid_idx ON invtypes USING btree (groupid);


--
-- TOC entry 2087 (class 1259 OID 17018)
-- Dependencies: 171
-- Name: invtypes_published_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invtypes_published_idx ON invtypes USING btree (published);


--
-- TOC entry 2088 (class 1259 OID 17019)
-- Dependencies: 171
-- Name: invtypes_typename_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invtypes_typename_idx ON invtypes USING btree (typename);


--
-- TOC entry 2089 (class 2606 OID 17020)
-- Dependencies: 164 162 2049
-- Name: dgmeffects_dischargeattributeid; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_dischargeattributeid FOREIGN KEY (dischargeattributeid) REFERENCES dgmattribs(attributeid);


--
-- TOC entry 2090 (class 2606 OID 17025)
-- Dependencies: 164 162 2049
-- Name: dgmeffects_durationattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_durationattributeid_fkey FOREIGN KEY (durationattributeid) REFERENCES dgmattribs(attributeid);


--
-- TOC entry 2091 (class 2606 OID 17030)
-- Dependencies: 164 162 2049
-- Name: dgmeffects_falloffattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_falloffattributeid_fkey FOREIGN KEY (falloffattributeid) REFERENCES dgmattribs(attributeid);


--
-- TOC entry 2092 (class 2606 OID 17035)
-- Dependencies: 162 164 2049
-- Name: dgmeffects_rangeattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_rangeattributeid_fkey FOREIGN KEY (rangeattributeid) REFERENCES dgmattribs(attributeid);


--
-- TOC entry 2093 (class 2606 OID 17040)
-- Dependencies: 164 162 2049
-- Name: dgmeffects_trackingspeedattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_trackingspeedattributeid_fkey FOREIGN KEY (trackingspeedattributeid) REFERENCES dgmattribs(attributeid);


--
-- TOC entry 2094 (class 2606 OID 17045)
-- Dependencies: 165 162 2049
-- Name: dgmtypeattribs_attributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmtypeattribs
    ADD CONSTRAINT dgmtypeattribs_attributeid_fkey FOREIGN KEY (attributeid) REFERENCES dgmattribs(attributeid);


--
-- TOC entry 2095 (class 2606 OID 17050)
-- Dependencies: 166 164 2058
-- Name: dgmtypeeffects_effectid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmtypeeffects
    ADD CONSTRAINT dgmtypeeffects_effectid_fkey FOREIGN KEY (effectid) REFERENCES dgmeffects(effectid);


--
-- TOC entry 2096 (class 2606 OID 17055)
-- Dependencies: 166 171 2085
-- Name: dgmtypeeffects_typeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmtypeeffects
    ADD CONSTRAINT dgmtypeeffects_typeid_fkey FOREIGN KEY (typeid) REFERENCES invtypes(typeid);


--
-- TOC entry 2097 (class 2606 OID 17060)
-- Dependencies: 167 2072 168
-- Name: invgroups_categoryid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invgroups
    ADD CONSTRAINT invgroups_categoryid_fkey FOREIGN KEY (categoryid) REFERENCES invcategories(categoryid);


--
-- TOC entry 2098 (class 2606 OID 17065)
-- Dependencies: 169 2077 170
-- Name: invmetatypes_metagroupid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invmetatypes
    ADD CONSTRAINT invmetatypes_metagroupid_fkey FOREIGN KEY (metagroupid) REFERENCES invmetagroups(metagroupid);


--
-- TOC entry 2099 (class 2606 OID 17070)
-- Dependencies: 170 171 2085
-- Name: invmetatypes_parenttypeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invmetatypes
    ADD CONSTRAINT invmetatypes_parenttypeid_fkey FOREIGN KEY (parenttypeid) REFERENCES invtypes(typeid);


--
-- TOC entry 2100 (class 2606 OID 17075)
-- Dependencies: 170 171 2085
-- Name: invmetatypes_typeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invmetatypes
    ADD CONSTRAINT invmetatypes_typeid_fkey FOREIGN KEY (typeid) REFERENCES invtypes(typeid);


--
-- TOC entry 2101 (class 2606 OID 17080)
-- Dependencies: 171 2075 168
-- Name: invtypes_groupid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invtypes
    ADD CONSTRAINT invtypes_groupid_fkey FOREIGN KEY (groupid) REFERENCES invgroups(groupid);


-- Completed on 2012-07-15 11:22:10 CEST

--
-- PostgreSQL database dump complete
--


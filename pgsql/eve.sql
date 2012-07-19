--
-- PostgreSQL database dump
--

-- Dumped from database version 9.1.4
-- Dumped by pg_dump version 9.1.4
-- Started on 2012-07-16 19:26:44 CEST

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- TOC entry 6 (class 2615 OID 26157)
-- Name: eve; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA eve;


SET search_path = eve, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- TOC entry 161 (class 1259 OID 26158)
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
-- TOC entry 162 (class 1259 OID 26164)
-- Dependencies: 6
-- Name: dgmcacheexpressions; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmcacheexpressions (
    expressionid integer NOT NULL,
    exp text NOT NULL
);


--
-- TOC entry 163 (class 1259 OID 26170)
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
-- TOC entry 164 (class 1259 OID 26176)
-- Dependencies: 6
-- Name: dgmtypeattribs; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmtypeattribs (
    typeid integer NOT NULL,
    attributeid smallint NOT NULL,
    value double precision NOT NULL
);


--
-- TOC entry 165 (class 1259 OID 26179)
-- Dependencies: 6
-- Name: dgmtypeeffects; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmtypeeffects (
    typeid integer NOT NULL,
    effectid smallint NOT NULL,
    isdefault smallint
);


--
-- TOC entry 166 (class 1259 OID 26182)
-- Dependencies: 1897 1898 6
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
-- TOC entry 167 (class 1259 OID 26190)
-- Dependencies: 1899 1900 6
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
-- TOC entry 171 (class 1259 OID 26347)
-- Dependencies: 6
-- Name: invmarketgroups; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invmarketgroups (
    parentgroupid integer,
    marketgroupid integer NOT NULL,
    marketgroupname character varying(100) NOT NULL,
    description character varying(3000) NOT NULL,
    graphicid integer,
    hastypes integer NOT NULL,
    iconid integer,
    dataid integer NOT NULL,
    marketgroupnameid integer NOT NULL,
    descriptionid integer NOT NULL
);


--
-- TOC entry 168 (class 1259 OID 26198)
-- Dependencies: 1901 1902 6
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
-- TOC entry 169 (class 1259 OID 26206)
-- Dependencies: 6
-- Name: invmetatypes; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invmetatypes (
    typeid integer NOT NULL,
    parenttypeid integer,
    metagroupid smallint
);


--
-- TOC entry 170 (class 1259 OID 26209)
-- Dependencies: 1903 1904 6
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
-- TOC entry 1909 (class 2606 OID 26218)
-- Dependencies: 162 162
-- Name: cacheexpressions_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmcacheexpressions
    ADD CONSTRAINT cacheexpressions_pkey PRIMARY KEY (expressionid);


--
-- TOC entry 1907 (class 2606 OID 26220)
-- Dependencies: 161 161
-- Name: dgmattribs_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmattribs
    ADD CONSTRAINT dgmattribs_pkey PRIMARY KEY (attributeid);


--
-- TOC entry 1916 (class 2606 OID 26222)
-- Dependencies: 163 163
-- Name: dgmeffects_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_pkey PRIMARY KEY (effectid);


--
-- TOC entry 1923 (class 2606 OID 26224)
-- Dependencies: 164 164 164
-- Name: dgmtypeattribs_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmtypeattribs
    ADD CONSTRAINT dgmtypeattribs_pkey PRIMARY KEY (typeid, attributeid);


--
-- TOC entry 1927 (class 2606 OID 26226)
-- Dependencies: 165 165 165
-- Name: dgmtypeeffects_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmtypeeffects
    ADD CONSTRAINT dgmtypeeffects_pkey PRIMARY KEY (typeid, effectid);


--
-- TOC entry 1930 (class 2606 OID 26228)
-- Dependencies: 166 166
-- Name: invcategories_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invcategories
    ADD CONSTRAINT invcategories_pkey PRIMARY KEY (categoryid);


--
-- TOC entry 1933 (class 2606 OID 26230)
-- Dependencies: 167 167
-- Name: invgroups_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invgroups
    ADD CONSTRAINT invgroups_pkey PRIMARY KEY (groupid);


--
-- TOC entry 1949 (class 2606 OID 26354)
-- Dependencies: 171 171
-- Name: invmarketgroups_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invmarketgroups
    ADD CONSTRAINT invmarketgroups_pkey PRIMARY KEY (marketgroupid);


--
-- TOC entry 1935 (class 2606 OID 26232)
-- Dependencies: 168 168
-- Name: invmetagroups_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invmetagroups
    ADD CONSTRAINT invmetagroups_pkey PRIMARY KEY (metagroupid);


--
-- TOC entry 1939 (class 2606 OID 26234)
-- Dependencies: 169 169
-- Name: invmetatypes_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invmetatypes
    ADD CONSTRAINT invmetatypes_pkey PRIMARY KEY (typeid);


--
-- TOC entry 1944 (class 2606 OID 26236)
-- Dependencies: 170 170
-- Name: invtypes_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invtypes
    ADD CONSTRAINT invtypes_pkey PRIMARY KEY (typeid);


--
-- TOC entry 1905 (class 1259 OID 26237)
-- Dependencies: 161
-- Name: dgmattribs_attributename_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmattribs_attributename_idx ON dgmattribs USING btree (attributename);


--
-- TOC entry 1910 (class 1259 OID 26238)
-- Dependencies: 163
-- Name: dgmeffects_dischargeattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_dischargeattributeid_idx ON dgmeffects USING btree (dischargeattributeid);


--
-- TOC entry 1911 (class 1259 OID 26239)
-- Dependencies: 163
-- Name: dgmeffects_durationattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_durationattributeid_idx ON dgmeffects USING btree (durationattributeid);


--
-- TOC entry 1912 (class 1259 OID 26240)
-- Dependencies: 163
-- Name: dgmeffects_effectcategory_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_effectcategory_idx ON dgmeffects USING btree (effectcategory);


--
-- TOC entry 1913 (class 1259 OID 26241)
-- Dependencies: 163
-- Name: dgmeffects_effectname_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_effectname_idx ON dgmeffects USING btree (effectname);


--
-- TOC entry 1914 (class 1259 OID 26242)
-- Dependencies: 163
-- Name: dgmeffects_falloffattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_falloffattributeid_idx ON dgmeffects USING btree (falloffattributeid);


--
-- TOC entry 1917 (class 1259 OID 26243)
-- Dependencies: 163
-- Name: dgmeffects_postexpression_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_postexpression_idx ON dgmeffects USING btree (postexpression);


--
-- TOC entry 1918 (class 1259 OID 26244)
-- Dependencies: 163
-- Name: dgmeffects_preexpression_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_preexpression_idx ON dgmeffects USING btree (preexpression);


--
-- TOC entry 1919 (class 1259 OID 26245)
-- Dependencies: 163
-- Name: dgmeffects_rangeattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_rangeattributeid_idx ON dgmeffects USING btree (rangeattributeid);


--
-- TOC entry 1920 (class 1259 OID 26246)
-- Dependencies: 163
-- Name: dgmeffects_trackingspeedattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_trackingspeedattributeid_idx ON dgmeffects USING btree (trackingspeedattributeid);


--
-- TOC entry 1921 (class 1259 OID 26247)
-- Dependencies: 164
-- Name: dgmtypeattribs_attributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmtypeattribs_attributeid_idx ON dgmtypeattribs USING btree (attributeid);


--
-- TOC entry 1924 (class 1259 OID 26248)
-- Dependencies: 164
-- Name: dgmtypeattribs_typeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmtypeattribs_typeid_idx ON dgmtypeattribs USING btree (typeid);


--
-- TOC entry 1925 (class 1259 OID 26249)
-- Dependencies: 165
-- Name: dgmtypeeffects_effectid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmtypeeffects_effectid_idx ON dgmtypeeffects USING btree (effectid);


--
-- TOC entry 1928 (class 1259 OID 26250)
-- Dependencies: 165
-- Name: dgmtypeeffects_typeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmtypeeffects_typeid_idx ON dgmtypeeffects USING btree (typeid);


--
-- TOC entry 1931 (class 1259 OID 26251)
-- Dependencies: 167
-- Name: invgroups_categoryid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invgroups_categoryid_idx ON invgroups USING btree (categoryid);


--
-- TOC entry 1947 (class 1259 OID 26360)
-- Dependencies: 171
-- Name: invmarketgroups_parentgroupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmarketgroups_parentgroupid_idx ON invmarketgroups USING btree (parentgroupid);


--
-- TOC entry 1936 (class 1259 OID 26252)
-- Dependencies: 169
-- Name: invmetatypes_metagroupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmetatypes_metagroupid_idx ON invmetatypes USING btree (metagroupid);


--
-- TOC entry 1937 (class 1259 OID 26253)
-- Dependencies: 169
-- Name: invmetatypes_parenttypeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmetatypes_parenttypeid_idx ON invmetatypes USING btree (parenttypeid);


--
-- TOC entry 1940 (class 1259 OID 26254)
-- Dependencies: 169
-- Name: invmetatypes_typeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmetatypes_typeid_idx ON invmetatypes USING btree (typeid);


--
-- TOC entry 1941 (class 1259 OID 26255)
-- Dependencies: 170
-- Name: invtypes_groupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invtypes_groupid_idx ON invtypes USING btree (groupid);


--
-- TOC entry 1942 (class 1259 OID 26361)
-- Dependencies: 170
-- Name: invtypes_marketgroupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invtypes_marketgroupid_idx ON invtypes USING btree (marketgroupid);


--
-- TOC entry 1945 (class 1259 OID 26256)
-- Dependencies: 170
-- Name: invtypes_published_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invtypes_published_idx ON invtypes USING btree (published);


--
-- TOC entry 1946 (class 1259 OID 26257)
-- Dependencies: 170
-- Name: invtypes_typename_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invtypes_typename_idx ON invtypes USING btree (typename);


--
-- TOC entry 1950 (class 2606 OID 26258)
-- Dependencies: 1906 161 163
-- Name: dgmeffects_dischargeattributeid; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_dischargeattributeid FOREIGN KEY (dischargeattributeid) REFERENCES dgmattribs(attributeid);


--
-- TOC entry 1951 (class 2606 OID 26263)
-- Dependencies: 163 161 1906
-- Name: dgmeffects_durationattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_durationattributeid_fkey FOREIGN KEY (durationattributeid) REFERENCES dgmattribs(attributeid);


--
-- TOC entry 1952 (class 2606 OID 26268)
-- Dependencies: 163 1906 161
-- Name: dgmeffects_falloffattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_falloffattributeid_fkey FOREIGN KEY (falloffattributeid) REFERENCES dgmattribs(attributeid);


--
-- TOC entry 1953 (class 2606 OID 26273)
-- Dependencies: 1906 161 163
-- Name: dgmeffects_rangeattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_rangeattributeid_fkey FOREIGN KEY (rangeattributeid) REFERENCES dgmattribs(attributeid);


--
-- TOC entry 1954 (class 2606 OID 26278)
-- Dependencies: 163 1906 161
-- Name: dgmeffects_trackingspeedattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_trackingspeedattributeid_fkey FOREIGN KEY (trackingspeedattributeid) REFERENCES dgmattribs(attributeid);


--
-- TOC entry 1955 (class 2606 OID 26283)
-- Dependencies: 1906 164 161
-- Name: dgmtypeattribs_attributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmtypeattribs
    ADD CONSTRAINT dgmtypeattribs_attributeid_fkey FOREIGN KEY (attributeid) REFERENCES dgmattribs(attributeid);


--
-- TOC entry 1956 (class 2606 OID 26288)
-- Dependencies: 165 163 1915
-- Name: dgmtypeeffects_effectid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmtypeeffects
    ADD CONSTRAINT dgmtypeeffects_effectid_fkey FOREIGN KEY (effectid) REFERENCES dgmeffects(effectid);


--
-- TOC entry 1957 (class 2606 OID 26293)
-- Dependencies: 165 170 1943
-- Name: dgmtypeeffects_typeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmtypeeffects
    ADD CONSTRAINT dgmtypeeffects_typeid_fkey FOREIGN KEY (typeid) REFERENCES invtypes(typeid);


--
-- TOC entry 1958 (class 2606 OID 26298)
-- Dependencies: 1929 166 167
-- Name: invgroups_categoryid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invgroups
    ADD CONSTRAINT invgroups_categoryid_fkey FOREIGN KEY (categoryid) REFERENCES invcategories(categoryid);


--
-- TOC entry 1964 (class 2606 OID 26355)
-- Dependencies: 171 171 1948
-- Name: invmarketgroups_parentgroupid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invmarketgroups
    ADD CONSTRAINT invmarketgroups_parentgroupid_fkey FOREIGN KEY (parentgroupid) REFERENCES invmarketgroups(marketgroupid) DEFERRABLE INITIALLY DEFERRED;


--
-- TOC entry 1959 (class 2606 OID 26303)
-- Dependencies: 168 1934 169
-- Name: invmetatypes_metagroupid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invmetatypes
    ADD CONSTRAINT invmetatypes_metagroupid_fkey FOREIGN KEY (metagroupid) REFERENCES invmetagroups(metagroupid);


--
-- TOC entry 1960 (class 2606 OID 26308)
-- Dependencies: 1943 170 169
-- Name: invmetatypes_parenttypeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invmetatypes
    ADD CONSTRAINT invmetatypes_parenttypeid_fkey FOREIGN KEY (parenttypeid) REFERENCES invtypes(typeid);


--
-- TOC entry 1961 (class 2606 OID 26313)
-- Dependencies: 1943 170 169
-- Name: invmetatypes_typeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invmetatypes
    ADD CONSTRAINT invmetatypes_typeid_fkey FOREIGN KEY (typeid) REFERENCES invtypes(typeid);


--
-- TOC entry 1962 (class 2606 OID 26318)
-- Dependencies: 167 1932 170
-- Name: invtypes_groupid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invtypes
    ADD CONSTRAINT invtypes_groupid_fkey FOREIGN KEY (groupid) REFERENCES invgroups(groupid);


--
-- TOC entry 1963 (class 2606 OID 26362)
-- Dependencies: 1948 171 170
-- Name: invtypes_marketgroupid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invtypes
    ADD CONSTRAINT invtypes_marketgroupid_fkey FOREIGN KEY (marketgroupid) REFERENCES invmarketgroups(marketgroupid);


-- Completed on 2012-07-16 19:26:44 CEST

--
-- PostgreSQL database dump complete
--


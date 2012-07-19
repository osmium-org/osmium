--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: eve; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA eve;


SET search_path = eve, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
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
-- Name: dgmcacheexpressions; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmcacheexpressions (
    expressionid integer NOT NULL,
    exp text NOT NULL
);


--
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
-- Name: dgmtypeattribs; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmtypeattribs (
    typeid integer NOT NULL,
    attributeid smallint NOT NULL,
    value double precision NOT NULL
);


--
-- Name: dgmtypeeffects; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmtypeeffects (
    typeid integer NOT NULL,
    effectid smallint NOT NULL,
    isdefault smallint
);


--
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
-- Name: invmetatypes; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invmetatypes (
    typeid integer NOT NULL,
    parenttypeid integer,
    metagroupid smallint
);


--
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
-- Name: cacheexpressions_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmcacheexpressions
    ADD CONSTRAINT cacheexpressions_pkey PRIMARY KEY (expressionid);


--
-- Name: dgmattribs_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmattribs
    ADD CONSTRAINT dgmattribs_pkey PRIMARY KEY (attributeid);


--
-- Name: dgmeffects_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_pkey PRIMARY KEY (effectid);


--
-- Name: dgmtypeattribs_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmtypeattribs
    ADD CONSTRAINT dgmtypeattribs_pkey PRIMARY KEY (typeid, attributeid);


--
-- Name: dgmtypeeffects_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmtypeeffects
    ADD CONSTRAINT dgmtypeeffects_pkey PRIMARY KEY (typeid, effectid);


--
-- Name: invcategories_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invcategories
    ADD CONSTRAINT invcategories_pkey PRIMARY KEY (categoryid);


--
-- Name: invgroups_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invgroups
    ADD CONSTRAINT invgroups_pkey PRIMARY KEY (groupid);


--
-- Name: invmarketgroups_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invmarketgroups
    ADD CONSTRAINT invmarketgroups_pkey PRIMARY KEY (marketgroupid);


--
-- Name: invmetagroups_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invmetagroups
    ADD CONSTRAINT invmetagroups_pkey PRIMARY KEY (metagroupid);


--
-- Name: invmetatypes_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invmetatypes
    ADD CONSTRAINT invmetatypes_pkey PRIMARY KEY (typeid);


--
-- Name: invtypes_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY invtypes
    ADD CONSTRAINT invtypes_pkey PRIMARY KEY (typeid);


--
-- Name: dgmattribs_attributename_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmattribs_attributename_idx ON dgmattribs USING btree (attributename);


--
-- Name: dgmeffects_dischargeattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_dischargeattributeid_idx ON dgmeffects USING btree (dischargeattributeid);


--
-- Name: dgmeffects_durationattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_durationattributeid_idx ON dgmeffects USING btree (durationattributeid);


--
-- Name: dgmeffects_effectcategory_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_effectcategory_idx ON dgmeffects USING btree (effectcategory);


--
-- Name: dgmeffects_effectname_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_effectname_idx ON dgmeffects USING btree (effectname);


--
-- Name: dgmeffects_falloffattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_falloffattributeid_idx ON dgmeffects USING btree (falloffattributeid);


--
-- Name: dgmeffects_postexpression_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_postexpression_idx ON dgmeffects USING btree (postexpression);


--
-- Name: dgmeffects_preexpression_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_preexpression_idx ON dgmeffects USING btree (preexpression);


--
-- Name: dgmeffects_rangeattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_rangeattributeid_idx ON dgmeffects USING btree (rangeattributeid);


--
-- Name: dgmeffects_trackingspeedattributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_trackingspeedattributeid_idx ON dgmeffects USING btree (trackingspeedattributeid);


--
-- Name: dgmtypeattribs_attributeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmtypeattribs_attributeid_idx ON dgmtypeattribs USING btree (attributeid);


--
-- Name: dgmtypeattribs_typeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmtypeattribs_typeid_idx ON dgmtypeattribs USING btree (typeid);


--
-- Name: dgmtypeeffects_effectid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmtypeeffects_effectid_idx ON dgmtypeeffects USING btree (effectid);


--
-- Name: dgmtypeeffects_typeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmtypeeffects_typeid_idx ON dgmtypeeffects USING btree (typeid);


--
-- Name: invgroups_categoryid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invgroups_categoryid_idx ON invgroups USING btree (categoryid);


--
-- Name: invmarketgroups_parentgroupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmarketgroups_parentgroupid_idx ON invmarketgroups USING btree (parentgroupid);


--
-- Name: invmetatypes_metagroupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmetatypes_metagroupid_idx ON invmetatypes USING btree (metagroupid);


--
-- Name: invmetatypes_parenttypeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmetatypes_parenttypeid_idx ON invmetatypes USING btree (parenttypeid);


--
-- Name: invmetatypes_typeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmetatypes_typeid_idx ON invmetatypes USING btree (typeid);


--
-- Name: invtypes_groupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invtypes_groupid_idx ON invtypes USING btree (groupid);


--
-- Name: invtypes_marketgroupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invtypes_marketgroupid_idx ON invtypes USING btree (marketgroupid);


--
-- Name: invtypes_published_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invtypes_published_idx ON invtypes USING btree (published);


--
-- Name: invtypes_typename_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invtypes_typename_idx ON invtypes USING btree (typename);


--
-- Name: dgmeffects_dischargeattributeid; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_dischargeattributeid FOREIGN KEY (dischargeattributeid) REFERENCES dgmattribs(attributeid);


--
-- Name: dgmeffects_durationattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_durationattributeid_fkey FOREIGN KEY (durationattributeid) REFERENCES dgmattribs(attributeid);


--
-- Name: dgmeffects_falloffattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_falloffattributeid_fkey FOREIGN KEY (falloffattributeid) REFERENCES dgmattribs(attributeid);


--
-- Name: dgmeffects_rangeattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_rangeattributeid_fkey FOREIGN KEY (rangeattributeid) REFERENCES dgmattribs(attributeid);


--
-- Name: dgmeffects_trackingspeedattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_trackingspeedattributeid_fkey FOREIGN KEY (trackingspeedattributeid) REFERENCES dgmattribs(attributeid);


--
-- Name: dgmtypeattribs_attributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmtypeattribs
    ADD CONSTRAINT dgmtypeattribs_attributeid_fkey FOREIGN KEY (attributeid) REFERENCES dgmattribs(attributeid);


--
-- Name: dgmtypeeffects_effectid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmtypeeffects
    ADD CONSTRAINT dgmtypeeffects_effectid_fkey FOREIGN KEY (effectid) REFERENCES dgmeffects(effectid);


--
-- Name: dgmtypeeffects_typeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmtypeeffects
    ADD CONSTRAINT dgmtypeeffects_typeid_fkey FOREIGN KEY (typeid) REFERENCES invtypes(typeid);


--
-- Name: invgroups_categoryid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invgroups
    ADD CONSTRAINT invgroups_categoryid_fkey FOREIGN KEY (categoryid) REFERENCES invcategories(categoryid);


--
-- Name: invmarketgroups_parentgroupid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invmarketgroups
    ADD CONSTRAINT invmarketgroups_parentgroupid_fkey FOREIGN KEY (parentgroupid) REFERENCES invmarketgroups(marketgroupid) DEFERRABLE INITIALLY DEFERRED;


--
-- Name: invmetatypes_metagroupid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invmetatypes
    ADD CONSTRAINT invmetatypes_metagroupid_fkey FOREIGN KEY (metagroupid) REFERENCES invmetagroups(metagroupid);


--
-- Name: invmetatypes_parenttypeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invmetatypes
    ADD CONSTRAINT invmetatypes_parenttypeid_fkey FOREIGN KEY (parenttypeid) REFERENCES invtypes(typeid);


--
-- Name: invmetatypes_typeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invmetatypes
    ADD CONSTRAINT invmetatypes_typeid_fkey FOREIGN KEY (typeid) REFERENCES invtypes(typeid);


--
-- Name: invtypes_groupid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invtypes
    ADD CONSTRAINT invtypes_groupid_fkey FOREIGN KEY (groupid) REFERENCES invgroups(groupid);


--
-- Name: invtypes_marketgroupid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY invtypes
    ADD CONSTRAINT invtypes_marketgroupid_fkey FOREIGN KEY (marketgroupid) REFERENCES invmarketgroups(marketgroupid);


--
-- PostgreSQL database dump complete
--


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
-- Name: averagemarketprices; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE averagemarketprices (
    typeid integer NOT NULL,
    averageprice numeric(15,2) NOT NULL
);


--
-- Name: dgmattribs; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmattribs (
    attributeid smallint NOT NULL,
    attributename character varying(100) NOT NULL,
    displayname character varying(100),
    defaultvalue real NOT NULL,
    stackable boolean NOT NULL,
    highisgood boolean NOT NULL,
    unitid integer,
    categoryid integer,
    published boolean NOT NULL
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
    effectid smallint NOT NULL,
    effectname character varying(300) NOT NULL,
    effectcategory smallint NOT NULL,
    preexpression integer NOT NULL,
    postexpression integer NOT NULL,
    durationattributeid smallint,
    trackingspeedattributeid smallint,
    dischargeattributeid smallint,
    rangeattributeid smallint,
    falloffattributeid smallint
);


--
-- Name: dgmexpressions; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmexpressions (
    expressionid integer NOT NULL,
    operandid smallint NOT NULL,
    arg1 integer,
    arg2 integer,
    expressionname text,
    expressionvalue text,
    expressiontypeid integer,
    expressiongroupid integer,
    expressionattributeid smallint
);


--
-- Name: dgmoperands; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmoperands (
    operandid smallint NOT NULL,
    operandkey character varying(100) NOT NULL
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
    isdefault boolean NOT NULL
);


--
-- Name: dgmunits; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmunits (
    unitid integer NOT NULL,
    displayname character varying(100) NOT NULL
);


--
-- Name: invcategories; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invcategories (
    categoryid integer NOT NULL,
    categoryname character varying(100) NOT NULL
);


--
-- Name: invgroups; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invgroups (
    groupid integer NOT NULL,
    categoryid integer NOT NULL,
    groupname character varying(100) NOT NULL,
    published boolean NOT NULL
);


--
-- Name: invmarketgroups; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invmarketgroups (
    marketgroupid integer NOT NULL,
    parentgroupid integer,
    marketgroupname character varying(100) NOT NULL
);


--
-- Name: invmetagroups; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invmetagroups (
    metagroupid smallint NOT NULL,
    metagroupname character varying(100) NOT NULL
);


--
-- Name: invmetatypes; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invmetatypes (
    typeid integer NOT NULL,
    metagroupid smallint NOT NULL
);


--
-- Name: invtypes; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE invtypes (
    typeid integer NOT NULL,
    groupid integer NOT NULL,
    typename character varying(100) NOT NULL,
    mass double precision NOT NULL,
    volume double precision NOT NULL,
    capacity double precision NOT NULL,
    published boolean NOT NULL,
    marketgroupid integer
);


--
-- Name: averagemarketprices_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY averagemarketprices
    ADD CONSTRAINT averagemarketprices_pkey PRIMARY KEY (typeid);


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
-- Name: dgmexpressions_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmexpressions
    ADD CONSTRAINT dgmexpressions_pkey PRIMARY KEY (expressionid);


--
-- Name: dgmoperands_operandkey_uniq; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmoperands
    ADD CONSTRAINT dgmoperands_operandkey_uniq UNIQUE (operandkey);


--
-- Name: dgmoperands_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmoperands
    ADD CONSTRAINT dgmoperands_pkey PRIMARY KEY (operandid);


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
-- Name: dgmunits_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmunits
    ADD CONSTRAINT dgmunits_pkey PRIMARY KEY (unitid);


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
-- Name: dgmattribs_unitid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmattribs_unitid_idx ON dgmattribs USING btree (unitid);


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
-- Name: invgroups_published_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invgroups_published_idx ON invgroups USING btree (published);


--
-- Name: invmarketgroups_parentgroupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmarketgroups_parentgroupid_idx ON invmarketgroups USING btree (parentgroupid);


--
-- Name: invmetatypes_metagroupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmetatypes_metagroupid_idx ON invmetatypes USING btree (metagroupid);


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
-- Name: averagemarketprices_typeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY averagemarketprices
    ADD CONSTRAINT averagemarketprices_typeid_fkey FOREIGN KEY (typeid) REFERENCES invtypes(typeid);


--
-- Name: dgmattribs_unitid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmattribs
    ADD CONSTRAINT dgmattribs_unitid_fkey FOREIGN KEY (unitid) REFERENCES dgmunits(unitid);


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
-- Name: dgmexpressions_operandid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmexpressions
    ADD CONSTRAINT dgmexpressions_operandid_fkey FOREIGN KEY (operandid) REFERENCES dgmoperands(operandid);


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


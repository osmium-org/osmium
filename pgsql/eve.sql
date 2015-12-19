--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
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
-- Name: dgmeffects; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmeffects (
    effectid smallint NOT NULL,
    effectname character varying(300) NOT NULL,
    effectcategory smallint NOT NULL,
    fittingusagechanceattributeid smallint,
    durationattributeid smallint,
    npcactivationchanceattributeid smallint,
    npcusagechanceattributeid smallint,
    rangeattributeid smallint,
    dischargeattributeid smallint,
    falloffattributeid smallint,
    trackingspeedattributeid smallint,
    isoffensive boolean,
    isassistance boolean,
    iswarpsafe boolean,
    preexpression text,
    postexpression text
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
    effectid smallint NOT NULL
);


--
-- Name: dgmunits; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE dgmunits (
    unitid integer NOT NULL,
    displayname character varying(100) NOT NULL
);


--
-- Name: infoelements; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE infoelements (
    elementid integer NOT NULL,
    name text NOT NULL,
    description text NOT NULL
);


--
-- Name: infotypebonuses; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE infotypebonuses (
    typeid integer NOT NULL,
    sourcetypeid integer,
    sourceother integer,
    priority integer NOT NULL,
    name text NOT NULL,
    unitid integer,
    bonus double precision,
    CONSTRAINT infotypebonuses_bonus_has_unit CHECK (((bonus IS NULL) OR (unitid IS NOT NULL))),
    CONSTRAINT infotypebonuses_has_source CHECK ((((sourcetypeid IS NOT NULL) AND (sourceother IS NULL)) OR ((sourcetypeid IS NULL) AND (sourceother IS NOT NULL))))
);


--
-- Name: infotypeelements; Type: TABLE; Schema: eve; Owner: -; Tablespace: 
--

CREATE TABLE infotypeelements (
    typeid integer NOT NULL,
    elementid integer NOT NULL,
    priority integer NOT NULL
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
    metagroupid smallint NOT NULL,
    parenttypeid integer NOT NULL
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
    marketgroupid integer,
    description text NOT NULL
);


--
-- Name: averagemarketprices_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY averagemarketprices
    ADD CONSTRAINT averagemarketprices_pkey PRIMARY KEY (typeid);


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
-- Name: dgmunits_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY dgmunits
    ADD CONSTRAINT dgmunits_pkey PRIMARY KEY (unitid);


--
-- Name: infoelements_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY infoelements
    ADD CONSTRAINT infoelements_pkey PRIMARY KEY (elementid);


--
-- Name: infotypeelements_pkey; Type: CONSTRAINT; Schema: eve; Owner: -; Tablespace: 
--

ALTER TABLE ONLY infotypeelements
    ADD CONSTRAINT infotypeelements_pkey PRIMARY KEY (typeid, elementid);


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
-- Name: dgmeffects_effectcategory_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_effectcategory_idx ON dgmeffects USING btree (effectcategory);


--
-- Name: dgmeffects_effectname_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX dgmeffects_effectname_idx ON dgmeffects USING btree (effectname);


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
-- Name: infotypebonuses_typeid_sourcetypeid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX infotypebonuses_typeid_sourcetypeid_idx ON infotypebonuses USING btree (typeid, sourcetypeid);


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
-- Name: invmetatypes_parentgroupid_idx; Type: INDEX; Schema: eve; Owner: -; Tablespace: 
--

CREATE INDEX invmetatypes_parentgroupid_idx ON invmetatypes USING btree (parenttypeid);


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
-- Name: dgmeffects_dischargeattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_dischargeattributeid_fkey FOREIGN KEY (dischargeattributeid) REFERENCES dgmattribs(attributeid);


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
-- Name: dgmeffects_fittingusagechanceattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_fittingusagechanceattributeid_fkey FOREIGN KEY (fittingusagechanceattributeid) REFERENCES dgmattribs(attributeid);


--
-- Name: dgmeffects_npcactivationchanceattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_npcactivationchanceattributeid_fkey FOREIGN KEY (npcactivationchanceattributeid) REFERENCES dgmattribs(attributeid);


--
-- Name: dgmeffects_npcusagechanceattributeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY dgmeffects
    ADD CONSTRAINT dgmeffects_npcusagechanceattributeid_fkey FOREIGN KEY (npcusagechanceattributeid) REFERENCES dgmattribs(attributeid);


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
-- Name: infotypebonuses_sourcetypeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY infotypebonuses
    ADD CONSTRAINT infotypebonuses_sourcetypeid_fkey FOREIGN KEY (sourcetypeid) REFERENCES invtypes(typeid);


--
-- Name: infotypebonuses_typeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY infotypebonuses
    ADD CONSTRAINT infotypebonuses_typeid_fkey FOREIGN KEY (typeid) REFERENCES invtypes(typeid);


--
-- Name: infotypebonuses_unitid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY infotypebonuses
    ADD CONSTRAINT infotypebonuses_unitid_fkey FOREIGN KEY (unitid) REFERENCES dgmunits(unitid);


--
-- Name: infotypeelements_elementid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY infotypeelements
    ADD CONSTRAINT infotypeelements_elementid_fkey FOREIGN KEY (elementid) REFERENCES infoelements(elementid);


--
-- Name: infotypeelements_typeid_fkey; Type: FK CONSTRAINT; Schema: eve; Owner: -
--

ALTER TABLE ONLY infotypeelements
    ADD CONSTRAINT infotypeelements_typeid_fkey FOREIGN KEY (typeid) REFERENCES invtypes(typeid);


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


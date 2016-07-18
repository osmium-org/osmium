CREATE OR REPLACE VIEW siattributes AS 
SELECT dgmtypeattribs.typeid,
dgmtypeattribs.attributeid,
dgmattribs.attributename,
dgmattribs.displayname,
dgmtypeattribs.value,
dgmattribs.unitid,
dgmunits.displayname AS udisplayname,
dgmattribs.categoryid,
dgmattribs.published
FROM dgmtypeattribs
JOIN dgmattribs ON dgmtypeattribs.attributeid = dgmattribs.attributeid
LEFT JOIN dgmunits ON dgmattribs.unitid = dgmunits.unitid
UNION ALL
SELECT invtypes.typeid,
dgmattribs.attributeid,
dgmattribs.attributename,
dgmattribs.displayname,
invtypes.volume AS value,
dgmattribs.unitid,
dgmunits.displayname AS udisplayname,
dgmattribs.categoryid,
dgmattribs.published
FROM invtypes
JOIN dgmattribs ON dgmattribs.attributeid = 161
LEFT JOIN dgmunits ON dgmattribs.unitid = dgmunits.unitid
UNION ALL
SELECT invtypes.typeid,
dgmattribs.attributeid,
dgmattribs.attributename,
dgmattribs.displayname,
invtypes.capacity AS value,
dgmattribs.unitid,
dgmunits.displayname AS udisplayname,
dgmattribs.categoryid,
dgmattribs.published
FROM invtypes
JOIN dgmattribs ON dgmattribs.attributeid = 38
LEFT JOIN dgmunits ON dgmattribs.unitid = dgmunits.unitid
UNION ALL
SELECT invtypes.typeid,
dgmattribs.attributeid,
dgmattribs.attributename,
dgmattribs.displayname,
invtypes.mass AS value,
dgmattribs.unitid,
dgmunits.displayname AS udisplayname,
dgmattribs.categoryid,
dgmattribs.published
FROM invtypes
JOIN dgmattribs ON dgmattribs.attributeid = 4
LEFT JOIN dgmunits ON dgmattribs.unitid = dgmunits.unitid
UNION ALL
SELECT typeid,
-1::smallint as attributeid,
'priceEstimate'::character varying(200) attributename,
'Price Estimate'::character varying(200) as displayname,
averageprice AS value,
133 as unitid,
dgmunits.displayname AS udisplayname,
4 as categoryid,
true as published
FROM averagemarketprices
LEFT JOIN dgmunits ON dgmunits.unitid = 133;

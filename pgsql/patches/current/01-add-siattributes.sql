CREATE OR REPLACE VIEW siattributes AS
        (        (         SELECT dgmtypeattribs.typeid,
                            dgmtypeattribs.attributeid,
                            dgmattribs.attributename,
                            dgmattribs.displayname,
                            dgmtypeattribs.value,
                            dgmattribs.unitid,
                            dgmunits.displayname AS udisplayname,
                            dgmattribs.categoryid,
                            dgmattribs.published
                           FROM ((eve.dgmtypeattribs
                      JOIN eve.dgmattribs ON ((dgmtypeattribs.attributeid = dgmattribs.attributeid)))
                 LEFT JOIN eve.dgmunits ON ((dgmattribs.unitid = dgmunits.unitid)))
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
                           FROM ((eve.invtypes
                      JOIN eve.dgmattribs ON ((dgmattribs.attributeid = 161)))
                 LEFT JOIN eve.dgmunits ON ((dgmattribs.unitid = dgmunits.unitid))))
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
                   FROM ((eve.invtypes
              JOIN eve.dgmattribs ON ((dgmattribs.attributeid = 38)))
         LEFT JOIN eve.dgmunits ON ((dgmattribs.unitid = dgmunits.unitid))))
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
           FROM ((eve.invtypes
      JOIN eve.dgmattribs ON ((dgmattribs.attributeid = 4)))
   LEFT JOIN eve.dgmunits ON ((dgmattribs.unitid = dgmunits.unitid)));

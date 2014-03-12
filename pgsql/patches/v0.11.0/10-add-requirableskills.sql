CREATE OR REPLACE VIEW requirableskills AS 
 SELECT DISTINCT dta.value::integer AS skilltypeid
   FROM eve.invtypes it
   JOIN eve.invgroups ig ON ig.groupid = it.groupid
   JOIN eve.dgmtypeattribs dta ON dta.typeid = it.typeid AND (dta.attributeid = ANY (ARRAY[182, 183, 184, 1285, 1289, 1290]))
  WHERE ig.categoryid = ANY (ARRAY[6, 7, 8, 18, 20, 32]);

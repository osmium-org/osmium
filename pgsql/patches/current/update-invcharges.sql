CREATE OR REPLACE VIEW invcharges AS 
 SELECT modattribs.typeid AS moduleid, invtypes.typeid AS chargeid, invtypes.typename AS chargename
   FROM eve.dgmtypeattribs AS modattribs
   LEFT JOIN eve.dgmtypeattribs modchargesize ON modchargesize.attributeid = 128 AND modchargesize.typeid = modattribs.typeid
   JOIN eve.invtypes ON modattribs.value::integer = invtypes.groupid
   LEFT JOIN eve.dgmtypeattribs chargesize ON chargesize.attributeid = 128 AND chargesize.typeid = invtypes.typeid
   JOIN eve.invtypes modcapacity ON modcapacity.typeid = modattribs.typeid
  WHERE (modattribs.attributeid = ANY (ARRAY[604, 605, 606, 609, 610])) 
  AND (chargesize.value IS NULL OR modchargesize.value IS NULL OR chargesize.value = modchargesize.value) 
  AND (modcapacity.capacity >= invtypes.volume)
  AND invtypes.published = 1;

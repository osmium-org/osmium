CREATE OR REPLACE VIEW invbeacons AS 
 SELECT invtypes.typeid,
    invtypes.typename
   FROM eve.invtypes WHERE groupid = 920;
   

CREATE OR REPLACE VIEW tagcount AS 
 SELECT ft.tagname, count(ft.fittinghash) AS count
   FROM allowedloadoutsanonymous a
   JOIN loadoutslatestrevision llr ON a.loadoutid = llr.loadoutid
   JOIN loadouthistory lh ON lh.loadoutid = a.loadoutid AND lh.revision = llr.latestrevision
   JOIN loadouts l ON l.loadoutid = a.loadoutid
   JOIN fittingtags ft ON ft.fittinghash = lh.fittinghash
  WHERE l.visibility = 0
  GROUP BY ft.tagname;

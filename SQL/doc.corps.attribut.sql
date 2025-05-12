USE ecfc6;

SELECT 
    CONCAT(
    '<attributs>
          <complementaire>false</complementaire>
          <contratAlternance>false</contratAlternance>
          <pasAssureRemunere>false</pasAssureRemunere>
          <pasDeReembauche>', 
          CASE 
              WHEN EXISTS (
                  SELECT 1 
                  FROM ecfc6.salaries s2
                  WHERE s2.drupture IN ('202204', '202205', '202206')
                  AND s2.numcafat = s.numcafat
              ) THEN 'true'
              ELSE 'false'
          END,
          '</pasDeReembauche>
      </attributs>'
    ) AS xml_output
FROM ecfc6.bulletin b
INNER JOIN ecfc6.salaries s ON b.salarie_id = s.id
WHERE b.periode IN ('202204', '202205', '202206')
GROUP BY s.numcafat;

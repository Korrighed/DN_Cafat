USE ecfc6;

SELECT CONCAT(
'
  <assurer>
    <numero>', s.numcafat, '</numero>
    <nom>', UPPER(s.nom), '</nom>
    <prenoms>', UPPER(s.prenom), '</prenoms>
    <dateNaissance>', s.dnaissance, '</dateNaissance>
    <codeAT>PRINCIPAL</codeAT>
    <nombreHeures>', REPLACE(FORMAT(SUM(b.nombre_heures), 2), ',', '.'), '</nombreHeures>
    <remuneration>', ROUND(SUM(b.brut)), '</remuneration>
',    
    -- Inclure dateEmbauche seulement si elle existe ET est dans la période
    CASE 
        WHEN s.dembauche IS NOT NULL AND s.dembauche BETWEEN '2022-04-01' AND '2022-06-30'
        THEN CONCAT('    <dateEmbauche>', s.dembauche, '</dateEmbauche>
')
        ELSE ''
    END,
    
    -- Inclure dateRupture seulement si elle existe ET est dans la période
    CASE 
        WHEN s.drupture IS NOT NULL AND s.drupture BETWEEN '2022-04-01' AND '2022-06-30'
        THEN CONCAT('    <dateRupture>', s.drupture, '</dateRupture>
')
        ELSE ''
    END,
'  </assurer>
'
) AS xml_output
FROM bulletin b
INNER JOIN salaries s ON b.salarie_id = s.id
WHERE b.periode IN ('202204', '202205', '202206')
GROUP BY s.numcafat

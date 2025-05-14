WITH assiette_types AS (
    SELECT 
        l.id,
        l.bulletin_id,
        b.salarie_id,
        l.base,  
        r.libelle,
        CASE 
            WHEN r.libelle LIKE '%RUAMM%' THEN 'RUAMM'
            WHEN r.libelle LIKE '%FIAF%' THEN 'FIAF'
            WHEN r.libelle LIKE '%retraite Agirc%' THEN 'RETRAITE_AGIRC'
            WHEN r.libelle LIKE '%retraite CEG%' THEN 'RETRAITE_CEG'
            WHEN r.libelle LIKE '%Cho%' THEN 'CHOMAGE'
            WHEN r.libelle LIKE '%Accident du travail%' THEN 'ATMP'
            WHEN r.libelle LIKE '%FDS Financement Dialogue Social%' THEN 'FDS'
            WHEN r.libelle LIKE '%Formation Professionnelle continue%' THEN 'FORMATION_PROFESSIONNELLE'
            WHEN r.libelle LIKE '%Fond Social de l%Habitat%' THEN 'FSH'
            WHEN r.libelle LIKE 'C.R.E.%' THEN 'CRE'
        END AS type_identifier
    FROM ligne_bulletin l
    INNER JOIN bulletin b ON l.bulletin_id = b.id
    INNER JOIN rubrique r ON l.rubrique_id = r.id
    WHERE 
        b.periode IN ('202204', '202205', '202206')
        AND (
            r.libelle LIKE '%RUAMM%' OR 
            r.libelle LIKE 'C.R.E.%' OR 
            r.libelle LIKE '%retraite%' OR
            r.libelle LIKE '%FIAF%' OR
            r.libelle LIKE '%Accident du travail%' OR
            r.libelle LIKE '%FDS Financement Dialogue Social%' OR
            r.libelle LIKE '%Formation Professionnelle continue%' OR
            r.libelle LIKE '%Fond Social de l%Habitat%' OR
            r.libelle LIKE '%CHOMAGE%' OR
            r.libelle LIKE '%Cho%'
        )
)

SELECT CONCAT(
    CASE
        WHEN COUNT(CASE WHEN at.type_identifier IN ('RUAMM', 'CHOMAGE', 'ATMP', 'FSH', 'FORMATION_PROFESSIONNELLE', 'FIAF', 'RETRAITE_AGIRC', 'RETRAITE_CEG') AND at.base > 0 THEN 1 ELSE NULL END) > 0 THEN
            CONCAT('
    <assiettes>',
                GROUP_CONCAT(
                    CASE 
                        -- RUAMM avec plafond spécial (deux tranches)
                        WHEN at.type_identifier = 'RUAMM' AND at.base > 0 THEN
                            CASE
                                WHEN at.base <= 548600 THEN
                                    CONCAT('
      <assiette>
        <type>RUAMM</type>
        <valeur>', LEFT(ROUND(at.base * 0.0285), 18), '</valeur>
      </assiette>')
                                ELSE
                                    CONCAT('
      <assiette>
        <type>RUAMM</type>
        <tranche>TRANCHE_1</tranche>
        <valeur>', LEFT(ROUND(548600 * 0.0285), 18), '</valeur>
      </assiette>
      <assiette>
        <type>RUAMM</type>
        <tranche>TRANCHE_2</tranche>
        <valeur>', LEFT(ROUND((at.base - 548600) * 0.0125), 18), '</valeur>
      </assiette>')
                            END
                            
                        -- CHOMAGE avec plafond à 390900
                        WHEN at.type_identifier = 'CHOMAGE' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>CHOMAGE</type>
        <valeur>', LEFT(ROUND(IF(at.base > 390900, 390900, at.base) * 0.0069), 18), '</valeur>
      </assiette>')
                            
                        -- ATMP avec plafond à 390900
                        WHEN at.type_identifier = 'ATMP' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>ATMP</type>
        <valeur>', LEFT(ROUND(IF(at.base > 390900, 390900, at.base) * 0.0132), 18), '</valeur>
      </assiette>')
                            
                        -- FSH avec plafond à 329700
                        WHEN at.type_identifier = 'FSH' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>FSH</type>
        <valeur>', LEFT(ROUND(IF(at.base > 329700, 329700, at.base) * 0.009), 18), '</valeur>
      </assiette>')
                            
                        -- Formation Professionnelle continue
                        WHEN at.type_identifier = 'FORMATION_PROFESSIONNELLE' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>FORMATION_PROFESSIONNELLE</type>
        <valeur>', LEFT(ROUND(at.base * 0.007), 18), '</valeur>
      </assiette>')
                            
                        -- FIAF avec plafond à 548600
                        WHEN at.type_identifier = 'FIAF' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>FIAF</type>
        <valeur>', LEFT(ROUND(IF(at.base > 548600, 548600, at.base) * 0.002), 18), '</valeur>
      </assiette>')
                            
                        -- RETRAITE_AGIRC avec plafond à 468377
                        WHEN at.type_identifier = 'RETRAITE_AGIRC' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>RETRAITE</type>
        <valeur>', LEFT(ROUND(IF(at.base > 468377, 468377, at.base) * 0.00315), 18), '</valeur>
      </assiette>')
                            
                        -- RETRAITE_CEG avec plafond à 468377
                        WHEN at.type_identifier = 'RETRAITE_CEG' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>RETRAITE</type>
        <valeur>', LEFT(ROUND(IF(at.base > 468377, 468377, at.base) * 0.0086), 18), '</valeur>
      </assiette>')
                            
                        ELSE '' 
                    END
                SEPARATOR ''), '
    </assiettes>')
        ELSE ''  
    END, '
') AS xml_output
FROM assiette_types at
INNER JOIN salaries s ON at.salarie_id = s.id
INNER JOIN bulletin b ON at.bulletin_id = b.id
GROUP BY s.numcafat, b.periode;

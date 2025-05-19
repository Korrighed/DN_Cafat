WITH assiette_types AS (
    SELECT 
        l.id,
        l.bulletin_id,
        b.salarie_id,
        l.base,  
        l.libelle,
        CASE 
            WHEN l.libelle LIKE '%RUAMM%' THEN 'RUAMM'
            WHEN l.libelle LIKE '%FIAF%' THEN 'FIAF'
            WHEN l.libelle LIKE 'Cotisations CAFA%' THEN 'COTIS-CAFAT'
            WHEN l.libelle LIKE '%Accident du travail%' THEN 'ATMP'
            WHEN l.libelle LIKE '%FDS Financement Dialogue Social%' THEN 'FDS'
            WHEN l.libelle LIKE '%Formation Professionnelle continue%' THEN 'FORMATION_PROFESSIONNELLE'
            WHEN l.libelle LIKE '%Fond Social de l%Habitat%' THEN 'FSH'
            WHEN l.libelle LIKE 'C.R.E.%' THEN 'CRE'
        END AS type_identifier
    FROM ligne_bulletin l
    INNER JOIN bulletin b ON l.bulletin_id = b.id
    INNER JOIN rubrique r ON l.rubrique_id = r.id
    WHERE 
        b.periode IN ('202204', '202205', '202206')
        AND (
            l.libelle LIKE '%RUAMM%' OR 
            l.libelle LIKE '%FIAF%' OR
            l.libelle LIKE 'Cotisations CAFA%' OR
            l.libelle LIKE '%Accident du travail%' OR
            l.libelle LIKE '%FDS Financement Dialogue Social%' OR
            l.libelle LIKE '%Formation Professionnelle continue%' OR
            l.libelle LIKE '%Fond Social de l%Habitat%' OR
            l.libelle LIKE 'C.R.E.%'
        )
)

SELECT CONCAT(
    CASE
        WHEN COUNT(CASE WHEN at.type_identifier IN ('RUAMM', 'FSH', 'FORMATION_PROFESSIONNELLE', 'FIAF', 'COTIS-CAFAT', 'CRE', 'ATMP') AND at.base > 0 THEN 1 ELSE NULL END) > 0 THEN
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
                            
                        -- FIAF avec plafond à 548600
                        WHEN at.type_identifier = 'FIAF' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>FIAF</type>
        <valeur>', LEFT(ROUND(IF(at.base > 548600, 548600, at.base) * 0), 18), '</valeur>
      </assiette>')
                            
                        -- COTIS-CAFAT génère trois balises
                        WHEN at.type_identifier = 'COTIS-CAFAT' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>RETRAITE</type>
        <valeur>', LEFT(ROUND(IF(at.base > 548600, 548600, at.base) * 0.0420), 18), '</valeur>
      </assiette>
      <assiette>
        <type>CHOMAGE</type>
        <valeur>', LEFT(ROUND(IF(at.base > 390900, 390900, at.base) * 0.0034), 18), '</valeur>
      </assiette>
      <assiette>
        <type>PRESTATIONS_FAMILIALES</type>
        <valeur>', LEFT(ROUND(IF(at.base > 390900, 390900, at.base) * 0), 18), '</valeur>
      </assiette>')
                            
                        -- ATMP avec plafond à 390900
                        WHEN at.type_identifier = 'ATMP' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>ATMP</type>
        <valeur>', LEFT(ROUND(IF(at.base > 390900, 390900, at.base) * 0), 18), '</valeur>
      </assiette>')
                            
                        -- FSH avec plafond à 329700
                        WHEN at.type_identifier = 'FSH' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>FSH</type>
        <valeur>', LEFT(ROUND(IF(at.base > 329700, 329700, at.base) * 0.0), 18), '</valeur>
      </assiette>')
                            
                        -- Formation Professionnelle continue
                        WHEN at.type_identifier = 'FORMATION_PROFESSIONNELLE' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>FORMATION_PROFESSIONNELLE</type>
        <valeur>', LEFT(ROUND(at.base * 0.0), 18), '</valeur>
      </assiette>')

                        -- CRE avec plafond
                        WHEN at.type_identifier = 'CRE' AND at.base > 0 THEN
                            CONCAT('
      <assiette>
        <type>CRE</type>
        <valeur>', LEFT(ROUND(IF(at.base > 468377, 468377, at.base) * 0.0315), 18), '</valeur>
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

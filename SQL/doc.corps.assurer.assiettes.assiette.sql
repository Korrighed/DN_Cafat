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
            WHEN r.libelle LIKE '%retraite%' THEN 'RETRAITE'
            WHEN r.libelle LIKE '%Cho%' THEN 'CHOMAGE'
            WHEN r.libelle LIKE '%Accident du travail%' THEN 'ATMP'
            WHEN r.libelle LIKE '%FDS Financement Dialogue Social%' THEN 'FDS'
            WHEN r.libelle LIKE '%Formation Professionnelle continue%' THEN 'FORMATION_PROFESSIONNELLE'
            WHEN r.libelle LIKE '%Fond Social de l%Habitat%' THEN 'FSH'
            WHEN r.libelle LIKE 'C.R.E.%' THEN 'CRE'
            WHEN r.libelle LIKE '%CHOMAGE%' THEN 'CHOMAGE'
        END AS type_identifier,
        
        -- Détermine la tranche pour RUAMM
        CASE 
            WHEN r.libelle LIKE '%RUAMM%Tranche 1%' THEN 'TRANCHE_1'
            WHEN r.libelle LIKE '%RUAMM%Tranche 2%' THEN 'TRANCHE_2'
            ELSE ''
        END AS tranche
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
            r.libelle LIKE '%CHOMAGE%'
        )
)

SELECT CONCAT(
    CASE
        -- Ne génère le bloc assiettes que s'il y a au moins une assiette valide
        WHEN COUNT(CASE WHEN at.type_identifier IS NOT NULL AND at.base IS NOT NULL AND at.base > 0 THEN 1 ELSE NULL END) > 0 THEN
            CONCAT('
    <assiettes>',
                GROUP_CONCAT(
                    -- Ne génère la balise assiette que si à la fois type et valeur sont valides
                    CASE WHEN at.type_identifier IS NOT NULL AND at.base IS NOT NULL AND at.base > 0 THEN
                        CONCAT('
      <assiette>
        <type>', at.type_identifier, '</type>',
                        CASE WHEN at.tranche != '' THEN CONCAT('
        <tranche>', at.tranche, '</tranche>') ELSE '' END,
                        '
        <valeur>', LEFT(ROUND(at.base), 18), '</valeur>
      </assiette>')
                    ELSE '' END
                SEPARATOR ''), '
    </assiettes>')
        ELSE ''  -- Pas de bloc assiettes du tout si aucune assiette valide
    END, '
') AS xml_output
FROM assiette_types at
INNER JOIN salaries s ON at.salarie_id = s.id
INNER JOIN bulletin b ON at.bulletin_id = b.id
GROUP BY s.numcafat, b.periode;

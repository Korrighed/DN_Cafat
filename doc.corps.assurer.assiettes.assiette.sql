SELECT CONCAT(
    CASE
        -- Ne génère le bloc assiettes que s'il y a au moins une assiette valide
    WHEN COUNT(CASE WHEN d.type_identifier IS NOT NULL AND d.base IS NOT NULL AND d.base != 0 THEN 1 ELSE NULL END) > 0 THEN
            CONCAT('
    <assiettes>',
                GROUP_CONCAT(
                    -- Ne génère la balise assiette que si à la fois type et valeur sont valides
                    CASE WHEN d.type_identifier IS NOT NULL AND d.base IS NOT NULL AND d.base != 0 THEN
                        CONCAT('
      <assiette>
        <type>', d.type_identifier, '</type>',
                        d.tranche_value,
                        CASE 
                            WHEN d.base IS NOT NULL AND d.base != 0
                            THEN CONCAT('
        <valeur>', LEFT(ROUND(d.base), 18), '</valeur>')
                            ELSE ''
                        END, '
      </assiette>')
                    ELSE '' END
                SEPARATOR ''), '
    </assiettes>')
        ELSE ''  -- Pas de bloc assiettes du tout si aucune assiette valide
    END, '
') AS xml_output
FROM salaries s
INNER JOIN bulletin b ON s.id = b.salarie_id
INNER JOIN (
    -- Sous-requête qui extrait et classifie les types d'assiette
    SELECT 
        l.id,
        l.bulletin_id,
        l.libelle,
        l.base,  
        CASE 
            WHEN l.libelle LIKE '%RUAMM%' THEN 'RUAMM'
            WHEN l.libelle LIKE '%FIAF%' THEN 'FIAF'
            WHEN l.libelle LIKE '%retraite%' THEN 'RETRAITE'
            WHEN l.libelle LIKE '%Accident du Travail%' THEN 'ATMP'
            WHEN l.libelle LIKE '%FDS Financement Dialogue Social%' THEN 'FDS'
            WHEN l.libelle LIKE '%Formation Professionnelle continue%' THEN 'FORMATION_PROFESSIONNELLE'
            WHEN l.libelle LIKE '%Fond Social de l%Habitat%' THEN 'FSH'
            WHEN l.libelle LIKE 'C.R.E.%' THEN 'CRE'
            WHEN l.libelle LIKE '%CHOMAGE%' THEN 'CHOMAGE'
        END AS type_identifier,
        
        -- Détermine la tranche pour RUAMM
        CASE 
            WHEN l.libelle LIKE '%RUAMM%Tranche 1%' THEN '
        <tranche>TRANCHE_1</tranche>'
            WHEN l.libelle LIKE '%RUAMM%Tranche 2%' THEN '
        <tranche>TRANCHE_2</tranche>'
            ELSE ''
        END AS tranche_value
    FROM ligne_bulletin l
    INNER JOIN bulletin bb ON l.bulletin_id = bb.id
    WHERE 
        bb.periode IN ('202204', '202205', '202206')
        AND (
            l.libelle LIKE '%RUAMM%' OR 
            l.libelle LIKE 'C.R.E.%' OR 
            l.libelle LIKE '%retraite%' OR
            l.libelle LIKE '%FIAF%' OR
            l.libelle LIKE '%Accident du Travail%' OR
            l.libelle LIKE '%FDS Financement Dialogue Social%' OR
            l.libelle LIKE '%Formation Professionnelle continue%' OR
            l.libelle LIKE '%Fond Social de l%Habitat%' OR
            l.libelle LIKE '%CHOMAGE%'
        )
) AS d ON d.bulletin_id = b.id
GROUP BY s.numcafat, b.periode

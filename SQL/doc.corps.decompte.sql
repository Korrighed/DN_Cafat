USE ecfc6;

WITH cotisation_types AS (
    SELECT 
        l.id,
        l.bulletin_id,
        l.base,
        r.libelle,
        r.taux_patronal AS taux_ref_patronal,
        CASE 
            WHEN r.libelle LIKE '%RUAMM%' THEN 'RUAMM'
            WHEN r.libelle LIKE '%FIAF%' THEN 'FIAF'
            WHEN r.libelle LIKE '%retraite%' THEN 'RETRAITE'
            WHEN r.libelle LIKE '%Cho%' THEN 'CHOMAGE'
            WHEN r.libelle LIKE 'C.R.E.%' THEN 'CRE'
            WHEN r.libelle LIKE '%FDS Financement Dialogue Social%' THEN 'FDS'
            WHEN r.libelle LIKE '%Formation%' THEN 'FORMATION_PROFESSIONNELLE'
            WHEN r.libelle LIKE '%Accident du travail%' THEN 'ATMP_PRINCIPAL'
            WHEN r.libelle LIKE '%CS%' THEN 'CSS'
            WHEN r.libelle LIKE '%Fond Social de l%Habitat%' THEN 'FSH'
        END AS type_cotisation,
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
            r.libelle LIKE '%CHOMAGE%' OR
            r.libelle LIKE '%CS%'
        )
),
cotisation_data AS (
    SELECT 
        -- Stocke le XML pour chaque cotisation individuelle
        CASE WHEN 
            ct.type_cotisation IS NOT NULL AND 
            SUM(ct.base) > 0 AND
            SUM(CASE
                WHEN ct.libelle LIKE '%Accident du travail%' THEN ct.base * s.tauxat / 100
                ELSE ct.base * ct.taux_ref_patronal / 100
            END) > 0
        THEN
            CONCAT('
            <cotisation>
                    <type>', ct.type_cotisation, '</type>',
            CASE WHEN ct.tranche != '' THEN CONCAT('
                    <tranche>', ct.tranche, '</tranche>') ELSE '' END,
        '
                    <assiette>', LEFT(ROUND(SUM(ct.base)), 18), '</assiette>
                    <valeur>',
            LEFT(ROUND(SUM(CASE
                WHEN ct.libelle LIKE '%Accident du travail%' THEN ct.base * s.tauxat / 100
                ELSE ct.base * ct.taux_ref_patronal / 100
            END)), 18),'</valeur>
            </cotisation>')
        ELSE 
            ''
        END AS xml_output,
        
        -- Reste du code inchangé pour montant_cotisation
        CASE WHEN 
            ct.type_cotisation IS NOT NULL AND 
            SUM(ct.base) > 0 AND
            SUM(CASE
                WHEN ct.libelle LIKE '%Accident du travail%' THEN ct.base * s.tauxat / 100
                ELSE ct.base * ct.taux_ref_patronal / 100
            END) > 0
        THEN 
            SUM(CASE
                WHEN ct.libelle LIKE '%Accident du travail%' THEN ct.base * s.tauxat / 100
                ELSE ct.base * ct.taux_ref_patronal / 100
            END)
        ELSE 
            0 
        END AS montant_cotisation
    FROM cotisation_types ct
    INNER JOIN bulletin b ON ct.bulletin_id = b.id
    INNER JOIN societe s ON 1=1
    GROUP BY ct.type_cotisation, ct.tranche
)

SELECT 
    CONCAT(
        '<decompte>
    <cotisations>',

    GROUP_CONCAT(xml_output SEPARATOR ''),
        '
    </cotisations>
    <totalCotisations>', CAST(ROUND(SUM(montant_cotisation)) AS UNSIGNED), '</totalCotisations>
    <deductions></deductions>
    <montantAPayer>', CAST(ROUND(SUM(montant_cotisation)) AS UNSIGNED), '</montantAPayer>
</decompte>'
    ) AS xml_decompte
FROM cotisation_data;

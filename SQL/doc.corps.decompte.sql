USE ecfc6;

WITH cotisation_types AS (
    SELECT 
        l.id,
        l.bulletin_id,
        l.base,
        r.libelle,
        r.taux_salarial + r.taux_patronal AS taux_total,
        CASE 
            WHEN r.libelle LIKE '%RUAMM%' THEN 'RUAMM'
            WHEN r.libelle LIKE '%FIAF%' THEN 'FIAF'
            WHEN r.libelle LIKE '%retraite Agirc%' THEN 'RETRAITE_AGIRC'
            WHEN r.libelle LIKE '%retraite CEG%' THEN 'RETRAITE_CEG'
            WHEN r.libelle LIKE '%Cho%' THEN 'CHOMAGE'
            WHEN r.libelle LIKE 'C.R.E.%' THEN 'CRE'
            WHEN r.libelle LIKE '%FDS Financement Dialogue Social%' THEN 'FDS'
            WHEN r.libelle LIKE '%Formation Professionnelle continue%' THEN 'FORMATION_PROFESSIONNELLE'
            WHEN r.libelle LIKE '%Accident du travail%' THEN 'ATMP_PRINCIPAL'
            WHEN r.libelle LIKE '%CS%' THEN 'CCS'
            WHEN r.libelle LIKE '%Fond Social de l%Habitat%' THEN 'FSH'
        END AS type_cotisation
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
        ct.type_cotisation,
        SUM(ct.base) AS total_base,
        CASE 
            -- RUAMM avec les deux tranches
            WHEN ct.type_cotisation = 'RUAMM' AND SUM(ct.base) > 0 THEN
                CASE
                    WHEN SUM(ct.base) <= 548600 THEN
                        CONCAT('
            <cotisation>
                <type>RUAMM</type>
                <assiette>', LEFT(ROUND(SUM(ct.base)), 18), '</assiette>
                <valeur>', LEFT(ROUND(SUM(ct.base) * 0.065), 18), '</valeur>
            </cotisation>')
                    ELSE
                        CONCAT('
            <cotisation>
                <type>RUAMM</type>
                <tranche>TRANCHE_1</tranche>
                <assiette>', LEFT(ROUND(548600), 18), '</assiette>
                <valeur>', LEFT(ROUND(548600 * 0.065), 18), '</valeur>
            </cotisation>
            <cotisation>
                <type>RUAMM</type>
                <tranche>TRANCHE_2</tranche>
                <assiette>', LEFT(ROUND(SUM(ct.base) - 548600), 18), '</assiette>
                <valeur>', LEFT(ROUND((SUM(ct.base) - 548600) * 0.025), 18), '</valeur>
            </cotisation>')
                END
                
            -- CHOMAGE avec plafond à 390900
            WHEN ct.type_cotisation = 'CHOMAGE' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>CHOMAGE</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 390900)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 390900) * 0.0206), 18), '</valeur>
            </cotisation>')
                
            -- ATMP avec plafond à 390900
            WHEN ct.type_cotisation = 'ATMP_PRINCIPAL' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>ATMP_PRINCIPAL</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 390900)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 390900) * s.tauxat / 100), 18), '</valeur>
            </cotisation>')
                
            -- FSH avec plafond à 329700
            WHEN ct.type_cotisation = 'FSH' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>FSH</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 329700)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 329700) * 0.02), 18), '</valeur>
            </cotisation>')
                
            -- FIAF
            WHEN ct.type_cotisation = 'FIAF' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>FIAF</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 548600)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 548600) * 0.002), 18), '</valeur>
            </cotisation>')
                
            -- RETRAITE_AGIRC
            WHEN ct.type_cotisation = 'RETRAITE_AGIRC' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>RETRAITE</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 468377)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 468377) * 0.0787), 18), '</valeur>
            </cotisation>')
                
            -- RETRAITE_CEG
            WHEN ct.type_cotisation = 'RETRAITE_CEG' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>RETRAITE_CEG</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 468377)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 468377) * 0.0215), 18), '</valeur>
            </cotisation>')
                
            -- FORMATION_PROFESSIONNELLE
            WHEN ct.type_cotisation = 'FORMATION_PROFESSIONNELLE' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>FORMATION_PROFESSIONNELLE</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 390900)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 390900) * 0.0070), 18), '</valeur>
            </cotisation>')
                
            -- FDS
            WHEN ct.type_cotisation = 'FDS' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>FDS</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 390900)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 390900) * 0.0075), 18), '</valeur>
            </cotisation>')
                
            -- CCS sans plafond
            WHEN ct.type_cotisation = 'CCS' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>CCS</type>
                <assiette>', LEFT(ROUND(SUM(ct.base)), 18), '</assiette>
                <valeur>', LEFT(ROUND(SUM(ct.base) * 0.02), 18), '</valeur>
            </cotisation>')
                
            -- CRE
            WHEN ct.type_cotisation = 'CRE' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>CRE</type>
                <assiette>', LEFT(ROUND(SUM(ct.base)), 18), '</assiette>
                <valeur>', LEFT(ROUND(SUM(ct.base) * ct.taux_total / 100), 18), '</valeur>
            </cotisation>')
                
            ELSE ''
        END AS xml_output,
        
        -- Calcul des montants de cotisation pour le total
        CASE 
            -- RUAMM avec deux tranches
            WHEN ct.type_cotisation = 'RUAMM' AND SUM(ct.base) > 0 THEN
                CASE
                    WHEN SUM(ct.base) <= 548600 THEN
                        SUM(ct.base) * 0.065
                    ELSE
                        (548600 * 0.065) + ((SUM(ct.base) - 548600) * 0.025)
                END
                
            -- CHOMAGE
            WHEN ct.type_cotisation = 'CHOMAGE' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 390900) * 0.0206
                
            -- ATMP
            WHEN ct.type_cotisation = 'ATMP_PRINCIPAL' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 390900) * s.tauxat / 100
                
            -- FSH
            WHEN ct.type_cotisation = 'FSH' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 329700) * 0.02
                
            -- FIAF
            WHEN ct.type_cotisation = 'FIAF' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 548600) * 0.002
                
            -- RETRAITE_AGIRC
            WHEN ct.type_cotisation = 'RETRAITE_AGIRC' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 468377) * 0.0787
                
            -- RETRAITE_CEG
            WHEN ct.type_cotisation = 'RETRAITE_CEG' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 468377) * 0.0215
                
            -- FORMATION_PROFESSIONNELLE
            WHEN ct.type_cotisation = 'FORMATION_PROFESSIONNELLE' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 390900) * 0.0070
                
            -- FDS
            WHEN ct.type_cotisation = 'FDS' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 390900) * 0.0075
                
            -- CCS
            WHEN ct.type_cotisation = 'CCS' AND SUM(ct.base) > 0 THEN
                SUM(ct.base) * 0.02
                
            -- CRE
            WHEN ct.type_cotisation = 'CRE' AND SUM(ct.base) > 0 THEN
                SUM(ct.base) * ct.taux_total / 100
                
            ELSE 0
        END AS montant_cotisation
        
    FROM cotisation_types ct
    INNER JOIN bulletin b ON ct.bulletin_id = b.id
    INNER JOIN societe s ON 1=1
    GROUP BY ct.type_cotisation
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

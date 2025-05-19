USE ecfc6;

WITH cotisation_types AS (
    SELECT 
        l.id,
        l.bulletin_id,
        l.base,
        l.libelle,
        CASE 
            WHEN l.libelle LIKE '%RUAMM%' THEN 'RUAMM'
            WHEN l.libelle LIKE '%FIAF%' THEN 'FIAF'
            WHEN l.libelle LIKE 'Cotisations CAFA%' THEN 'COTIS-CAFAT'
            WHEN l.libelle LIKE 'C.R.E.%' THEN 'CRE'
            WHEN l.libelle LIKE '%FDS Financement Dialogue Social%' THEN 'FDS'
            WHEN l.libelle LIKE '%Formation Professionnelle continue%' THEN 'FORMATION_PROFESSIONNELLE'
            WHEN l.libelle LIKE '%Accident du travail%' THEN 'ATMP_PRINCIPAL'
            WHEN l.libelle LIKE '%CS%' THEN 'CCS'
            WHEN l.libelle LIKE '%Fond Social de l%Habitat%' THEN 'FSH'
        END AS type_cotisation
    FROM ligne_bulletin l
    INNER JOIN bulletin b ON l.bulletin_id = b.id
    INNER JOIN rubrique r ON l.rubrique_id = r.id
    WHERE 
        b.periode IN ('202204', '202205', '202206')
        AND (
            l.libelle LIKE '%RUAMM%' OR 
            l.libelle LIKE 'C.R.E.%' OR 
            l.libelle LIKE 'Cotisations CAFA%' OR
            l.libelle LIKE '%FIAF%' OR
            l.libelle LIKE '%Accident du travail%' OR
            l.libelle LIKE '%FDS Financement Dialogue Social%' OR
            l.libelle LIKE '%Formation Professionnelle continue%' OR
            l.libelle LIKE '%Fond Social de l%Habitat%' OR
            l.libelle LIKE '%CHOMAGE%' OR
            l.libelle LIKE '%CS%'
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
                <valeur>', LEFT(ROUND(SUM(ct.base) * 0.1452), 18), '</valeur>
            </cotisation>')
                    ELSE
                        CONCAT('
            <cotisation>
                <type>RUAMM</type>
                <tranche>TRANCHE_1</tranche>
                <assiette>', LEFT(ROUND(548600), 18), '</assiette>
                <valeur>', LEFT(ROUND(548600 * 0.1452), 18), '</valeur>
            </cotisation>
            <cotisation>
                <type>RUAMM</type>
                <tranche>TRANCHE_2</tranche>
                <assiette>', LEFT(ROUND(SUM(ct.base) - 548600), 18), '</assiette>
                <valeur>', LEFT(ROUND((SUM(ct.base) - 548600) * 0.05), 18), '</valeur>
            </cotisation>')
                END
                
            -- COTIS-CAFAT
            WHEN ct.type_cotisation = 'COTIS-CAFAT' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>RETRAITE</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 548600)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 548600) * 0.14), 18), '</valeur>
            </cotisation>
            <cotisation>
                <type>CHOMAGE</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 390900)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 390900) * 0.0206), 18), '</valeur>
            </cotisation>
            <cotisation>
                <type>PRESTATIONS_FAMILIALES</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 390900)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 390900) * 0.0563), 18), '</valeur>
            </cotisation>')
           
            -- ATMP avec plafond à 390900
            WHEN ct.type_cotisation = 'ATMP_PRINCIPAL' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>ATMP_PRINCIPAL</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 390900)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 390900) * 0.0072), 18), '</valeur>
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
                
            -- FORMATION_PROFESSIONNELLE
            WHEN ct.type_cotisation = 'FORMATION_PROFESSIONNELLE' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>FORMATION_PROFESSIONNELLE</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 468377)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 468377) * 0.0070), 18), '</valeur>
            </cotisation>')
                
            -- FDS
            WHEN ct.type_cotisation = 'FDS' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>FDS</type>
                <assiette>', LEFT(ROUND(SUM(ct.base)), 18), '</assiette>
                <valeur>', LEFT(ROUND(SUM(ct.base) * 0.00075), 18), '</valeur>
            </cotisation>')
                
            -- CCS sans plafond
            WHEN ct.type_cotisation = 'CCS' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>CCS</type>
                <assiette>', LEFT(ROUND(SUM(ct.base)), 18), '</assiette>
                <valeur>', LEFT(ROUND(SUM(ct.base) * 0.03), 18), '</valeur>
            </cotisation>')
                
            -- CRE
            WHEN ct.type_cotisation = 'CRE' AND SUM(ct.base) > 0 THEN
                CONCAT('
            <cotisation>
                <type>CRE</type>
                <assiette>', LEFT(ROUND(LEAST(SUM(ct.base), 468377)), 18), '</assiette>
                <valeur>', LEFT(ROUND(LEAST(SUM(ct.base), 468377) * 0.0787), 18), '</valeur>
            </cotisation>')
                
            ELSE ''
        END AS xml_output,
        
        -- Calcul des montants de cotisation pour le total
        CASE 
            -- RUAMM avec deux tranches
            WHEN ct.type_cotisation = 'RUAMM' AND SUM(ct.base) > 0 THEN
                CASE
                    WHEN SUM(ct.base) <= 548600 THEN
                        SUM(ct.base) * 0.1452
                    ELSE
                        (548600 * 0.1452) + ((SUM(ct.base) - 548600) * 0.05)
                END
                
            -- COTIS-CAFAT (additionne retraite, chômage et prestations familiales)
            WHEN ct.type_cotisation = 'COTIS-CAFAT' AND SUM(ct.base) > 0 THEN
                (LEAST(SUM(ct.base), 548600) * 0.14) + 
                (LEAST(SUM(ct.base), 390900) * 0.0206) + 
                (LEAST(SUM(ct.base), 390900) * 0.0563)
                
            -- ATMP
            WHEN ct.type_cotisation = 'ATMP_PRINCIPAL' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 390900) * 0.0072
                
            -- FSH
            WHEN ct.type_cotisation = 'FSH' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 329700) * 0.02
                
            -- FIAF
            WHEN ct.type_cotisation = 'FIAF' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 548600) * 0.002
                
            -- FORMATION_PROFESSIONNELLE
            WHEN ct.type_cotisation = 'FORMATION_PROFESSIONNELLE' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 468377) * 0.0070
                
            -- FDS
            WHEN ct.type_cotisation = 'FDS' AND SUM(ct.base) > 0 THEN
                SUM(ct.base) * 0.00075
                
            -- CCS
            WHEN ct.type_cotisation = 'CCS' AND SUM(ct.base) > 0 THEN
                SUM(ct.base) * 0.03
                
            -- CRE
            WHEN ct.type_cotisation = 'CRE' AND SUM(ct.base) > 0 THEN
                LEAST(SUM(ct.base), 468377) * 0.0787
                
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

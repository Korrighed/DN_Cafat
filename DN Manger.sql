-- Export avec valeurs constantes
SELECT 
    'DN' AS type, 
    'VERSIO_2_0' AS version, 
    'Nom Entreprise' AS emetteur,
    NOW() AS dateGeneration,
    'MINDSEYE' AS editeur,
    'DECLARATION-NOMINATIVE-MANAGER' AS nom_logiciel,
    '1' AS version_logiciel,
    '2023-05-15' AS dateVersion
INTO OUTFILE 'D:/Travail/DevWeb/DN_Cafat/tempalte - Copie.xml'
FIELDS TERMINATED BY '</>' 
ENCLOSED BY '<' 
LINES TERMINATED BY '\n'

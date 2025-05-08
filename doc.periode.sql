SELECT 
CONCAT(
'<doc>
    <entete>
        <type>DN</type>
        <version>VERSIO_2_0</version>
        <emetteur>',(SELECT enseigne FROM nom_de_votre_bd.societe LIMIT 1),'</emetteur>
        <dateGeneration>', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%s'), '</dateGeneration>
        <logiciel>
            <editeur>MINDSEYE</editeur>
            <nom>DECLARATION-NOMINATIVE-MANAGER</nom>
            <version>1</version>
            <dateVersion>2023-05-15</dateVersion>
        </logiciel>
    </entete>
</doc>'
) AS xml_output
INTO OUTFILE 'D:/Travail/DevWeb/DN_Cafat/tempalte - Copie.xml'
-- Notez que nous n'utilisons pas FIELDS TERMINATED BY ou ENCLOSED BY ici
LINES TERMINATED BY '\n'

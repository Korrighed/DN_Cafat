SELECT 
CONCAT(
'<doc>
    <entete>
        <type>DN</type>
        <version>VERSION_2_0</version>
        <emetteur>', UPPER((SELECT enseigne FROM ecfc6.societe LIMIT 1)), '</emetteur>
        <dateGeneration>', DATE_FORMAT(NOW(), '%Y-%m-%dT%H:%i:%s'), '</dateGeneration>
        <logiciel>
            <editeur>WEBDEV-2025</editeur>
            <nom>DECLARATION-NOMINATIVE-MANAGER</nom>
            <version>1</version>
            <dateVersion>2023-05-15</dateVersion>
        </logiciel>
    </entete>
</doc>'
) AS xml_output

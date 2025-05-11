SELECT 
CONCAT(
'<doc>
  <periode>
			<type>TRIMESTRIEL || ANNUELLE</type>
			<annee>Year INput</annee>
			<numero> 1 ||2 || 3 || 4 </numero>
		</periode>
</doc>'
) AS xml_output
INTO OUTFILE 'D:/Travail/DevWeb/DN_Cafat/tempalte - Copie.xml'
-- Notez que nous n'utilisons pas FIELDS TERMINATED BY ou ENCLOSED BY ici
LINES TERMINATED BY '\n'

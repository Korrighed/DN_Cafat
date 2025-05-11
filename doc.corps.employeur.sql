SELECT 
CONCAT(
'<doc>
  <employeur>
    <numero>', SUBSTRING_INDEX((SELECT numerocafat FROM ecfc6.societe LIMIT 1), '/', 1), '</numero>
    <suffixe>', SUBSTRING_INDEX((SELECT numerocafat FROM ecfc6.societe LIMIT 1), '/', -1), '</suffixe>
    <nom>', UPPER((SELECT enseigne FROM ecfc6.societe LIMIT 1)), '</nom>
    <rid>', SUBSTRING_INDEX((SELECT ridet FROM ecfc6.societe LIMIT 1), '.', 1), '</rid>
    <codeCotisation>001</codeCotisation>
    <tauxATPrincipal>', (SELECT tauxat FROM ecfc6.societe LIMIT 1), '</tauxATPrincipal>
  </employeur>
</doc>'
) AS xml_output

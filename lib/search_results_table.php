<?php
// $Header: /cvsroot/phpldapadmin/phpldapadmin/lib/search_results_table.php,v 1.9.2.2 2007/12/26 09:26:33 wurley Exp $

/**
 * Incoming variables (among others)
 *  $results: The result from the ldapsearch.
 *  $ldapserver: LDAP Server Object.
 *  $start_entry: The index of the entry at which to begin displaying
 *  $end_entry: The index of the entry at which to end displaying
 * @package phpLDAPadmin
 */

if ($_SESSION[APPCONFIG]->isCommandAvailable('schema')) {
	$all_attrs = array('' =>1, 'dn'=>1);
} else {
	$all_attrs = array('' =>1);
}
$entries_display = array();

/* Iterate over each entry and store the whole dang thing in memory (necessary to extract
 * all attribute names and display in table format in a single pass) */
$i=0;

foreach ($results as $dn => $dndetails) {
	$i++;
	if ($i <= $start_entry)
		continue;

	if ($i >= $end_entry)
		break;

	$edit_url = sprintf('cmd.php?cmd=template_engine&amp;server_id=%s&amp;dn=%s',$ldapserver->server_id,rawurlencode($dn));
	$attrs_display = array();
	$attrs_display[''] = sprintf('<center><a href="%s"><img src="images/%s" alt="icon" /></a></center>',$edit_url,get_icon($ldapserver,$dn));

	if ($_SESSION[APPCONFIG]->isCommandAvailable('schema')) {
		$dn_display = strlen($dn) > 40
		              ? sprintf('<acronym title="%s">%s...</acronym>',htmlspecialchars($dn),htmlspecialchars(substr($dn,0,40)))
		              : htmlspecialchars($dn);

		$attrs_display['dn'] = sprintf('<a href="%s">%s</a>',$edit_url,$dn_display);
	}

	# Iterate over each attribute for this entry and store in associative array $attrs_display
	foreach ($dndetails as $attr => $values) {
		# Ignore DN, we've already displayed it.
		if ($attr == 'dn')
			continue;

		$attr_display = $_SESSION[APPCONFIG]->getFriendlyHTML($attr);

		if (! isset($all_attrs[$attr_display]))
			$all_attrs[$attr_display] = 1;

		# Get the values
		$display = '';

		if ($ldapserver->isJpegPhoto($attr)) {
			ob_start();
			draw_jpeg_photos($ldapserver,$dn,$attr,false,false,'align="center"');
			if (ob_get_level()) {
				$display = ob_get_contents();
				ob_end_clean();
			}

		} elseif ($ldapserver->isAttrBinary($attr)) {
			$display = array('(binary)');

		} else {
			if (! is_array($values))
				$display .= str_replace(' ','&nbsp;',htmlspecialchars($values)).'<br />';
			else
				foreach ($values as $value )
					$display .= str_replace(' ','&nbsp;',htmlspecialchars($value)).'<br />';
		}

		$attrs_display[$attr_display] = $display;
	}

	$entries_display[] = $attrs_display;
}

$all_attrs = array_keys($all_attrs);

# Store the header row so it can be repeated later
$header_row = '<tr>';
foreach ($all_attrs as $attr)
	$header_row .= sprintf('<th>%s</th>',$attr);
$header_row .= '</tr>';

# Begin drawing table
echo '<br />';
echo '<center>';
echo '<table class="search_result_table">';

for ($i=0;$i<count($entries_display);$i++) {
	$result = $entries_display[$i];

	if ($i %10 == 0)
		echo $header_row;

	if ($i % 2 == 0 )
		echo '<tr class="highlight">';
	else
		echo '<tr>';

	foreach ($all_attrs as $attr) {
		echo '<td>';
		if (isset($result[$attr]))
			echo $result[$attr];
		echo '</td>';
	}
	echo '</tr>';
}

echo '</table>';
echo '</center>';
?>

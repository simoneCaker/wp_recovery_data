<?php
/*
	Plugin Name: Recovery WP Options
	Plugin URI: http://www.wordpress.org/extend/plugins/recovery_wp_options
	Description: Restore option of all wordpress plugins, menus and widgets if you transfered your wordpress site to another server.
	Version: 1.0
	Author: Simone Cannella
	Author URI: http://www.new-way.it/
*/

/* Motore */
function recovery($string) {
	$tmp = explode(':"', $string);
	$length = count($tmp);
	for($i = 1; $i < $length; $i++) {
		list($string) = explode('"', $tmp[$i]);
		$str_length = strlen($string);
		$tmp2 = explode(':', $tmp[$i-1]);
		$last = count($tmp2) - 1;
		$tmp2[$last] = $str_length;
		$tmp[$i-1] = join(':', $tmp2);
	}
	return join(':"', $tmp);
}

function is_serial_ok($string) {
	return (@unserialize($string) !== false);
}

function get_table_options() {
	global $wpdb;
	$count = 0;
	$serial = 0;
	$fail = 0;
	$html = "<div style='width:600px; height:300px; overflow:scroll; border:1px solid #ccc;' >";
	
	$total = $wpdb->query('Select option_value from ' . $wpdb->options . ' where CHAR_LENGTH(option_value) > 15 ');
	
	echo '<p>';
	if($total === false) 
	{
		echo 'No fields to recovery';
		return;
	}
	echo "Total field founds: $total<br>";
	
	$fields = $wpdb->get_results( $wpdb->prepare( 'Select option_id,option_value from ' . $wpdb->options . ' where CHAR_LENGTH(option_value) > %d ', 8), ARRAY_A );
	
	foreach($fields as $field) {
		if(is_serialized($field['option_value'])) {
			if(!is_serial_ok($field['option_value'])) {
				$data_recovered = recovery($field['option_value']);
				$serial++;
				if($wpdb->update(
						$wpdb->options,
						array(
								'option_value' => $data_recovered,	// stringa
						),
						array( 'option_id' => $field['option_id'] ),
						array(
								'%s'	// valore1
						),
						array( '%d' )
				)) 
				{
					$html .= "<span style='color:green'>Option_id {$field['option_id']} recovered!</span><br/>";
					$count++;
				}
				else {
					$html .= "<span style='color:red'>Option_id {$field['option_id']} fail recovered!</span><br/>";
					$fail++;
				}
				
			} else {
				$html .= "<span style='color:#999'>Option_id {$field['option_id']} no need recovered!</span><br/>";
			}
			
		 }
	}

	$html .= "<span style='color:orange'>Posts Meta Table recover</span><br/>";
	$fields = $wpdb->get_results( $wpdb->prepare( 'Select meta_key, meta_value from ' . $wpdb->postmeta . ' where CHAR_LENGTH(option_value) > %d ', 8), ARRAY_A );
	
	foreach($fields as $field) {
		if(is_serialized($field['meta_value'])) {
			if(!is_serial_ok($field['meta_value'])) {
				$data_recovered = recovery($field['meta_value']);
				$serial++;
				if($wpdb->update(
						$wpdb->postmeta,
						array(
								'meta_value' => $data_recovered,	// stringa
						),
						array( 'meta_key' => $field['meta_key'] ),
						array(
								'%s'	// valore1
						),
						array( '%d' )
				)) 
				{
					$html .= "<span style='color:green'>Option_id {$field['meta_key']} recovered!</span><br/>";
					$count++;
				}
				else {
					$html .= "<span style='color:red'>Option_id {$field['meta_key']} fail recovered!</span><br/>";
					$fail++;
				}
				
			} else {
				$html .= "<span style='color:#999'>Option_id {$field['meta_key']} no need recovered!</span><br/>";
			}
			
		 }
	}
	
	$html .= "</div>";
	
	echo $html;
	
	echo "<h2>Final Report:</h2>";
	echo "<ul>";
	echo "<li>Total fields: $total</li>";
	echo "<li>Real fields to recovery: $serial</li>";
	echo "<li>Fields recovered: $count</li>";
	echo "<li>Fields Fails: $fail</li>";
	echo "</ul>";
	
	echo '</p>';
}

/* Add Menu voice */
add_action('admin_menu', 'wp_recovery_menu');
function wp_recovery_menu() {


	$page_title		=	'Recovery WP Options';
	$menu_title		=	'Recovery WP Op';
	$capability		=	'manage_options';
	$menu_slug		=	'recovery_wp_options';
	$function		=	'recovery_wp_options';
	$icon			=	plugin_dir_url( __FILE__ ).'icon.png';
	add_menu_page($page_title,$menu_title,$capability,$menu_slug,$function,$icon);
}

/* Page */
function recovery_wp_options() {
	echo '<div class="wrap">';
?>
		<h2>Recovery WP Options</h2>
		<small>Click recovery button to recover you data</small>
		<form method="post" action="?page=recovery_wp_options">
			<input type="hidden" name="action" value="recovery">
			<input name="submit" type="submit" class="button" value="Recovery!">
		</form>
<?php 
		if($_REQUEST['action'] == 'recovery') {
			get_table_options();
		}
	echo '</div>';
}

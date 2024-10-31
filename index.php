<?php
/*
	Plugin Name: (NPC) NeatekPostConnect
	Description: Many-to-many connections
	Version: 1.0.2
	Author: Vladimir Zhelnov
	Author URI: https://neatek.ru/
	Text Domain: neatek-post-connect
*/
/** NEATEK - Vladimir Zhelnov - 24.01.2018 **/
define('NPC_SUPPORT_ALTERNATIVE', true);
//define('NPC_DISABLE_MAIN_ALGORITHM', false);
define('NPC_TEXTDOMAIN', 'neatek-post-connect');
/*define('array('revision', 'attachment', 'nav_menu_item', 'custom_css', 'customize_changeset', 'acf-field-group', 'acf-field', 'wpcf7_contact_form' )', array('revision', 'attachment', 'nav_menu_item', 'custom_css', 'customize_changeset', 'acf-field-group', 'acf-field', 'wpcf7_contact_form' ));*/

add_action('plugins_loaded', 'npc_load_textdomain');
function npc_load_textdomain() {
	load_plugin_textdomain( NPC_TEXTDOMAIN, false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
}

function npc_getposttypes() {
	return npc_clear_posttypes(get_post_types());
}

function npc_clear_posttypes($types) {
	foreach ($types as $key => $value) {
		if(in_array($value, array('revision', 'attachment', 'nav_menu_item', 'custom_css', 'customize_changeset', 'acf-field-group', 'acf-field', 'wpcf7_contact_form' ))) {
			unset($types[$key]);
		}
	}
	return $types;
}

function npc_minify_my_metabox( $classes ) {
	array_push( $classes, 'closed' );
	return $classes;
}

function npc_metabox() {
	$screens = npc_get_types();
	if(!empty($screens)) {
		foreach ( $screens as $screen ) {
			
			if(in_array($screen, array('revision', 'attachment', 'nav_menu_item', 'custom_css', 'customize_changeset', 'acf-field-group', 'acf-field', 'wpcf7_contact_form' ))) 
				continue;
			
			foreach (npc_get_types_by_type($screen) as $key => $connected_type) {
				add_meta_box( 'npc_sections_'.$connected_type, ''.__( 'Connect posts', NPC_TEXTDOMAIN ).' - '.$connected_type, 'npc_metabox_cb', $screen, 'side', 'default', array('post_type' => $connected_type) );

				add_filter( "postbox_classes_".$screen."_npc_sections_".$connected_type, 'npc_minify_my_metabox' );
			}
		}
	}
}
add_action('add_meta_boxes', 'npc_metabox');

function npc_metabox_cb($post, $metabox) {
	//wp_nonce_field( plugin_basename(__FILE__), 'myplugin_noncename' );
	$connected = npc_get_post_ids($post->ID, $metabox['args']['post_type']);
	// npc_get_post_ids_alt($post->ID, $metabox['args']['post_type'])
	echo '<div id="npc_posts_'.$metabox['args']['post_type'].'" class="npc_box">';
	$args = array(
		'post_type' => $metabox['args']['post_type'],
		'posts_per_page' => 30,
		'post__not_in' => $connected
	);
	//$query = new WP_Query( $args );
	//npc_print_posts($query);
	npc_print_posts(get_posts($args));
	echo '</div><h2 style="padding-left:0;"><b>'.__( 'Searching', NPC_TEXTDOMAIN ).'</b></h2><div>
		<input type="text" id="npc_search_'.$metabox['args']['post_type'].'" name="npc_search_'.$metabox['args']['post_type'].'" placeholder="'.__( 'Enter title', NPC_TEXTDOMAIN ).'" style="width:100%;">
		<p><input id="npc_dosearch_'.$metabox['args']['post_type'].'" type="button" name="npc_dosearch_'.$metabox['args']['post_type'].'" class="button button-primary" value="'.__( 'Search', NPC_TEXTDOMAIN ).'"></p>
	</div>';


	echo '<b>'.__( 'Connected posts', NPC_TEXTDOMAIN ).'</b>
	<div id="npc_connected_'.$metabox['args']['post_type'].'" class="npc_box">
	';
	if(!empty($connected)) {
		$args = array(
			'post_type' => $metabox['args']['post_type'],
			'posts_per_page' => -1,
			'post__in' => $connected
		);
		//$query = new WP_Query( $args );
		//npc_print_posts($query, true); // query, unlink (true/false)
		npc_print_posts(get_posts($args), true);
	} else {
		echo __( 'Sorry, posts not found.', NPC_TEXTDOMAIN );
	}
	echo '</div>';
?>
	<script type="text/javascript" >
	jQuery(document).ready(function($) {
		$( "#npc_dosearch_<?= $metabox['args']['post_type'] ?>" ).click(function( event ) {
			var data = {action: 'npc_search_callback', npc_search: $('#npc_search_<?= $metabox['args']['post_type'] ?>').val(), post_id: '<?= $post->ID; ?>', cache: false, post_type: '<?= $metabox['args']['post_type'] ?>'}; 
			jQuery.post( ajaxurl, data, function(response) {
				$('#npc_posts_<?= $metabox['args']['post_type'] ?>').html(response);
			});
		});
		$('body').on('click', 'a.npc_connect_<?= $metabox['args']['post_type'] ?>', function() {
			var to_id = $(this).attr('id').replace('npc_connect_','');
			var data = {action: 'npc_connectposts_callback', post_id: '<?= $post->ID; ?>', cache: false, to_post_id: to_id } 
			jQuery.post( ajaxurl, data, function(response) {
				var data = {action: 'npc_ajax_reloadlist_callback', post_id: '<?= $post->ID; ?>', cache: false, post_type: '<?= $metabox['args']['post_type'] ?>'}; 
				jQuery.post( ajaxurl, data, function(response) {
					$('#npc_posts_<?= $metabox['args']['post_type'] ?>').html(response);
					
					var data = {action: 'npc_ajax_reloadconnected_callback', post_id: '<?= $post->ID; ?>', cache: false, post_type: '<?= $metabox['args']['post_type'] ?>'}; 
					jQuery.post( ajaxurl, data, function(response) {
						console.log('npc_ajax_reloadconnected_callback');
						console.log(response);
						$('#npc_connected_<?= $metabox['args']['post_type'] ?>').html(response);
					});
				});
			});

			event.preventDefault();
		});
		$('body').on('click', 'a.npc_unlink_<?= $metabox['args']['post_type'] ?>', function() {
			var to_id = $(this).attr('id').replace('npc_connect_','');
			console.log(to_id);
			var data = {action: 'npc_unconnectposts_callback', post_id: '<?= $post->ID; ?>', cache: false, to_post_id: to_id } 
			jQuery.post( ajaxurl, data, function(response) {
				var data = {action: 'npc_ajax_reloadlist_callback', post_id: '<?= $post->ID; ?>', cache: false, post_type: '<?= $metabox['args']['post_type'] ?>'}; 
				jQuery.post( ajaxurl, data, function(response) {
					$('#npc_posts_<?= $metabox['args']['post_type'] ?>').html(response);
					var data = {action: 'npc_ajax_reloadconnected_callback', post_id: '<?= $post->ID; ?>', cache: false, post_type: '<?= $metabox['args']['post_type'] ?>'}; 
					jQuery.post( ajaxurl, data, function(response) {
						console.log('npc_ajax_reloadconnected_callback');
						console.log(response);
						$('#npc_connected_<?= $metabox['args']['post_type'] ?>').html(response);
					});
				});
			});
			event.preventDefault();
		});
	});
	</script>
<?php
}

add_action('wp_ajax_npc_search_callback', 'npc_ajax_search_callback');
add_action('wp_ajax_npc_connectposts_callback', 'npc_connectposts_callback');
add_action('wp_ajax_npc_unconnectposts_callback', 'npc_unconnectposts_callback');
add_action('wp_ajax_npc_ajax_reloadlist_callback', 'npc_ajax_reloadlist_callback');
add_action('wp_ajax_npc_ajax_reloadconnected_callback', 'npc_ajax_reloadconnected_callback');
//add_action('wp_ajax_nopriv_my_action', 'my_action_callback');
function npc_ajax_reloadconnected_callback() {
	$post_type = sanitize_text_field($_POST['post_type']);
	$post_id = sanitize_text_field($_POST['post_id']);
	$connected = npc_get_post_ids($post_id, $post_type);
	if(!empty($connected)) {
		$args = array(
			'post_type' => $post_type,
			'posts_per_page' => -1,
			'post__in' => $connected
		);
		//$query = new WP_Query( $args );
		//npc_print_posts($query,true);
		npc_print_posts(get_posts($args),true);
	}
	else {
		echo __( 'Sorry, posts not found.', NPC_TEXTDOMAIN );
	}
	wp_die();
}

function npc_unconnectposts_callback() {
	$post_id = (int) sanitize_text_field($_POST['post_id']);
	$to_post_id = (int) sanitize_text_field($_POST['to_post_id']);
	npc_unconnect_posts($post_id, $to_post_id);
	wp_die();
}

function npc_connectposts_callback() {
	$post_id = (int) sanitize_text_field($_POST['post_id']);
	$to_post_id = (int) sanitize_text_field($_POST['to_post_id']);
	npc_connect_posts($post_id, $to_post_id);
	wp_die();
}

function npc_ajax_reloadlist_callback() {
	$post_type = sanitize_text_field($_POST['post_type']);
	$post_id = sanitize_text_field($_POST['post_id']);
	$connected = npc_get_post_ids($post_id, $post_type);
	$args = array(
		'post_type' => $post_type,
		'posts_per_page' => 30,
		'post__not_in' => $connected
	);
	//$query = new WP_Query( $args );
	//npc_print_posts($query);
	npc_print_posts(get_posts($args));
	wp_die();
}

function npc_ajax_search_callback() {
	$search = sanitize_text_field($_POST['npc_search']);
	$post_type = sanitize_text_field($_POST['post_type']);
	$post_id = sanitize_text_field($_POST['post_id']);
	$exclude_exists = npc_get_post_ids($post_id, $post_type);
	$args = array(
		'post_type' => $post_type,
		'cache_results' => false,
		's' => $search,
		'posts_per_page' => -1,
		'post__not_in' => $exclude_exists,
		//'orderby' => 'title',
		//'order' => 'DESC',
		'exact' => false
	);
	//$query = new WP_Query( $args );
	//$query =
	npc_print_posts(get_posts($args));
	wp_die();
}

function npc_print_posts($posts, $delete = false) {
	//if($query->have_posts()) {
	if(!empty($posts)) 
	{
		//while($query->have_posts()) {
			//$query->the_post();
		foreach ($posts as $key => $value) {
			//$post_type = get_post_type($value->);
			$post_type = $value->post_type;
			if(in_array($post_type, array('revision', 'attachment', 'nav_menu_item', 'custom_css', 'customize_changeset', 'acf-field-group', 'acf-field', 'wpcf7_contact_form' ))) {
				continue;
			}
			if(!empty($value->post_title)) {
				echo '<div class="npc-choise-post">';
				if(function_exists('qtranxf_use')) {
					echo qtranxf_use('en',$value->post_title);
				}
				else {
					echo strip_tags($value->post_title);
				}

				if($delete == false)
					echo '<a style="float:right;" href="#" class="npc_connect_'.$post_type.'" id="npc_connect_'.$value->ID.'">'.__( 'Link', NPC_TEXTDOMAIN ).'</a></div>';
				else
					echo '<a style="float:right;" href="#" class="npc_unlink_'.$post_type.'" id="npc_connect_'.$value->ID.'">'.__( 'Unlink', NPC_TEXTDOMAIN ).'</a></div>';
			}
		}
		//}
	}
	else { 
		echo __( 'Sorry, posts not found.', NPC_TEXTDOMAIN );
	}

/*	wp_reset_query();
	wp_reset_postdata();*/
}

add_action( 'admin_menu', 'npc_admin_menu' );
function npc_admin_menu() {
	add_options_page( 'NeatekPostConnect', 'NeatekPostConnect', 'manage_options', 'neatek-posts-connects', 'npc_plugin_options' );
}

function npc_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.', NPC_TEXTDOMAIN ) );
	}
	$npc_option = get_option( 'npc_connected_types' );
	if($_POST) {
		if(isset($_POST['del1']) && isset($_POST['del2'])) {
			if($_POST['del1'] != $_POST['del2']) {
				$npc_option = npc_get_connected_types();
				if(!empty($npc_option) && isset($npc_option[$_POST['del1']])) {
					foreach ($npc_option[$_POST['del1']] as $key => $value) {
						if(strcmp($_POST['del2'], $value) == 0) {
							unset($npc_option[$_POST['del1']][$key]);
							if(empty($npc_option[$_POST['del1']])) {
								unset($npc_option[$_POST['del1']]);
							}
						}
					}
					foreach ($npc_option[$_POST['del2']] as $key => $value) {
						if(strcmp($_POST['del1'], $value) == 0) {
							unset($npc_option[$_POST['del2']][$key]);
							if(empty($npc_option[$_POST['del2']])) {
								unset($npc_option[$_POST['del2']]);
							}
						}
					}
					update_option( 'npc_connected_types', json_encode($npc_option) );
				}
			}
		}
		if(isset($_POST['type1']) && isset($_POST['type2'])) {
			if($_POST['type1'] != $_POST['type2']) {
				$npc_option = get_option( 'npc_connected_types' );
				if(empty($npc_option)){
					$npc_option = array();
				}
				else {
					$npc_option = json_decode($npc_option, true, 512, JSON_OBJECT_AS_ARRAY);
				}
				if(!isset($npc_option[$_POST['type1']])) {
					$npc_option[$_POST['type1']] = array($_POST['type2']);
				}
				else {
					if(is_array($npc_option[$_POST['type1']])) {
						if(!in_array($_POST['type2'], $npc_option[$_POST['type1']]))
							$npc_option[$_POST['type1']][] = $_POST['type2'];
					}
				}
				if(!isset($npc_option[$_POST['type2']])) {
					$npc_option[$_POST['type2']] = array($_POST['type1']);
				}
				else {
					if(is_array($npc_option[$_POST['type2']])) {
						if(!in_array($_POST['type1'], $npc_option[$_POST['type2']]))
							$npc_option[$_POST['type2']][] = $_POST['type1'];
					}
				}
				update_option( 'npc_connected_types', json_encode($npc_option) );
			}
		}
	}
	echo '<div class="wrap"><h1>NeatekPostConnect</h1>
	<div class="welcome-panel" style="padding:20px;padding-top:0;">
	<p>'.__( 'Connect two types of posts', NPC_TEXTDOMAIN ).'</p>
	<form action="" method="post">
	<select name="type1">';
	$types = npc_getposttypes();
	foreach ($types as $key => $value) {
		echo '<option value="'.$value.'">'.$value.'</option>';
	}
	echo '</select><select name="type2">';
	$types = npc_getposttypes();
	foreach ($types as $key => $value) {
		echo '<option value="'.$value.'">'.$value.'</option>';
	}
	echo '</select>
	<input class="button button-primary" type="submit" value="'.__( 'Connect', NPC_TEXTDOMAIN ).'">
	</form>
	</div>
	<div class="welcome-panel" style="padding:20px;padding-top:0;">
	<p>'.__( 'Connected types', NPC_TEXTDOMAIN ).'</p><table>';
	$connected = npc_get_connected_types();
	if(!empty($connected))
		foreach ($connected as $post_type => $post_types) {
			//echo '<form method="post">'..'</form>';
			foreach ($post_types as $num => $p_type) {
				echo '<tr><form method="post"><td>'.$post_type.' => '.$p_type.'</td><td><input type="submit" class="button button-primary" value="'.__( 'Delete', NPC_TEXTDOMAIN ).'"><input type="text" name="del1" value="'.$post_type.'" hidden style="display:none;"><input type="text" name="del2" value="'.$p_type.'" hidden style="display:none;"></td></form></tr>';
			}
			
		}
	else {
		echo __( 'Sorry, connected types not found.', NPC_TEXTDOMAIN );
	}
	echo '</table></div></div>';
}

function npc_unconnect_posts_pre($id, $to_id) {
	$object_id = get_post( $id );
	$connect_id = get_post( $to_id );
	if(empty($object_id) || empty($connect_id)) {
		return false;
	}
	global $wpdb;
	$connected_posts = $wpdb->get_var('SELECT `connected_posts` FROM `neatek_post_connect` WHERE `object` = '.$object_id->ID.' AND `post_type` = \''.$object_id->post_type.'\' AND `connected_type`=\''.$connect_id->post_type.'\'');
	if(!empty($connected_posts)) {
		$connected_posts = explode(',', $connected_posts);
		$search = array_search($to_id, $connected_posts);
		if(!is_bool($search)) {
			unset($connected_posts[$search]);
			if(empty($connected_posts)) {
				$sql = "DELETE FROM `neatek_post_connect` WHERE `object` = '".$object_id->ID."' AND `post_type` = '".$object_id->post_type."' AND `connected_type`='".$connect_id->post_type."'";
				if(!is_bool($wpdb->query($sql))) {
					return true;
				}
			}
			else {
				$sql = "UPDATE `neatek_post_connect` SET `connected_posts`='".implode(',', $connected_posts)."' WHERE `object` = '".$object_id->ID."' AND `post_type` = '".$object_id->post_type."' AND `connected_type`='".$connect_id->post_type."'";
				if(!is_bool($wpdb->query($sql))) {
					return true;
				}
			}
		}
	}

	return false;
}

function npc_connect_posts_pre($id, $to_id) {
	$object_id = get_post( $id );
	$connect_id = get_post( $to_id );
	if(empty($object_id) || empty($connect_id)) {
		return false;
	}
	$allowed_types_connect = npc_get_types_by_id($id);
	if(empty($allowed_types_connect)) {
		return false;
	}
	if(in_array($connect_id->post_type, $allowed_types_connect)) {
		if(!is_array($to_id)) {
			$to_id = array($connect_id->ID);
		}
		global $wpdb;
		$connected_posts = $wpdb->get_var('SELECT `connected_posts` FROM `neatek_post_connect` WHERE `object` = '.$object_id->ID.' AND `post_type` = \''.$object_id->post_type.'\' AND `connected_type`=\''.$connect_id->post_type.'\'');
		if(empty($connected_posts)) {
			$imploded = implode(',', $to_id);
			$sql = "INSERT INTO `neatek_post_connect` (`id`, `object`, `post_type`, `connected_type`, `connected_posts`) VALUES (NULL, '".$object_id->ID."', '".$object_id->post_type."', '".$connect_id->post_type."', '".$imploded."');";
			if(!empty($imploded)) {
				$result = $wpdb->query($sql);
				if(!is_bool($result)) {
					npc_connect_posts($to_id, $id);
					return true;
				}
			}
			else {
				return false;
			}
		}
		else {
			$connected_posts = explode(',', $connected_posts);
			$connected_posts_before = $connected_posts;
			$connected_posts = array_merge($connected_posts, $to_id);
			$connected_posts = array_unique($connected_posts);
			if(!empty(array_diff($connected_posts, $connected_posts_before))) {
				$imploded = implode(',', $connected_posts);
				$sql = "UPDATE `neatek_post_connect` SET `connected_posts` = '".$imploded."' WHERE `object` = '".$object_id->ID."' AND `post_type` = '".$object_id->post_type."' AND `connected_type`='".$connect_id->post_type."';";
				if(!empty($imploded)) {
					$result = $wpdb->query($sql);
					if(!is_bool($result)) {
						npc_connect_posts($to_id, $id);
						return true;
					}
				}
				else  {
					return false;
				}
			}
		}
	}
}

function npc_unconnect_posts_alt_pre($id, $to_id) {
	$object = (int) $id;
	$connected = (int) $to_id;
	if(empty($object) || empty($connected)) return false;
	global $wpdb;
	$sql = "DELETE FROM `neatek_post_connect_alt` WHERE `object`=".$object." AND `connected`=".$connected;
	if(!is_bool($wpdb->query($sql))) return true;
	return false;
}

add_action( 'admin_print_footer_scripts', 'npc_print_footer_style' );
function npc_print_footer_style(){
?>
	<style type="text/css">
		.npc-choise-post {
			background-color: #fafafa;
			/* border-radius:5px; */
			border:1px rgba(0,0,0,.07) solid;
			margin-bottom:5px;
			overflow:auto;
			padding:4px;
			transition: 0.3s;
			margin:2px;
		}
		.npc-choise-post:hover {
			background-color: #7DB05B;
			color:#fff;
		}
		.npc-choise-post:hover a {
			color: #DEF0D3;
		}
		.npc-choise-post a {
			outline: 0;
		}
		.npc_box {
			overflow: auto;
			height:150px;
			overflow-y: scroll;
			border:1px #ccc solid;
		}
	</style>
<?php
}

register_activation_hook( __FILE__, 'npc_activate_plugin' );

function npc_activate_plugin() {
	/** Current **/
	$table = 'CREATE TABLE IF NOT EXISTS `neatek_post_connect` (
	  `id` int(11) NOT NULL,
	  `object` int(11) NOT NULL,
	  `post_type` varchar(48) NOT NULL,
	  `connected_type` varchar(48) NOT NULL,
	  `connected_posts` text NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
	global $wpdb;
	$wpdb->query($table);
	$wpdb->query('ALTER TABLE `neatek_post_connect` ADD PRIMARY KEY (`id`);');
	$wpdb->query('ALTER TABLE `neatek_post_connect` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');

	/** Alternative **/
	$table = 'CREATE TABLE `neatek_post_connect_alt` (
	  `id` int(11) NOT NULL,
	  `object` int(11) NOT NULL,
	  `object_type` varchar(50) NOT NULL,
	  `connected` int(11) NOT NULL,
	  `connected_type` varchar(50) NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
	global $wpdb;
	$wpdb->query($table);
	$wpdb->query('ALTER TABLE `neatek_post_connect_alt` ADD PRIMARY KEY (`id`);');
	$wpdb->query('ALTER TABLE `neatek_post_connect_alt` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');
}

function npc_filter_needle_type($connected_posts = array(), $need_type = '') {
	$return = array();
	foreach ($connected_posts as $key => $value) {
		if(strpos($value, $need_type) !== false) {
			$x = explode('_', $value);
			$return[] = (int) $x[0];
		}
	}
	return array_unique($return);
}

/***
	PLUGIN API
		Functions that could be used
***/
/** RETURN TYPES CONNECTED TO TYPE **/
function npc_get_types_by_type($type = '') {
	if(empty($type)) return array();
	$npc_option = npc_get_connected_types();
	if(isset($npc_option[$type])) return $npc_option[$type];
	else return array();
}

/** RETURN CONNECTED TYPES TO POST_ID **/
function npc_get_types_by_id($id = '') {
	$id = (int) $id;
	$post = get_post( $id );
	//var_dump($post->post_type);
	return npc_get_types_by_type($post->post_type);
}

/** RETURN CONNECTED TYPES **/
function npc_get_connected_types() {
	$npc_option = get_option( 'npc_connected_types' );
	if(!empty($npc_option)) {
		$npc_option = json_decode($npc_option, true, 512, JSON_OBJECT_AS_ARRAY);
		return $npc_option;
	}
	
	return false;
}

/** RETURN ALL TYPES WHICH HAVE CONNECTIONS **/
function npc_get_types() {
	$npc_option = npc_get_connected_types();
	$out = array();
	if(!empty($npc_option)) {
		foreach ($npc_option as $key => $value) {
			if(!empty($value)) {
				$out[]=$key; // array_keys
			}
		}
	}
	return $out;
}

/** CONNECT TWO POSTS id & id **/
function npc_connect_posts($id, $to_id) {
	if(defined('NPC_SUPPORT_ALTERNATIVE')) {
		npc_connect_posts_alt($id, $to_id);
	}
	//if(NPC_DISABLE_MAIN_ALGORITHM == false) {
	if(npc_connect_posts_pre($id, $to_id) == true && npc_connect_posts_pre($to_id, $id) == true) {
		return true;
	}
	//}
	return false;
}

/** UNLINK TWO POSTS id & id **/
function npc_unconnect_posts($id, $to_id) {
	if(defined('NPC_SUPPORT_ALTERNATIVE')) {
		npc_unconnect_posts_alt($id, $to_id);
	}
	//if(NPC_DISABLE_MAIN_ALGORITHM == false) {
	if(npc_unconnect_posts_pre($id, $to_id) == true && npc_unconnect_posts_pre($to_id, $id) == true) {
		return true;
	}
	//}
	return false;
}

/** UNLINK TWO POSTS (ALTERNATIVE) DB_TABLE - neatek_post_connect_alt **/
function npc_unconnect_posts_alt($id, $to_id) {
	if(npc_unconnect_posts_alt_pre($id, $to_id) == true && npc_unconnect_posts_alt_pre($to_id, $id) == true) {
		return true;
	}

	return false;
}

/** CONNECT TWO POSTS (ALTERNATIVE) DB_TABLE - neatek_post_connect_alt **/
function npc_connect_posts_alt($id, $to_id) {
	$object = (int) $id;
	$connected = (int) $to_id;
	$object_type = get_post_type($id);
	$connected_type = get_post_type($connected);
	if(empty($object) || empty($connected) || empty($object_type) || empty($connected_type)) return false;
	global $wpdb;
	$connected_posts = $wpdb->get_var('SELECT `id` FROM `neatek_post_connect_alt` WHERE `object` = '.$object.' AND `connected` = '.$connected.' AND `object_type`=\''.$object_type.'\' AND `connected_type`=\''.$connected_type.'\';');
	if(empty($connected_posts)) {
		$result = $wpdb->query("INSERT INTO `neatek_post_connect_alt` (`id`, `object`, `object_type`, `connected`, `connected_type`) VALUES (NULL, '".$object."', '".$object_type."', '".$connected."', '".$connected_type."')");
		if(!is_bool($result)) {
			npc_connect_posts_alt($to_id, $id);
			//return true;
		}
	}
	//return false;
}

/** RETURN CONNECTED POSTS BY TYPE (ALTERNATIVE) DB_TABLE - neatek_post_connect_alt  **/
function npc_get_post_ids_alt($post_id, $need_type = '') {
	$post_id = (int) $post_id;
	global $wpdb;
	$sql = "SELECT `connected` FROM `neatek_post_connect_alt` WHERE `object` = '".$post_id."' AND `object_type` = '".get_post_type($post_id)."' AND `connected_type` = '".$need_type."'";
	return $wpdb->get_col($sql);
}

/** RETURN CONNECTED POSTS BY TYPE **/
function npc_get_post_ids($post_id, $need_type = '') {
	$post_id = (int) $post_id;
	$type = get_post_type($post_id);
	if(empty($type)) return array();
	global $wpdb;
	$connected_posts = $wpdb->get_var('SELECT connected_posts FROM neatek_post_connect WHERE object = '.$post_id.' AND post_type = \''.$type.'\' AND `connected_type`=\''.esc_sql(sanitize_text_field($need_type)).'\'');
	if(empty($connected_posts)) {
		return array();
	}
	//$connected_posts = 
	return explode(',', $connected_posts);
	//npc_filter_needle_type($connected_posts, $need_type);
}

/** RETURN WP_QUERY OBJECT OF CONNECTED POSTS WITH NEEDLE TYPE **/
function npc_query_connected($post_id = '', $need_type = '', $overflow_args = array()) {
	return npc_query($post_id, $need_type, $overflow_args, false);
}

function npc_query($post_id = '', $need_type = '', $overflow_args = array(), $getposts = false) {
	$post_id = (int) $post_id;
	if(empty($need_type)) {
		//var_dump('test');
		if($getposts == true) {
			return array();
		}

		return new WP_Query();
	}
	global $wpdb;
	$connected_posts = npc_get_post_ids($post_id, $need_type);
	if(empty($connected_posts)) {
		//var_dump('test');
		if($getposts == true) {
			return array();
		}

		return new WP_Query();
	}
	$args = array(
		'post_type' => $need_type,
		'posts_per_page' => -1,
		'post__in' => $connected_posts
	);
	
	if(!empty($overflow_args)) {
		foreach ($overflow_args as $key => $value) {
			$args[$key] = $value;
		}
	}
	if($getposts == true) {
		return get_posts($args);
	}

	$query = new WP_Query( $args );
	return $query;

}

function npc_get_connected($post_id = '', $need_type = '', $overflow_args = array()) {
	return npc_query($post_id, $need_type, $overflow_args, true);
}
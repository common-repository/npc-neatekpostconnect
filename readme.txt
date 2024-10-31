=== [NPC] Neatek Post Connect (many-to-many) ===
Contributors: neatek
Donate link: https://neatek.ru/support/
Tags: many-to-many, posts2posts, connections, links, posts, relationships, custom post types
Requires at least: 4.6
Tested up to: 5.2.1
Stable tag: 5.2.1
Version: 1.1
License: GPLv2 or later
Requires PHP: 7.0 or high
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin can make Many-To-Many connections of posts.

== Description ==

This plugin can make Many-To-Many connections of posts (also custom posts), 
you can setup it in wp-admin/options-general.php?page=neatek-posts-connects

Plugin supports English and Russian languages.

Snippet to get connected posts:

`<?php
	// $post_id = get_the_id();
	$query = npc_query_connected($post_id, 'needed_post_type');
	while($query->have_posts()) {
		$query->the_post();
		the_title();
	}
?>`

It will get all connected posts with posttype 'needed_post_type' for $post_id ID Post.
Also, you can get_posts() connected posts.

`<?php
	// $post_id = get_the_id();
	$c_posts = npc_get_connected($post_id, 'needed_post_type');
	var_dump($c_posts);
?>`

You can modify WP_Query or get_posts() args:

`<?php
	$args = array(
		'posts_per_page' => 5
	);
	// $post_id = get_the_id();
	$c_posts = npc_get_connected($post_id, 'needed_post_type', $args);
?>`

Or you can just get IDS of connected posts:

`<?php
	// $post_id = get_the_id();
	$ids = npc_get_post_ids($post_id, 'needed_post_type');
	var_dump($ids);
?>`

Important: Do not use 'numberposts', use 'posts_per_page'.

The best alternative for:
https://ru.wordpress.org/plugins/posts-to-posts/

Thank you for using my plugin.

== Installation ==

Just install and press activate, and start setup.

1. Go to wp-admin/options-general.php?page=neatek-posts-connects and connect some types of posts.
1. Go to any posts in connected types.
1. Create link between posts.
1. Use Plugin functions in Theme.

== Changelog ==

= 1.0 =
First release.

== Upgrade Notice ==

= 1.0 =
Alternative for posts2posts.
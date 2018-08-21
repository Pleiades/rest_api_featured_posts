<?php
/**
 * Plugin Name: Featured Posts for REST API
 * Plugin URI: http://pleiadesservices.com	
 * Description: This plugin that lets authors mark a post as "Featured on Our Site" so that other sites can access them through the REST API. You can access this end point at http://yourwebsite.com/wp-json/featured_posts_for_rest_api/featured/.
 * Version: 1.0
 * Author: Nicholas Batik
 * Author URI: http://PleiadesServices.com
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU 
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume 
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package      featured_rest
 * @version 	 1.0
 * @author       Nicholas Batik <nbatik@PleiadesServices.com>
 * @copyright    Copyright (c) 2018, Nicholas Batik
 * @license      http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
?>

<?php
/**
 * Get 5 latest posts marked as "Featured"
 *
 */

function add_custom_meta_box() {
    
    add_meta_box("demo-meta-box", "REST API Featured Post", "rest_api_featured_meta", "post", "side", "high", null);
}

add_action("add_meta_boxes", "add_custom_meta_box");


add_action( 'rest_api_init', function () {
    register_rest_route( 'featured_posts_for_rest_api', 'featured', array(
			'methods'         => WP_REST_Server::READABLE,
			'callback' => 'rest_api_featured_posts',
		)
	);
} );



function rest_api_featured_meta($object) {

    wp_nonce_field(basename(__FILE__), "meta-box-nonce");

    ?>
        <div>
            <label for="meta-box-checkbox">Featured Post: </label>
            <?php
                $checkbox_value = get_post_meta($object->ID, "meta-box-checkbox", true);

                if($checkbox_value == "") {
                    ?>
                        <input name="meta-box-checkbox" type="checkbox" value="true">
                    <?php
                }
                else if($checkbox_value == "true") {
                    ?>  
                        <input name="meta-box-checkbox" type="checkbox" value="true" checked>
                    <?php
                }
            ?>
        </div>
    <?php  
}


function save_rest_api_custom_meta($post_id, $post, $update) {

    if (!isset($_POST["meta-box-nonce"]) || !wp_verify_nonce($_POST["meta-box-nonce"], basename(__FILE__)))
        return $post_id;

    if(!current_user_can("edit_post", $post_id))
        return $post_id;

    if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
        return $post_id;

    $slug = "post";
    if($slug != $post->post_type)
        return $post_id;

    $meta_box_checkbox_value = "";

    if(isset($_POST["meta-box-checkbox"]))
    {
        $meta_box_checkbox_value = $_POST["meta-box-checkbox"];
    }   
    update_post_meta($post_id, "meta-box-checkbox", $meta_box_checkbox_value);
}

add_action("save_post", "save_rest_api_custom_meta", 10, 3);

function rest_api_featured_posts() {
            
    $args = array (
    	'post_type'      => 'post',
    	'posts_per_page' => '5',
    	'meta_key'       => 'meta-box-checkbox',
    	'mate_value'     => true,
    );
    $posts_list = get_posts( $args );
    
    $post_data = array();
    
    foreach( $posts_list as $posts) {
        
        $post_id = $posts->ID;
        
        $post_data[ $post_id ][ 'author' ]  = $posts->post_author;
        $post_data[ $post_id ][ 'title' ]   = $posts->post_title;
        $post_data[ $post_id ][ 'content' ] = $posts->post_content;
        $post_data[ $post_id ][ 'images' ]  = featured_routes_post_images( $post_id );
    }
    
    wp_reset_postdata();
    
    return rest_ensure_response( $post_data );
}

function featured_routes_post_images( $post_id ) {
	
	$args = array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'post_status'    => null,
		'posts_per_page' => -1,
		'order'          => 'ASC',
		'post_parent'    => $post_id,
	);
	
	$attachments = get_posts( $args );
	
	if ( $attachments ) {
		
		foreach ( $attachments as $attachment ) {
			$src = wp_get_attachment_image_src( $attachment->ID );
			if ( $src ) { $images[] = $src[0]; }
		}
		return json_encode( $images );
		
	} else {
		
		return false;
	}
} 

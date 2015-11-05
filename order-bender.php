<?php
/*
Plugin Name: Order Bender
Plugin URI: http://mtekk.us/code/
Description: Adds a metabox that allows you to set the prefered hierarchical taxonomy term for a post.
Version: 0.6.0
Author: John Havlik
Author URI: http://mtekk.us/
License: GPL2
TextDomain: mtekk-order-bender
DomainPath: /languages/
*/
/*  Copyright 2012-2015  John Havlik  (email : john.havlik@mtekk.us)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/**
 * The plugin class 
 */
class mtekk_order_bender
{
	protected $version = '0.6.0';
	protected $full_name = 'Order Bender';
	protected $short_name = 'Order Bender';
	protected $access_level = 'manage_options';
	protected $identifier = 'mtekk_order_bender';
	protected $unique_prefix = 'mob';
	protected $plugin_basename = 'order-bender/order-bender.php';
	/**
	 * mlba_video
	 * 
	 * Class default constructor
	 */
	function __construct()
	{
		//We set the plugin basename here, could manually set it, but this is for demonstration purposes
		$this->plugin_basename = plugin_basename(__FILE__);
		add_action('add_meta_boxes', array($this, 'meta_boxes'), 2, 10);
		add_filter('get_the_terms', array($this, 'reorder_terms'), 3, 10);
		add_action('save_post', array($this, 'save_post'));
	}
	/**
	 * Function that fires on the add_meta_boxes action
	 * 
	 * @param string $post_type The name of the post type which the current post being edited
	 * @param WP_Post $post The WP_Post object for the current post being edited
	 */
	function meta_boxes($post_type, $post)
	{
		global $wp_taxonomies;
		foreach($wp_taxonomies as $taxonomy)
		{
			if($taxonomy->hierarchical && in_array($post_type, $taxonomy->object_type))
			{
				//Add our primary category metabox for the current post type
				add_meta_box($this->unique_prefix . '_' . $taxonomy->name . '_primary_term_div', sprintf(__('Primary %s', 'mtekk-order-bender'), $taxonomy->labels->singular_name), array($this,'primary_taxonomy_term_meta_box'), $post_type, 'side', 'low', array($taxonomy));
			}
		}
	}
	/**
	 * This function outputs the primary category metabox
	 * 
	 * @param WP_Post $post The post object for the post being edited
	 * @param array $callback_args The callback argument array
	 */
	function primary_taxonomy_term_meta_box($post, $callback_args)
	{
		//Grab our taxonomy from the args
		$taxonomy = $callback_args['args'][0];
		//Nonce this bad boy up
		wp_nonce_field($this->plugin_basename, $this->unique_prefix . '-' . $taxonomy->name . '-prefered-nonce');
		$pref_id = get_post_meta($post->ID, $this->unique_prefix .'_' . $taxonomy->name . '_prefered', true);
		//Need inline style to keep our category drop down from doing bad things width wise
		echo '<style>.' . $this->unique_prefix . '_primary_term{max-width: 100%;}</style>';
		wp_dropdown_categories(array(
			'name' => $this->unique_prefix . '_' . $taxonomy->name . '_primary_term',
			'id' => $this->unique_prefix . '_' . $taxonomy->name . '_primary_term',
			'class' => $this->unique_prefix . '_primary_term',
			'echo' => 1,
			'depth' => 0,
			'hierarchical' => 1,
			'show_option_none' => __( '&mdash; Select &mdash;' ),
			'option_none_value' => '0',
			'selected' => $pref_id,
			'taxonomy' => $taxonomy->name));
	}
	/**
	 * This function hooks into the save_post action and saves our prefered category
	 * 
	 * @param int $post_id The ID of the post that was just saved
	 */
	function save_post($post_id)
	{
		global $wp_post_types, $wp_taxonomies;
		foreach($wp_post_types as $post_type)
		{
			foreach($wp_taxonomies as $taxonomy)
			{
				if($taxonomy->hierarchical && in_array($post_type->name, $taxonomy->object_type))
				{
					//Exit early if we don't have our data
					if(!isset($_POST[$this->unique_prefix . '_' . $taxonomy->name . '_primary_term']))
					{
						continue;
					}
					//Exit early if the nonce fails
					if(!wp_verify_nonce($_POST[$this->unique_prefix . '-' . $taxonomy->name . '-prefered-nonce'], $this->plugin_basename))
					{
						continue;
					}
					//Grab the prefered category ID
					$prefered_term = absint($_POST[$this->unique_prefix . '_' . $taxonomy->name . '_primary_term']);
					//Save the prefered category as a postmeta
					update_post_meta($post_id, $this->unique_prefix .'_' . $taxonomy->name . '_prefered', $prefered_term);
				}
			}
		}
	}
	/**
	 * This function changes the order of the input terms to place a prefered term at the top
	 * 
	 * @param array $terms The array of WP_Term objects
	 * @param int $post_id The ID of the post in question
	 * @param string $taxonomy The taxonomy of the term in question
	 */
	function reorder_terms($terms, $post_id, $taxonomy)
	{
		//Get the prefered taxonomy term for the post here
		$pref_id = get_post_meta($post_id, $this->unique_prefix . '_' . $taxonomy . '_prefered', true);
		//Have to iterate over all of the terms, searching for the prefered term
		foreach($terms as $key => $term)
		{
			//If the current term is the prefered term, move it to the front of the array
			if($term->term_id === (int) $pref_id)
			{
				//Remove the term from the array
				unset($terms[$key]);
				//Place in the front of the array
				array_unshift($terms, $term);
				//And, now we're done
				break;
			}
		}
		//Return the array
		return $terms;
	}
}
$mtekk_order_bender = new mtekk_order_bender();
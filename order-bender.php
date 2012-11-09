<?php
/*
Plugin Name: Order Bender
Plugin URI: http://mtekk.us/code/
Description: Adds a metabox that allows you to set a page as the parent of a post
Version: 0.1.0
Author: John Havlik
Author URI: http://mtekk.us/
License: GPL2
TextDomain: mtekk-order-bender
DomainPath: /languages/
*/
/*  Copyright 2012  John Havlik  (email : mtekkmonkey@gmail.com)

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
	protected $version = '0.2.0';
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
		add_action('add_meta_boxes', array($this, 'meta_boxes'));
		add_filter('get_the_terms', array($this, 'reorder_terms'), 3, 10);
		add_action('save_post', array($this, 'save_post'));
	}
	/**
	 * Function that fires on the add_meta_boxes action
	 */
	function meta_boxes()
	{
		global $wp_post_types;
		foreach($wp_post_types as $post_type)
		{
			//We only want custom post types that are public
			if(!$post_type->_builtin && $post_type->public)
			{
				//Add our primary category metabox for the current post type
				add_meta_box('postparentdiv', __('Primary Category', 'mtekk-order-bender'), array($this,'primary_category_meta_box'), $post_type->name, 'side', 'low');
			}
		}
		//Add our primary category metabox for posts
		add_meta_box('postparentdiv', __('Primary Category', 'mtekk-order-bender'), array($this,'primary_category_meta_box'), 'post', 'side', 'low');
	}
	/**
	 * This function outputs the primary category metabox
	 * 
	 * @param WP_Post $post The post object for the post being edited
	 */
	function primary_category_meta_box($post)
	{
		//Nonce this bad boy up
		wp_nonce_field($this->plugin_basename, $this->unique_prefix . '-category-prefered-nonce');
		$pref_id = get_post_meta($post->ID, $this->unique_prefix . '_category_prefered', true);
		//Need inline style to keep our category drop down from doing bad things width wise
		echo "<style>#primary_cat{max-width: 100%;}</style>";
		wp_dropdown_categories(array(
			'name' => $this->unique_prefix . '_primary_cat',
			'id' => 'primary_cat',
			'echo' => 1,
			'show_option_none' => __( '&mdash; Select &mdash;' ),
			'option_none_value' => '0',
			'selected' => $pref_id));
	}
	/**
	 * This function hooks into the save_post action and saves our prefered category
	 * 
	 * @param int $post_id The ID of the post that was just saved
	 */
	function save_post($post_id)
	{
		//Exit early if we don't have our data
		if(!isset($_POST[$this->unique_prefix . '_primary_cat']))
		{
			return;
		}
		//Exit early if the nonce fails
		if(!wp_verify_nonce($_POST[$this->unique_prefix . '-category-prefered-nonce'], $this->plugin_basename))
		{
			return;
		}
		//Grab the prefered category ID
		$prefered_category = absint($_POST[$this->unique_prefix . '_primary_cat']);
		//Save the prefered category as a postmeta
		update_post_meta($post_id, $this->unique_prefix . '_category_prefered', $prefered_category);
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
		//Get the prefered category for the post here
		$pref_id = get_post_meta($post_id, $this->unique_prefix . '_' . $taxonomy . '_prefered', true);
		//Make sure that ID is in the array
		if(array_key_exists($pref_id, $terms))
		{
			//Store our prefered term
			$perf_term = array($pref_id => $terms[$pref_id]);
			//Remove it from the array
			unset($terms[$pref_id]);
			//Recombine the array
			$terms = $perf_term + $terms;
		}
		//Return the array
		return $terms;
	}
}
$mtekk_order_bender = new mtekk_order_bender();
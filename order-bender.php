<?php
/*
Plugin Name: Order Bender
Plugin URI: http://mtekk.us/code/
Description: Adds a metabox that allows you to set a page as the parent of a post
Version: 0.0.1
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
	protected $version = '0.0.3';
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
	}
	/**
	 * Function that fires on the add_meta_boxes action
	 */
	function meta_boxes()
	{
		//Add our post parent metabox
		add_meta_box('postparentdiv', __('Parent', 'mtekk-post-parents'), array($this,'parent_meta_box'), 'post', 'side', 'low');
	}
	/**
	 * This function outputs the post parent metabox
	 * 
	 * @param WP_Post $post The post object for the post being edited
	 */
	function parent_meta_box($post)
	{
		//If we use the parent_id we can sneak in with WP's styling and post save routines
		wp_dropdown_pages(array(
			'name' => 'parent_id',
			'id' => 'parent_id',
			'echo' => 1,
			'show_option_none' => __( '&mdash; Select &mdash;' ),
			'option_none_value' => '0',
			'selected' => $post->post_parent)
		);
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
		get_post_meta($post_id, $this->unique_prefix . '_' . '$taxonomy' . '_prefered', true);
		$pref_id = 16;
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
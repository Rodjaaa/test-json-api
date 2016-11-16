<?php
/**
 * Plugin Name: JSON Api
 * Description: Test for Nolte
 * Author: Nikola Djordjevic
 * Author URI: http://github.com/airesvsg
 * Version: 0.0.1
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'JSON_Api' ) ) {
	class JSON_Api {
		
		public function __construct(){
			$this->add_cpt();				
			$this->add_meta();
			$this->add_scode();
			$this->export_json();
		}
		public function meta_fields(){
			return array(
					'poster_url' => array(
										'id' => 'poster_url', 
										'title' => 'Poster URL'
									),
					'year' => array(
									'id' => 'year',
									'title' => 'Year of release'
									),
					'rating' => array(
									'id' => 'rating',
									'title' => 'Rating'
									),
					'short_description' => array(
											'id' => 'short_description',
											'title' => 'Short HTML of movie'
											)
						);
		}
		private function meta_fields_as_array($prefix = ""){
			$meta_array = array();
			foreach($this->meta_fields() as $k=>$v){
				$meta_array[] = $prefix.$k;
			}
			return $meta_array;
		}
		public function add_cpt(){
			add_action( 'init', array ( $this, 'cpt' ) );			
		}
		private function add_meta(){
			add_action('add_meta_boxes', array( $this, 'metaboxes') );
			add_action('save_post', array( $this, 'save') );
			add_action('the_content', array($this, 'display_meta') );
		}
		public function add_scode(){
			//add_action( 'pre_get_posts', array( $this, 'add_movies_to_query') );
			add_shortcode( 'list-movies', array($this, 'shortcode_render') );
			add_action( 'wp_enqueue_scripts', array( $this, 'register_shortcode_styles' ) );
		}
		private function export_json(){
			add_action( 'init', array($this, 'json_rewrite') ); 
			add_action( 'template_redirect', array( $this, 'json_data') );
		}
		public function add_movies_to_query($query){
			
			if ( is_home() && $query->is_main_query() )
				$query->set( 'post_type', array( 'post', 'movies' ) );
			
			return $query;
		}
		public function shortcode_render(){
			$output = '';
			$args = array(
				'post_type'      => 'movies',
				'posts_per_page' => 10				
				);
			$movie_query = new WP_Query( $args );			
			if ( $movie_query->have_posts() ) : while ( $movie_query->have_posts() ) : $movie_query->the_post();
				$output .= '<div class="json-row">
								<div class="json-col-1">
									<img src="'. get_post_meta(get_the_ID(),'_movie_poster_url', true). ' alt="'. get_the_title() .'">
								</div>
								<div class="json-col-2">
									<h3>'. get_the_title() .'</h3>
									<p>'. get_post_meta(get_the_ID(),'_movie_short_description',true) .'</p>
									<p>
										<span class="json-span">Rating: '. get_post_meta(get_the_ID(),'_movie_rating', true) .'</span>
										<span class="json-span">Year Published: '. get_post_meta(get_the_ID(),'_movie_year', true) .'</span>
									</p>
								</div>
								<div class="json-col-3">
									<a href="'.get_permalink() .'">Read more</a>
								</div>
							</div>';
							;
			endwhile;
			else :
				$output = 'no movies';
			endif;
			return '<div class="json-row">'. $output. '</div>';
		}
		
		public function cpt(){
			$labels = array(
				'name' => _x( 'Movies json', 'movies' ),
				'singular_name' => _x( 'Movies json', 'movies' ),
				'add_new' => _x( 'Add New', 'movies' ),
				'add_new_item' => _x( 'Add New Movies json', 'movies' ),
				'edit_item' => _x( 'Edit Movies json', 'movies' ),
				'new_item' => _x( 'New Movies json', 'movies' ),
				'view_item' => _x( 'View Movies json', 'movies' ),
				'search_items' => _x( 'Search Movies json', 'movies' ),
				'not_found' => _x( 'No Movies json found', 'movies' ),
				'not_found_in_trash' => _x( 'No Movies json found in Trash', 'movies' ),
				'parent_item_colon' => _x( 'Parent Movies json:', 'movies' ),
				'menu_name' => _x( 'Movies json', 'movies' ),
			);
 
			$args = array(
				'labels' => $labels,
				'hierarchical' => true,
				'description' => 'Movies json filterable by genre',
				'supports' => array( 'title', 'author' ),
				'taxonomies' => array( 'genres' ),
				'public' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'menu_position' => 5,
				'menu_icon' => 'dashicons-format-video',
				'show_in_nav_menus' => true,
				'publicly_queryable' => true,
				'exclude_from_search' => false,
				'has_archive' => true,
				'query_var' => true,
				'can_export' => true,
				'rewrite' => true,
				'capability_type' => 'post'				
			);
			register_post_type( 'movies', $args );
		}
		public function metaboxes($post_type){
			if ($post_type == 'movies'){
			foreach($this->meta_fields() as $field){
				add_meta_box('movies_meta_'.$field['id'], $field['title'], array($this,'render'), 'movies', 'normal', 'high');
			}
			}
		}
		public function render($post, $args){
			wp_nonce_field('meta_nonce_check', 'meta_nonce_check_value');
			switch($args['id']){
				case 'movies_meta_poster_url':
				
 
					// Use get_post_meta to retrieve an existing value from the database.
					$poster_url = get_post_meta($post -> ID, '_movie_poster_url', true);
 
					// Display the form, using the current value.
					$poster_url = get_post_meta($post->ID, '_movie_poster_url', true);
	
					// Echo out the field
					echo '<input type="url" name="_movie_poster_url" value="' . $poster_url  . '" class="widefat" placeholder="http://" />';
					break;
				case 'movies_meta_rating':
					
 
					// Get the location data if its already been entered
					$movie_rating = get_post_meta($post->ID, '_movie_rating', true);
	
					// Echo out the field
					echo '<input type="number" min="1" max="5" name="_movie_rating" value="' . $movie_rating  . '" class="widefat" step="0.01" />';
					break;
				case 'movies_meta_year':
					
					$movie_year = get_post_meta($post->ID, '_movie_year', true);	
					// Echo out the field
					echo '<input type="number" min="1900" max="'.date("Y",time()).'" name="_movie_year" value="' . $movie_year  . '" class="widefat" step="1" />';
					break;
				case 'movies_meta_short_description':
					
					$movie_desc = get_post_meta($post->ID, '_movie_short_description', true);
	
					// Echo out the field
					echo '<textarea name="_movie_short_description" class="widefat"/>'.$movie_desc.'</textarea>';
					break;			
			}
		}
		public function save($post_id){
			global $post;
			 $nonce = $_POST['meta_nonce_check_value'];
			
			// Verify that the nonce is valid.
			if (!wp_verify_nonce($nonce, 'meta_nonce_check'))
				return $post_id;
			// Is the user allowed to edit the post or page?
			if ( !current_user_can( 'edit_post', $post->ID ))
				return $post->ID;			
			$metas = array();
			$meta_array = $this->meta_fields_as_array('_movie_');
			
			foreach($_POST as $k=>$v){
				if (in_array($k,$meta_array))
					$metas[$k] = $v;										
			}	
			
			foreach ($metas as $key => $value) { // Cycle through the $events_meta array!
				if( $post->post_type == 'revision' ) return; // Don't store custom data twice
				$value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
				if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
					update_post_meta($post->ID, $key, $value);
				} else { // If the custom field doesn't have a value
					add_post_meta($post->ID, $key, $value);
				}
				
				if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
			}
		}
		public function display_meta($content) {
			global $post;			
			if($post->post_type !== 'movies')	
				return $content;
			
			$output = '';
			//$output = $content; If we need content also then we must uncomment this line
			foreach ($this->meta_fields_as_array('_movie_') as $meta){
				$data = get_post_meta($post -> ID, $meta, true);
				
				switch($meta){
					case '_movie_poster_url':
						$output .= '<img src="'.$data.'">';
						break;
					case '_movie_rating':
						$output .= '<p>Current movie rating: '.$data.'</p>';
						break;
					case '_movie_short_description':
						$output .= '<p>'.$data.'</p>';
						break;
					case '_movie_year':
						$output .= '<p>Year published: '.$data.'</p>';
						break;
				}
			}
			return $output;
		}
		public function json_data($post){
			global $wp_query;
			
			$movie_tag = $wp_query->get( 'pagename' );
    
			if ( ! $movie_tag || $movie_tag !== 'movies-json' ) {
				return;
			}
     
			
			$movie_data_needed = array('_movie_poster_url','_movie_desc','_movie_rating','_movie_year');
			$args = array(
				'post_type'      => 'movies',
				'posts_per_page' => 10,
				'movie_tag'    => esc_attr( $movie_tag ),
				);
			$movie_query = new WP_Query( $args );
			$movie_data = array();
			if ( $movie_query->have_posts() ) : while ( $movie_query->have_posts() ) : $movie_query->the_post();            		
				$movie_data[] = array(
					'id' => get_the_ID(),                
					'title' => get_the_title(),
					'poster_url' => get_post_meta(get_the_ID(),'_movie_poster_url'),
					'rating' => get_post_meta(get_the_ID(),'_movie_rating'),
					'year' => get_post_meta(get_the_ID(),'_movie_year'),
					'short_description' => get_post_meta(get_the_ID(),'_movie_short_description'),
				);
			
			endwhile; wp_reset_postdata(); else:
				$movie_data = 'None';
			endif;
			header( "HTTP/1.1 200 OK" );
			wp_send_json( array( 'data'=>$movie_data) );
			
		}
		public function json_rewrite(){
			add_rewrite_tag( '%movies.json%', '([^&]+)' );
			add_rewrite_rule( 'movies.json/([^&]+)/?', 'index.php?movies.json=$matches[1]', 'top' );
		}
		public function register_shortcode_styles(){
			wp_register_style('styles-json', plugin_dir_url( __FILE__ ) . 'css/style.css');
			wp_enqueue_style( 'styles-json' );
		}
	}
	$api = new JSON_Api();
	
}
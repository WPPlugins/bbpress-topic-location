<?php
/*
Plugin Name: bbPress Topic Location
Plugin URI: http://dev.pellicule.org/?page_id=9515
Description: This plugin adds the ability to geo-locate a topic in bbPress.
Version: 1.0.1
Author: G.Breant
Author URI: http://dev.pellicule.org
*/

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @version 1.0.1
 * @author G.Breant
 * @copyright Copyright (c) 2012, G.Breant
 * @link http://dev.pellicule.org
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/**
 * Main bbPress Genesis Extend init class
 */
class bbp_topic_location {

	/** Version ***************************************************************/

	/**
	 * @public string plugin version
	 */
	public $version = '1.0.1';

	/**
	 * @public string plugin DB version
	 */
	public $db_version = '100';
	
	/** Paths *****************************************************************/

	public $file = '';
	
	/**
	 * @public string Basename of the plugin directory
	 */
	public $basename = '';

	/**
	 * @public string Absolute path to the plugin directory
	 */
	public $plugin_dir = '';

	/** URLs ******************************************************************/

	/**
	 * @public string URL to the plugin directory
	 */
	public $plugin_url = '';


	/**
	 * __construct()
	 */
	function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	
	function setup_globals() {
		/** Paths *************************************************************/
		$this->file       = __FILE__;
		$this->basename   = plugin_basename( $this->file );
		$this->plugin_dir = plugin_dir_path( $this->file );
		$this->plugin_url = plugin_dir_url ( $this->file );
	}
	
	function setup_actions(){
		//scripts + styles
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_styles_backend' ) ); //backend

		
		//add geo location field (frontend)
		add_action('bbp_theme_after_topic_form_tags',array( $this, 'frontend_geolocation_field'));
		
		//add geo location field (backend)
		add_action( 'add_meta_boxes',array( $this, 'backend_geolocation_box'));

		//new topic - check geolocation
		add_action( 'bbp_new_topic_pre_extras',array( $this, 'new_topic_geolocation_field'));
		//existing topic - check geolocation
		add_action( 'bbp_edit_topic_pre_extras',array( $this, 'edit_topic_geolocation_field' ));

		//new topic - validate location
		add_filter('bbp_new_topic_pre_geolocation',array( $this, 'validate_geolocation'));
		//existing topic - validate location
		add_filter('bbp_edit_topic_pre_geolocation',array( $this, 'validate_geolocation'));

		//new topic - save geolocation (frontend)
		add_action( 'bbp_new_topic',array( $this, 'topic_save_geolocation'));
		//edit topic - save geolocation (frontend)
		add_action('bbp_edit_topic_post_extras',array( $this, 'topic_save_geolocation'));
		//topic - save geolocation (backend)
		add_action( 'save_post',array( $this, 'backend_topic_save_geolocation' ) );


		//display location in topic
		add_action ('bbp_theme_after_reply_content',array( $this, 'topic_location' ));

		//display location as icon
		add_action('bbp_theme_after_topic_meta',array( $this, 'topic_location' ));
	}
	
	function includes(){
	}
	
	/**
	 * scripts_styles()
	 */
	function scripts_styles() {
               
		//SCRIPTS
		wp_register_script( 'bbpress-topic-location', $this->plugin_url . '_inc/js/scripts.js',array('jquery'));
		wp_enqueue_script( 'bbpress-topic-location' );
		
		//localize vars
		$localize_vars['geo_error_timeout']=__('Time out','bbpress');
		$localize_vars['geo_error_unavailable']=__('Position unavailable','bbpress');
		$localize_vars['geo_error_capability']=__('Permission denied','bbpress');
		$localize_vars['geo_error']=__('Unknown error','bbpress');
		$localize_vars['geo_placeholder']=__('Location','bbpress');
		
		wp_localize_script( 'bbptl', 'bbptl', $localize_vars);
		
		//STYLES
		wp_register_style( 'bbpress-topic-location', $this->plugin_url . '_inc/style.css' );
		wp_enqueue_style( 'bbpress-topic-location' );
	}

	
	function scripts_styles_backend( $hook ) {
		global $post,$bbp;

		if ( $hook != 'post-new.php' && $hook != 'post.php' ) return;
		if ( $bbp->topic_post_type != $post->post_type ) return;
		
		$this->scripts_styles();

	}
	
	function scripts_backend( $hook ) {
		global $post,$bbp;

		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			if ( $bbp->topic_post_type === $post->post_type ) {     
				echo "<br/><br/>scripts_backend";
				//wp_enqueue_script(  'myscript', get_stylesheet_directory_uri().'/js/myscript.js' );
			}
		}
	}
	
        /**
        * Output value of topic location field
        *
        * @since bbPress (r2976)
        * @uses get_form_topic_location() To get the value of topic location field
        */
        function form_topic_location() {
                echo $this->get_form_topic_location();
        }
                /**
                * Return value of topic location field
                *
                * @since bbPress (r2976)
                *
                * @uses bbp_is_topic_edit() To check if it's the topic edit page
                * @uses apply_filters() Calls 'get_form_topic_location' with the location
                * @return string Value of topic location field
                */
                function get_form_topic_location() {
                        global $post;

                        // Get _POST data
                        if ( 'post' == strtolower( $_SERVER['REQUEST_METHOD'] ) && isset( $_POST['bbp_topic_location'] ) ) {
                                $topic_location = $_POST['bbp_topic_location'];

                        // Get edit data
                        } elseif ( !empty( $post ) ) {

                                // Post is a topic
                                if ( bbp_get_topic_post_type() == $post->post_type ) {
                                        $topic_id = $post->ID;
                                }


                                // Topic exists
                                if ( !empty( $topic_id ) ) {

                                        $location = $this->get_reply_location_raw($topic_id);
                                        $topic_location = $location['Address'];

                                }


                        // No data
                        } else {
                                $topic_location = '';
                        }

                        return apply_filters( 'bbp_get_form_topic_location', esc_attr( $topic_location ) );
                }

        /**
        * Output the location of the reply
        *
        * @since bbPress (r2553)
        *
        * @param int $reply_id Optional. Reply id
        * @uses get_reply_location() To get the reply location
        */
        function reply_location( $reply_id = 0 ) {
                echo $this->get_reply_location( $reply_id );
        }

                /**
                * Return the location of the reply
                *
                * @since bbPress (r2553)
                *
                * @param int $reply_id Optional. Reply id
                * @uses bbp_get_reply_id() To get the reply id
                * @uses bbp_get_reply_location_raw() To get the location infos
                * @uses apply_filters() Calls 'bbp_get_reply_location' with the address,reply id and location infos
                * @return string Address
                */
		function get_reply_location( $reply_id = 0 ) {
				$reply_id = bbp_get_reply_id( $reply_id );

				$location = $this->get_reply_location_raw($reply_id);

				if (!$location) return false;

				return apply_filters( 'bbp_get_reply_location', $location['Address'], $reply_id,$location );
		}

		function get_reply_location_raw( $reply_id = 0 ) {
				$reply_id = bbp_get_reply_id( $reply_id );

				$geo_info = get_post_meta($reply_id,'_bbp_topic_geo_info',true);
				$lat = get_post_meta($reply_id,'_bbp_topic_geo_lat',true);
				$long = get_post_meta($reply_id,'_bbp_topic_geo_long',true);

				if ((!$lat) || (!$long)) return false;

				$location = $geo_info;
				$location['Latitude'] = $lat;
				$location['Longitude'] = $long;

				return $location;
		}
                
        function location_to_coordinates($location) {

            preg_match_all("/-?\d+[\.|,]\d+/", $location, $coords, PREG_SET_ORDER);

            $lat = str_replace(',', '.', $coords[0][0]);
            $lng = str_replace(',', '.', $coords[1][0]);
            
            if($lat&&$lng) return array($lat,$lng);

            
        }
                

        function validate_geolocation($input=false) {

                $input = trim($input);
                if(!$input)return false;

                $coords=$this->location_to_coordinates($input);

                if($coords){
                    $args['latlng']=$coords[0].','.$coords[1];
                }else{
                    $args['address']=urlencode($input);
                }

                $args['sensor']='false';
                $gmaps_url = add_query_arg($args,'http://maps.google.com/maps/api/geocode/json');
                $geocode=file_get_contents($gmaps_url);
                $output=json_decode($geocode);

                if ($output->status=='OK') {
                        $result = $output->results[0];
                        $position['Latitude']= $result->geometry->location->lat;
                        $position['Longitude']= $result->geometry->location->lng;
                        $position['Address'] = $result->formatted_address;
                        $position['Input'] = $input;
                }else {
                    bbp_add_error( 'bbp_topic_geolocation_unknown', __( '<strong>ERROR</strong>: We were unable to find this location.', 'bbpress' ) );
                    return false;
                }

                return $position;
        }
		
		function backend_geolocation_box(){
			global $bbp;
			
			$title =__( 'Topic Location', 'bbpress' ).'<small> (<a href="#">'.__('get my location','bbpress').'</a>)</small>';

			add_meta_box( 'bbp_topic_location_field',$title,array(&$this,'backend_geolocation_box_content'),$bbp->topic_post_type, 'normal', 'high' );  
		}
		
		function backend_geolocation_box_content()
		{
			?>
			<input type="text" id="bbp_topic_location" value="<?php $this->form_topic_location(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_location"/>
			<?php
		}

        function frontend_geolocation_field(){
            ?>
            <p id="bbp_topic_location_field">
                    <label for="bbp_topic_location"><?php _e( 'Topic Location:', 'bbpress' );?><small> (<a href="#"><?php _e('get my location','bbpress');?></a>)</small></label><br />
                    <input type="text" id="bbp_topic_location" value="<?php $this->form_topic_location(); ?>" tabindex="<?php bbp_tab_index(); ?>" size="40" name="bbp_topic_location"/>
            </p>
            <?php
        }

        function new_topic_geolocation_field(){
                global $topic_geolocation;

                if ( !empty( $_POST['bbp_topic_location'] ) )
                        $topic_geolocation = $_POST['bbp_topic_location'];

                // Filter and sanitize
                $topic_geolocation = apply_filters( 'bbp_new_topic_pre_geolocation',$topic_geolocation);

                // No topic location
                if ( empty( $topic_geolocation ) )
                        bbp_add_error( 'bbp_topic_geolocation', __( '<strong>ERROR</strong>: Your topic location cannot be empty.', 'bbpress' ) );
        }
        function edit_topic_geolocation_field($topic_id){
                global $topic_geolocation;

                if ( !empty( $_POST['bbp_topic_location'] ) )
                        $topic_geolocation = $_POST['bbp_topic_location'];

                // Filter and sanitize
                $topic_geolocation = apply_filters( 'bbp_edit_topic_pre_geolocation',$topic_geolocation);

                // No topic location
                if ( empty( $topic_geolocation ) )
                        bbp_add_error( 'bbp_topic_geolocation', __( '<strong>ERROR</strong>: Your topic location cannot be empty.', 'bbpress' ) );
        }

        function topic_save_geolocation($topic_id){
            global $topic_geolocation;

            $geo_info['Input']=$topic_geolocation['Input'];
            $geo_info['Address']=$topic_geolocation['Address'];

            $lat=$topic_geolocation['Latitude'];
            $long=$topic_geolocation['Longitude'];

            if ((!$lat) || (!$long)) {  //no position found and strict mode ON
                    delete_post_meta($topic_id, '_bbp_topic_geo_info');
                    delete_post_meta($topic_id, '_bbp_topic_geo_lat');
                    delete_post_meta($topic_id, '_bbp_topic_geo_long');

                    return false;
            }else {
                    update_post_meta($topic_id, '_bbp_topic_geo_info', $geo_info);
                    update_post_meta($topic_id, '_bbp_topic_geo_lat', $lat);
                    update_post_meta($topic_id, '_bbp_topic_geo_long', $long);
            }

            return true;
        }
		
		function backend_topic_save_geolocation($topic_id){
			global $bbp;
			global $topic_geolocation;

			// Bail if doing an autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $topic_id;

			// Bail if not a post request
			if ( 'POST' != strtoupper( $_SERVER['REQUEST_METHOD'] ) )
				return $topic_id;

			// Bail if post_type is not a topic
			if ( get_post_type( $topic_id ) != $bbp->topic_post_type )
				return;

			// Bail if current user cannot edit this topic
			if ( !current_user_can( 'edit_topic', $topic_id ) )
				return $topic_id;
				
			////
			//validate input
			if ( !empty( $_POST['bbp_topic_location'] ) )
					$topic_geolocation = $_POST['bbp_topic_location'];

			// Filter and sanitize
			$topic_geolocation = apply_filters( 'bbp_new_topic_pre_geolocation',$topic_geolocation);

			// No topic location
			if ( empty( $topic_geolocation ) )
					bbp_add_error( 'bbp_topic_geolocation', __( '<strong>ERROR</strong>: Your topic location cannot be empty.', 'bbpress' ) );
			
			//save location
			$success = $this->topic_save_geolocation($topic_id);
			
			return $topic_id;
				
				
		}

        function topic_location(){
            global $post;
            $location_raw = $this->get_reply_location_raw($post->ID);
            if(!$location_raw) return false;
            ?>
            <p class="bbp-topic-meta bbp-topic-location">
				<img src="<?php echo apply_filters('bbp_topic_location_location_icon',$this->plugin_url.'_inc/images/home_icon.gif');?>" alt="<?php echo $this->get_reply_location($post->ID);?>"/>
                <span><?php echo $this->get_reply_location($post->ID); ?></span>
            </p>
            <?php
        }

}

new bbp_topic_location();
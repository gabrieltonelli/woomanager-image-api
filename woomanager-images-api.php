<?php
/* @wordpress-plugin
 * Plugin Name:       WooManager Images API REST
 * Plugin URI:        https://pampasoftware.com/
 * Description:       Permite subir imagenes de los productos desde WooManager.
 * Version:           1.0.0
 * WC requires at least: 3.0
 * WC tested up to: 4.9
 * Author:            PampaSoftware
 * Author URI:        https://pampasoftware.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

class ImagesAPI{

	protected static $_instance = null;

	public static function get_instance(){
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
	    if(!$this->is_woocommerce_active()){
	        return;
        }

		add_action( 'plugins_loaded', array($this, 'load_plugin_textdomain') );
		add_action( 'plugins_loaded', array($this, 'init_woomanager_images_api') );
	}

    public function init_woomanager_images_api(){
		add_action( 'rest_api_init', function () {
		  register_rest_route( 'api/v1', '/imagen',
		    array(
		      'methods' => 'POST', 
		      'callback' => array($this, 'fn_api_upload_image')
		    )
		  );
		});
	}

    public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woomanager-images-api', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	private function is_woocommerce_active(){
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() )
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

		return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
    }

    function fn_api_upload_image($data){
	    if($data['token'] == 'PHSCDXamcaqzhnPStmPIWVrF7FF4Xtbp'){
	    	if($data['image'] && $data['image_name']){
	    		$upload_dir = wp_upload_dir();
	    		$imagen = array();
				$url = $upload_dir['basedir'].'/'.$data['image_name'];
		        if (file_put_contents($url, base64_decode($data['image'])) === false){
		            return new WP_Error( 'upload_error', 'Se produjo un error al subir la imagen del producto', array( 'status' => 409 ) );
		        }
		        else{
		        	$image_url = $upload_dir['baseurl'].'/'.$data['image_name'];
					$image_data = file_get_contents( $image_url );
					$filename = basename( $image_url );
					if ( wp_mkdir_p( $upload_dir['path'] ) ) {
					  $file = $upload_dir['path'] . '/' . $filename;
					}
					else {
					  $file = $upload_dir['basedir'] . '/' . $filename;
					}
					file_put_contents( $file, $image_data );
					$wp_filetype = wp_check_filetype( $filename, null );
					$attachment = array(
					  'post_mime_type' => $wp_filetype['type'],
					  'post_title' => sanitize_file_name( $filename ),
					  'post_content' => '',
					  'post_status' => 'inherit'
					);
					$attach_id = wp_insert_attachment( $attachment, $file );
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
					wp_update_attachment_metadata( $attach_id, $attach_data );
		        	$imagen[] = array('url_image' => $upload_dir['url'].'/'.$data['image_name']);
		        	unlink($url);
		            return $imagen;
		        }
	    	}
	    	else{
	    		return new WP_Error( 'param_error', 'Faltan algunos parametros', array( 'status' => 404 ) );
	    	}
	    }
	    else{
	        return new WP_Error( 'token_error', 'Token invalido', array( 'status' => 401 ) );
	    }
	}
}

ImagesAPI::get_instance();
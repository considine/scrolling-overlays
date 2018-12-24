<?php
/*
Plugin Name: Koptional Scrolling Video
Plugin URI: https://koptional.com/plugins/health-check/
Description: Adds video overlays on scroll
Version: 0.1.0
Author: Jack Considine
 */

function wporg_options_page()
{
    // add top level menu page
    add_menu_page(
        'Video Overlay',
        'Video Overlay',
        'manage_options',
        'wporg',
        'koptional_video_overlays'
    );
}
add_action('admin_menu', 'wporg_options_page');

// https://wordpress.stackexchange.com/questions/235406/how-do-i-select-an-image-from-media-library-in-my-plugin
function koptional_video_overlays()
{
    $image_id = get_option('myprefix_image_id');
    ?>
<p id="shortcodeResp"> </p>
<input type="hidden" name="myprefix_image_id" id="myprefix_image_id" value="<?php echo esc_attr($image_id); ?>" class="regular-text" />
<br>
<input type='button' class="button-primary" value="<?php esc_attr_e('Select a image', 'mytextdomain');?>" id="myprefix_media_manager" />
<?php
}

// As you are dealing with plugin settings,
// I assume you are in admin side
add_action('admin_enqueue_scripts', 'load_wp_media_files');
function load_wp_media_files($page)
{
    // change to the $page where you want to enqueue the script
    if ($page == 'toplevel_page_wporg') {
        // Enqueue WordPress media scripts
        wp_enqueue_media();
        // Enqueue custom script that will interact with wp.media
        wp_enqueue_script('myprefix_script', plugins_url('/js/myscript.js', __FILE__), array('jquery'), '0.1');
    }
}

add_action('wp_ajax_add_review', 'koptional_add_video_overlay');
function koptional_add_video_overlay()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'koptional_overlay';
    if (isset($_POST['url'])) {
        $t = $wpdb->insert(
            $table_name,
            array(
              "url" => "{$_POST['url']}",
              "type" => 'VIDEO'
        ));
        $insert_id = $wpdb->insert_id;
        $shortcode = "[kopoverlay id=\"{$insert_id}\" type=\"video\"]";
        echo json_encode(["success" => $t, "shortcode" => $shortcode]);
    }
    wp_die();
}



global $kop_db_version;
$kop_db_version = '1.0';

function kop_install() {
	global $wpdb;
	global $kop_db_version;

	$table_name = $wpdb->prefix . 'koptional_overlay';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		url varchar(200) DEFAULT '' NOT NULL,
        type varchar(15) DEFAULT 'VIDEO' NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'kop_db_version', $kop_db_version );
}


register_activation_hook( __FILE__, 'kop_install' );


function kopoverlay_func( $atts ) {
    global $wpdb;

	$a = shortcode_atts( array(
		'id' => null,
		'type' => null,
    ), $atts );
    
    $table_name = $wpdb->prefix . 'koptional_overlay';
    // return  'SELECT * FROM ' . $table_name . ' WHERE ID=' . "{$a['id']}";
    $ar = $wpdb->get_results( 'SELECT * FROM ' . $table_name . ' WHERE ID=' . "{$a['id']} and type='VIDEO'" );
    $url = $ar[0]->url;
    $id = $ar[0]->id;
    return <<<HTML
    <div data-type="koptional-video-overlay" data-target="{$id}" id="koptional-overlay-{$id}" class="koptional-overlay-insert">
      <div class="koptional-overlay">
        <video class="video-js">
        <source src="{$url}" type="video/mp4">
        </video>
      </div>
    </div>
HTML;
}
add_shortcode( 'kopoverlay', 'kopoverlay_func' );


// Enqueue static scripts and style
function koptional_overlay_enqueue_style() {
	wp_enqueue_style( 'video-js-css',  plugins_url( '/static/video-js.min.css', __FILE__ ), false ); 
}

function koptional_overlay_enqueue_script() {
	wp_enqueue_script( 'koptional-js', plugins_url( '/static/main.5bca63f8.js', __FILE__ ), false );
}

add_action( 'wp_enqueue_scripts', 'koptional_overlay_enqueue_style' );
add_action( 'wp_enqueue_scripts', 'koptional_overlay_enqueue_script' );
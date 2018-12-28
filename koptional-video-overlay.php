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
        'edit_posts',
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
<img style="max-width: 100%; width: 500px;" src="<?php echo plugins_url('/embed.gif', __FILE__) ?>">
<p class="noselect"> <small> For videos, you can switch to Youtube. after generating your shortcode the normal way
add a youtube attribute (and optionally a fallback URL where the User can click a link). It will look like this:
</small> </p>
<img style="max-width: 100%; width: 500px;"   alt="Instructions" src="<?php echo plugins_url('/demoimage.png', __FILE__) ?>">

<p id="shortcodeResp"> </p>
<input type='button' class="button-primary" value="<?php esc_attr_e('Select an image');?>" id="image_media_manager" />
<input type='button' class="button-primary" value="<?php esc_attr_e('Select a video');?>" id="video_media_manager" />
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
    if (isset($_POST['url']) && isset($_POST["type"])) {
        $type = $_POST['type'];
        $t = $wpdb->insert(
            $table_name,
            array(
                "url" => "{$_POST['url']}",
                "type" => "{$type}",
            ));
        $insert_id = $wpdb->insert_id;

        if ($type == "VIDEO") {
            $shortcode = "[kopoverlay id=\"{$insert_id}\" type=\"video\"]";
        } else if ($type == "IMAGE") {
            $shortcode = "[kopoverlay id=\"{$insert_id}\" type=\"image\" caption=\"\"]";
        }
        echo json_encode(["success" => $t, "shortcode" => $shortcode]);
    }
    wp_die();
}

global $kop_db_version;
$kop_db_version = '1.0';

function kop_install()
{
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

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    add_option('kop_db_version', $kop_db_version);
}

register_activation_hook(__FILE__, 'kop_install');

function kopoverlay_func($atts)
{
    global $wpdb;

    $a = shortcode_atts(array(
        'id' => null,
        'type' => null,
        'caption' => '',
        "side" => null,
        'youtube' => null,
        'fallbackurl' => null,
    ), $atts);

    $table_name = $wpdb->prefix . 'koptional_overlay';
    // return  'SELECT * FROM ' . $table_name . ' WHERE ID=' . "{$a['id']}";
    $ar = $wpdb->get_results('SELECT * FROM ' . $table_name . ' WHERE ID=' . "{$a['id']}");
    if (sizeof($ar) == 0) {
        return "";
    }

    $url = $ar[0]->url;
    $id = $ar[0]->id;
    $type = $ar[0]->type;
    $caption = $a['caption'];
    $youtube = $a["youtube"];
    $fallbackurl = $a["fallbackurl"];
    $style = $a['side'] == "right" ? "right: 0;" : "left: 0;";
    if ($type == 'VIDEO' && $youtube) {
        $fallbackHTML = $fallbackurl ? "<p class='fallback-text'> Trouble watching YouTube? Click <a target='_blank' href='{$fallbackurl}'>
        here </a> to watch instead </p>" : "";
        return <<<HTML
 <div data-type="koptional-youtube-overlay" data-target="{$id}" id="koptional-overlay-{$id}" class="koptional-overlay-insert">
      <div class="koptional-overlay">
        <!-- <video id="vid1" autoplay='autoplay' muted='muted' class="video-js vjs-default-skin" controls muted="muted"
            autoplay width="700" data-setup='{ "techOrder": ["youtube"], "sources": [{ "type": "video/youtube", "src": "https://www.youtube.com/watch?v=emNgfuw8vlA"}], "youtube": { "iv_load_policy": 1 } }'>
          </video> -->
        <div class="koptional-embed-wrapper" style="max-width: 135vh;">
            <div class="responsive-embed">
                <iframe enablejsapi="1" id="youtube-video-{$id}" width="420" height="315" src="{$youtube}?enablejsapi=1&mute=1"
                frameborder="0" allowfullscreen></iframe>
            </div>
        </div>
       {$fallbackHTML}
      </div>
    </div>
HTML;
    }
    if ($type == 'VIDEO') {
        return <<<HTML
        <div data-type="koptional-video-overlay" data-target="{$id}" id="koptional-overlay-{$id}" class="koptional-overlay-insert">
          <div class="koptional-overlay">
            <video class="video-js">
            <source src="{$url}" type="video/mp4">
            </video>
          </div>
        </div>
HTML;
    } else if ($type == "IMAGE") {
        return <<<HTML
       <div data-type="koptional-image-overlay" data-target="{$id}" id="koptional-overlay-{$id}" class="koptional-overlay-insert">
      <div class="koptional-overlay">
        <img src="{$url}" alt="Photo">
        <p class="scrolling-text" style="position: absolute;
                color: white;
                top: 10%;
                max-width: 400px;
                {$style}
                font-size: 20px;
                text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
                max-width: 320px;
                text-align: left;
                padding: 32px;"> {$caption}
         </p>
      </div>
    </div>
HTML;
    }

}
add_shortcode('kopoverlay', 'kopoverlay_func');

// Enqueue static scripts and style
function koptional_overlay_enqueue_style()
{
    wp_enqueue_style('video-js-css', plugins_url('/static/video-js.min.css', __FILE__), false);
    // wp_enqueue_style('koptional-montage-css', plugins_url('/static/montage.css', __FILE__), false);
    // wp_enqueue_style('koptional-video-css', plugins_url('/static/style.css', __FILE__), false);
}

function koptional_overlay_enqueue_script()
{
    wp_enqueue_script('koptional-js', plugins_url('/static/main.80eda2fc.js', __FILE__), false);
}

add_action('wp_enqueue_scripts', 'koptional_overlay_enqueue_style');
add_action('wp_enqueue_scripts', 'koptional_overlay_enqueue_script');
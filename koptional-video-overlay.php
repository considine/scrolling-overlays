<?php
/*
Plugin Name: Scrolling Overlays
Plugin URI: https://wordpress.org/plugins/scrolling-overlays
Description: Adds media overlays on scroll
Version: 0.1.1
Author: Cassidy Mcdonald & Jack Considine
 */

define('KOPTIONAL_OVERLAY_TABLE', 'koptional_overlays');
function wporg_options_page() {
    // add top level menu page
    add_menu_page(
        'Scrolling Overlays',
        'Scrolling Overlays',
        'edit_posts',
        'scrolling_overlays',
        'koptional_video_overlays'
    );
}
add_action('admin_menu', 'wporg_options_page');

// https://wordpress.stackexchange.com/questions/235406/how-do-i-select-an-image-from-media-library-in-my-plugin
function koptional_video_overlays() {
    $image_id = get_option('myprefix_image_id');
    ?>
<h2>
    <?php esc_attr_e('For self hosted media');?>
</h2>
<p id="shortcodeResp"> </p>
<input type='button' class="button-primary" value="<?php esc_attr_e('Select an image');?>" id="image_media_manager" />
<input type='button' class="button-primary" value="<?php esc_attr_e('Select a video');?>" id="video_media_manager" />

<h2>
    <?php esc_attr_e('For Youtube videos');?>
</h2>

<form id="youtubeForm" class="koptional-style" style="padding-bottom: 16px;">
    <label>Youtube URL <br> <input type="text" required="required" id="youtube_url">
    </label>
    <br>
    <br>
    <input type='submit' class="button-primary" value="<?php esc_attr_e('Select a video');?>" id="youtube_media_manager" />
</form>

<div id="overlaySchema" class="koptional-style">
    <h2> Shortcode Attributes </h2>
    <table>
        <thead>
            <tr>
                <td> </td>
                <td> Video </td>
                <td> Image </td>
                <td> Youtube Video </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th scope="row">id </th>
                <td> Required </td>
                <td> Required </td>
                <td> Required </td>
            </tr>
            <tr>
                <th scope="row">fallbackurl </th>
                <td> x </td>
                <td> x </td>
                <td> Optional- this link will display if the youtube video doesn't load </td>
            </tr>
            <tr>
                <th scope="row">caption </th>
                <td> x </td>
                <td> Optional- caption that appears above the image </td>
                <td> x </td>
            </tr>
        </tbody>
    </table>
</div>
<hr>
<div id="pastOverlays" class="koptional-style">
    <h2> Created Shortcodes </h2>
</div>

<?php
}

// As you are dealing with plugin settings,
// I assume you are in admin side
add_action('admin_enqueue_scripts', 'load_wp_media_files');
function load_wp_media_files($page) {
    // change to the $page where you want to enqueue the script
    if ($page == 'toplevel_page_wporg') {
        // Enqueue WordPress media scripts
        wp_enqueue_media();
        // Enqueue custom script that will interact with wp.media
        wp_enqueue_script('koptional_admin_script', plugins_url('/js/admin_script.js', __FILE__), array('jquery'), '0.1');
        wp_enqueue_script('koptional_plugin_script', plugins_url('/static/main.81eda2fc.js', __FILE__), array(), '0.1');
    }
}

add_action('wp_ajax_koptional_create_overlay', 'koptional_add_video_overlay');
add_action('wp_ajax_koptional_create_youtube_overlay', 'koptional_add_youtube_overlay');
function koptional_add_video_overlay() {
    global $wpdb;
    $table_name = $wpdb->prefix . KOPTIONAL_OVERLAY_TABLE;
    if (isset($_POST['url']) && isset($_POST["type"]) && isset($_POST['name'])) {
        $type = $_POST['type'];
        $t = $wpdb->insert(
            $table_name,
            array(
                "url" => "{$_POST['url']}",
                "type" => "{$type}",
                'fname' => "{$_POST['name']}",
            ));
        $insert_id = $wpdb->insert_id;

        $shortcode = koptional_generate_shortcode($insert_id, $type);
        echo json_encode(["success" => $t, "shortcode" => $shortcode]);
    }
    wp_die();
}

function koptional_add_youtube_overlay() {
    global $wpdb;
    $table_name = $wpdb->prefix . KOPTIONAL_OVERLAY_TABLE;
    if (isset($_POST['url'])) {
        try {
        $embed = koptional_get_youtube_embed($_POST['url']);

            $t = $wpdb->insert(
            $table_name,
            array(
                "url" => "{$embed}",
                "type" => "YOUTUBE",
                'fname' => "{$embed}",
            ));
            $insert_id = $wpdb->insert_id;

            $shortcode = koptional_generate_shortcode($insert_id, 'YOUTUBE');
            echo json_encode(["success" => $t, "shortcode" => $shortcode]);
            wp_die();

        } catch (Exception $e) {
            // Return error
            return wp_send_json_error("Invalid Youtube URL", 400);
        }
    }

}

function koptional_generate_shortcode($id, $type) {
    if ($type == "VIDEO") {
        return "[kopoverlay id=\"{$id}\"]";
    } else if ($type == "IMAGE") {
        return "[kopoverlay id=\"{$id}\" caption=\"\"]";
    } else if ($type === "YOUTUBE") {
        return "[kopoverlay id=\"{$id}\" fallbackurl=\"\"]";
    }
}

add_action("wp_ajax_koptional_get_overlays", 'koptional_get_video_overlays');
function koptional_get_video_overlays() {
    global $wpdb;
    $table_name = $wpdb->prefix . KOPTIONAL_OVERLAY_TABLE;
    $query = $wpdb->get_results('SELECT * FROM ' . $table_name . ' ORDER BY id DESC');
    $results = [];
    foreach ($query as $row) {
        array_push($results, [
            "shortcode" => koptional_generate_shortcode($row->id, $row->type),
            "name" => $row->fname,
            "url" => $row->url,
            "type" => strtolower($row->type)
        ]);
    }
    echo json_encode(["data" => $results]);
    wp_die();
}

global $kop_db_version;
$kop_db_version = '1.1';

function kop_install() {
    global $wpdb;
    global $kop_db_version;

    $table_name = $wpdb->prefix . KOPTIONAL_OVERLAY_TABLE;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		url varchar(200) DEFAULT '' NOT NULL,
        type varchar(15) DEFAULT 'VIDEO' NOT NULL,
        fname varchar(50) NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    add_option('kop_db_version', $kop_db_version);
}

register_activation_hook(__FILE__, 'kop_install');

function kopoverlay_func($atts) {
    global $wpdb;

    $a = shortcode_atts(array(
        'id' => null,
        'type' => null,
        'caption' => '',
        "side" => null,
        'youtube' => null,
        'fallbackurl' => null,
    ), $atts);

    $table_name = $wpdb->prefix . KOPTIONAL_OVERLAY_TABLE;
    // return  'SELECT * FROM ' . $table_name . ' WHERE ID=' . "{$a['id']}";
    $ar = $wpdb->get_results('SELECT * FROM ' . $table_name . ' WHERE ID=' . "{$a['id']}");
    if (sizeof($ar) == 0) {
        return "";
    }
    $url = $ar[0]->url;
    $id = $ar[0]->id;
    $type = $ar[0]->type;
    $shortcode = $ar[0];
    if (!isset($url) || !isset($type)) {
        return "";
    }
    $caption = $a['caption'];
    $fallbackurl = $a["fallbackurl"];
    $style = $a['side'] == "right" ? "right: 0;" : "left: 0;";


    if ($type == 'YOUTUBE') {
        $fallbackHTML = ($fallbackurl && sizeof($fallbackurl) > 0) ? "<p class='fallback-text'> Trouble watching YouTube? Click <a target='_blank' href='{$fallbackurl}'>
        here </a> to watch instead </p>" : "";
        return <<<HTML
 <div data-type="koptional-youtube-overlay" data-target="{$id}" id="koptional-overlay-{$id}" class="koptional-overlay-insert">
      <div class="koptional-overlay">
        <!-- <video id="vid1" autoplay='autoplay' muted='muted' class="video-js vjs-default-skin" controls muted="muted"
            autoplay width="700" data-setup='{ "techOrder": ["youtube"], "sources": [{ "type": "video/youtube", "src": "https://www.youtube.com/watch?v=emNgfuw8vlA"}], "youtube": { "iv_load_policy": 1 } }'>
          </video> -->
        <div class="koptional-embed-wrapper" style="max-width: 130vh;">
            <div class="responsive-embed">
                <iframe enablejsapi="1" id="youtube-video-{$id}" width="420" height="315" src="{$url}?enablejsapi=1&mute=1"
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
function koptional_overlay_enqueue_style() {
    wp_enqueue_style('video-js-css', plugins_url('/static/video-js.min.css', __FILE__), false);
    // wp_enqueue_style('koptional-montage-css', plugins_url('/static/montage.css', __FILE__), false);
    // wp_enqueue_style('koptional-video-css', plugins_url('/static/style.css', __FILE__), false);
}

function koptional_overlay_enqueue_script() {
    wp_enqueue_script('koptional-js', plugins_url('/static/main.82eda2fc.js', __FILE__), false);
}

add_action('wp_enqueue_scripts', 'koptional_overlay_enqueue_style');
add_action('wp_enqueue_scripts', 'koptional_overlay_enqueue_script');

function koptional_get_youtube_embed($link) {
    // Is video already in embedded form?
    $regExp = '/.*\/embed.*\//';
    preg_match($regExp, $link, $matches, PREG_OFFSET_CAPTURE);
    if (sizeof($matches) === 1 && $matches[0][1] === 0) {
        return $link;
    }
    $regExp = '/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/';
    preg_match($regExp, $link, $matches, PREG_OFFSET_CAPTURE);
    if (sizeof($matches) === 3) {
        return "https://www.youtube.com/embed/" . $matches[2][0];
    } else {
        throw new Exception('Invalid Link');
    }
}
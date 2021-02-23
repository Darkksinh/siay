<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function sirv_gallery($atts){

    //if(is_admin()) return false;

    wp_enqueue_style('sirv-gallery', plugins_url('/css/wp-sirv-gallery.css', __FILE__));
    //wp_enqueue_script('sirv-js', 'https://scripts.sirv.com/sirv.js', array(), false, true);

    extract(shortcode_atts( array('id' => ''), $atts));

    global $wpdb;

    $table_name = $wpdb->prefix . 'sirv_shortcodes';

    $row =  $wpdb->get_row("SELECT * FROM $table_name WHERE id = " . intval($id), ARRAY_A);

    if (empty($row)) return;

    $images_data = unserialize($row['images']);
    $shortcode_options = unserialize($row['shortcode_options']);

    if(empty($shortcode_options['global_options'])) $shortcode_options['global_options'] = array();
    if(empty($shortcode_options['spin_options'])) $shortcode_options['spin_options'] = array();
    if(empty($shortcode_options['zgallery_data_options'])) $shortcode_options['zgallery_data_options'] = array();
    if(empty($shortcode_options['zgallery_data_options']['thumbnails'])) $shortcode_options['zgallery_data_options']['thumbnails'] = 'bottom';

    $images = array();
    $captions = array();

    foreach ($images_data as $image) {
        //array_push($images, $image['url']);
        $image_width = array_key_exists('image_width', $image) ? $image['image_width'] : 0;
        $image_height = array_key_exists('image_height', $image) ? $image['image_height'] : 0;
        array_push($images, array(
            'url' =>$image['url'],
            'image_width'=> $image_width,
            'image_height'=> $image_height,
            'caption' => stripslashes($image['caption'])
        ));
        array_push($captions, stripslashes($image['caption']));
    }

    $options = array(
        'width' => $row['width'],
        'height' => 'auto',
        'link_image' => filter_var($row['link_image'], FILTER_VALIDATE_BOOLEAN),
        'profile' => trim($row['profile']),
        'show_caption' => filter_var($row['show_caption'], FILTER_VALIDATE_BOOLEAN),
        'is_gallery' => filter_var($row['use_as_gallery'], FILTER_VALIDATE_BOOLEAN),
        'apply_zoom' => filter_var($row['use_sirv_zoom'], FILTER_VALIDATE_BOOLEAN),
        'thumbnails_height' => $row['thumbs_height'],
        'gallery_styles' => $row['gallery_styles'],
        'gallery_align' => $row['align'],
        'zgallery_data_options' => $shortcode_options['zgallery_data_options'],
        'zgallery_thumbs_position' => $shortcode_options['zgallery_data_options']['thumbnails'],
        'spin_options' => $shortcode_options['spin_options'],
        'global_options' => $shortcode_options['global_options']
    );

    $gallery = null;
    $js_type = (int) get_option('SIRV_JS_FILE');
    $dependencies = array('jquery');

    /*global $pagenow;
    if ( !in_array($pagenow, array('post-new.php', 'post.php', 'edit-tags.php')) ) {
        $dependencies[] = 'sirv-js';
    } */

    if($js_type == 3){
        wp_enqueue_script('sirv-gallery-mv-viewer', plugins_url('/js/wp-sirv-mv-gallery.js', __FILE__), $dependencies, false, true);
        require_once 'sirv-gallery-mv.php';
        $gallery = new Sirv_Gallery_MV($options, $images, $captions);
    }else{
        wp_enqueue_script('sirv-gallery-viewer', plugins_url('/js/wp-sirv-gallery.js', __FILE__), $dependencies, false, true);
        require_once 'sirv-gallery.php';
        $gallery = new Sirv_Gallery($options, $images, $captions);
    }



return $gallery->render();

}

add_shortcode( 'sirv-gallery', 'sirv_gallery' );
?>

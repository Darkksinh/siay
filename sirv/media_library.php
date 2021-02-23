<?php

    defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

    function check_empty_options(){
        $host = get_option('SIRV_AWS_HOST');
        $bucket = get_option('SIRV_AWS_BUCKET');
        $key = get_option('SIRV_AWS_KEY');
        $secret_key = get_option('SIRV_AWS_SECRET_KEY');

        //die($key);

        if(empty($host) || empty($bucket) || empty($key) || empty($secret_key)){

            return false;
        }else return true;

    }


    if(check_empty_options()){
        wp_enqueue_style('fontAwesome', "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css", array());
        //wp_enqueue_style('sirv_style', plugins_url('css/wp-sirv.css', __FILE__));
        wp_register_style('sirv_style', plugins_url('css/wp-sirv.css', __FILE__));
        wp_enqueue_style('sirv_style');
        wp_enqueue_script( 'sirv_logic', plugins_url('js/wp-sirv.js', __FILE__), array( 'jquery', 'jquery-ui-sortable' ), '1.0.0');
        wp_localize_script( 'sirv_logic', 'sirv_ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'assets_path' => plugins_url('assets', __FILE__) ) );
        wp_enqueue_script( 'sirv_media_library_logic', plugins_url('js/wp-sirv-media-library.js', __FILE__), array( 'jquery'), '1.0.0');
        wp_enqueue_script( 'sirv_logic-md5', plugins_url('js/wp-sirv-md5.min.js', __FILE__), array(), '1.0.0');


    include('templates/media_library.html');

    }else{
        wp_enqueue_style('sirv_style', plugins_url('css/wp-sirv.css', __FILE__));
        include('templates/login_error.html');
    }
?>

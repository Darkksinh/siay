<?php

/**
 * Plugin Name: Sirv
 * Plugin URI: http://sirv.com
 * Description: Fully-automatic image optimization, next-gen formats (WebP), responsive resizing, lazy loading and CDN delivery. Every best-practice your website needs. Use "Add Sirv Media" button to embed images, galleries, zooms, 360 spins and streaming videos in posts / pages. Stunning media viewer for WooCommerce. Watermarks, text titles... every WordPress site deserves this plugin! <a href="admin.php?page=sirv/sirv/options.php">Settings</a>
 * Version: 5.8.0
 * Author: sirv.com
 * Author URI: sirv.com
 * License: GPLv2
 */

defined('ABSPATH') or die('No script kiddies please!');

define('SIRV_PLUGIN_VERSION', '5.8.0');

global $isLogger;
global $startTime;
global $startLoggerTime;

$isLogger = false;
$startTime = time();


define('SIRV_PLUGIN_PATH', str_replace('/sirv.php', '', plugin_basename(__FILE__)));
define('SIRV_PLUGIN_PATH_WITH_SLASH', '/' . SIRV_PLUGIN_PATH);
define('SIRV_PLUGIN_URL_PATH', plugin_dir_url(__FILE__));

require_once(dirname(__FILE__) . '/sirv/error.class.php');
require_once(dirname(__FILE__) . '/sirv/shortcodes.php');
require_once(dirname(__FILE__) . '/sirv/woo.class.php');
require_once(dirname(__FILE__) . '/sirv/options-service.php');

//add_action( 'wp_head', 'get_enqueued_scripts', 1000 );
function get_enqueued_scripts()
{
  $scripts = wp_scripts();
  var_dump(array_keys($scripts->groups));
}

//add_action('wp_enqueue_scripts', 'tstss', PHP_INT_MAX - 100);
function tstss()
{
  $scripts = wp_scripts();
  sirv_debug_msg($scripts->queue);
}

add_action('admin_head', 'sirv_global_logo_fix');

function sirv_global_logo_fix()
{
  echo '
  <style>
    a[href*="page=sirv/sirv/options.php"] img {
      padding-top:7px !important;
    }
  </style>';
}


function sirv_timer_log($msg = '', $type = '')
{
  global $isLogger;

  if (!$isLogger) return;

  $path = realpath(dirname(__FILE__));
  $fn = fopen($path . DIRECTORY_SEPARATOR . 'timerLog.txt', 'a+');

  global $startTime;
  global $startLoggerTime;
  $sec = 0;

  if ($type == 'start') {
    $startLoggerTime = time();
    $sec = 0;
  } else if ($type == 'end') {
    $sec = time() - $startLoggerTime;
  }

  if ($msg) {
    if ($type) {
      fwrite($fn, $startTime . "_" . $msg . "_" . $type . ' sec: ' . $sec . PHP_EOL);
    } else {
      fwrite($fn, $startTime . "_" . $msg . PHP_EOL);
    }
  }

  fwrite($fn, $startTime . "_" . "global working time(sec): " . (time() - $startTime) . PHP_EOL);

  fclose($fn);
}


//error_log("You messed up!", 3, "/var/tmp/my-errors.log");

//error_log('four ' . (microtime(true) - $time_pre) . PHP_EOL, 3, "/www/secewequ_376/public/wp-content/plugins/sirv/php_errors.log");


/*ini_set("log_errors", 1);
ini_set("error_log", "/tmp/php-error.log");
error_log( "Hello, errors!" );*/

global $s3client;
global $APIClient;
global $foldersData;
global $syncData;
global $pathsData;
global $isLocalHost;
global $isLoggedInAccount;
global $isAdmin;
global $isFetchUpload;
global $isFetchUrl;
global $base_prefix;
global $pagenow;
global $sirv_woo_is_enable;
global $sirv_cdn_url;

$s3client = false;
$APIClient = false;
$syncData = array();
$pathsData = array();
$isLocalHost = sirv_is_local_host();
$isLoggedInAccount = (get_option('SIRV_AWS_BUCKET') !== '' && get_option('SIRV_CDN_URL') !== '') ? true : false;
$isAdmin = sirv_isAdmin();
$isFetchUpload = true;
$isFetchUrl = false;
$base_prefix = sirv_get_base_prefix();


/*---------------------------------WooCommerce--------------------------------*/
$sirv_woo_is_enable_option = get_option('SIRV_WOO_IS_ENABLE');
$sirv_woo_is_enable = !empty($sirv_woo_is_enable_option) && $sirv_woo_is_enable_option == '2' ? true : false;

//if( $sirv_woo_is_enable && in_array($pagenow, array('post-new.php', 'post.php')) ){
if (in_array($pagenow, array('post-new.php', 'post.php'))) {
  $woo = new Woo;
}


add_action('woocommerce_init', 'wc_init');
function wc_init()
{
  global $sirv_woo_is_enable;

  add_action('woocommerce_product_after_variable_attributes', array('Woo', 'render_variation_gallery'), 10, 3);
  add_action('woocommerce_save_product_variation', array('Woo', 'save_sirv_variation_data'), 10, 2);

  if ($sirv_woo_is_enable) {
    //remove filter that conflict with sirv
    remove_filter('wc_get_template', 'wvg_gallery_template_override', 30, 2);
    remove_filter('wc_get_template_part', 'wvg_gallery_template_part_override', 30, 2);

    add_filter('wc_get_template_part', 'sirv_woo_template_part_override', 30, 3);
    add_filter('wc_get_template', 'sirv_woo_template_override', 30, 3);

    //add_action( 'woocommerce_before_single_product', 'sirv_on_woo_product_load', 10 );
  }
}


function sirv_woo_template_part_override($template, $slug, $name)
{
  $path = '';
  if ($slug == 'single-product/product-image') {
    $path = untrailingslashit(plugin_dir_path(__FILE__)) . '/sirv/woo-template.php';
  }

  return file_exists($path) ? $path : $template;
}


function sirv_woo_template_override($template, $template_name, $template_path)
{
  $path = '';

  if ($template_name == 'single-product/product-image.php') {
    $path = untrailingslashit(plugin_dir_path(__FILE__)) . '/sirv/woo-template.php';
  }

  /* if ( $template_name == 'single-product/product-thumbnails.php' ) {
    $path = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/sirv/woo-template-thumbs.php';
  } */

  return file_exists($path) ? $path : $template;
}


function sirv_on_woo_product_load()
{
  global $post;

  $woo = new Woo($post->ID);
  $woo->register_thumbs_filter();
}
/*-------------------------------WooCommerce END--------------------------------*/


function sirv_is_local_host()
{
  return (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')) || $_SERVER['SERVER_NAME'] == 'localhost' || preg_match('/\/\/(localhost|127.0.0.1)/ims', get_site_url()));
}


function sirv_isAdmin()
{
  $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
  $http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
  $pattern = '/wp-admin/';
  if (preg_match($pattern, $request_uri) || preg_match($pattern, $http_referer)) return true;

  return false;
}


function sirv_debug_msg($msg, $isBoolVar = false)
{
  $path = realpath(dirname(__FILE__));
  $fn = fopen($path . DIRECTORY_SEPARATOR . 'debug.txt', 'a+');
  if (is_array($msg)) {
    fwrite($fn, print_r($msg, true) . PHP_EOL);
  } else if (is_object($msg)) {
    fwrite($fn, print_r(json_decode(json_encode($msg), true), true) . PHP_EOL);
  } else {
    if ($isBoolVar) {
      $data = var_export($msg, true);
      fwrite($fn, $data . PHP_EOL);
    } else {
      fwrite($fn, $msg . PHP_EOL);
    }
  }

  fclose($fn);
}


function sirv_get_base_prefix()
{
  global $wpdb;

  $prefix = $wpdb->prefix;

  if (is_multisite()) $prefix = $wpdb->get_blog_prefix(0);

  return $prefix;
}


add_action('wp_insert_site', 'sirv_added_new_blog', 10);

function sirv_added_new_blog($new_site)
{
  global $wpdb;

  if (!function_exists('is_plugin_active_for_network')) {
    require_once(ABSPATH . '/wp-admin/includes/plugin.php');
  }

  if (is_plugin_active_for_network('sirv/sirv.php')) {
    $current_blog = $wpdb->blogid;
    switch_to_blog($new_site->blog_id);

    sirv_create_plugin_tables();
    sirv_update_options();

    switch_to_blog($current_blog);
  }
}


//create shortcode's table on plugin activate
register_activation_hook(__FILE__, 'sirv_activation_callback');

function sirv_activation_callback($networkwide)
{
  sirv_register_settings();

  if (function_exists('is_multisite') && is_multisite()) {
    if ($networkwide) {
      update_site_option('SIRV_WP_NETWORK_WIDE', '1');
      global $wpdb;
      $current_blog = $wpdb->blogid;
      $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
      foreach ($blogids as $blog_id) {
        switch_to_blog($blog_id);
        sirv_create_plugin_tables();
      }
      switch_to_blog($current_blog);
    } else {
      update_site_option('SIRV_WP_NETWORK_WIDE', '');
      sirv_create_plugin_tables();
    }
  } else {
    sirv_create_plugin_tables();
  }

  set_transient('isSirvActivated', true, 30);
  //migrations
  sirv_upgrade_plugin();
  sirv_congrat_notice();
}


add_action('plugins_loaded', 'sirv_upgrade_plugin');
function sirv_upgrade_plugin()
{
  $sirv_plugin_version_installed = get_option('SIRV_VERSION_PLUGIN_INSTALLED');


  if (empty($sirv_plugin_version_installed) || $sirv_plugin_version_installed != SIRV_PLUGIN_VERSION) {

    //4.1.1
    if (function_exists('is_multisite') && is_multisite()) {
      if (get_site_option('SIRV_WP_NETWORK_WIDE')) {
        global $wpdb;
        $current_blog = $wpdb->blogid;
        $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
        foreach ($blogids as $blog_id) {
          switch_to_blog($blog_id);

          sirv_update_options();
          update_option('SIRV_VERSION_PLUGIN_INSTALLED', SIRV_PLUGIN_VERSION);
        }
        switch_to_blog($current_blog);
      } else {
        sirv_update_options();
        update_option('SIRV_VERSION_PLUGIN_INSTALLED', SIRV_PLUGIN_VERSION);
      }
    } else {
      sirv_update_options();
      update_option('SIRV_VERSION_PLUGIN_INSTALLED', SIRV_PLUGIN_VERSION);
    }

    global $base_prefix;
    global $wpdb;

    $shortcodes_t = $base_prefix . 'sirv_shortcodes';

    $t_structure = $wpdb->get_results("DESCRIBE $shortcodes_t", ARRAY_A);
    $t_fields = sirv_get_field_names($t_structure);

    if (!in_array('shortcode_options', $t_fields)) {
      $wpdb->query("ALTER TABLE $shortcodes_t ADD COLUMN shortcode_options TEXT NOT NULL after images");
    }

    if (!in_array('timestamp', $t_fields)) {
      //$wpdb->query("ALTER TABLE $shortcodes_t ADD COLUMN shortcode_options TEXT NOT NULL after images");
      $wpdb->query("ALTER TABLE $shortcodes_t ADD COLUMN timestamp DATETIME NULL DEFAULT NULL AFTER shortcode_options");
    }

    if (!sirv_is_unique_field('attachment_id')) {
      sirv_set_unique_field('attachment_id');
    }

    sirv_fix_db();

    //5.0
    require_once(dirname(__FILE__) . '/sirv/woo.options.class.php');
    Woo_options::register_settings();

    //5.7.1
    sirv_remove_autoload();

  }
}


function sirv_remove_autoload(){
//SIRV_CLIENT_ID SIRV_CLIENT_SECRET SIRV_TOKEN SIRV_TOKEN_EXPIRE_TIME SIRV_MUTE SIRV_STAT SIRV_FOLDERS_DATA
$client_id = get_option('SIRV_CLIENT_ID');
$client_secret = get_option('SIRV_CLIENT_SECRET');
$token = get_option('SIRV_TOKEN');
$token_expire_time = get_option('SIRV_TOKEN_EXPIRE_TIME');
$mute = get_option('SIRV_MUTE');
$stat = get_option('SIRV_STAT');
$folders_data = get_option('SIRV_FOLDERS_DATA');

update_option('SIRV_CLIENT_ID', $client_id, 'no');
update_option('SIRV_CLIENT_SECRET', $client_secret, 'no');
update_option('SIRV_TOKEN', $token, 'no');
update_option('SIRV_TOKEN_EXPIRE_TIME', $token_expire_time, 'no');
update_option('SIRV_MUTE', $mute, 'no');
update_option('SIRV_STAT', $stat, 'no');
update_option('SIRV_FOLDERS_DATA', $folders_data, 'no');
}


function sirv_get_default_crop()
{
  $crop_data = array();
  $wp_sizes = sirv_get_image_sizes();

  ksort($wp_sizes);

  foreach ($wp_sizes as $size_name => $size) {
    $cropMethod = (bool) $size['crop'] ? 'wp_crop' : 'none';
    $crop_data[$size_name] = $cropMethod;
  }

  return json_encode($crop_data, ENT_QUOTES);
}



function sirv_update_options()
{
  if (get_option('WP_USE_SIRV_CDN') && !get_option('SIRV_ENABLE_CDN')) update_option('SIRV_ENABLE_CDN', get_option('WP_USE_SIRV_CDN'));
  if (get_option('WP_SIRV_SHORTCODES_PROFILES') && !get_option('SIRV_SHORTCODES_PROFILES')) update_option('SIRV_SHORTCODES_PROFILES', get_option('WP_SIRV_SHORTCODES_PROFILES'));
  if (get_option('WP_SIRV_CDN_PROFILES') && !get_option('SIRV_CDN_PROFILES')) update_option('SIRV_CDN_PROFILES', get_option('WP_SIRV_CDN_PROFILES'));
  if (get_option('WP_USE_SIRV_RESPONSIVE') && !get_option('SIRV_USE_SIRV_RESPONSIVE')) update_option('SIRV_USE_SIRV_RESPONSIVE', get_option('WP_USE_SIRV_RESPONSIVE'));
  if (get_option('WP_SIRV_JS') && !get_option('SIRV_JS')) update_option('SIRV_JS', get_option('WP_SIRV_JS'));
  if (get_option('WP_SIRV_JS_FILE') && !get_option('SIRV_JS_FILE')) update_option('SIRV_JS_FILE', get_option('WP_SIRV_JS_FILE'));
  if (get_option('WP_FOLDER_ON_SIRV')) {
    update_option('SIRV_FOLDER', get_option('WP_FOLDER_ON_SIRV'));
    delete_option('WP_FOLDER_ON_SIRV');
  }

  sirv_fill_empty_options();
}


function sirv_fill_empty_options()
{
  if (!get_option('SIRV_CLIENT_ID')) update_option('SIRV_CLIENT_ID', '', 'no');
  if (!get_option('SIRV_CLIENT_SECRET')) update_option('SIRV_CLIENT_SECRET', '', 'no');
  if (!get_option('SIRV_TOKEN')) update_option('SIRV_TOKEN', '', 'no');
  if (!get_option('SIRV_TOKEN_EXPIRE_TIME')) update_option('SIRV_TOKEN_EXPIRE_TIME', '', 'no');
  if (!get_option('SIRV_MUTE')) update_option('SIRV_MUTE', '', 'no');
  if (!get_option('SIRV_ACCOUNT_EMAIL')) update_option('SIRV_ACCOUNT_EMAIL', '');
  if (!get_option('SIRV_CDN_URL')) update_option('SIRV_CDN_URL', '');
  if (!get_option('SIRV_STAT')) update_option('SIRV_STAT', '', 'no');
  if (!get_option('SIRV_AWS_BUCKET')) update_option('SIRV_AWS_BUCKET', '');
  if (!get_option('SIRV_AWS_KEY')) update_option('SIRV_AWS_KEY', '');
  if (!get_option('SIRV_AWS_SECRET_KEY')) update_option('SIRV_AWS_SECRET_KEY', '');
  if (!get_option('SIRV_FETCH_MAX_FILE_SIZE')) update_option('SIRV_FETCH_MAX_FILE_SIZE', '');

  if (get_option('SIRV_AWS_HOST') !== 's3.sirv.com' || !get_option('SIRV_AWS_HOST')) update_option('SIRV_AWS_HOST', 's3.sirv.com');
  if (!get_option('SIRV_NETWORK_TYPE')) update_option('SIRV_NETWORK_TYPE', '2');
  if (!get_option('SIRV_PARSE_STATIC_IMAGES')) update_option('SIRV_PARSE_STATIC_IMAGES', '2');
  if (!get_option('SIRV_USE_SIRV_RESPONSIVE')) update_option('SIRV_USE_SIRV_RESPONSIVE', '0');
  if (!get_option('SIRV_ENABLE_CDN')) update_option('SIRV_ENABLE_CDN', '2');
  if (!get_option('SIRV_JS')) update_option('SIRV_JS', '2');
  if (!get_option('SIRV_JS_FILE')) update_option('SIRV_JS_FILE', '1');
  if (!get_option('SIRV_JS_FILE_EXTEND')) update_option('SIRV_JS_FILE_EXTEND', json_encode(array(
    'image' => true,
    'zoom' => true,
    'spin' => true,
    'video' => true,
    )));
  if (!get_option('SIRV_CUSTOM_CSS')) update_option('SIRV_CUSTOM_CSS', '');

  if (!get_option('SIRV_CROP_SIZES')) update_option('SIRV_CROP_SIZES', sirv_get_default_crop());
  if (!get_option('SIRV_RESPONSIVE_PLACEHOLDER')) update_option('SIRV_RESPONSIVE_PLACEHOLDER', '2');


  $domain = empty($_SERVER['HTTP_HOST']) ? 'MediaLibrary' : $_SERVER['HTTP_HOST'];
  if (!get_option('SIRV_FOLDER')) update_option('SIRV_FOLDER', 'WP_' . $domain);

  if (!get_site_option('SIRV_WP_NETWORK_WIDE')) update_site_option('SIRV_WP_NETWORK_WIDE', '');
}


function sirv_fix_db()
{
  global $wpdb;
  global $base_prefix;
  $wpdb->show_errors();
  $t_images = $wpdb->prefix . 'sirv_images';
  $t_errors = $base_prefix . 'sirv_fetching_errors';

  if (sirv_is_db_field_exists('sirv_images', 'sirvpath')) {
    //$wpdb->query("ALTER TABLE $t_images RENAME COLUMN 'wp_path' TO 'img_path'");
    $result = $wpdb->query("ALTER TABLE $t_images CHANGE `wp_path` `img_path` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL");
    $result = $wpdb->query("ALTER TABLE $t_images
                  DROP `sirvpath`,
                  DROP `sirv_image_url`,
                  DROP `sirv_folder`");
    $result = $wpdb->query("ALTER TABLE $t_images ADD `checks` TINYINT UNSIGNED NULL DEFAULT 0 AFTER `timestamp_synced`");
    $result = $wpdb->query("ALTER TABLE $t_images ADD `timestamp_checks` INT NULL DEFAULT NULL AFTER `checks`");
    $result = $wpdb->query("ALTER TABLE $t_images ADD `status` enum('NEW', 'PROCESSING', 'SYNCED', 'FAILED') DEFAULT NULL AFTER `size`");
    $result = $wpdb->query("ALTER TABLE $t_images ADD `error_type` TINYINT UNSIGNED NULL DEFAULT NULL AFTER `status`");
    $result = $wpdb->query("ALTER TABLE $t_images ADD `sirv_path` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `img_path`");
    $result = $wpdb->query("UPDATE $t_images SET status = 'SYNCED'");
    //$delete = $wpdb->query("TRUNCATE TABLE $t_images");
  }

  if (!sirv_is_db_field_exists('sirv_images', 'error_type')) {
    $result = $wpdb->query("ALTER TABLE $t_images ADD `error_type` TINYINT UNSIGNED NULL DEFAULT NULL AFTER `status`");
  }

  if (!sirv_is_db_field_exists('sirv_images', 'sirv_path')) {
    $result = $wpdb->query("ALTER TABLE $t_images ADD `sirv_path` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' AFTER `img_path`");
  }

  if (sirv_is_db_field_exists('sirv_images', 'timestamp_checks')) {
    $result = $wpdb->query("ALTER TABLE $t_images CHANGE COLUMN `timestamp_checks` `timestamp_checks` INT NULL DEFAULT NULL");
  }

  if (empty($wpdb->get_results("SHOW TABLES LIKE '$t_errors'", ARRAY_N))) {
    $sql_errors = "CREATE TABLE $t_errors (
      id int unsigned NOT NULL auto_increment,
      error_msg varchar(255) DEFAULT '',
      PRIMARY KEY  (id))
      ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_errors);
    sirv_fill_err_table($t_errors);
  } else {
    if( $wpdb->query("TRUNCATE TABLE $t_errors") ){
      $wpdb->delete($t_images, array('status' => 'FAILED'));
      sirv_fill_err_table($t_errors);
    }


  }
}


function sirv_fill_err_table($t_errors)
{
  global $wpdb;

  foreach (FetchError::get_errors() as $error_msg) {
    $wpdb->insert($t_errors, array('error_msg' => $error_msg));
  }
}


function sirv_is_db_field_exists($table, $field)
{
  global $wpdb;
  $table_name = $wpdb->prefix . $table;

  return !empty($wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE '$field'", ARRAY_A));
}


function sirv_get_field_names($data)
{
  $tmp_arr = array();

  foreach ($data as $key => $field_data) {
    $tmp_arr[] = $field_data['Field'];
  }

  return $tmp_arr;
}


add_action('wp_head', 'sirv_meta_head', 10);

function sirv_meta_head()
{

  $sirv_url = sirv_get_sirv_path();

  echo '<link rel="preconnect" href="' . $sirv_url . '" crossorigin>' . PHP_EOL;
  echo '<link rel="dns-prefetch" href="' . $sirv_url . '">' . PHP_EOL;

  //echo '<link rel="preconnect" href="https://scripts.sirv.com" crossorigin>' . PHP_EOL;
  //echo '<link rel="dns-prefetch" href="https://scripts.sirv.com">' . PHP_EOL;
}


//gutenberg includes
if (function_exists('register_block_type')) {
  if (!function_exists('sirv_addmedia_block')) {
    function sirv_addmedia_block()
    {

      wp_register_script(
        'sirv-addmedia-block-editor-js',
        plugins_url('/sirv/gutenberg/addmedia-block/editor-script.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'sirv_modal', 'sirv_logic', 'sirv_modal-logic', 'sirv_logic-md5', 'jquery'),
        false,
        true
      );

      /*wp_register_style(
        'sirv-addmedia-block-css',
        plugins_url( '/sirv/gutenberg/addmedia-block/style.css', __FILE__ ),
        array( 'wp-edit-blocks' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'sirv/gutenberg/addmedia-block/style.css' )
      );*/

      wp_register_style(
        'sirv-addmedia-block-editor-css',
        plugins_url('/sirv/gutenberg/addmedia-block/editor-style.css', __FILE__),
        array('wp-edit-blocks'),
        filemtime(plugin_dir_path(__FILE__) . 'sirv/gutenberg/addmedia-block/editor-style.css')
      );

      register_block_type('sirv/addmedia-block', array(
        'editor_script' => 'sirv-addmedia-block-editor-js',
        'editor_style'  => 'sirv-addmedia-block-editor-css',
        //'style'         => 'sirv-addmedia-block-css'
      ));
    }

    add_action('init', 'sirv_addmedia_block');
  }
}


//show message on plugin activation
add_action('admin_notices', 'sirv_admin_notices');

function sirv_admin_notices(){
  if ($notices = get_option('sirv_admin_notices')) {
    foreach ($notices as $notice) {
      echo "<div class='updated'><p>$notice</p></div>";
    }
    delete_option('sirv_admin_notices');
  }

  sirv_review_notice();
  sirv_empty_logins_notice();
}


function sirv_congrat_notice()
{
  $notices = get_option('sirv_admin_notices', array());
  $notices[] = 'Congratulations, you\'ve just installed Sirv for WordPress! Now <a href="admin.php?page=' . SIRV_PLUGIN_PATH . '/sirv/submenu_pages/account.php">configure the Sirv plugin</a> to start using it.';

  update_option('sirv_admin_notices', $notices);
}


function sirv_empty_logins_notice(){

  $page = 'sirv/sirv/submenu_pages/account.php';

  if( isset($_GET['page']) && $_GET['page'] == $page ) return;

  $sirvAPIClient = sirv_getAPIClient();
  $sirvStatus = $sirvAPIClient->preOperationCheck();
  $isMuted = $sirvAPIClient->isMuted();

  if (!$sirvStatus && !$isMuted) {

    $notice = '<p>Please <a href="admin.php?page=' . SIRV_PLUGIN_PATH . '/sirv/submenu_pages/account.php">configure the Sirv plugin</a> to start using it.</p>';
    echo sirv_get_wp_notice($notice, 'warning', false);
  }
}


function sirv_get_wp_notice($msg, $notice_type = 'info', $is_dismissible = true)
{
  //notice-error, notice-warning, notice-success, or notice-info
  $dismissible = $is_dismissible ? 'is-dismissible' : '';
  $notice = '<div class="notice notice-' . $notice_type . ' ' . $dismissible . '">' . $msg . '</div>';

  return $notice;
}


function sirv_review_notice()
{
  $sirv_review_notice = get_option('sirv_review_notice');
  if (empty($sirv_review_notice)) {
    update_option('sirv_review_notice', time());
    $sirv_review_notice = NULL;
  }
  if (is_numeric($sirv_review_notice)) {
    $noticed_time = (int) $sirv_review_notice;
    $fire_time = $noticed_time + (14 * 24 * 60 * 60);
    if (time() >= $fire_time) {
      wp_enqueue_script('sirv_review', plugins_url('/sirv/js/wp-sirv-review.js', __FILE__), array('jquery'), '1.0.0');

      $notice = '<p>We noticed you\'ve been using Sirv for some time now - we hope you love it! We\'d be thrilled if you could <a target="_blank" href="https://wordpress.org/support/plugin/sirv/reviews/">give us a 5-star rating on WordPress.org!</a></p>
      <p>As a thank you, we\'ll give you 1GB extra free storage (regardless of the rating you choose).</p>
      <p>If you need help with the Sirv plugin, please <a href="admin.php?page=' . SIRV_PLUGIN_PATH . '/sirv/submenu_pages/feedback.php">contact our team</a> and we\'ll reply ASAP.</p>';

      echo sirv_get_wp_notice($notice, 'info');
    }
  }
}


function sirv_create_plugin_tables()
{
  global $base_prefix;
  global $wpdb;

  $t_shortcodes = $base_prefix . 'sirv_shortcodes';
  $t_images = $wpdb->prefix . 'sirv_images';
  $t_errors = $base_prefix . 'sirv_fetching_errors';

  $sql_shortcodes = "CREATE TABLE $t_shortcodes (
      id int unsigned NOT NULL auto_increment,
      width varchar(20) DEFAULT 'auto',
      thumbs_height varchar(20) DEFAULT NULL,
      gallery_styles varchar(255) DEFAULT NULL,
      align varchar(30) DEFAULT '',
      profile varchar(100) DEFAULT 'false',
      link_image varchar(10) DEFAULT 'false',
      show_caption varchar(10) DEFAULT 'false',
      use_as_gallery varchar(10) DEFAULT 'false',
      use_sirv_zoom varchar(10) DEFAULT 'false',
      images text DEFAULT NULL,
      shortcode_options text NOT NULL,
      timestamp datetime DEFAULT NULL,
      PRIMARY KEY  (id))
      ENGINE=InnoDB DEFAULT CHARSET=utf8;";

  $sql_sirv_images = "CREATE TABLE $t_images (
      id int unsigned NOT NULL auto_increment,
      attachment_id int(11) NOT NULL,
      img_path varchar(255) DEFAULT NULL,
      sirv_path varchar(255) DEFAULT NULL,
      size int(10) DEFAULT NULL,
      status enum('NEW', 'PROCESSING', 'SYNCED', 'FAILED') DEFAULT NULL,
      error_type TINYINT UNSIGNED NULL DEFAULT NULL,
      timestamp datetime DEFAULT NULL,
      timestamp_synced datetime DEFAULT NULL,
      checks TINYINT UNSIGNED NULL DEFAULT 0,
      timestamp_checks INT DEFAULT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY `unique_key` (attachment_id)
      )ENGINE=InnoDB DEFAULT CHARSET=utf8;";

  $sql_errors = "CREATE TABLE $t_errors (
      id int unsigned NOT NULL auto_increment,
      error_msg varchar(255) DEFAULT '',
      PRIMARY KEY  (id))
      ENGINE=InnoDB DEFAULT CHARSET=utf8;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

  $is_sirv_images_exists = $wpdb->get_results("SHOW TABLES LIKE '$t_images'", ARRAY_N);
  $is_sirv_shortcodes_exists = $wpdb->get_results("SHOW TABLES LIKE '$t_shortcodes'", ARRAY_N);
  $is_sirv_errors_exists = $wpdb->get_results("SHOW TABLES LIKE '$t_errors'", ARRAY_N);

  if (empty($is_sirv_shortcodes_exists)) dbDelta($sql_shortcodes);
  if (empty($is_sirv_images_exists)) dbDelta($sql_sirv_images);
  if (empty($is_sirv_errors_exists)) {
    dbDelta($sql_errors);
    foreach (FetchError::get_errors() as $error_msg) {
      $wpdb->insert($t_errors, array('error_msg' => $error_msg));
    }
  }
}

register_deactivation_hook(__FILE__, 'sirv_deactivation_callback');

function sirv_deactivation_callback()
{
  //some code here
}


$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'sirv_plugin_settings_link');

function sirv_plugin_settings_link($links)
{
  $settings_link = '<a href="admin.php?page=' . SIRV_PLUGIN_PATH . '/sirv/options.php">Settings</a>';
  array_unshift($links, $settings_link);
  return $links;
}


//add button Sirv Media near Add Media
add_action('media_buttons', 'sirv_button', 11);

function sirv_button($editor_id = 'content')
{
  wp_register_style('sirv_style', plugins_url('/sirv/css/wp-sirv.css', __FILE__));
  wp_enqueue_style('sirv_style');
  wp_register_style('sirv_mce_style', plugins_url('/sirv/css/wp-sirv-shortcode-view.css', __FILE__));
  wp_enqueue_style('sirv_mce_style');
  wp_register_script('sirv_logic', plugins_url('/sirv/js/wp-sirv.js', __FILE__), array('jquery', 'jquery-ui-sortable'), '1.1.0');
  wp_localize_script('sirv_logic', 'sirv_ajax_object', array('ajaxurl' => admin_url('admin-ajax.php'), 'assets_path' => plugins_url('/sirv/assets', __FILE__), 'plugin_path' => SIRV_PLUGIN_PATH));
  wp_enqueue_script('sirv_logic');
  wp_enqueue_script('sirv_logic-md5', plugins_url('/sirv/js/wp-sirv-md5.min.js', __FILE__), array(), '1.0.0');
  wp_enqueue_script('sirv_modal', plugins_url('/sirv/js/wp-sirv-bpopup.min.js', __FILE__), array('jquery'), '1.0.0');
  wp_enqueue_script('sirv_modal-logic', plugins_url('/sirv/js/wp-sirv-modal.js', __FILE__), array('jquery'), '1.0.0');

  $isNotEmptySirvOptions = sirv_check_empty_options_on_backend();
  wp_localize_script('sirv_modal-logic', 'modal_object', array(
    'media_add_url' =>  plugins_url('/sirv/templates/media_add.html', __FILE__), 'login_error_url' => plugins_url('/sirv/templates/login_error.html', __FILE__), 'featured_image_url' => plugins_url('/sirv/templates/featured_image.html', __FILE__), 'isNotEmptySirvOptions' => $isNotEmptySirvOptions
  ));
  wp_enqueue_script('sirv-shortcodes-page', plugins_url('/sirv/js/wp-sirv-shortcodes-page.js', __FILE__), array('jquery'), '1.0.0');

  echo '<a href="#" class="button sirv-modal-click" title="Sirv add/insert images"><span class="dashicons dashicons-format-gallery" style="padding-top: 2px;"></span> Add Sirv Media</a><div class="sirv-modal"><div class="modal-content"></div></div>';
}


function sirv_check_empty_options_on_backend()
{
  $host = getValue::getOption('SIRV_AWS_HOST');
  $bucket = getValue::getOption('SIRV_AWS_BUCKET');
  $key = getValue::getOption('SIRV_AWS_KEY');
  $secret_key = getValue::getOption('SIRV_AWS_SECRET_KEY');

  if (empty($host) || empty($bucket) || empty($key) || empty($secret_key)) {
    return false;
  } else {
    return true;
  }
}


//create menu for wp plugin and register settings
add_action("admin_menu", "sirv_create_menu", 0);

function sirv_create_menu()
{
  $settings_item = SIRV_PLUGIN_PATH_WITH_SLASH . '/sirv/options.php';
  $library_item = SIRV_PLUGIN_PATH_WITH_SLASH . '/sirv/media_library.php';
  $shortcodes_view_item = SIRV_PLUGIN_PATH_WITH_SLASH . '/sirv/shortcodes-view.php';
  $account_item = SIRV_PLUGIN_PATH_WITH_SLASH . '/sirv/submenu_pages/account.php';
  //$stats_item = SIRV_PLUGIN_PATH_WITH_SLASH . '/sirv/submenu_pages/stats.php';
  $help_item = SIRV_PLUGIN_PATH_WITH_SLASH . '/sirv/submenu_pages/help.php';
  $feedback_item = SIRV_PLUGIN_PATH_WITH_SLASH . '/sirv/submenu_pages/feedback.php';
  //$stats = 'admin.php?page='. SIRV_PLUGIN_PATH .'/sirv/options.php#sirv-stats';

  add_menu_page('Sirv Menu', 'Sirv', 'manage_options', $settings_item, NULL, plugins_url(SIRV_PLUGIN_PATH_WITH_SLASH . '/sirv/assets/menu-icon.svg'));
  add_submenu_page($settings_item, 'Sirv Settings', 'Settings', 'manage_options', $settings_item);
  add_submenu_page($settings_item, 'Sirv Account', 'Account', 'manage_options', $account_item);
  //add_submenu_page($settings_item, 'Sirv Stats', 'Stats', 'manage_options', $stats_item);
  add_submenu_page($settings_item, 'Sirv Shortcodes', 'Shortcodes', 'manage_options', $shortcodes_view_item);
  add_submenu_page($settings_item, 'Sirv Media Library', 'Media Library', 'manage_options', $library_item);
  add_submenu_page($settings_item, 'Sirv Help', 'Help', 'manage_options', $help_item);
  add_submenu_page($settings_item, 'Sirv Feedback', 'Feedback', 'manage_options', $feedback_item);
  //add_submenu_page( $settings_item, 'Sirv Stats', 'Stats', 'manage_options', $stats);
}


add_action('admin_enqueue_scripts', 'sirv_admin_scripts', 20);
function sirv_admin_scripts()
{
  //if(!is_admin() && !(isset($_GET['page'] && $_GET['page'])) return;
  if (!is_admin()) return;

  $option_page = SIRV_PLUGIN_PATH . '/sirv/options.php';
  $account_page = SIRV_PLUGIN_PATH . '/sirv/submenu_pages/account.php';
  $stats_page = SIRV_PLUGIN_PATH . '/sirv/submenu_pages/stats.php';
  $help_page = SIRV_PLUGIN_PATH . '/sirv/submenu_pages/help.php';
  $feedback_page = SIRV_PLUGIN_PATH . '/sirv/submenu_pages/feedback.php';

  global $pagenow;

  //check if this is post or new post page or categories
  if (in_array($pagenow, array('post-new.php', 'post.php', 'edit-tags.php'))) {
    //check if gutenberg is active or it is categories page
    if (function_exists('register_block_type') || $pagenow == 'edit-tags.php') {
      wp_enqueue_style('fontAwesome', "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css", array());
      wp_register_style('sirv_style', plugins_url('/sirv/css/wp-sirv.css', __FILE__));
      wp_enqueue_style('sirv_style');
      wp_register_style('sirv_mce_style', plugins_url('/sirv/css/wp-sirv-shortcode-view.css', __FILE__));
      wp_enqueue_style('sirv_mce_style');
      wp_register_script('sirv_logic', plugins_url('/sirv/js/wp-sirv.js', __FILE__), array('jquery', 'jquery-ui-sortable'), '1.1.0');
      wp_localize_script('sirv_logic', 'sirv_ajax_object', array('ajaxurl' => admin_url('admin-ajax.php'), 'assets_path' => plugins_url('/sirv/assets', __FILE__), 'plugin_path' => SIRV_PLUGIN_PATH, 'sirv_cdn_url' => get_option('SIRV_CDN_URL')));
      wp_enqueue_script('sirv_logic');
      wp_enqueue_script('sirv_logic-md5', plugins_url('/sirv/js/wp-sirv-md5.min.js', __FILE__), array(), '1.0.0');
      wp_enqueue_script('sirv_modal', plugins_url('/sirv/js/wp-sirv-bpopup.min.js', __FILE__), array('jquery'), '1.0.0');
      wp_enqueue_script('sirv_modal-logic', plugins_url('/sirv/js/wp-sirv-modal.js', __FILE__), array('jquery'), '1.0.0');

      $isNotEmptySirvOptions = sirv_check_empty_options_on_backend();
      wp_localize_script(
        'sirv_modal-logic',
        'modal_object',
        array(
          'media_add_url' =>  plugins_url('/sirv/templates/media_add.html', __FILE__),
          'login_error_url' => plugins_url('/sirv/templates/login_error.html', __FILE__),
          'featured_image_url' => plugins_url('/sirv/templates/featured_image.html', __FILE__),
          'woo_media_add_url' => plugins_url('/sirv/templates/woo_media_add.html', __FILE__),
          'isNotEmptySirvOptions' => $isNotEmptySirvOptions
        )
      );
      wp_enqueue_script('sirv-shortcodes-page', plugins_url('/sirv/js/wp-sirv-shortcodes-page.js', __FILE__), array('jquery'), '1.0.0');
    }
  }

  if ( isset($_GET['page']) && ( $_GET['page'] == $option_page || $_GET['page'] == $help_page ) ) {
    wp_register_style('sirv_options_style', plugins_url('/sirv/css/wp-options.css', __FILE__));
    wp_enqueue_style('sirv_options_style');
    wp_enqueue_script('sirv_scrollspy', plugins_url('/sirv/js/scrollspy.js', __FILE__), array('jquery'), '1.0.0');
    wp_enqueue_script('sirv_options', plugins_url('/sirv/js/wp-options.js', __FILE__), array('jquery'), '1.0.0', true);
  }

  if (isset($_GET['page']) && ( $_GET['page'] == $feedback_page || $_GET['page'] == $account_page ||  $_GET['page'] == $stats_page) ) {
    wp_register_style('sirv_options_style', plugins_url('/sirv/css/wp-options.css', __FILE__));
    wp_enqueue_style('sirv_options_style');
    wp_enqueue_script('sirv_options', plugins_url('/sirv/js/wp-options.js', __FILE__), array('jquery'), '1.0.0', true);
  }

  if (isset($_GET['page']) && $_GET['page'] == SIRV_PLUGIN_PATH . '/sirv/shortcodes-view.php') {
    wp_enqueue_style('fontAwesome', "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css", array());
    wp_register_style('sirv_style', plugins_url('/sirv/css/wp-sirv.css', __FILE__));
    wp_enqueue_style('sirv_style');
    wp_enqueue_script('sirv_logic', plugins_url('/sirv/js/wp-sirv.js', __FILE__), array('jquery', 'jquery-ui-sortable'), '1.0.0');
    wp_localize_script('sirv_logic', 'sirv_ajax_object', array('ajaxurl' => admin_url('admin-ajax.php'), 'assets_path' => plugins_url('/sirv/assets', __FILE__)));
    wp_enqueue_script('sirv_logic-md5', plugins_url('/sirv/js/wp-sirv-md5.min.js', __FILE__), array(), '1.0.0');
    wp_enqueue_script('sirv_modal', plugins_url('/sirv/js/wp-sirv-bpopup.min.js', __FILE__), array('jquery'), '1.0.0');

    wp_localize_script('sirv_modal', 'modal_object', array('media_add_url' =>  plugins_url('/sirv/templates/media_add.html', __FILE__), 'login_error_url' => plugins_url('/sirv/templates/login_error.html', __FILE__), 'featured_image_url' => plugins_url('/sirv/templates/featured_image.html', __FILE__)));

    wp_register_script('sirv-shortcodes-page', plugins_url('/sirv/js/wp-sirv-shortcodes-page.js', __FILE__), array('jquery'), '1.0.0');
    wp_enqueue_script('sirv-shortcodes-page');
    wp_localize_script('sirv-shortcodes-page', 'sirvShortcodeObject', array('isShortcodesPage' => true));
  }
}




//load sirv widget for elementor builder
add_action('plugins_loaded', 'sirv_elementor_widget', 10);
function sirv_elementor_widget()
{
  if (did_action('elementor/loaded')) {
    require_once(__DIR__ . '/sirv/htmlBuilders/elementor/Plugin.php');
  }
}


//include plugin for tinyMCE to show sirv gallery shortcode in visual mode
add_filter('mce_external_plugins', 'sirv_tinyMCE_plugin_shortcode_view');

function sirv_tinyMCE_plugin_shortcode_view()
{
  return array('sirvgallery' => plugins_url('sirv/js/wp-sirv-shortcode-view.js', __FILE__));
}


add_filter( 'script_loader_tag', 'sirv_add_defer_to_js', 10, 2 );

function sirv_add_defer_to_js($tag, $handle)
{
  /* print('<br>-------------------<br>');
  print_r($handle);
  print('<br>-------------------<br>'); */

  //sirv_debug_msg($handle);

  //global $wp_scripts;
  //sirv_debug_msg($wp_scripts);

  if ('sirv-js' !== $handle) {
    return $tag;
  }

  if (stripos($tag, 'sirv.full.js') === false) {
    $js_components = getValue::getJsLoadcomponents();
    //$components_str = getValue::renderComponentsToString($js_components);
    return str_replace('src',  getValue::renderComponentsToString($js_components) . 'src', $tag);
  }

  return $tag;
}


add_action('admin_init', 'sirv_admin_init');
function sirv_admin_init()
{
  sirv_tinyMCE_plugin_shortcode_view_styles();
  sirv_redirect_to_options();
}


//add styles for tinyMCE plugin
function sirv_tinyMCE_plugin_shortcode_view_styles()
{
  add_editor_style(plugins_url('/sirv/css/wp-sirv-shortcode-view.css', __FILE__));
}

//redirect to options after activate plugin
function sirv_redirect_to_options()
{
  // Bail if no activation redirect
  if (!get_transient('isSirvActivated')) {
    return;
  }

  // Delete the redirect transient
  delete_transient('isSirvActivated');

  // Bail if activating from network, or bulk
  if (is_network_admin() || isset($_GET['activate-multi'])) {
    return;
  }

  if (get_option('SIRV_AWS_BUCKET') == '' || get_option('SIRV_AWS_KEY') == '' || get_option('SIRV_AWS_SECRET_KEY') == '') {
    // Redirect to bbPress about page
    wp_safe_redirect(add_query_arg(array('page' => SIRV_PLUGIN_PATH . '/sirv/submenu_pages/account.php'), admin_url('admin.php')));
  }
}


function sirv_register_settings()
{
  register_setting('sirv-settings-group', 'SIRV_AWS_KEY');
  register_setting('sirv-settings-group', 'SIRV_AWS_SECRET_KEY');
  register_setting('sirv-settings-group', 'SIRV_AWS_HOST');
  register_setting('sirv-settings-group', 'SIRV_AWS_BUCKET');
  register_setting('sirv-settings-group', 'SIRV_FOLDER');
  register_setting('sirv-settings-group', 'SIRV_ENABLE_CDN');
  register_setting('sirv-settings-group', 'SIRV_NETWORK_TYPE');
  register_setting('sirv-settings-group', 'SIRV_PARSE_STATIC_IMAGES');
  register_setting('sirv-settings-group', 'SIRV_CLIENT_ID');
  register_setting('sirv-settings-group', 'SIRV_CLIENT_SECRET');
  register_setting('sirv-settings-group', 'SIRV_TOKEN');
  register_setting('sirv-settings-group', 'SIRV_TOKEN_EXPIRE_TIME');
  register_setting('sirv-settings-group', 'SIRV_MUTE');
  register_setting('sirv-settings-group', 'SIRV_ACCOUNT_EMAIL');
  register_setting('sirv-settings-group', 'SIRV_CDN_URL');
  register_setting('sirv-settings-group', 'SIRV_STAT');
  register_setting('sirv-settings-group', 'SIRV_FETCH_MAX_FILE_SIZE');

  register_setting('sirv-settings-group', 'SIRV_SHORTCODES_PROFILES');
  register_setting('sirv-settings-group', 'SIRV_CDN_PROFILES');
  register_setting('sirv-settings-group', 'SIRV_USE_SIRV_RESPONSIVE');

  register_setting('sirv-settings-group', 'SIRV_VERSION_PLUGIN_INSTALLED');
  register_setting('sirv-settings-group', 'SIRV_JS');
  register_setting('sirv-settings-group', 'SIRV_JS_FILE');
  register_setting('sirv-settings-group', 'SIRV_JS_FILE_EXTEND');
  register_setting('sirv-settings-group', 'SIRV_CUSTOM_CSS');

  register_setting('sirv-settings-group', 'SIRV_CROP_SIZES');
  register_setting('sirv-settings-group', 'SIRV_RESPONSIVE_PLACEHOLDER');

  register_setting('sirv-settings-group', 'SIRV_WP_NETWORK_WIDE');

  sirv_fill_empty_options();

  require_once(dirname(__FILE__) . '/sirv/woo.options.class.php');
  Woo_options::register_settings();
}


add_action('update_option_SIRV_NETWORK_TYPE', 'sirv_set_network_type_config', 10, 2);
function sirv_set_network_type_config($old_value, $new_value)
{
  if ($old_value !== $new_value) {
    $sirvAPIClient = sirv_getAPIClient();
    $sirvAPIClient->configCDN($new_value === '1', get_option('SIRV_AWS_BUCKET'));
  }
}


add_action('update_option_SIRV_FOLDER', 'sirv_set_folder_config', 10, 2);
function sirv_set_folder_config($old_value, $new_value)
{
  if ($old_value !== $new_value) {

    $s3object = sirv_getS3Client();
    $s3object->createFolder($new_value . '/');

    $sirvAPIClient = sirv_getAPIClient();
    $sirvAPIClient->setFolderOptions($new_value, array('scanSpins' => false));
  }
}

add_action('update_option_SIRV_WOO_IS_ENABLE', 'sirv_set_mv_js', 10, 2);
function sirv_set_mv_js($old_value, $new_value)
{
  if ($old_value !== $new_value && $new_value == '2') {
    update_option('SIRV_JS_FILE', '3');
  }
}


function sirv_is_unique_field($field)
{
  global $wpdb;
  $sirv_images_t = $wpdb->prefix . 'sirv_images';

  $check_data = $wpdb->get_results("SHOW INDEXES FROM $sirv_images_t WHERE Column_name='$field' AND NOT Non_unique", ARRAY_A);

  if (empty($check_data) || $check_data[0]['Non_unique'] == 1) return false;
  else return true;
}


function sirv_set_unique_field($field)
{
  global $wpdb;
  $sirv_images_t = $wpdb->prefix . 'sirv_images';
  $duplicated_ids = array();

  $duplicates_count = $wpdb->get_results("
    SELECT COUNT(t1.id) AS count FROM $sirv_images_t t1
    INNER JOIN $sirv_images_t t2
    WHERE t1.id > t2.id AND t1.$field = t2.$field
    ", ARRAY_A);

  $counter = intval($duplicates_count[0]['count']) >= 1000 ? 1000 : intval($duplicates_count[0]['count']);

  do {
    $duplicated_ids = $wpdb->get_results("
    SELECT t1.id FROM $sirv_images_t t1
    INNER JOIN $sirv_images_t t2
    WHERE t1.id > t2.id AND t1.$field = t2.$field
    LIMIT 1000
    ", ARRAY_A);

    if (!empty($duplicated_ids)) {
      $ids = implode("','", array_values(array_unique(sirv_flattern_array($duplicated_ids, true, 'id'))));
      $wpdb->query("DELETE FROM $sirv_images_t WHERE id IN ('$ids')");
    }

    if ($counter >= intval($duplicates_count[0]['count'])) break;
    else $counter += 1000;
  } while (!empty($duplicated_ids));

  $wpdb->query("ALTER TABLE $sirv_images_t ADD UNIQUE ($field)");
}


if (get_option('SIRV_JS') === '1') {
  add_action('wp_enqueue_scripts', 'sirv_add_sirv_js', 20);
}


function sirv_add_sirv_js()
{
  //$sirv_js_path = getValue::getOption('SIRV_JS_FILE');
  $sirv_js_path = getValue::getSirvJSPath('SIRV_JS_FILE');

  wp_register_script('sirv-js', $sirv_js_path, array(), false, false);
  wp_enqueue_script('sirv-js');

  $sirv_custom_css = get_option('SIRV_CUSTOM_CSS');
  if (!empty($sirv_custom_css)) {
    wp_register_style('sirv-custom-css', false);
    wp_enqueue_style('sirv-custom-css');

    wp_add_inline_style('sirv-custom-css', $sirv_custom_css);
  }
}


function sirv_buffer_start()
{
  ob_start("sirv_check_responsive");
}


function sirv_buffer_end()
{
  ob_end_flush();
  sirv_processFetchQueue();
}


function sirv_check_responsive($content)
{

  if (is_admin()) return $content;

  if (get_option('SIRV_JS') === '2') {
    $pattern = '/<(img|div).*?class=\".*?Sirv.*?\"/s';
    /* $sirvjs_pattern = '/(<script.*?src=[\"\'].*?sirv.com\/.*?sirv\.js.*?[\"\'].*?>)/s'; */
    /* $sirvjs_pattern = '/(<script.*?src=[\"\']https:\/\/scripts\.sirv\.com\/.*?sirv(\.full)?\.js.*?[\"\'].*?>)/s'; */
    $sirvjs_pattern = '/(<script\ssrc=[\"\']https:\/\/scripts\.sirv\.com\/.*?sirv(\.full)?\.js.*?[\"\'].*?>)/s';

    if (preg_match($pattern, $content) === 1) {
      if (preg_match($sirvjs_pattern, $content) == 0) {
        //$sirv_js_path = getValue::getOption('SIRV_JS_FILE');
        $sirv_js_path = getValue::getSirvJSPath('SIRV_JS_FILE');
        $components_str = '';
        if(stripos($sirv_js_path, 'sirv.full.js') === false){
          $js_components = getValue::getJsLoadcomponents();
          $components_str = getValue::renderComponentsToString($js_components);
        }

        $content = preg_replace('/(<\/head>)/is', '<script src="' . $sirv_js_path . '"'. $components_str .'></script>$1', $content, 1);
      }

      $sirv_custom_css = get_option('SIRV_CUSTOM_CSS');
      if (!empty($sirv_custom_css)) {
        $content = preg_replace('/(<\/head>)/is', '<style id="sirv-custom-css">' . $sirv_custom_css . '</style>$1', $content, 1);
      }
    }
  }

  //remove BOM symbol
  $content = str_replace("\xEF\xBB\xBF", '', $content);

  //if cdn on parse  non wp proccessing images and return cdn version
  if (get_option('SIRV_ENABLE_CDN') === '1' && get_option('SIRV_PARSE_STATIC_IMAGES') == '1') {
    $content = sirv_the_content($content, 'content');
  }

  return $content;
}


/* function sirv_get_sirv_js_load_components(){
  //image, zoom, 360 spin, video
  //<script src="https://scripts.sirv.com/sirvjs/v3/sirv.js" data-components="image, spin"></script>
  $load_components = Array();
  $data = json_decode(get_option('SIRV_JS_FILE_EXTEND'), true);

  foreach ($data as $component => $isLoad) {
    if($isLoad){
      $load_components[] = $component;
    }
  }

  return $load_components;
} */


if (!function_exists("sirv_fix_envision_url")) {
  function sirv_fix_envision_url($url, $w, $h, $crop = true)
  {
    $clsUrl = (stripos($url, '?') === false) ? $url : preg_replace('/\?.*/is', '', $url);
    $mdfyUrl = '';
    if ($crop) {
      $mdfyUrl = "$clsUrl?w=$w&h=$h&scale.option=fill&cw=$w&ch=$h&cx=center&cy=center";
    } else {
      $mdfyUrl = "$clsUrl?w=$w&h=$h";
    }

    return $mdfyUrl;
  }
}


add_filter('fl_builder_render_css', 'sirv_builder_render_css', 10, 3);
function sirv_builder_render_css($css, $nodes, $global_settings)
{
  return sirv_the_content($css, 'css');
}


// remove http(s) from host in sirv options
add_action('admin_notices', 'sirv_check_option');

function sirv_check_option()
{
  global $pagenow;
  if ($pagenow == 'admin.php' && $_GET['page'] == SIRV_PLUGIN_PATH . '/sirv/options.php') {
    if ((isset($_GET['updated']) && $_GET['updated'] == 'true') || (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true')) {
      update_option('SIRV_AWS_HOST', preg_replace('/(http|https)\:\/\/(.*)/ims', '$2', get_option('SIRV_AWS_HOST')));
    }
  }
}



add_action('init', 'sirv_init', 20);
function sirv_init()
{
  global $isAdmin;

  if (is_admin() || $isAdmin) return;

  global $isLoggedInAccount;

  if (get_option('SIRV_ENABLE_CDN') === '1' && $isLoggedInAccount) {

    $GLOBALS['sirv_wp_additional_image_sizes'] = isset($GLOBALS['_wp_additional_image_sizes']) ? $GLOBALS['_wp_additional_image_sizes'] : array();

    add_filter('wp_get_attachment_image_src', 'sirv_wp_get_attachment_image_src', 10, 4);
    add_filter('image_downsize', "sirv_image_downsize", 10, 3);
    add_filter('wp_get_attachment_url', 'sirv_wp_get_attachment_url', 10, 2);
    add_filter('wp_calculate_image_srcset', 'sirv_add_custom_image_srcset', 10, 5);
    add_filter('vc_wpb_getimagesize', 'sirv_vc_wpb_filter', 10, 3);
    add_filter('envira_gallery_image_src', 'sirv_envira_crop', 10, 4);
    add_filter('wp_prepare_attachment_for_js', 'sirv_wp_prepare_attachment_for_js', 10, 3);

    if (get_option('SIRV_USE_SIRV_RESPONSIVE') === '1') {
      add_filter('wp_get_attachment_image_attributes', 'sirv_do_responsive_images', 99, 3);
    }
  }

  if ( get_option('SIRV_JS') === '2' ) {
    add_action('wp_head', 'sirv_buffer_start', 0);
    add_action('wp_footer', 'sirv_buffer_end', PHP_INT_MAX - 100);
  }

  add_action('wp_enqueue_scripts', 'sirv_enqueue_frontend_scripts', 30);
}


//as filter wp_get_attachment_thumb_url doesn't work, need use filter image_downsize to get correct links with resized images from SIRV
function sirv_image_downsize($downsize, $attachment_id, $size)
{

  if (empty($downsize)) return false;

  $wp_sizes = sirv_get_image_sizes();
  $img_sizes = array();
  $image = wp_get_attachment_url($attachment_id);

  if (empty($image) || empty($size) || $size == 'full' || (is_array($size) && empty($size[0]) && empty($size[1]))) {
    return false;
  }

  if (is_string($size) && !empty($size)) {
    if (!empty($wp_sizes) && in_array($size, array_keys($wp_sizes))) {
      $img_sizes['width'] = $wp_sizes[$size]['width'];
      $img_sizes['height'] = $wp_sizes[$size]['height'];
      $img_sizes['isCrop'] = (bool) $wp_sizes[$size]['crop'];
    }
  } elseif (is_array($size)) {
    $img_sizes['width'] = $size[0];
    $img_sizes['height'] = $size[1];
    $img_sizes['isCrop'] = $size[0] === $size[1] ? true : false;
  }

  if (empty($img_sizes)) return false;

  $scaled_img = $image . sirv_get_scale_pattern($img_sizes['width'], $img_sizes['height'], $img_sizes['isCrop']);

  return array($scaled_img, $img_sizes['width'], $img_sizes['height']);
}


function sirv_wp_get_attachment_thumb_url($url, $post_id)
{
  return $url;
}


function sirv_envira_crop($resized_image, $id, $item, $data)
{

  if (is_admin()) return $resized_image;

  if (stripos($resized_image, 'sirv.com') !== false) {
    preg_match('/(^http.*)-(\d{2,4})x(\d{2,4})(_[a-z]{1,2})?(\..*)/is', $resized_image, $m);

    $orig_url = '';
    $w = 0;
    $h = 0;
    $isCrop = false;

    if (!empty($m)) {
      $orig_url = $m[1] . $m[5];
      $w = $m[2];
      $h = $m[3];
      $isCrop = $m[4] !== '' ? true : false;
    }

    if ($orig_url !== '' && $isCrop) {
      $crop_direction = sirv_crop_direction($m[4]);
      $pattern_crop = '?w=' . $w . '&h=' . $h . '&scale.option=fill&canvas.width=' . $w . '&canvas.height=' . $h;
      $resized_image = $orig_url . $pattern_crop . $crop_direction;
    }
  }
  return $resized_image;
}


function sirv_crop_direction($type)
{
  $param_crop_coords = '';

  switch ($type) {
    case '_c':
      $param_crop_coords = '&canvas.position=center';
      break;
    case '_tl':
      $param_crop_coords = '&canvas.position=northeast';
      break;
    case '_tr':
      $param_crop_coords = '&canvas.position=northwest';
      break;
    case '_bl':
      $param_crop_coords = '&canvas.position=southwest';
      break;
    case '_br':
      $param_crop_coords = '&canvas.position=southeast';
      break;
  }

  return $param_crop_coords;
}


function sirv_enqueue_frontend_scripts()
{
  wp_enqueue_style('sirv_frontend_style', plugins_url('/sirv/css/sirv-responsive-frontend.css', __FILE__));
  wp_enqueue_script('sirv_miscellaneous', plugins_url('/sirv/js/wp-sirv-diff.js', __FILE__), array('jquery'), '1.0.0', true);
}


function sirv_get_cached_cdn_url()
{
  global $sirv_cdn_url;

  if (!isset($sirv_cdn_url)) {
    $sirv_cdn_url = get_option('SIRV_CDN_URL');
  }
  return $sirv_cdn_url;
}


function sirv_do_responsive_images($attr, $attachment, $size)
{
  if (is_admin()) return $attr;

  $sirv_cdn_url = sirv_get_cached_cdn_url();

  if (empty($sirv_cdn_url) || stripos($attr['src'], $sirv_cdn_url) === false) return $attr;

  $url = sirv_prepareResponsiveImage($attr['src']);
  $plchldr_data  = sirv_prepare_placeholder_data($url, $size);
  //$classes = $plchldr_data['url'] ? 'Sirv placeholder-blurred': 'Sirv';
  //$classes = 'Sirv';

  $attr['class'] = isset($attr['class']) ? $attr['class'] . ' ' . $plchldr_data['classes'] : $plchldr_data['classes'];
  $attr['data-src'] = $url;
  if ($plchldr_data['url']) {
    $attr['src'] = $plchldr_data['url'];
    $attr['width'] = $plchldr_data['width'];
  } else {
    unset($attr['src']);
  }

  unset($attr['srcset']);
  unset($attr['sizes']);

  return $attr;
}


function sirv_prepareResponsiveImage($url)
{
  $profile = get_option('SIRV_CDN_PROFILES');
  $url = sirv_clean_get_params($url);

  if ($profile) $url .= "?profile=$profile";

  return $url;
}


function sirv_prepare_placeholder_data($url, $size)
{
  $placeholder_type = get_option('SIRV_RESPONSIVE_PLACEHOLDER');
  $wp_sizes = sirv_get_image_sizes();
  $tmp_arr = array('url' => '', 'width' => '', 'classes' => 'Sirv');
  $svg_placehodler = "data:image/gif;base64, R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
  $svg_placehodler_grey = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAKSURBVAgdY3gPAADxAPAXl1qaAAAAAElFTkSuQmCC";

  $placeholder_grey_params = '?q=1&w=10&colorize.color=efefef';

  if (is_array($size)) {
    $tmp_arr['width'] = $size[0];
  } else {
    /* if($size == 'full'){
      try {
        $full_size = getimagesize($url);
        $tmp_arr['width'] = $full_size[0];
      } catch (Exception $e) {
      }
    } */
    if (isset($wp_sizes[$size])) {
      $tmp_arr['width'] = $wp_sizes[$size]['width'];
    }
  }

  if ($tmp_arr['width']) {
    //if (pathinfo(preg_replace('/\?.*/is', '', $url), PATHINFO_EXTENSION) == 'svg' || $placeholder_type == '2') {
    if ( $placeholder_type == '2' ) {
      $tmp_arr['url'] = $url . $placeholder_grey_params;
    } else {
      $size = intval($tmp_arr['width'] / 10);
      $delimiter = stripos($url, 'profile') !== false ? '&' : '?';
      $tmp_arr['url'] = $url . $delimiter . 'w=' . $size . '&q=20';
      $tmp_arr['classes'] .= ' placeholder-blurred';
    }
  }

  return $tmp_arr;
}

//-----------------------------------------------------------------------------------------------------
function sirv_the_content($content, $type)
{

  if (is_admin()) return $content;

  global $wpdb;

  $uploads_dir = wp_get_upload_dir();
  $root_url_images_path = $uploads_dir['baseurl'];

  $quoted_base_url = preg_replace('/https?\\\:/ims', 'https?\:', preg_quote($root_url_images_path, '/'));

  $wrappedImageStart = '';
  $wrappedImageEnd = '';

  switch ($type) {
    case 'content':
      preg_match_all('/' . $quoted_base_url . '\/([^\s]*?)(\-[0-9]{1,}(?:x|&#215;)[0-9]{1,})?(\.(?:jpg|jpeg|png|gif))/ims', $content, $m);
      break;
    case 'css':
      preg_match_all('/url\([\'"]' . $quoted_base_url . '\/([^\s]*?)(\-[0-9]{1,}(?:x|&#215;)[0-9]{1,})?(\.(?:jpg|jpeg|png|gif))[\'"]\)/ims', $content, $m);
      $wrappedImageStart = "url('";
      $wrappedImageEnd = "')";
      break;
  }

  if (!empty($m[0]) && is_array($m[0]) && count($m[0])) {
    $all_image_sizes = sirv_get_image_sizes();
    foreach ($m[0] as $i => $fullURL) {
      $attachment = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s",
        $m[1][$i] . $m[3][$i]
      ), ARRAY_A);

      if (!empty($attachment) && !empty($attachment['post_id'])) {
        $imageURL = '';
        if (empty($m[2][$i])) {
          $resized = wp_get_attachment_image_src($attachment['post_id'], 'full');
          $imageURL = $resized[0];
        } else {
          list($w, $h) = explode('x', str_replace('-', '', str_replace('&#215;', 'x', $m[2][$i])));
          $attachment_meta = wp_get_attachment_metadata($attachment['post_id']);
          foreach ($attachment_meta['sizes'] as $size => $size_arr) {
            if ($w == $size_arr['width'] && $h == $size_arr['height']) {
              $resized = wp_get_attachment_image_src($attachment['post_id'], $size);
              $imageURL = $resized[0];
            }
          }
        }
        if ($imageURL != '') {
          $content = str_replace($m[0][$i], $wrappedImageStart . $imageURL . $wrappedImageEnd, $content);
        } else {
        }
      }
    }
  }

  return $content;
}

//------------------------------------------------------------------------------------------------------------------
function sirv_wp_prepare_attachment_for_js($response, $attachment, $meta)
{
  if (!empty($response['sizes'])) {
    if (preg_match('/^image/ims', $response['type'])) {
      foreach ($response['sizes'] as $size => $image) {
        $response['sizes'][$size]['url'] = preg_replace('/(.*)(?:\-[0-9]{1,}x[0-9]{1,}(\.[a-z]{1,})$)/ims', '$1$2?w=' . $image['width'] . '&h=' . $image['height'], $image['url']);
      }
    }
  }
  return $response;
}


function sirv_wp_get_attachment_image_src($image, $attachment_id, $size, $icon)
{

  //disable in admin area
  if (is_admin() || !is_array($image) || empty($attachment_id)) return $image;

  //$paths = sirv_get_paths_info($attachment_id);
  $paths = sirv_get_cached_wp_img_file_path($attachment_id);

  if (empty($paths) || isset($paths['wrong_file'])) return $image;

  $root_url_images_path = $paths['url_images_path'];

  //check if get_option('siteurl') return http or https
  if (stripos(get_option('siteurl'), 'https://') === 0) {
    $root_url_images_path = str_replace('http:', 'https:', $root_url_images_path);
  }

  $image_url = $image[0];
  $image_width = $image[1];
  $image_height = $image[2];
  $isCrop = isset($image[3]) ? (bool) $image[3] : false;

  //clean get params
  //$image_url = sirv_clean_get_params($image_url);

  //fix if image url without site path
  //$image_url = sirv_convert_to_corrected_link($image_url);

  /* if(stripos(clean_protocol($image_url), clean_protocol($root_url_images_path)) === false){
      if(stripos($image_url, $paths['sirv_url_path']) !== false) {
          $image[0] = sirv_scale_image($paths['sirv_full_url_path'], $image_width, $image_height, $size, $paths['img_file_path']);
      }
  }else{
      $cdn_image_url = sirv_get_cdn_image($attachment_id, false);
      if(!empty($cdn_image_url)){
          $image[0] = sirv_scale_image($paths['sirv_full_url_path'], $image_width, $image_height, $size, $paths['img_file_path']);
      }
  } */

  //$cdn_image_url = sirv_get_cdn_image($attachment_id, false);
  $cdn_image_url = sirv_cache_sync_data($attachment_id, false);
  if (!empty($cdn_image_url)) {
    $image[0] = sirv_scale_image($cdn_image_url, $image_width, $image_height, $size, $paths['img_file_path'], $isCrop);
  }

  return $image;
}


function sirv_clean_get_params($url)
{
  return (stripos($url, '?') === false) ? $url : preg_replace('/\?.*/is', '', $url);
}


function clean_protocol($url)
{
  return preg_replace('/^https?/is', '', $url);
}


function sirv_wp_get_attachment_url($url, $attachment_id)
{
  if (is_admin()) return $url;

  //$cdn_image_url = sirv_get_cdn_image($attachment_id, false);
  $cdn_image_url = sirv_cache_sync_data($attachment_id, false);

  if (!empty($cdn_image_url)) {
    $url = addProfile($cdn_image_url);
  }

  return $url;
}


function sirv_calculate_image_sizes($sizes, $size, $image_src, $image_meta, $attachment_id)
{
  return $sizes;
}


function sirv_add_custom_image_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id)
{
  if (is_admin() || !is_array($sources) || empty($attachment_id)) return $sources;

  //$paths = sirv_get_paths_info($attachment_id);
  $paths = sirv_get_cached_wp_img_file_path($attachment_id);

  if (empty($paths) || isset($paths['wrong_file'])) return $sources;

  //$image = sirv_get_cdn_image($attachment_id);
  $image = sirv_cache_sync_data($attachment_id);

  if ($image) {

    $original_image_path = $paths['img_file_path'];
    $image_sizes = array_keys($sources);
    $image_width = '';
    $image_height = '';
    $size_name = null;

    $max_size = $size_array[0];

    if (is_numeric($max_size) && $max_size > 0) {
      if (!array_key_exists($max_size, $sources)) {
        $sources[$max_size] = array('url' => $image_src, 'descriptor' => 'w', 'value' => $max_size);
      }
    }

    foreach ($image_sizes as $size) {
      if ($image_meta['width'] == $size && is_numeric($image_meta['height'])) {
        $image_width = $image_meta['width'];
        $image_height = $image_meta['height'];
      } else {
        $size_name = sirv_get_size_name($size, $image_meta['sizes']);
        if (isset($size_name) && !is_null($size_name)) {
          $image_width = $image_meta['sizes'][$size_name]['width'];
          $image_height = $image_meta['sizes'][$size_name]['height'];
        } else {
          $image_width = $size;
          $image_height = $size;
        }
      }

      $sources[$size]['url'] = sirv_scale_image($image, $image_width, $image_height, $size_name, $original_image_path, true);
    }
  }
  return $sources;
}


function sirv_vc_wpb_filter($img, $img_id, $attributes)
{

  if (is_admin()) return $img;

  if ($attributes['thumb_size'] == 'full' || in_array($attributes['thumb_size'], array_values(get_intermediate_image_sizes()))) return $img;

  require_once(ABSPATH . 'wp-admin/includes/file.php');

  $sirv_folder = get_option('SIRV_FOLDER');

  $uploads_dir = wp_get_upload_dir();
  $root_images_path = $uploads_dir['basedir'];
  $sirv_root_path = sirv_get_sirv_path($sirv_folder);

  preg_match('/(\d{2,4})x(\d{2,4})/is', $attributes['thumb_size'], $sizes);
  $img_sizes = array();
  $img_sizes['width'] = $sizes[1];
  $img_sizes['height'] = $sizes[2];

  $original_image_url = preg_replace('/\?scale.*/is', '', $img['p_img_large'][0]);
  $original_image_path =  str_replace($sirv_root_path, $root_images_path, $original_image_url);

  $scale_pattern = sirv_get_scale_pattern($img_sizes['width'], $img_sizes['height'], true, $original_image_path);
  $img['thumbnail'] = preg_replace('/-' . $sizes[0] . '(\.[jpg|jpeg|png|gif]*)/is', '$1' . $scale_pattern, $img['thumbnail']);
  $img['p_img_large'][0] = $original_image_url;

  return $img;
}


function sirv_get_image_size($size)
{
  $sizes = array();
  $sizes['width'] = get_option("{$size}_size_w'");
  $sizes['heigh'] = get_option("{$size}_size_h'");
  $sizes['crop'] = (bool)get_option("{$size}_crop'");
}

function sirv_get_image_sizes()
{
  global $_wp_additional_image_sizes;

  if (!empty($GLOBALS['sirv_wp_additional_image_sizes'])) $_wp_additional_image_sizes = $GLOBALS['sirv_wp_additional_image_sizes'];

  $sizes = array();

  foreach (get_intermediate_image_sizes() as $_size) {
    if (in_array($_size, array('thumbnail', 'medium', 'medium_large', 'large'))) {
      $sizes[$_size]['width']  = get_option("{$_size}_size_w");
      $sizes[$_size]['height'] = get_option("{$_size}_size_h");
      $sizes[$_size]['crop']   = (bool) get_option("{$_size}_crop");
    } elseif (isset($_wp_additional_image_sizes[$_size])) {
      $sizes[$_size] = array(
        'width'  => $_wp_additional_image_sizes[$_size]['width'],
        'height' => $_wp_additional_image_sizes[$_size]['height'],
        'crop'   => (bool)$_wp_additional_image_sizes[$_size]['crop'],
      );
    }

    //if( ($sizes[ $_size ]['width'] == 0) && ($sizes[ $_size ]['height'] == 0) ) unset( $sizes[ $_size ] );
    if (($sizes[$_size]['width'] == 0) || ($sizes[$_size]['height'] == 0)) unset($sizes[$_size]);
  }

  return $sizes;
}


/* function sirv_get_original_image($image_url, $paths){
    $sirv_root_path = $paths['sirv_root_path'];
    $root_images_path = $paths['root_images_path'];

    $pattern = '/(.*?)[-|-]\d{1,4}x\d{1,4}(\.[a-zA-Z]{2,5})/is';
    $tested_image = preg_replace($pattern, '$1$2', $image_url);
    $image_path_on_disc = str_replace($sirv_root_path, $root_images_path, $tested_image);
    $orig_image = array();
    if(file_exists($image_path_on_disc)){
        $orig_image['original_image_url'] = $tested_image;
        $orig_image['original_image_path'] = $image_path_on_disc;

    }else{
        $orig_image['original_image_url'] = $image_url;
        $orig_image['original_image_path'] = str_replace($sirv_root_path, $root_images_path, $image_url);
    }
    return $orig_image;
} */


function sirv_get_original_sizes($original_image_path)
{
  $sizes = array('width' => 0, 'height' => 0);

  //$uploads_dir = wp_get_upload_dir();
  //$root_images_path = $uploads_dir['basedir'];

  //if(stripos($original_image_path, $root_images_path) !== false){
  if ($original_image_path && file_exists($original_image_path)) {
    $image_dimensions = getimagesize($original_image_path);
    $sizes['width'] = $image_dimensions[0];
    $sizes['height'] = $image_dimensions[1];
  }

  return $sizes;
}


function sirv_scale_image($image_url, $image_width, $image_height, $size, $original_image_path, $isCrop = false)
{

  $sizes = sirv_get_image_sizes();

  $image_url = sirv_clean_get_params($image_url);

  $get_param_symbol = (stripos($image_url, '?') === false) ? '?' : '&';

  //fix if width or height received from sirv_wp_get_attachment_image_src == 0
  if ($image_width == 0 || $image_height == 0 || $image_width >= 3000 || $image_height >= 3000) {
    if (!empty($sizes) && !is_null($size) && in_array($size, array_keys($sizes))) {
      $image_width = $sizes[$size]['width'];
      $image_height = $sizes[$size]['height'];
    }
  }

  $cropType = sirv_get_crop_type($size, $sizes, $isCrop);

  $url = $image_url . sirv_get_scale_pattern($image_width, $image_height, $cropType, $original_image_path,  $get_param_symbol);

  return addProfile($url);
}


function sirv_get_crop_type($size, $sizes, $isCrop)
{
  $cropType = '';

  if ($size == 'full' || empty($size)) return $cropType;

  $crop_data = json_decode(get_option('SIRV_CROP_SIZES'), true);

  if (is_array($size)) {
    foreach ($sizes as $size_name => $sz) {
      if ($sz['width'] == $size[0] && $sz['height'] == $size[1]) {
        //$isCrop = (bool)$sz['crop'];
        $cropType = $crop_data[$size_name];
        break;
      }
    }
  } else {
    /* if (in_array($size, array_keys($sizes))) {
      $isCrop = (bool) $sizes[$size]['crop'];
    } */
    if (isset($crop_data[$size]))
      $cropType = $crop_data[$size];
  }

  return $cropType;
}


function sirv_get_scale_pattern($image_width, $image_height, $cropType, $original_image_path = '', $get_param_symbol = '?')
{
  $sw = empty($image_width) ? '' : 'w=' . $image_width;
  $sh = empty($image_height) ? '' : 'h=' . $image_height;
  $size_params = array($sw, $sh);

  $wp_crop = sirv_get_params($get_param_symbol, $size_params) . '&scale.option=fill&cw=' . $image_width . '&ch=' . $image_height . '&cx=center&cy=center';
  $sirv_crop = sirv_get_params($get_param_symbol, $size_params) . '&scale.option=fit&canvas.width=' . $image_width . '&canvas.height=' . $image_height . '&cx=center&cy=center';
  $pattern_scale = sirv_get_params($get_param_symbol, $size_params);
  $scale_width = sirv_get_params($get_param_symbol, array($sw));
  $scale_height = sirv_get_params($get_param_symbol, array($sh));
  $original = '';
  $usedPattern = '';


  //sometimes wp has strange giant image sizes
  if ($image_width > 3000) return $scale_height;
  if ($image_height > 3000) return $scale_width;
  if ($image_height > 3000 && $image_width > 3000) return $original;
  if (empty($image_width) && empty($image_height)) return $original;

  $original_image_sizes = sirv_get_original_sizes($original_image_path);
  if ($original_image_sizes['width'] == $image_width && $original_image_sizes['height'] == $image_height) return $original;

  if ($cropType && $cropType != 'none') {
    $usedPattern = $cropType == 'wp_crop' ? $wp_crop : $sirv_crop;
  } else {
    $usedPattern = $pattern_scale;
  }

  return $usedPattern;
}


function sirv_get_params($param_start, $params)
{
  $params_str = '';
  foreach ($params as $index => $param) {
    if (!empty($param)) {
      $params_str .= $index == 0 ? $param : "&${param}";
    } else {
      $params_str .= '';
    }
  }

  if (empty($params_str)) return '';

  return $param_start . $params_str;
}


function sirv_test_orientation($sizes)
{
  if ($sizes['width'] > $sizes['height']) return 'landsape';
  if ($sizes['width'] < $sizes['height']) return 'portrait';
  if ($sizes['width'] == $sizes['height']) return 'square';
}


function sirv_get_sirv_path($path = '')
{
  $network_type = get_option('SIRV_NETWORK_TYPE');
  $sirv_path = '';

  if ($network_type === '2') {
    $bucket = get_option('SIRV_AWS_BUCKET');
    $sirv_path = "https://{$bucket}.sirv.com/{$path}";
  } else {
    $cdn_url = get_option('SIRV_CDN_URL');
    $sirv_path = "https://{$cdn_url}/{$path}";
  }

  return $sirv_path;
}


function addProfile($url)
{
  if (stripos($url, 'profile') !== false) {
    return $url;
  }

  $profile = get_option('SIRV_CDN_PROFILES');

  if (!empty($profile)) {
    $encoded_profle = rawurlencode($profile);
    $url .= (stripos($url, '?') === false) ? '?profile=' . $encoded_profle : '&profile=' . $encoded_profle;
  }

  return $url;
}


function sirv_convert_to_corrected_link($image_url)
{
  $site_url = get_site_url();

  if (stripos($image_url, $site_url) === false) {
    if (stripos($image_url, '/wp-content') === 0) {
      $image_url = $site_url . $image_url;
    }
  }

  return $image_url;
}


function sirv_get_size_name($size, $array_of_sizes)
{
  foreach ($array_of_sizes as $size_name_key => $size_name_value) {
    if ($size_name_value['width'] == $size) return $size_name_key;
  }

  return null;
}


function encode_spaces($string)
{
  return str_replace(' ', '%20', $string);
}


function sirv_cache_sync_data($attachment_id, $wait = false)
{
  global $syncData;

  if (!isset($syncData[$attachment_id])) {
    $syncData[$attachment_id] = sirv_get_cdn_image($attachment_id, $wait);
  }

  return $syncData[$attachment_id];
}


function sirv_get_full_sirv_url_path($sirv_url_path, $image)
{

  $sirv_rel_path = empty($image['sirv_path']) ? $image['img_path'] : $image['sirv_path'];

  return $sirv_url_path . $sirv_rel_path;
}


function sirv_set_db_failed($wpdb, $table, $attachment_id, $paths, $error_type = 1)
{
  $img_path = isset($paths['image_rel_path']) ? $paths['image_rel_path'] : $paths['wrong_file'];
  $data = array(
    'attachment_id' => $attachment_id,
    'img_path' => $img_path,
    'status' => 'FAILED',
    'error_type' => $error_type,
  );
  $wpdb->replace($table, $data);
}


function sirv_get_cdn_image($attachment_id, $wait = false)
{
  global $wpdb;
  global $isFetchUpload;
  global $isFetchUrl;

  $table_name = $wpdb->prefix . 'sirv_images';

  $sirv_folder = get_option('SIRV_FOLDER');
  $sirv_url_path = sirv_get_sirv_path($sirv_folder);


  $image = $wpdb->get_row("
  SELECT * FROM $table_name
  WHERE attachment_id = $attachment_id
  ", ARRAY_A);

  //$sirv_rel_path = empty($image['sirv_path']) ? $image['img_path'] : $image['sirv_path'];

  if ($image && $image['status'] == 'SYNCED') {
    //return $sirv_url_path . $sirv_rel_path;
    return sirv_get_full_sirv_url_path($sirv_url_path, $image);
  }


  if (!$image) {
    $paths = sirv_get_paths_info($attachment_id);
    $headers = array();

    if (!isset($paths['img_file_path'])) {
      sirv_set_db_failed($wpdb, $table_name, $attachment_id, $paths);
      return '';
    } else {
      if (!file_exists($paths['img_file_path'])) {
        $headers = get_headers($paths['image_full_url'], 1);
        if (!isset($headers['Content-Length'])) {
          sirv_set_db_failed($wpdb, $table_name, $attachment_id, $paths);
          return '';
        } else {
          $isFetchUrl = true;
        }
      } else {
        if (is_dir($paths['img_file_path'])) {
          sirv_set_db_failed($wpdb, $table_name, $attachment_id, $paths);
          return '';
        } else {
          $isFetchUrl = false;
        }
      }
    }

    //exit if file doesn't exist on disc
    /* if ( empty($paths) || isset($paths['wrong_file']) || !file_exists($paths['img_file_path']) || is_dir($paths['img_file_path']) ) {
      $img_path = isset($paths['image_rel_path']) ? $paths['image_rel_path'] : $paths['wrong_file'];
      return '';
    } */

    $image_size = empty($headers) ? filesize($paths['img_file_path']) : (int) $headers['Content-Length'];
    $image_created_timestamp = empty($headers)
      ? date("Y-m-d H:i:s", filemtime($paths['img_file_path']))
      : date("Y-m-d H:i:s", strtotime($headers['Last-Modified']));

    $data = array();
    $data['attachment_id'] = $attachment_id;
    $data['img_path'] = $paths['image_rel_path'];
    $data['sirv_path'] = $paths['sirv_rel_path'];
    $data['size'] = $image_size;
    $data['status'] = 'NEW';
    $data['error_type'] = NULL;
    $data['timestamp'] = $image_created_timestamp;
    $data['timestamp_synced'] = NULL;
    $data['checks'] = 0;
    $data['timestamp_checks'] = NULL;

    $result = $wpdb->insert($table_name, $data);

    if ($result) {
      $image = $data;
      $image['img_file_path'] = $paths['img_file_path'];
      $image['sirv_full_path'] = $paths['sirv_full_path'];
      $image['image_full_url'] = $paths['image_full_url'];
    }
  }


  if ($image && $image['status'] == 'NEW') {

    if (!isset($image['img_file_path'])) {
      $paths = sirv_get_cached_wp_img_file_path($attachment_id);

      $image['img_file_path'] = $paths['img_file_path'];
      $image['sirv_full_path'] = $sirv_folder . $image['sirv_path'];
      $image['image_full_url'] = $paths['url_images_path'] . $image['img_path'];
    }


    $img_data = array(
      'id'            => $image['attachment_id'],
      'imgPath'       => $image['img_file_path'],
    );

    $fetch_max_file_size = empty((int)get_option('SIRV_FETCH_MAX_FILE_SIZE')) ? 1000000000 : (int)get_option('SIRV_FETCH_MAX_FILE_SIZE');
    $isFetchUpload = (int) $image['size'] < $fetch_max_file_size ? true : false;
    $isFetchUpload = $isFetchUrl ? true : $isFetchUpload;

    $file = sirv_uploadFile($image['sirv_full_path'], $image['img_file_path'], $img_data, $image['image_full_url'], $wait);

    if (is_array($file)) {
      if ($file['status'] == 'uploaded') {
        $wpdb->update($table_name, array(
          'timestamp_synced' => date("Y-m-d H:i:s"),
          'status' => 'SYNCED'
        ), array('attachment_id' => $attachment_id));

        sirv_set_image_meta('/' . $image['sirv_full_path'], $image['attachment_id']);

        //$image['status'] = 'SYNCED';
        //return $sirv_url_path . $sirv_rel_path;
        //return $paths['sirv_full_url_path'];
        return sirv_get_full_sirv_url_path($sirv_url_path, $image);
      } else {
        $wpdb->update($table_name, array(
          'status' => 'FAILED',
          'error_type' => 6
        ), array('attachment_id' => $attachment_id));

        return '';
      }
    } else {
      return '';
    }
  }

  if ($image && $image['status'] == 'PROCESSING') {
    if ((int)$image['checks'] <= 5 && ($image['timestamp_checks'] == 'NULL' ||  time() - (int) $image['timestamp_checks'] >= 10)) {
      //if(sirv_checkIfImageExists($paths['sirv_full_path'])){
      if (sirv_checkIfImageExists($sirv_folder . $image['sirv_path'])) {
        $wpdb->update($table_name, array(
          'timestamp_synced' => date("Y-m-d H:i:s"),
          'status' => 'SYNCED'
        ), array('attachment_id' => $attachment_id));

        //return $paths['sirv_full_url_path'];
        return sirv_get_full_sirv_url_path($sirv_url_path, $image);
      } else {
        $wpdb->update($table_name, array(
          'checks' => (int)$image['checks'] + 1,
          'timestamp_checks' => time()
        ), array('attachment_id' => $attachment_id));

        return '';
      }
    } else if ((int) $image['checks'] >= 5) {
      $wpdb->update($table_name, array(
        'status' => 'FAILED',
        'error_type' => 4
      ), array('attachment_id' => $attachment_id));

      return '';
    }
  }
}


function sirv_get_cached_wp_img_file_path($attachment_id)
{
  global $pathsData;

  if (!isset($pathsData[$attachment_id])) {
    $pathsData[$attachment_id] = sirv_get_wp_img_file_path($attachment_id);
  }

  return $pathsData[$attachment_id];
}


function sirv_get_wp_img_file_path($attachment_id)
{
  require_once(ABSPATH . 'wp-admin/includes/file.php');

  $uploads_dir_info = wp_get_upload_dir();
  $root_images_path = $uploads_dir_info['basedir'];
  $url_images_path = $uploads_dir_info['baseurl'];

  $img_file_path = get_attached_file($attachment_id);

  if (!$img_file_path) return array('wrong_file' => 'File name/path missing from WordPress media library');

  if (stripos($img_file_path, $root_images_path) === false) {
    if (file_exists($img_file_path)) {
      if (stripos($img_file_path, '/wp-content/uploads/') !== false) {
        $root_images_path = preg_replace('/(.*?\/wp-content\/uploads)\/.*/im', '$1', $img_file_path);
      } else return array('wrong_file' => $img_file_path);
    } else {
      return array('wrong_file' => $img_file_path);
    }
  }

  return array(
    'root_images_path' => $root_images_path,
    'url_images_path' => $url_images_path,
    'img_file_path' => $img_file_path
  );
}


function sirv_get_paths_info($attachment_id)
{

  if (empty($attachment_id)) return array('wrong_file' => 'Empty attachment');

  //$wp_img_path_data = sirv_get_wp_img_file_path($attachment_id);
  $wp_img_path_data = sirv_get_cached_wp_img_file_path($attachment_id);
  if (isset($wp_img_path_data['wrong_file'])) return $wp_img_path_data;

  $sirv_folder = get_option('SIRV_FOLDER');

  $paths = array(
    'root_images_path' => $wp_img_path_data['root_images_path'],
    'url_images_path' => $wp_img_path_data['url_images_path'],
    'sirv_base_url_path' => sirv_get_sirv_path($sirv_folder),
  );

  $paths['img_file_path'] = $wp_img_path_data['img_file_path'];
  $paths['image_basename'] = basename($paths['img_file_path']);

  $paths['image_rel_path'] = str_replace($paths['root_images_path'], '', $paths['img_file_path']);
  $image_sanitized_basename = sirv_get_correct_filename($paths['image_basename'], $paths['img_file_path']);
  $paths['image_base_path'] = str_replace(basename($paths['image_rel_path']), '', $paths['image_rel_path']);
  //$dispersion_sirv_path = sirv_get_dispersion_path($image_sanitized_basename);
  $dispersion_sirv_path = sirv_get_path_strategy($paths['image_base_path'], $image_sanitized_basename);
  $modified_sirv_path = $dispersion_sirv_path . $image_sanitized_basename;

  $paths['sirv_url_path'] = $paths['sirv_base_url_path'] . $paths['image_base_path'] . $dispersion_sirv_path;
  $paths['sirv_full_url_path'] = $paths['sirv_url_path'] . $image_sanitized_basename;
  $paths['sirv_rel_path'] = $paths['image_base_path'] . $modified_sirv_path;
  $paths['sirv_full_path'] = $sirv_folder . $paths['image_base_path'] . $modified_sirv_path;
  $paths['image_full_url'] = $paths['url_images_path'] . encode_spaces($paths['image_rel_path']);

  return $paths;
}


function sirv_get_correct_filename($filename, $filepath)
{
  $filename = preg_replace('/[^a-z0-9_\\-\\.]+/i', '_', $filename);
  $fileInfo = pathinfo($filename);
  if (preg_match('/^_+$/', $fileInfo['filename'])) {
    //$filename = 'file.' . $fileInfo['extension'];
    $filename = sirv_get_file_md5($filepath) . '.' . $fileInfo['extension'];
  }
  return $filename;
}


function sirv_get_dispersion_path($filename)
{
  $filename = pathinfo($filename)['filename'];
  $char = 0;
  $dispertionPath = '';
  while ($char <= 2 && $char < strlen($filename)) {
    if (empty($dispertionPath)) {
      $dispertionPath = ('.' == $filename[$char]) ? '_' : $filename[$char];
    } else {
      if ($char == 2) $char = strlen($filename) - 1;
      $dispertionPath = sirv_add_dir_separator($dispertionPath) . ('.' == $filename[$char] ? '_' : $filename[$char]);
    }
    $char++;
  }
  return $dispertionPath . '/';
}


function sirv_get_path_strategy($folder_path, $filename)
{
  global $foldersData;
  $folders_data = sirv_get_data_images_per_folder();
  $path = '';

  if ($folders_data['isOverheadImgCount']) {
    if (isset($folders_data['cached_folders_data'][$folder_path]) && $folders_data['cached_folders_data'][$folder_path] >= 5000) {
      $path = sirv_get_dispersion_path($filename);
    } else {
      if (array_key_exists($folder_path, $foldersData['cached_folders_data'])) {
        $foldersData['cached_folders_data'][$folder_path] += 1;
      } else {
        $foldersData['cached_folders_data'][$folder_path] = 1;
      }

      update_option('SIRV_FOLDERS_DATA', json_encode($foldersData), 'no');
    }
  }

  return $path;
}


function sirv_add_dir_separator($dir)
{
  if (substr($dir, -1) != '/') {
    $dir .= '/';
  }
  return $dir;
}


function sirv_get_file_md5($file_path)
{

  return substr(md5_file($file_path), 0, 12);
}


//return array with images using in posts
function sirv_get_all_images()
{
  $query_images_args = array(
    'post_type'      => 'attachment',
    'post_mime_type' => 'image',
    'post_status'    => 'inherit',
    'posts_per_page' => -1,
  );

  $query_images = new WP_Query($query_images_args);

  $images = array();
  $images['count'] = count($query_images->posts);
  $tmp_images = array();

  foreach ($query_images->posts as $image) {
    $tmp_images[] = array('image_url' => wp_get_attachment_url($image->ID), 'attachment_id' => $image->ID);
  }

  $images['images'] = $tmp_images;

  return $images;
}


function sirv_get_unsynced_images($limit = 100)
{

  global $wpdb;
  $sirv_images_t = $wpdb->prefix . 'sirv_images';
  $posts_t = $wpdb->prefix . 'posts';

  return $wpdb->get_results("
      SELECT $posts_t.ID as attachment_id, $posts_t.guid as image_url FROM $posts_t
      WHERE $posts_t.ID NOT IN (SELECT attachment_id FROM $sirv_images_t)
      AND ($posts_t.post_mime_type LIKE 'image/%')
      AND $posts_t.post_type = 'attachment'
      AND (($posts_t.post_status = 'inherit'))
      ORDER BY $posts_t.post_date DESC LIMIT $limit
      ", ARRAY_A);
}


function sirv_get_all_post_images_count()
{
  global $wpdb;
  $posts_t = $wpdb->prefix . 'posts';

  return $wpdb->get_var("
        SELECT count(*) FROM $posts_t WHERE ($posts_t.post_mime_type LIKE 'image/%')
        AND $posts_t.post_type = 'attachment'
        AND $posts_t.post_status = 'inherit'
      ");
}


function sirv_get_unsynced_images_count()
{
  global $wpdb;
  $sirv_images_t = $wpdb->prefix . 'sirv_images';
  $posts_t = $wpdb->prefix . 'posts';


  return $wpdb->get_var("
      SELECT count(*) FROM $posts_t WHERE $posts_t.ID NOT IN (SELECT attachment_id FROM $sirv_images_t)
      AND $posts_t.post_mime_type LIKE 'image/%'
      AND $posts_t.post_type = 'attachment'
      AND $posts_t.post_status = 'inherit'
      ", ARRAY_A);
}


function sirv_get_uncached_images($post_images)
{
  global $wpdb;
  $table_name = $wpdb->prefix . 'sirv_images';

  //cached images
  $sql_result = $wpdb->get_results("SELECT attachment_id FROM " . $table_name, ARRAY_N);

  $uncached_ids = array_values(array_diff(sirv_flattern_array($post_images, true, 'attachment_id'), sirv_flattern_array($sql_result)));

  return sirv_get_unique_items($post_images, $uncached_ids);
}


function sirv_get_unique_items($search_array, $unique_items)
{
  $tmp_arr = array();
  foreach ($search_array as $item) {
    if (in_array($item['attachment_id'], $unique_items)) array_push($tmp_arr, $item);
  }

  return $tmp_arr;
}


function sirv_flattern_array($array, $isAssociativeArray = false, $associativeField = '')
{
  $tmp_arr = array();
  foreach ($array as $item) {
    if ($isAssociativeArray) {
      if ($associativeField !== '') {
        array_push($tmp_arr, intval($item[$associativeField]));
      } else return array();
    } else array_push($tmp_arr, intval($item[0]));
  }

  return $tmp_arr;
}


function sirv_calc_images_per_folder($overheadLimit = 5000)
{
  global $foldersData;

  $isOverheadImgCount = (int) sirv_get_all_post_images_count() >= $overheadLimit;

  $foldersData = array(
    'time' => time(),
    'isOverheadImgCount' => $isOverheadImgCount,
    'cached_folders_data' => array(),
  );

  if ($isOverheadImgCount) {
    $foldersData['cached_folders_data'] = sirv_calc_images_per_folder_in_cache();
  }

  update_option('SIRV_FOLDERS_DATA', json_encode($foldersData), 'no');

  return $foldersData;
}


function sirv_calc_images_per_folder_in_cache()
{
  global $wpdb;
  $images = $wpdb->prefix . 'sirv_images';

  $img_count = array();

  $results = $wpdb->get_results("SELECT REPLACE(sirv_path, SUBSTRING_INDEX(sirv_path, '/', -1), '') as img_path, count(*) as count FROM $images WHERE `status` != 'FAILED' GROUP BY REPLACE(sirv_path, SUBSTRING_INDEX(sirv_path, '/', -1), '')", ARRAY_A);

  if ($results) {
    foreach ($results as $result) {
      $path = $result['img_path'];
      $count = $result['count'];

      $img_count[$path] = (int) $count;
    }
  }

  return $img_count;
}


function sirv_get_data_images_per_folder($isForsed = false)
{
  global $foldersData;

  if ($isForsed) {
    $foldersData = sirv_calc_images_per_folder();
  } else {
    if (empty($foldersData)) {
      $foldersData = json_decode(get_option('SIRV_FOLDERS_DATA'), true);
    }

    if (empty($foldersData) || time() - (int)$foldersData['time'] > 60 * 20) $foldersData = sirv_calc_images_per_folder();
  }

  return $foldersData;
}


add_action('wp_ajax_sirv_tst', 'sirv_tst');
function sirv_tst()
{
  if (!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  /* global $base_prefix;
  global $wpdb;

  $t_errors = $base_prefix . 'sirv_fetching_errors';

  $sql_errors = "CREATE TABLE $t_errors (
      id int unsigned NOT NULL auto_increment,
      error_msg varchar(255) DEFAULT '',
      PRIMARY KEY  (id))
      ENGINE=InnoDB DEFAULT CHARSET=utf8;";

  require_once(ABSPATH . 'wp-admin/includes/upgrade.php');


  //$is_sirv_errors_exists = $wpdb->get_results("SHOW TABLES LIKE '$t_errors'", ARRAY_N);

  //sirv_debug_msg($is_sirv_errors_exists);
  $wpdb->query("DROP TABLE IF EXISTS $t_errors");


  //if (empty($is_sirv_errors_exists)) {
    dbDelta($sql_errors);
    foreach (FetchError::get_errors() as $error_msg) {
      $wpdb->insert($t_errors, array('error_msg' => $error_msg));
    }
  //} */

  echo json_encode(array('done' => true));

  wp_die();
}


//---------------------------------------------YOAST SEO fixes for og images-----------------------------------------------------------------------//

add_filter('wpseo_opengraph_image', 'sirv_wpseo_opengraph_image', 10, 1);
add_filter('wpseo_twitter_image', 'sirv_wpseo_opengraph_image', 10, 1);


function sirv_wpseo_opengraph_image($img)
{
  if (stripos($img, '-cdn.sirv') != false) $img = str_replace('-cdn', '', $img);

  return $img;
}

//---------------------------------------------YOAST SEO meta fixes for og images END ------------------------------------------------------------------//


//-------------------------------------------------------------Ajax requests-------------------------------------------------------------------------//
function sirv_get_params_array($key = null, $secret_key = null, $bucket = null, $host = null)
{
  $host       = is_null($host) ? 's3.sirv.com' : $host;
  $bucket     = is_null($bucket) ? getValue::getOption('SIRV_AWS_BUCKET') : $bucket;
  $key        = is_null($key) ? getValue::getOption('SIRV_AWS_KEY') : $key;
  $secret_key = is_null($secret_key) ? getValue::getOption('SIRV_AWS_SECRET_KEY') : $secret_key;

  return array(
    'host'       => $host,
    'bucket'     => $bucket,
    'key'        => $key,
    'secret_key' => $secret_key
  );
}

function sirv_getS3Client()
{
  global $s3client;
  if ($s3client) {
    return $s3client;
  } else {
    require_once 'sirv/aws-s3-helper.php';
    return $s3client = new MagicToolbox_AmazonS3_Helper(sirv_get_params_array());
  }
}


function sirv_getAPIClient()
{
  global $APIClient;
  if ($APIClient) {
    return $APIClient;
  } else {
    require_once 'sirv/sirv.api.class.php';
    return $APIClient = new SirvAPIClient(
      get_option('SIRV_CLIENT_ID'),
      get_option('SIRV_CLIENT_SECRET'),
      get_option('SIRV_TOKEN'),
      get_option('SIRV_CLIENT_SECRET'),
      'Sirv/Wordpress'
    );
  }
}


function sirv_uploadFile($sirv_path, $image_path, $img_data, $imgURL = '', $wait = false)
{

  global $isLocalHost;
  global $isFetchUpload;
  //$s3client = sirv_getS3Client();
  $APIClient = sirv_getAPIClient();

  if ($isLocalHost || !$isFetchUpload) {
    //return $s3client->uploadFile($sirv_path, $image_path, $web_accessible = true);
    return $APIClient->uploadImage($image_path, $sirv_path);
  } else {
    $GLOBALS['sirv_fetch_queue'][$imgURL] = array(
      'imgURL'        => $imgURL,
      'sirvFileName'  => '/' . $sirv_path,
      'data'          => $img_data,
      'wait'          => $wait
    );

    return false;
  }
}


function sirv_processFetchQueue()
{
  if (empty($GLOBALS['sirv_fetch_queue']) || sirv_isMuted()) {
    return;
  }

  $APIClient = sirv_getAPIClient();
  global $wpdb;
  $table_name = $wpdb->prefix . 'sirv_images';


  $images2fetch = array_chunk($GLOBALS['sirv_fetch_queue'], 5);
  foreach ($images2fetch as $images) {
    $imgs = $imgs_data = array();
    foreach ($images as $image) {
      $imgs_data[$image['sirvFileName']] = $image;
      $imgs[] = array(
        'url'       =>  $image['imgURL'],
        'filename'  =>  $image['sirvFileName'],
        'wait'      =>  !empty($image['wait']) ? true : false
      );
    }

    $res = $APIClient->fetchImage($imgs);
    if ($res) {
      if (!empty($res->result) && is_array($res->result)) {
        foreach ($res->result as $result) {
          $image = $imgs_data[$result->filename];
          list($status, $error_type) = array_values(sirv_parse_fetch_data($result, $image['wait'], $APIClient));

          if (in_array($status, array('SYNCED', 'PROCESSING'))) {
            sirv_set_image_meta($image['sirvFileName'], $image['data']['id']);
          }

          $wpdb->update($table_name, array(
            'timestamp_synced'  => date("Y-m-d H:i:s"),
            'status'            => $status,
            'error_type'        => $error_type,
          ), array('attachment_id' => $image['data']['id']));
          /* if (!empty($result->success)) {
                //code here
              } */
        }
      }
    }
  }
  unset($GLOBALS['sirv_fetch_queue']);
}


function sirv_parse_fetch_data($res, $wait, $APIClient)
{
  $arr = array('status' => 'NEW', 'error_code' => NULL);
  if ($res->success) {
    $arr['status'] = 'SYNCED';
  } else {
    if ($wait) {
      try {
        if (is_array($res->attempts)) {
          $attempt = end($res->attempts);
          if (!empty($attempt->error)) {
            if (isset($attempt->error->httpCode) && $attempt->error->httpCode == 429) {
              preg_match('/Retry after ([0-9]{4}\-[0-9]{2}\-[0-9]{2}.*?\([a-z]{1,}\))/ims', $attempt->error->message, $m);
              $time = strtotime($m[1]);
              $APIClient->muteRequests($time);
              $arr['error_code'] = 5;
            } else {
              $error_msg = $attempt->error->message;
              $arr['error_code'] = FetchError::get_error_code($error_msg);
            }
          } else {
            $arr['error_code'] = 4;
          }
        } else {
          $arr['error_code'] = 4;
        }
      } catch (Exception $e) {
        sirv_debug_msg('error');
        sirv_debug_msg($e);
        $arr['error_code'] = 4;
      }
      $arr['status'] = 'FAILED';
    } else {
      $arr['status'] = 'PROCESSING';
    }
  }

  return $arr;
}


function sirv_checkIfImageExists($filename)
{
  $APIClient = sirv_getAPIClient();

  $stat = $APIClient->getFileStat($filename);

  return ($stat && !empty($stat->size));
}


function sirv_isMuted()
{
  return ((int) get_option('SIRV_MUTE') > time());
}


function sirv_get_attachment_meta($attachment_id)
{
  $attachment = get_post($attachment_id);

  return array(
    'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
    'caption' => $attachment->post_excerpt,
    'description' => $attachment->post_content,
    'href' => get_permalink($attachment->ID),
    'src' => $attachment->guid,
    'title' => $attachment->post_title
  );
}


function sirv_getFormatedFileSize($bytes, $fileName = "", $decimal = 2, $bytesInMM = 1000)
{
  if (!empty($fileName)) {
    $bytes = filesize($fileName);
  }

  $sign = ($bytes >= 0) ? '' : '-';
  $bytes = abs($bytes);

  if (is_numeric($bytes)) {
    $position = 0;
    $units = array(" Bytes", " KB", " MB", " GB", " TB");
    while ($bytes >= $bytesInMM && ($bytes / $bytesInMM) >= 1) {
      $bytes /= $bytesInMM;
      $position++;
    }
    return ($bytes == 0) ? '-' : $sign . round($bytes, $decimal) . $units[$position];
  } else {
    return "-";
  }
}


function sirv_get_formated_number($num)
{
  return number_format($num);
}


function sirv_getCacheInfo()
{
  global $wpdb;
  $images_t = $wpdb->prefix . 'sirv_images';
  $posts_t = $wpdb->prefix . 'posts';

  $total_count = (int) sirv_get_all_post_images_count();

  $stat = array(
    'NEW' => array('count' => 0, 'count_s' => '0', 'size' => 0, 'size_s' => '-'),
    'PROCESSING' => array('count' => 0, 'count_s' => '0', 'size' => 0, 'size_s' => '-'),
    'SYNCED' => array('count' => 0, 'count_s' => '0', 'size' => 0, 'size_s' => '-'),
    'FAILED' => array('count' => 0, 'count_s' => '0', 'size' => 0, 'size_s' => '-'),
    'q' => 0,
    'q_s' => '0',
    'size' => 0,
    'size_s' => '-',
    'total_count' => $total_count,
    'total_count_s' => sirv_get_formated_number($total_count),
    'garbage_count' => 0,
    'queued' => 0,
    'queued_s' => '0',
    'progress' => 0,
    'progress_complited' => 0,
    'progress_queued' => 0,
    'progress_failed' => 0,
  );

  $results = $wpdb->get_results("SELECT status, count(*) as `count`, SUM(size) as size FROM $images_t GROUP BY status", ARRAY_A);
  if ($results) {
    foreach ($results as $row) {
      $stat[$row['status']] = array(
        'count' => (int) $row['count'],
        'count_s' => sirv_get_formated_number((int) $row['count']),
        'size' => (int) $row['size'],
        'size_s' => sirv_getFormatedFileSize((int) $row['size']),
      );
    }


    $stat['size'] = (int) $stat['SYNCED']['size'];
    $stat['size_s'] = $stat['SYNCED']['size_s'];
    //$stat['q'] = (int) $stat['SYNCED']['count'];
    $stat['q'] = ( ((int) $stat['SYNCED']['count']) > $stat['total_count'] ) ? $stat['total_count']: (int) $stat['SYNCED']['count'];
    //$stat['q_s'] = sirv_get_formated_number((int) $stat['SYNCED']['count']);
    $stat['q_s'] = sirv_get_formated_number($stat['q']);

    $oldCache = (int) $wpdb->get_var("
          SELECT count(attachment_id) FROM $images_t WHERE attachment_id NOT IN (SELECT $posts_t.ID FROM $posts_t)
      ");

    $stat['garbage_count'] = $oldCache;
    $stat['garbage_count_s'] = sirv_get_formated_number($oldCache);
    $stat['queued'] = $stat['total_count'] - $stat['q'] - $stat['garbage_count'] - $stat['FAILED']['count'];
    $stat['queued_s'] = sirv_get_formated_number($stat['queued']);

    $progress_complited = $stat['total_count'] != 0 ? ($stat['q'] - $stat['garbage_count']) / $stat['total_count'] * 100 : 0;
    $progress_queued = $stat['total_count'] != 0 ? $stat['queued'] / $stat['total_count'] * 100 : 0;
    $progress_failed = $stat['total_count'] != 0 ? $stat['FAILED']['count'] / $stat['total_count'] * 100 : 0;

    $stat['progress'] = (int) $progress_complited;
    $stat['progress_complited'] = $progress_complited;
    $stat['progress_queued'] = $progress_queued;
    $stat['progress_failed'] = $progress_failed;
  }

  return $stat;
}


function sirv_getGarbage()
{
  global $wpdb;
  $sirv_images_t = $wpdb->prefix . 'sirv_images';
  $posts_t = $wpdb->prefix . 'posts';

  /*$unsynced_images_count = $wpdb->get_results("
      SELECT count(*) as count FROM $posts_t WHERE $posts_t.ID NOT IN (SELECT attachment_id FROM $sirv_images_t)
      AND ($posts_t.post_mime_type LIKE 'image/%')
      AND $posts_t.post_type = 'attachment'
      AND (($posts_t.post_status = 'inherit'))
      ", ARRAY_A);*/

  $t = (int) $wpdb->get_var("
      SELECT count(attachment_id) FROM $sirv_images_t WHERE attachment_id NOT IN (SELECT $posts_t.ID FROM $posts_t)
  ");

  return array($t > 0, $t);
}


add_action('wp_ajax_sirv_get_errors_info', 'sirv_getErrorsInfo');
function sirv_getErrorsInfo()
{

  if (!(defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $errors = FetchError::get_errors_from_db();
  $file_size_fetch_limit = empty((int) get_option('SIRV_FETCH_MAX_FILE_SIZE')) ?  '' : ' (' . sirv_getFormatedFileSize(get_option('SIRV_FETCH_MAX_FILE_SIZE')) . ')';
  $errData = array();

  global $wpdb;

  $t_error = $wpdb->prefix . 'sirv_images';

  $errors_desc = FetchError::get_errors_desc();



  foreach ($errors as $error) {
    if ((int)$error['id'] == 2) {
      $error['error_msg'] .= $file_size_fetch_limit;
    }
    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t_error WHERE status = 'FAILED' AND error_type = %d", $error['id']));
    $errData[$error['error_msg']]['count'] =  (int)$count;
    $errData[$error['error_msg']]['error_id'] =  (int)$error['id'];
    try {
      $errData[$error['error_msg']]['error_desc'] = $errors_desc[(int) $error['id']];
    } catch (Exception $e) {
      continue;
    }
  }

  echo json_encode($errData);
  wp_die();
}


function sirv_getStorageInfo($force_update = false)
{

  $cached_stat = get_option('SIRV_STAT');

  if (!empty($cached_stat) && !$force_update) {
    $storageInfo = @unserialize($cached_stat);
    if (is_array($storageInfo) && time() - $storageInfo['time'] < 60 * 60) {
      $storageInfo['data']['lastUpdate'] = date("H:i:s e", $storageInfo['time']);

      return $storageInfo['data'];
    }
  }

  $sirvAPIClient = sirv_getAPIClient();

  $storageInfo = $sirvAPIClient->getStorageInfo();

  $lastUpdateTime = time();

  $storageInfo['lastUpdate'] = date("H:i:s e",  $lastUpdateTime);

  update_option('SIRV_STAT', serialize(array(
    'time'  => $lastUpdateTime,
    'data'  => $storageInfo
  )),
  'no');

  return $storageInfo;
}


function decode_chunk($data)
{
  $data = explode(';base64,', $data);

  if (!is_array($data) || !isset($data[1])) {
    return false;
  }

  $data = base64_decode($data[1]);
  if (!$data) {
    return false;
  }

  return $data;
}


function checkAndCreatekDir($dir)
{
  if (!is_dir($dir)) {
    mkdir($dir);
  }
  chmod($dir, 0777);
}


//use ajax request to get php ini variables data
add_action('wp_ajax_sirv_get_php_ini_data', 'sirv_get_php_ini_data_callback');
function sirv_get_php_ini_data_callback()
{
  if (!(is_array($_POST) && isset($_POST['sirv_get_php_ini_data']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $s3object = sirv_getS3Client();
  $accountInfo = json_decode($s3object->getAccountInfo(), true);

  $php_ini_data = array();
  $php_ini_data['post_max_size'] = ini_get('post_max_size');
  $php_ini_data['max_file_uploads'] = ini_get('max_file_uploads');
  $php_ini_data['max_file_size'] = ini_get('upload_max_filesize');
  $php_ini_data['sirv_file_size_limit'] = $accountInfo['account']['fileSizeLimit'];

  echo json_encode($php_ini_data);

  wp_die();
}


//use ajax to clean 30 rows in table. For test purpose.
add_action('wp_ajax_sirv_delete_thirty_rows', 'sirv_delete_thirty_rows_callback');
function sirv_delete_thirty_rows_callback()
{
  if (!(is_array($_POST) && isset($_POST['sirv_delete_thirty_rows']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }
  global $wpdb;

  $table_name = $wpdb->prefix . 'sirv_images';
  $result = $wpdb->query("DELETE FROM $table_name WHERE id > 0 LIMIT 30");

  echo $result;


  wp_die();
}


add_action('wp_ajax_sirv_initialize_process_sync_images', 'sirv_initialize_process_sync_images');
function sirv_initialize_process_sync_images()
{
  if (!(is_array($_POST) && isset($_POST['sirv_initialize_sync']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  sirv_get_data_images_per_folder(true);

  echo json_encode(array('folders_calc_finished' => true));

  wp_die();
}


add_action('wp_ajax_sirv_process_sync_images', 'sirv_process_sync_images');
function sirv_process_sync_images()
{
  if (!(is_array($_POST) && isset($_POST['sirv_sync_uncached_images']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  global $isLocalHost;
  global $isFetchUpload;
  global $wpdb;
  $table_name = $wpdb->prefix . 'sirv_images';

  if (sirv_isMuted()) {
    sirv_return_limit_error();
    wp_die();
  }

  $sql = "SELECT * FROM $table_name
          WHERE status != 'FAILED'  AND status != 'SYNCED'
          ORDER BY IF(status='NEW',0,1), IF(status='PROCESSING', checks , 10) LIMIT 10";
  $results = $wpdb->get_results($sql, ARRAY_A);

  if (empty($results) || count($results) == 0) {
    //sirv_ProcessSirvFillTable();
    //$results = $wpdb->get_results($sql, ARRAY_A);
    $results = sirv_get_unsynced_images(10);
  }

  ini_set('max_execution_time', ($isLocalHost || !$isFetchUpload) ? 30 : 20);

  $maxExecutionTime = (int) ini_get('max_execution_time');

  if ($maxExecutionTime == 0) {
    $maxExecutionTime = 10;
  }

  $startTime = time();

  if (!empty($results)) {
    foreach ($results as $image_data) {
      sirv_get_cdn_image($image_data['attachment_id'], true);

      if ($maxExecutionTime && (time() - $startTime > $maxExecutionTime - 1)) {
        break;
      }
    }
  }

  try {
    sirv_processFetchQueue();
  } catch (Exception $e) {
    if (sirv_isMuted()) {
      sirv_return_limit_error();
      wp_die();
    }
  }

  echo json_encode(sirv_getCacheInfo());

  wp_die();
}


function sirv_return_limit_error()
{
  $sirvAPIClient = sirv_getAPIClient();
  $reset_time = (int) get_option('SIRV_MUTE');
  $errorMsg = 'Module disabled due to exceeding API usage rate limit. Refresh this page in ' . $sirvAPIClient->calcTime($reset_time) . ' ' . date("F j, Y, H:i a (e)", $reset_time);
  $cachedInfo = sirv_getCacheInfo();

  $cachedInfo['status'] = array(
    'isStopSync' => true,
    'errorMsg' => $errorMsg
  );

  echo json_encode($cachedInfo);
}


function sirv_ProcessSirvFillTable()
{
  global $wpdb;
  global $isLocalHost;
  $table_name = $wpdb->prefix . 'sirv_images';

  $unsynced_images = sirv_get_unsynced_images();

  if ($unsynced_images) {
    foreach ($unsynced_images as $image) {
      //$fetch_max_file_size = empty((int) get_option('SIRV_FETCH_MAX_FILE_SIZE')) ? 1000000000 : (int) get_option('SIRV_FETCH_MAX_FILE_SIZE');
      $paths = sirv_get_paths_info($image['attachment_id']);

      if (empty($paths) || !file_exists($paths['img_file_path']) || is_dir($paths['img_file_path'])) {
        $img_path = isset($paths['image_rel_path']) ? $paths['image_rel_path'] : $paths['wrong_file'];
        $data = array(
          'attachment_id' => $image['attachment_id'],
          'img_path' => $img_path,
          'status' => 'FAILED',
          'error_type' => 1,
        );
        $wpdb->replace($table_name, $data);
      } else {
        $image_size = filesize($paths['img_file_path']);
        $image_created_timestamp = date("Y-m-d H:i:s", filemtime($paths['img_file_path']));
        //$isUploading = $image_size < $fetch_max_file_size ? true : $isLocalHost ? true : false;

        $data = array();
        $data['attachment_id'] = $image['attachment_id'];
        $data['img_path'] = $paths['image_rel_path'];
        $data['sirv_path'] = $paths['sirv_rel_path'];
        $data['size'] = $image_size;
        /*$data['status'] = $isUploading ? 'NEW' : 'FAILED';
        $data['error_type'] = $isUploading ? NULL : 2;*/
        $data['status'] = 'NEW';
        $data['error_type'] = NULL;
        $data['timestamp'] = $image_created_timestamp;
        $data['timestamp_synced'] = NULL;
        $data['checks'] = 0;
        $data['timestamp_checks'] = NULL;

        $result = $wpdb->insert($table_name, $data);
      }
    }
  }
}


add_action('wp_ajax_sirv_refresh_stats', 'sirv_refresh_stats');
function sirv_refresh_stats()
{
  if (!(defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  echo json_encode(sirv_getStorageInfo(true));
  wp_die();
}


//ajax request to clear image cache
add_action('wp_ajax_sirv_clear_cache', 'sirv_clear_cache_callback');
function sirv_clear_cache_callback()
{
  if (!(is_array($_POST) && isset($_POST['clean_cache_type']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $clean_cache_type = $_POST['clean_cache_type'];

  global $wpdb;
  $images_t = $wpdb->prefix . 'sirv_images';
  $posts_t = $wpdb->prefix . 'posts';

  if ($clean_cache_type == 'failed') {

    $result = $wpdb->delete($images_t, array('status' => 'FAILED'));
  } else if ($clean_cache_type == 'garbage') {
    $atch_ids = $wpdb->get_results("SELECT attachment_id as attachment_id
                              FROM $images_t
                              WHERE attachment_id
                              NOT IN (SELECT $posts_t.ID FROM $posts_t)
      ", ARRAY_N);

    //$ids = implode( ",", sirv_flattern_array($a_ids));
    $ids_chunks = array_chunk(sirv_flattern_array($atch_ids), 500);

    foreach ($ids_chunks as $ids) {
      $ids_str = implode(",", $ids);
      $result = $wpdb->query("DELETE FROM $images_t WHERE attachment_id IN ($ids_str)");
    }
  } else if ($clean_cache_type == 'all') {

    $delete = $wpdb->query("TRUNCATE TABLE $images_t");
  }/* else if($clean_cache_type == 'master'){

      $s3object = sirv_getS3Client();
      $sirv_folder = get_option('SIRV_FOLDER');

      $ids = array();
      $files = array();

      do{

        $results = $wpdb->get_results("SELECT * FROM $images_t LIMIT 100", ARRAY_A);

        if($results){
          foreach ($results as  $file) {
            $files[] = '/' . $sirv_folder . $file['img_path'];
            $ids[] = $file['id'];
          }

          $ids_str = implode( ',', $ids);

          $wpdb->query("DELETE FROM $images_t WHERE id IN($ids_str)");

          $result = $s3object->deleteFiles($files);
        }

      }while(!empty($results));
    } */

  echo json_encode(sirv_getCacheInfo());

  wp_die();
}


//use ajax request to show data from sirv
add_action('wp_ajax_sirv_get_content', 'sirv_get_content');
function sirv_get_content()
{
  if (!(is_array($_POST) && isset($_POST['path']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $sirv_path = empty($_POST['path']) ? '/' : $_POST['path'];
  //$continuation = empty($_POST['continuation']) ? '' : $_POST['continuation'];
  $continuation = '';

  $sirv_path = rawurlencode($sirv_path);
  $sirv_path = str_replace('%2F', '/', $sirv_path);
  $sirv_path = str_replace('%5C', '', $sirv_path);

  $sirvAPIClient = sirv_getAPIClient();

  $content = array(
    'sirv_url' => get_option('SIRV_CDN_URL'),
    'current_dir' => rawurldecode($sirv_path),
    'content' => array('images' => array(), 'dirs' => array(), 'spins' => array(), 'files' => array(), 'videos' => array()),
    'continuation' => ''
  );

  $data = array();

  do {
    $result = $sirvAPIClient->getContent($sirv_path, $continuation);
    $continuation = '';
    if ($result) {
      $data = array_merge($data, $result->contents);
      if (isset($result->continuation)) $continuation = $result->continuation;
    }
  } while ($continuation);

  $content['content'] = sirv_sort_content_data($data);

  echo json_encode($content);

  wp_die();
}


function sirv_sort_content_data($data)
{
  //$valid_images_ext = array("jpg", "jpeg", "png", "gif", "bmp", "webp", "svg");
  $content = array('images' => array(), 'dirs' => array(), 'spins' => array(), 'files' => array(), 'videos' => array());
  $files = array();

  foreach ($data as $file) {
    if ($file->isDirectory) {
      if ((substr($file->filename, 0, 1) !== '.') && ($file->filename != 'Profiles')) $content['dirs'][] = $file;
    } else {
      $files[] = $file;
    }
  }

  foreach ($files as $file) {
    $ext = pathinfo($file->filename, PATHINFO_EXTENSION);
    $f_type = sirv_get_file_type($file->contentType);

    if ($f_type['type'] == 'image') {
      $content['images'][] = $file;
    } else if ($ext == 'spin') {
      $content['spins'][] = $file;
    } else if ($f_type['type'] == 'video') {
      $content['videos'][] = $file;
    } else {
      $content['files'][] = $file;
    }
  }


  $content = sirv_usort_obj_content($content, 'dirs');
  $content = sirv_usort_obj_content($content, 'spins');
  $content = sirv_usort_obj_content($content, 'images');
  $content = sirv_usort_obj_content($content, 'videos');
  $content = sirv_usort_obj_content($content, 'files');

  return $content;
}


function sirv_get_file_type($type)
{
  $tmp_t = explode('/', $type);

  return array('type' => $tmp_t[0], 'subtype' => $tmp_t[1]);
}


function sirv_usort_obj_content($data, $type)
{
  usort($data[$type], function ($a, $b) {
    return strnatcasecmp($a->filename, $b->filename);
  });

  return $data;
}


function sirv_remove_dirs($dirs, $dirs_to_remove)
{
  $tmp_arr = array();
  foreach ($dirs as $key => $dir) {
    if (!in_array($dir['Prefix'], $dirs_to_remove)) {
      $tmp_arr[] = $dir;
    }
  }
  return $tmp_arr;
}


//use ajax to upload images on sirv.com
add_action('wp_ajax_sirv_upload_files', 'sirv_upload_files_callback');

function sirv_upload_files_callback()
{

  if (!(is_array($_POST) && is_array($_FILES) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $current_dir = $_POST['current_dir'];
  $current_dir = $current_dir == '/' ? '' : $current_dir;
  $total = intval($_POST['totalFiles']);

  $totalPart = count($_FILES);

  //$s3object = sirv_getS3Client();
  $APIClient = sirv_getAPIClient();

  $arr_content = array();

  for ($i = 0; $i < $totalPart; $i++) {

    $filename = $current_dir . basename($_FILES[$i]["name"]);
    $file = $_FILES[$i]["tmp_name"];

    //$result = $s3object->uploadFile($filename, $file, $web_accessible = true, $headers = null);
    $result = $APIClient->uploadImage($file, $filename);

    session_id('image-uploading-status');
    session_start();

    $image_num = isset($_SESSION['uploadingStatus']['processedImage']) ? $_SESSION['uploadingStatus']['processedImage'] + 1 : 1;

    $arr_content['percent'] = intval($image_num / $total * 100);
    $arr_content['processedImage'] = $image_num;
    $arr_content['count'] = $total;

    $image_num++;

    $_SESSION['uploadingStatus'] = $arr_content;
    session_write_close();

    if (!empty($result)) echo json_encode($result);
  }

  wp_die();
}


//upload big file by chanks
add_action('wp_ajax_sirv_upload_file_by_chanks', 'sirv_upload_file_by_chanks_callback');

function sirv_upload_file_by_chanks_callback()
{
  if (!(is_array($_POST) && isset($_POST['binPart']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $arr_content = array();

  $uploads_dir = wp_get_upload_dir();
  $wp_uploads_dir = $uploads_dir['basedir'];


  $tmp_dir = $wp_uploads_dir . '/tmp_sirv_chunk_uploads/';
  checkAndCreatekDir($tmp_dir);

  $filename = $_POST['partFileName'];
  $binPart = decode_chunk($_POST['binPart']);
  $partNum = $_POST['partNum'];
  $totalParts = $_POST['totalParts'];
  $currentDir = $_POST['currentDir'] == '/' ? '' : $_POST['currentDir'];
  $totalOverSizedFiles =  intval($_POST['totalFiles']);

  $filePath = $tmp_dir . $filename;

  //if($binPart == false) echo 'base64 part cant converted to bin str';

  file_put_contents($filePath, $binPart, FILE_APPEND);
  chmod($filePath, 0777);


  if ($partNum == 1) {
    session_id("image-uploading-status");
    session_start();
    $_SESSION['uploadingStatus']['isPartFileUploading'] = true;
    $_SESSION['uploadingStatus']['percent'] = isset($_SESSION['uploadingStatus']['percent']) ? $_SESSION['uploadingStatus']['percent'] : null;
    $_SESSION['uploadingStatus']['processedImage'] = isset($_SESSION['uploadingStatus']['processedImage']) ? $_SESSION['uploadingStatus']['processedImage'] : null;
    $_SESSION['uploadingStatus']['count'] = isset($_SESSION['uploadingStatus']['count']) ? $_SESSION['uploadingStatus']['count'] : null;
    session_write_close();
  }

  if ($partNum == $totalParts) {

    //$s3object = sirv_getS3Client();
    $APIClient = sirv_getAPIClient();

    //$result = $s3object->uploadFile($currentDir . $filename, $filePath, $web_accessible = true, $headers = null);
    $result = $APIClient->uploadImage($filePath, $currentDir . $filename);

    unlink($filePath);

    session_id("image-uploading-status");
    session_start();

    $arr_content['processedImage'] = empty($_SESSION['uploadingStatus']['processedImage']) ? 1 : $_SESSION['uploadingStatus']['processedImage'] + 1;
    $arr_content['count'] = empty($_SESSION['uploadingStatus']['count']) ? $totalOverSizedFiles : $_SESSION['uploadingStatus']['count'];
    $arr_content['percent'] = intval($arr_content['processedImage'] / intval($arr_content['count']) * 100);

    $_SESSION['uploadingStatus'] = $arr_content;
    session_write_close();

    if ($arr_content['processedImage'] == $arr_content['count']) echo json_encode(array('stop' => true));
  }

  wp_die();
}


//monitoring status for creating sirv cache
add_action('wp_ajax_sirv_get_image_uploading_status', 'sirv_get_image_uploading_status_callback');
function sirv_get_image_uploading_status_callback()
{

  if (!(is_array($_POST) && isset($_POST['sirv_get_image_uploading_status']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  session_id('image-uploading-status');
  session_start();
  $session_data = isset($_SESSION['uploadingStatus']) ? $_SESSION['uploadingStatus'] : array();

  if (!empty($session_data)) {
    if (intval($session_data['percent']) >= 100) {
      echo json_encode($session_data);
      session_destroy();
    } else {
      echo json_encode($session_data);
      session_write_close();
    }
  } else {
    session_write_close();
    echo json_encode(array("percent" => null, "processedImage" => null, 'count' => null));
  }

  wp_die();
}


//use ajax to store gallery shortcode in DB
add_action('wp_ajax_sirv_save_shortcode_in_db', 'sirv_save_shortcode_in_db');

function sirv_save_shortcode_in_db()
{

  if (!(is_array($_POST) && isset($_POST['shortcode_data']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  global $base_prefix;
  global $wpdb;

  $table_name = $base_prefix . 'sirv_shortcodes';



  $data = $_POST['shortcode_data'];
  $data['images'] = serialize($data['images']);
  $data['shortcode_options'] = serialize($data['shortcode_options']);
  $data['timestamp'] = date("Y-m-d H:i:s");

  unset($data['isAltCaption']);

  $wpdb->insert($table_name, $data);

  echo $wpdb->insert_id;


  wp_die();
}


//use ajax to get data from DB by id
add_action('wp_ajax_sirv_get_row_by_id', 'sirv_get_row_by_id');

function sirv_get_row_by_id()
{

  if (!(is_array($_POST) && isset($_POST['row_id']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  global $base_prefix;
  global $wpdb;

  $table_name = $base_prefix . 'sirv_shortcodes';

  $id = intval($_POST['row_id']);

  $row =  $wpdb->get_row("SELECT * FROM $table_name WHERE id = $id", ARRAY_A);

  $row['images'] = unserialize($row['images']);
  $row['shortcode_options'] = unserialize($row['shortcode_options']);

  echo json_encode($row);

  //echo json_encode(unserialize($row['images']));


  wp_die();
}


//use ajax to get data from DB for shortcodes page
add_action('wp_ajax_sirv_get_shortcodes_data', 'sirv_get_shortcodes_data');

function sirv_get_shortcodes_data()
{

  if (!(is_array($_POST) && isset($_POST['shortcodes_page']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $limit = $_POST['itemsPerPage'] ? intval($_POST['itemsPerPage']) : 10;
  $sh_page = intval($_POST['shortcodes_page']);

  global $base_prefix;
  global $wpdb;

  $sh_table = $base_prefix . 'sirv_shortcodes';

  $sh_count = $wpdb->get_row("SELECT COUNT(*) AS count FROM $sh_table", ARRAY_A);
  $sh_pages = ceil(intval($sh_count['count']) / $limit);
  $sh_pages = $sh_pages === 0 ? 1 : $sh_pages;

  if ($sh_page > $sh_pages) $sh_page = $sh_pages;

  $offset =  ($sh_page - 1) * $limit;
  $offset = $offset < 0 ? 0 : $offset;

  $shortcodes =  $wpdb->get_results("
                SELECT *
                FROM $sh_table
                ORDER BY $sh_table.id
                DESC
                LIMIT $limit
                OFFSET $offset
            ", ARRAY_A);

  foreach ($shortcodes as $index => $shortcode) {
    $shortcodes[$index]['images'] = unserialize($shortcode['images']);
    $shortcodes[$index]['shortcode_options'] = unserialize($shortcode['shortcode_options']);
  }

  $tmp_arr = array('count' => $sh_count['count'], 'shortcodes' => $shortcodes);

  echo json_encode($tmp_arr);


  wp_die();
}


//use ajax to get data from DB for shortcodes page
add_action('wp_ajax_sirv_duplicate_shortcodes_data', 'sirv_duplicate_shortcodes_data');

function sirv_duplicate_shortcodes_data()
{
  if (!(is_array($_POST) && isset($_POST['shortcode_id']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $sh_id = intval($_POST['shortcode_id']);

  global $base_prefix;
  global $wpdb;
  $sh_table = $base_prefix . 'sirv_shortcodes';

  $data = $wpdb->get_row("
                          SELECT *
                          FROM $sh_table
                          WHERE $sh_table.id = $sh_id
                            ", ARRAY_A);

  unset($data['id']);

  $result = $wpdb->insert($sh_table, $data);

  if ($result === 1) {
    echo 'Shortcode ID=> ' . $sh_id . ' was duplicated';
  } else {
    echo 'Duplication was failed';
  }


  wp_die();
}


//use ajax to delete shortcodes
add_action('wp_ajax_sirv_delete_shortcodes', 'sirv_delete_shortcodes');

function sirv_delete_shortcodes()
{
  if (!(is_array($_POST) && isset($_POST['shortcode_ids']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  global $base_prefix;
  global $wpdb;

  $sh_table = $base_prefix . 'sirv_shortcodes';

  $shortcode_ids = json_decode($_POST['shortcode_ids']);

  function clean_ids($id)
  {
    return intval($id);
  }

  if (!empty($shortcode_ids)) {
    $ids = implode(',', array_map('clean_ids', $shortcode_ids));

    $result = $wpdb->query("DELETE FROM $sh_table WHERE ID IN($ids)");

    $msg = $result > 0 ? "Shortcodes were successful delete" : "Something went wrong during deleting shortcodes";
    echo $msg;
  } else {
    echo "Nothing to delete";
  }

  wp_die();
}


//use ajax to save edited shortcode
add_action('wp_ajax_sirv_update_sc', 'sirv_update_sc');

function sirv_update_sc()
{

  if (!(is_array($_POST) && isset($_POST['row_id']) && isset($_POST['shortcode_data']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  global $base_prefix;
  global $wpdb;

  $table_name = $base_prefix . 'sirv_shortcodes';

  $id = intval($_POST['row_id']);
  $data = $_POST['shortcode_data'];

  unset($data['isAltCaption']);

  $data['images'] = serialize($data['images']);
  $data['shortcode_options'] = serialize($data['shortcode_options']);


  $row =  $wpdb->update($table_name, $data, array('ID' => $id));

  echo $row;


  wp_die();
}


//use ajax to add new folder in sirv
add_action('wp_ajax_sirv_add_folder', 'sirv_add_folder');

function sirv_add_folder()
{

  if (!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $current_dir = $_POST['current_dir'];
  $current_dir = $current_dir == '/' ? '' : $current_dir;
  $new_dir = $_POST['new_dir'];

  $path = rawurlencode($current_dir) . $new_dir . '/';
  $path = str_replace('%2F', '/', $path);

  $s3object = sirv_getS3Client();
  $s3object->createFolder($path);

  wp_die();
}


//use ajax to check customer login details
add_action('wp_ajax_sirv_check_connection', 'sirv_check_connection', 10, 1);
function sirv_check_connection()
{

  if (!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $msg_ok = "Connection: OK";
  $msg_failed = 'Connection failed. Please check your <a href="https://my.sirv.com/#/account/settings" target="_blank">S3 details</a> match.';

  $host = $_POST['host'];
  $bucket = $_POST['bucket'];
  $key = $_POST['key'];
  $secret_key = $_POST['secret_key'];

  $s3object = sirv_getS3Client();

  $isConnection = $s3object->checkCredentials();

  if ($s3object->authMessage) {
    echo $s3object->authMessage;
    wp_die();
  }

  $message = $isConnection ? $msg_ok : $msg_failed;

  echo $message;


  wp_die();
}


//use ajax to remove review notice
add_action('wp_ajax_sirv_dismiss_review_notice', 'sirv_dismiss_review_notice', 10, 1);
function sirv_dismiss_review_notice()
{

  if (!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  update_option('sirv_review_notice', 'noticed');

  echo 'Sirv review notice dismissed';

  wp_die();
}


function sirv_test_connection($bucket, $key, $secret_key)
{
  $s3object = sirv_getS3Client();

  $isConnection = $s3object->checkCredentials();

  return array($isConnection, $s3object->authMessage);
}

//use ajax to delete files
add_action('wp_ajax_sirv_delete_files', 'sirv_delete_files');
function sirv_delete_files()
{
  if (!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $filenames = $_POST['filenames'];

  $s3Client = sirv_getS3Client();
  $APIClient = sirv_getAPIClient();

  $s_msg = 'File(s) has been deleted';
  $f_msg = 'File(s) hasn\'t been deleted';

  if (count($filenames) == 1) {
    $filename = stripos($filenames[0], "/") == 0 ? substr($filenames[0], 1) : $filenames[0];

    if ($APIClient->deleteFile($filename)) echo $s_msg;
    else echo $f_msg;
  } else {
    if ($s3Client->deleteFiles($filenames)) {
      echo $s_msg;
    } else {
      echo $f_msg;
    }
  }

  wp_die();
}


//use ajax to check if options is empty or not
add_action('wp_ajax_sirv_check_empty_options', 'sirv_check_empty_options');
function sirv_check_empty_options()
{
  $host = getValue::getOption('SIRV_AWS_HOST');
  $bucket = getValue::getOption('SIRV_AWS_BUCKET');
  $key = getValue::getOption('SIRV_AWS_KEY');
  $secret_key = getValue::getOption('SIRV_AWS_SECRET_KEY');

  if (empty($host) || empty($bucket) || empty($key) || empty($secret_key)) {
    echo false;
  } else {
    echo true;
  }

  wp_die();
}


//use ajax to get sirv profiles
add_action('wp_ajax_sirv_get_profiles', 'sirv_get_profiles');
function sirv_get_profiles()
{

  $profiles = sirv_getProfilesList();
  echo sirv_renderProfilesOptopns($profiles);

  wp_die();
}


function sirv_getProfilesList()
{

  $APIClient = sirv_getAPIClient();
  $profiles = $APIClient->getProfiles();
  if ($profiles && !empty($profiles->contents) && is_array($profiles->contents)) {
    $profilesList = array();
    foreach ($profiles->contents as $profile) {
      if (preg_match('/\.profile$/ims', $profile->filename) && $profile->filename != 'Default.profile') {
        $profilesList[] = preg_replace('/(.*?)\.profile$/ims', '$1', $profile->filename);
      }
    }
    sort($profilesList);
    return $profilesList;
  }
  return false;
}


function sirv_renderProfilesOptopns($profiles)
{
  $profiles_tpl = '';

  if (!empty($profiles)) {
    $profiles_tpl .= '<option disabled>Choose profile</option><option value="">-</option>';
    foreach ($profiles as $profile) {
      $profiles_tpl .= "<option value='{$profile}'>{$profile}</option>";
    }
  }

  return $profiles_tpl;
}


//use ajax to send message from sirv plugin
add_action('wp_ajax_sirv_send_message', 'sirv_send_message');

function sirv_send_message()
{
  if (!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }


  //$priority = $_POST['priority'];
  $summary = stripcslashes($_POST['summary']);
  $text = stripcslashes($_POST['text']);
  $name = $_POST['name'];
  $emailFrom = $_POST['emailFrom'];

  $account_name = get_option('SIRV_AWS_BUCKET');

  $text .= PHP_EOL . 'Account name: ' . $account_name;


  $headers = array(
    'From:' . $name . ' <' . $emailFrom . '>'
  );

  //wp_mail( $to, $subject, $message, $headers, $attachments );
  /*echo wp_mail('support@sirv.com', $summary .' - '. $priority, $text, $headers);*/
  echo wp_mail('support@sirv.com', $summary, $text, $headers);

  wp_die();
}


//use ajax to account connect
add_action('wp_ajax_sirv_init_account', 'sirv_init_account');
function sirv_init_account()
{
  if (!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $email = trim(strtolower($_POST['email']));
  $pass = trim(stripslashes($_POST['pass']));
  $f_name = $_POST['firstName'];
  $l_name = $_POST['lastName'];
  $alias = $_POST['accountName'];
  $is_new_account = (bool)$_POST['isNewAccount'];

  $sirvAPIClient = sirv_getAPIClient();

  if (!empty($is_new_account) && $is_new_account) {
    $account = $sirvAPIClient->registerAccount(
      $email,
      $pass,
      trim(strtolower($f_name)),
      trim(strtolower($l_name)),
      trim(strtolower($alias))
    );
    if (!$account) {
      $lastResp = $sirvAPIClient->getLastResponse();
      if (
        $lastResp->result->message == 'Supplied data is not valid' &&
        !empty($lastResp->result->validationErrors) &&
        preg_match('/AccountAlias/ims', $lastResp->result->validationErrors[0]->message)
      ) {
        $lastResp->result->message = 'Wrong value for account name. Please fix it.';
      }

      if ($lastResp->result->message == 'Duplicate entry') {
        $lastResp->result->message = 'That email address is already registered. Please login instead.';
      }

      echo json_encode(
        array('error' => $lastResp->result->message)
      );

      wp_die();
    }
  }

  $user_role_msg = "User $email does not have <a target=\"_blank\" href=\"https://sirv.com/help/articles/users-roles-permissions/\">permission</a> to connect this plugin. Ask for your role to be changed to Admin or Owner.";

  $users = $sirvAPIClient->getUsersList($email, $pass);
  if (empty($users) || !is_array($users)) {
    $lastResp = $sirvAPIClient->getLastResponse();
    if ($lastResp->result->message == 'Forbidden') {
      $lastResp->result->message =
        'That email or password is incorrect. Please check and try again. (' .
        '<a href="https://my.sirv.com/#/password/forgot" target="_blank">' .
        'Forgot your password' . '</a>?)';
    }

    $error = empty($lastResp->result->message) ? $lastResp->error : $lastResp->result->message;
    $error = empty($error) && empty($users) ? $user_role_msg : '';
    $error = empty($error) ? 'Unknown error during request to Sirv API' : $error;

    echo json_encode(
      array('error' => $error)
    );
  } else {
    echo json_encode(
      array('users' => $users)
    );
  }

  wp_die();
}


add_action('wp_ajax_sirv_setup_credentials', 'sirv_setup_credentials');
function sirv_setup_credentials()
{
  if (!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $email = trim(strtolower($_POST['email']));
  $alias = $_POST['sirv_account'];

  $sirvAPIClient = sirv_getAPIClient();

  if (!empty($alias)) {
    $res = $sirvAPIClient->setupClientCredentials($alias);
    update_option('SIRV_ACCOUNT_EMAIL', $email);
    if ($res) {
      $res = $sirvAPIClient->setupS3Credentials($email);
      if ($res) {
        $sirv_folder = get_option('SIRV_FOLDER');
        $s3object = sirv_getS3Client();
        $s3object->createFolder($sirv_folder . '/');

        $sirvAPIClient->setFolderOptions($sirv_folder, array('scanSpins' => false));

        sirv_getStorageInfo(true);

        echo json_encode(
          array('connected' => '1')
        );
        wp_die();
      }
    }
    echo json_encode(
      array('error' => 'An error occurred.')
    );
    wp_die();
  }

  echo json_encode(
    array('error' => 'An error occurred.')
  );

  wp_die();
}

add_action('wp_ajax_sirv_disconnect', 'sirv_disconnect');
function sirv_disconnect()
{
  if (!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  update_option('SIRV_CLIENT_ID', '', 'no');
  update_option('SIRV_CLIENT_SECRET', '', 'no');
  update_option('SIRV_TOKEN', '', 'no');
  update_option('SIRV_TOKEN_EXPIRE_TIME', '', 'no');
  update_option('SIRV_MUTE', '', 'no');
  update_option('SIRV_ACCOUNT_EMAIL', '');
  update_option('SIRV_STAT', '', 'no');
  update_option('SIRV_CDN_URL', '');
  update_option('SIRV_AWS_BUCKET', '');
  update_option('SIRV_AWS_KEY', '');
  update_option('SIRV_AWS_SECRET_KEY', '');
  //update_option('SIRV_AWS_HOST', '');

  echo json_encode(array('disconnected' => 1));

  wp_die();
}


add_action('wp_ajax_sirv_get_error_data', 'sirv_get_error_data');
function sirv_get_error_data()
{
  if (!(is_array($_POST) && isset($_POST['error_id']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  global $wpdb;
  $table_name = $wpdb->prefix . 'sirv_images';
  $error_id = intval($_POST['error_id']);
  $report_type = $_POST['report_type'];

  $results = $wpdb->get_results("SELECT  img_path, size, attachment_id FROM $table_name WHERE status = 'FAILED' AND error_type = $error_id ORDER BY attachment_id", ARRAY_A);

  $uploads_dir = wp_get_upload_dir();
  $url_images_path = $uploads_dir['baseurl'];

  $err_msgs = array('File name/path missing from WordPress media library', 'Empty attachment');

  if ($results) {
    require_once 'sirv/report.class.php';

    $fields = array('Image URL', 'File size', 'WP Attachment ID');
    $fimages = array();

    foreach ($results as $row) {
      //$row['img_path'] = $url_images_path . $row['img_path'];
      $row['error'] = in_array($row['img_path'], $err_msgs) ? true : false;
      $row['img_path'] = $row['error'] ? $row['img_path'] : $url_images_path . $row['img_path'];
      $size = sirv_getFormatedFileSize((int) $row['size']);
      $row['size'] = $size == '-' ? '' : $size;
      $fimages[] = $row;
    }

    if ($report_type == 'html') {
      array_unshift($fields, '#');
      $data = array('fields' => $fields, 'data' => $fimages);
      echo Report::generateFailedImagesHTMLReport($data, $error_id);
    } else {
      array_unshift($fimages, $fields);
      echo Report::generateFailedImagesCSVReport($fimages);
    }
  } else {
    echo '';
  }

  wp_die();
}

add_action('wp_ajax_sirv_get_search_data', 'sirv_get_search_data');

function sirv_get_search_data()
{
  if (!(is_array($_POST) && isset($_POST['search_query']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  require_once 'sirv/query-string.class.php';

  $c_query = new QueryString($_POST['search_query']);
  $from = $_POST['from'];

  $sirvAPIClient = sirv_getAPIClient();

  $res = $sirvAPIClient->search($c_query->getCompiledGlobalSearch(), $from);


  if ($res) {
    $res->sirv_url = get_option('SIRV_CDN_URL');
    echo json_encode($res);
  } else echo json_encode(array());

  wp_die();
}


function sirv_remove_first_slash($path)
{
  return stripos($path[0], "/") === 0 ? substr($path[0], 1) : $path[0];
}


add_action('wp_ajax_sirv_copy_file', 'sirv_copy_file');

function sirv_copy_file()
{
  if (!(is_array($_POST) && isset($_POST['copyPath']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $file_path = $_POST['filePath'];
  $copy_path = $_POST['copyPath'];


  $s3client = sirv_getS3Client();
  $result = $s3client->copyFile($file_path, $copy_path);

  echo json_encode(array('duplicated' => $result));

  wp_die();
}


add_action('wp_ajax_sirv_rename_file', 'sirv_rename_file');

function sirv_rename_file()
{
  if (!(is_array($_POST) && isset($_POST['filePath']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $file_path = $_POST['filePath'];
  $new_file_path = $_POST['newFilePath'];


  $s3client = sirv_getS3Client();
  $result = $s3client->renameFile($file_path, $new_file_path);

  echo json_encode(array('renamed' => $result));

  wp_die();
}


add_action('wp_ajax_sirv_empty_view_cache', 'sirv_empty_view_cache');

function sirv_empty_view_cache()
{
  if (!(is_array($_POST) && isset($_POST['type']) && defined('DOING_AJAX') && DOING_AJAX)) {
    return;
  }

  $clean_type = $_POST['type'];

  global $wpdb;
  $postmeta_t = $wpdb->prefix . 'postmeta';

  if ($clean_type == "all") {
    $result = $wpdb->query(
      "DELETE FROM $postmeta_t
        WHERE post_id IN (
          SELECT tmp.post_id FROM (
            SELECT post_id FROM $postmeta_t WHERE meta_key = '_sirv_woo_viewf_status')
          as `tmp`)
          AND meta_key IN ('_sirv_woo_viewf_data', '_sirv_woo_viewf_status')"
    );
  } else if ($clean_type == "empty") {
    $result = $result = $wpdb->query(
      "DELETE FROM $postmeta_t
        WHERE post_id IN (
          SELECT tmp.post_id FROM (
            SELECT post_id FROM $postmeta_t WHERE meta_key = '_sirv_woo_viewf_status' AND meta_value = 'EMPTY')
          as `tmp`)
        AND meta_key IN ('_sirv_woo_viewf_data', '_sirv_woo_viewf_status')"
    );
  } else if ($clean_type == "missing") {
    $result = $result = $wpdb->query(
      "DELETE FROM $postmeta_t
        WHERE post_id IN (
          SELECT tmp.post_id FROM (
            SELECT post_id FROM $postmeta_t WHERE meta_key = '_sirv_woo_viewf_status' AND meta_value = 'FAILED')
          as `tmp`)
        AND meta_key IN ('_sirv_woo_viewf_data', '_sirv_woo_viewf_status')"
    );
  }

  echo json_encode(array('result' => $result, 'cache_data' => sirv_get_view_cache_info()));
  wp_die();
}


function sirv_get_view_cache_info()
{
  global $wpdb;
  $postmeta_t = $wpdb->prefix . 'postmeta';

  $cache_info = array('all' => 'no data', 'empty' => 'no data', 'missing' => 'no data');

  $query_all = "SELECT COUNT(*) FROM $postmeta_t WHERE meta_key = '_sirv_woo_viewf_status'";
  $query_empty = "SELECT COUNT(*) FROM $postmeta_t WHERE meta_key = '_sirv_woo_viewf_status' AND meta_value = 'EMPTY'";
  $query_missing = "SELECT COUNT(*) FROM $postmeta_t WHERE meta_key = '_sirv_woo_viewf_status' AND meta_value = 'FAILED'";

  $cache_info['all'] = $wpdb->get_var($query_all);
  $cache_info['empty'] = $wpdb->get_var($query_empty);
  $cache_info['missing'] = $wpdb->get_var($query_missing);


  return $cache_info;
}


function sirv_set_image_meta($filename, $attachment_id)
{
  $res_title = '';
  $res_description = '';

  $meta = sirv_get_attachment_meta($attachment_id);


  if (!empty($meta['title']) || !empty($meta['description'])) {
    $sirvAPIClient = sirv_getAPIClient();

    if (!empty($meta['title'])) $res_title = $sirvAPIClient->setMetaTitle($filename, $meta['title']);
    if (!empty($meta['alt'])) $res_description = $sirvAPIClient->setMetaDescription($filename, $meta['alt']);
  }

  //return array( 'title_uploaded' => $res_title, 'description_uploaded' => $res_description );
  return $res_title && $res_description;
}

add_action('wp_ajax_sirv_images_storage_size', 'sirv_images_storage_size');
function sirv_images_storage_size(){
  $start_time = time();
  $start_microtime = microtime(true);

  $upload_dir     = wp_upload_dir();
  $upload_space   = sirv_foldersize( $upload_dir['basedir'] );
  $post_images_count = sirv_get_all_post_images_count();

  $ops_time = time() - $start_time;
  $ops_microtime = microtime(true) - $start_microtime;

    echo json_encode(
      array(
        'time' => $ops_time,
        'microtime_start' => $start_microtime,
        'microtime_end' => microtime(true),
        'microtime' => round($ops_microtime * 1000),
        'size' => sirv_format_size_t($upload_space),
        'count' => $post_images_count
      )
    );

  wp_die();
}

function sirv_foldersize($path)
{
  $total_size = 0;
  $files = scandir($path);
  $cleanPath = rtrim($path, '/') . '/';

  foreach ($files as $t) {
    if ('.' != $t && '..' != $t) {
      $currentFile = $cleanPath . $t;
      if (is_dir($currentFile)) {
        $size = sirv_foldersize($currentFile);
        $total_size += $size;
      } else {
        $size = filesize($currentFile);
        $total_size += $size;
      }
    }
  }

  return $total_size;
}

function sirv_format_size_t($size)
{
  $units = explode(' ', 'B KB MB GB TB PB');

  $mod = 1024;

  for ($i = 0; $size > $mod; $i++)
    $size /= $mod;

  $endIndex = strpos($size, ".") + 3;

  return substr($size, 0, $endIndex) . ' ' . $units[$i];
}

?>

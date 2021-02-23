<?php

defined('ABSPATH') or die('No script kiddies please!');

require_once(dirname(__FILE__) . '/woo.options.class.php');

$error = '';

//'SIRV_NETWORK_TYPE'
$base_options = ['SIRV_FOLDER', 'SIRV_CDN_URL', 'SIRV_ENABLE_CDN', 'SIRV_SHORTCODES_PROFILES', 'SIRV_CDN_PROFILES', 'SIRV_USE_SIRV_RESPONSIVE', 'SIRV_CROP_SIZES', 'SIRV_JS', 'SIRV_JS_FILE', 'SIRV_JS_FILE_EXTEND', 'SIRV_CUSTOM_CSS', 'SIRV_RESPONSIVE_PLACEHOLDER, SIRV_PARSE_STATIC_IMAGES'];
$options_names = array_merge($base_options, Woo_options::get_option_names_list());

function isWoocommerce()
{
  return is_plugin_active('woocommerce/woocommerce.php');
}


function sirv_getStatus()
{
  $status = get_option('SIRV_ENABLE_CDN');

  $class = $status == '1' ? 'sirv-status--enabled' : 'sirv-status--disabled';

  return $class;
}


function sirv_js_extend_checked($id)
{
  $data = json_decode(get_option('SIRV_JS_FILE_EXTEND'), true);
  if ($data) {
    if ($data[$id]) return 'checked';
  }

  return '';
}


$sirvAPIClient = sirv_getAPIClient();

$isMuted = $sirvAPIClient->isMuted();
if ($isMuted) {
  $reset_time = (int) get_option('SIRV_MUTE');
  $error = 'Module disabled due to exceeding API usage rate limit. Refresh this page in ' . $sirvAPIClient->calcTime($reset_time) . ' ' . date("F j, Y, H:i a (e)", $reset_time);
}

$sirvStatus = $sirvAPIClient->preOperationCheck();

if ($sirvStatus) {
  $isWoocommerce = isWoocommerce();
  $isMultiCDN = false;
  $customCDNs = array();
  $is_direct = get_option('SIRV_NETWORK_TYPE') == "2" ? true : false;
  $sirvCDNurl = get_option('SIRV_CDN_URL');

  $accountInfo = $sirvAPIClient->getAccountInfo();

  if (!empty($accountInfo)) {

    $isMultiCDN = count((array) $accountInfo->aliases) > 1 ? true : false;
    $is_direct = (isset($accountInfo->aliases->{$accountInfo->alias}->cdn) && $accountInfo->aliases->{$accountInfo->alias}->cdn) ? false : true;

    if ($isMultiCDN) {
      foreach ($accountInfo->aliases as $alias) {
        $customCDNs[] = $alias->customDomain;
      }
    }
  }

  $cacheInfo = sirv_getCacheInfo();
  $profiles = sirv_getProfilesList();


  $isOverCache = (int) $cacheInfo['q'] > (int) $cacheInfo['total_count'] ? true : false;
  $isFailed = (int) $cacheInfo['FAILED']['count'] > 0 ? true : false;
  $isGarbage = (int) $cacheInfo['garbage_count'] > 0 ? true : false;

  if ($isOverCache) {
    $cacheInfo['q'] = $isGarbage
      ? (int) $cacheInfo['q'] - (int) $cacheInfo['garbage_count'] > (int) $cacheInfo['total_count']
      ? (int) $cacheInfo['total_count']
      : (int) $cacheInfo['q'] - (int) $cacheInfo['garbage_count']
      : (int) $cacheInfo['total_count'];
  }

  $isSynced = ((int) $cacheInfo['q'] + (int) $cacheInfo['FAILED']['count']) == (int) $cacheInfo['total_count'];
  $is_sync_button_disabled = $isSynced ? 'disabled' : '';
  $sync_button_text = $isSynced ? (int) $cacheInfo['FAILED']['count'] == 0 ? '100% synced' : 'Synced' : 'Sync images';
  $is_show_resync_block = (int) $cacheInfo['q'] > 0 || $cacheInfo['FAILED']['count'] > 0 ? '' : 'display: none';
  $is_show_failed_block = (int) $cacheInfo['FAILED']['count'] > 0 ? '' : 'display: none';
} else {
  if (!$isMuted) {
    wp_safe_redirect(add_query_arg(array('page' => SIRV_PLUGIN_PATH . '/sirv/submenu_pages/account.php'), admin_url('admin.php')));
  }
}
?>

<style type="text/css">
  .sirv-logo-background {
    background-image: url("<?php echo plugin_dir_url(__FILE__) . "assets/logo.svg" ?>");
    background-position: center right;
    background-repeat: no-repeat;
    background-size: 68px 68px;
    min-height: 60px;
    margin: 0 !important;
  }

  a[href*="page=sirv/sirv/options.php"] img {
    padding-top: 7px !important;
  }
</style>

<form action="options.php" method="post" id="sirv-save-options">
  <?php
  wp_nonce_field('update-options');

  $active_tab = (isset($_POST['active_tab'])) ? $_POST['active_tab'] : '#sirv-settings';
  ?>
  <div class="sirv-wrapped-nav">
    <h1 class="sirv-options-title sirv-logo-background">Welcome to Sirv</h1>
    <nav class="nav-tab-wrapper">
      <?php if ($sirvStatus) { ?>
        <a class="nav-tab nav-tab-sirv-settings <?php echo ($active_tab == '#sirv-settings') ? 'nav-tab-active' : '' ?>" href="#sirv-settings" data-link="settings"><span class="dashicons dashicons-admin-generic"></span><span class="sirv-tab-txt">Settings</span></a>
        <?php if ($isWoocommerce) { ?>
          <a class="nav-tab nav-tab-sirv-woo <?php echo ($active_tab == '#sirv-woo') ? 'nav-tab-active' : '' ?>" href="#sirv-woo" data-link="woo"><span class="dashicons dashicons-cart"></span><span class="sirv-tab-txt">WooCommerce</span></a>
        <?php } ?>
        <a class="nav-tab nav-tab-sirv-cache <?php echo ($active_tab == '#sirv-cache') ? 'nav-tab-active' : '' ?>" href="#sirv-cache" data-link="cache"><span class="dashicons dashicons-update"></span><span class="sirv-tab-txt">Synchronization</span></a>
        <!-- <a class="nav-tab nav-tab-sirv-stats <?php echo ($active_tab == '#sirv-stats') ? 'nav-tab-active' : '' ?>" href="#sirv-stats"><span class="dashicons dashicons-chart-bar"></span><span class="sirv-tab-txt">Stats</span></a> -->
      <?php } ?>
    </nav>
  </div>

  <?php
    if($isMuted){
  ?>
  <div class="sirv-optiontable-holder">
    <div class="sirv-error"><?php if ($error) echo '<div id="sirv-settings-messages" class="sirv-message error-message">' . $error . '</div>'; ?></div>
  </div>

  <?php } ?>

  <?php if ($sirvStatus) { ?>
  <div class="sirv-tab-content sirv-tab-content-active" id="sirv-settings">
    <?php include(dirname(__FILE__) . '/submenu_pages/settings.php'); ?>
  </div>

  <?php if ($isWoocommerce) { ?>
    <div class="sirv-tab-content" id="sirv-woo">
      <?php include(dirname(__FILE__) . '/submenu_pages/woocommerce.php'); ?>
    </div>
  <?php } ?>

  <div class="sirv-tab-content" id="sirv-cache">
    <?php include(dirname(__FILE__) . '/submenu_pages/sync.php'); ?>
  </div>
<?php } ?>

<input type="hidden" name="active_tab" id="active_tab" value="#sirv-account" />
<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="<?php echo implode(', ', $options_names); ?>" />

</form>

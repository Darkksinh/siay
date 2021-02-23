<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' ); ?>

<style>
.sirv-skeleton{
  position: absolute;
  width: 100%;
  /* height: 300px; */
  padding-top: 87%;
  background-repeat: no-repeat;
  background-image:
    linear-gradient(#c7c6c6cc 100%, transparent 0),
    linear-gradient(#c7c6c6cc 100%, transparent 0),
    linear-gradient(#c7c6c6cc 100%, transparent 0),
    linear-gradient(#c7c6c6cc 100%, transparent 0),
    linear-gradient(#c7c6c6cc 100%, transparent 0),
    linear-gradient(#fdfdfdcc 100%, transparent 0);
  background-size:
    100% 70%,   /* main image */
    20% 70px,   /* selector 1 */
    20% 70px,   /* selector 2 */
    20% 70px,   /* selector 3 */
    20% 70px,   /* selector 4 */
    100% 100%;  /* container */
  background-position:
    0 0,     /* main image */
    10% 95%, /* selector 1 */
    37% 95%, /* selector 2 */
    64% 95%, /* selector 3 */
    91% 95%, /* selector 4 */
    0 0;     /* container */
}

.sirv-woo-wrapper{
  width: 100%;
  height: 100%;
  max-width: 100%;
  max-height: 100%;
}
</style>

<?php

  function sanitize_custom_styles($data){
    $string = $data;
    $string = str_replace('\r', "", $string);
    $string = str_replace('\n', "", $string);

    return $string;
  }

  require_once (dirname (__FILE__) . '/woo.class.php');

  global $post;

  $woo = new Woo($post->ID);
  $woo->add_frontend_assets();

  $custom_styles_data = get_option('SIRV_WOO_MV_CONTAINER_CUSTOM_CSS');
  $custom_styles = !empty($custom_styles_data) ? 'style="'. sanitize_custom_styles($custom_styles_data) .'"' : '';
?>

<div class="sirv-woo-wrapper" <?php echo $custom_styles; ?>>
  <div style="position: relative;">
    <div class="sirv-skeleton"></div>
  </div>
  <?php echo $woo->get_woo_gallery_html(); ?>
</div>

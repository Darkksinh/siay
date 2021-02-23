<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Woo
{
  protected $product_id;
  protected $viewer_config = array();
  protected $zoom_config = array(
            'wheel' => 'true',
            'mode' => 'deep'
  );
  protected $spin_config = array();
  protected $is_generated_thumb_html = false;

  function __construct($product_id=''){
    $this->product_id = $product_id;
    add_action( 'add_meta_boxes', [$this, 'add_sirv_metabox'] );
    add_action( 'save_post', [$this, 'save_sirv_gallery_data'] );

    return $this;
  }


  public function register_thumbs_filter(){
    $this->add_frontend_assets();
    add_filter( 'woocommerce_single_product_image_thumbnail_html', [$this, 'set_frontend_gallery'], 10, 2);
    add_action('sirv_action_get_woo_gallery_html', [$this, 'get_woo_gallery_html'], 10);
  }


  public function add_frontend_assets(){
    $show_all_variations = get_option('SIRV_WOO_SHOW_VARIATIONS') == '1' ? true : false;

    //wp_register_style('sirv-woo-style', plugins_url('css/wp-sirv-woo.css', __FILE__), array(), '1.0.0');
    //wp_enqueue_style('sirv-woo-style');

    wp_register_script( 'sirv-woo-js', plugins_url('js/wp-sirv-woo.js', __FILE__), array('jquery'), '1.0.0');
    wp_localize_script( 'sirv-woo-js', 'sirv_woo_product', array(
      'mainID' => $this->product_id,
      'showAllVariations' => $show_all_variations,
      )
    );
    wp_enqueue_script('sirv-woo-js');
  }


  public function add_sirv_metabox(){
    if (get_post_type() === 'product'){
        wp_enqueue_script( 'sirv-woo-admin-js', plugins_url('js/wp-sirv-woo-admin.js', __FILE__), array('jquery', 'sirv-shortcodes-page'), '1.0.0');
        wp_enqueue_style('sirv-woo-admin-style', plugins_url('css/wp-sirv-woo-admin.css', __FILE__));

        add_meta_box(
            'woo-sirv-gallery',
            __( 'Sirv Gallery' ),
            [$this, 'render_sirv_meta_box'],
            'product',
            'side',
            'low'
        );
    }
  }


  public function render_sirv_meta_box( $post ){
    $item_pattern = '?thumbnail=78&image';
    $product_id = absint( $post->ID );

    self::render_admin_gallery( $product_id, $item_pattern, 'gallery' );

  }


  public static function render_variation_gallery( $loop, $variation_data, $variation ){
    $item_pattern = '?thumbnail=64&image';
    $variation_id = absint( $variation->ID );

    self::render_admin_gallery( $variation_id, $item_pattern, 'variation' );

  }


  protected static function render_admin_gallery($id, $item_pattern, $type){
    $variation_class = $type == 'variation' ? ' sirv-variation-container' : '';
    ?>
      <div id="sirv-woo-gallery_<?php echo $id; ?>" class="sirv-woo-gallery-container <?php echo $variation_class ?>" data-id="<?php echo $id; ?>">
        <ul class="sirv-woo-images" id="sirv-woo-images_<?php echo $id; ?>" data-id="<?php echo $id; ?>">
          <?php
            $data = self::get_post_sirv_data($id, '_sirv_woo_gallery_data', true);
            if ($data && $data['items'] && !empty($data['items'])){
              $items = $data['items'];
              $count = count($items);

              foreach ( $items as $item ) {
                  $video_id = isset($item['videoID']) ? ' data-video-id="'. $item['videoID'] .'" ' : '';
                  $video_link = isset($item['videoLink']) ? ' data-video-link="'. $item['videoLink'] .'" ' : '';
                  $video_data  = $video_id . $video_link;
                  $thumb_url = empty($video_id) ?  $item['url'] . $item_pattern : $item['url'];

                  echo '<li class="sirv-woo-gallery-item" data-order="'. $item['order'] .'" data-type="'. $item['type'] .'"data-provider="'. $item['provider'] .'" data-url-orig="'. $item['url'] .'"'. $video_data .' data-view-id="'. $id .'">
                          <img class="sirv-woo-gallery-item-img" src="'. $thumb_url .'">
                          <ul class="actions">
                            <li><a href="#" class="delete sirv-delete-item tips" data-id="'. $id .'" data-tip="' . esc_attr__( 'Delete image', 'woocommerce' ) . '">' . __( 'Delete', 'woocommerce' ) . '</a></li>
                          </ul>
                        </li>';
              }
            }else{
              $data = array('items' => array(), 'id' => $id);
            }
          ?>
          </ul>
          <?php if($type == "gallery") {?>
          <div id="sirv-delete-all-images-container_<?php echo $id; ?>" class="sirv-delete-all-images-container" <?php if(isset($count) && $count >= 5) echo 'style="display:block;"'; ?>>
            <a class="button button-primary button-large sirv-woo-delete-all" data-id="<?php echo $id; ?>">Delete all items</a>
          </div>
          <?php } ?>
        <input type="hidden" id="sirv_woo_gallery_data_<?php echo $id; ?>" name="sirv_woo_gallery_data_<?php echo $id; ?>" value="<?php echo esc_attr( json_encode($data) ); ?>" />
        <div class="sirv-woo-gallery-toolbar hide-if-no-js">
          <div class="sirv-woo-gallery-toolbar-main">
            <a class="button button-primary button-large sirv-woo-add-media" data-type="<?php echo $type; ?>" data-id="<?php echo $id; ?>">Add Sirv Media</a>
            <a class="button button-primary button-large sirv-woo-add-online-video" data-id="<?php echo $id; ?>">Add Online Video</a>
          </div>
          <div class="sirv-add-online-videos-container" id="sirv-add-online-videos-container_<?php echo $id; ?>">
            <textarea class="sirv-online-video-links" id="sirv-online-video-links_<?php echo $id; ?>" placeholder="Add links to YouTube or Vimeo videos. One per line..."></textarea>
            <a class="button button-primary button-large sirv-woo-cancel-add-online-videos" data-id="<?php echo $id; ?>">Cancel</a>
            <a class="button button-primary button-large sirv-woo-add-online-videos" data-id="<?php echo $id; ?>">Add video(s)</a>
          </div>
        </div>
      </div>
    <?php
  }


  public function save_sirv_gallery_data($product_id){
    self::save_sirv_data($product_id);
  }


  public static function save_sirv_variation_data($variation_id, $loop){
    self::save_sirv_data($variation_id);
  }


  protected static function save_sirv_data($product_id){
    if ( !empty( $_REQUEST['action'] ) && ( $_REQUEST['action'] == 'editpost' || $_REQUEST['action'] == 'woocommerce_save_variations') ) {
            $data = isset( $_POST['sirv_woo_gallery_data_'. $product_id] ) ? json_decode(stripcslashes( $_POST['sirv_woo_gallery_data_'. $product_id] ), true )  : array();
            self::set_post_sirv_data($product_id, '_sirv_woo_gallery_data', $data);
        }
  }


  public function set_frontend_gallery($html, $post_thumbnail_id){
    if( !$this->is_generated_thumb_html ){
      $sirv_gallery = self::get_post_sirv_data($this->product_id, '_sirv_woo_gallery_data');
      $wc_gallery = $this->parse_wc_gallery($this->product_id);
      $sirv_variations = $this->parse_variations($this->product_id);
      $all_images = $this->get_all_images_data($sirv_gallery->items, $wc_gallery, $sirv_variations);

      if( $all_images ){
        $html = $this->get_gallery_html( $all_images );

        $this->is_generated_thumb_html = true;
      }
    }else{
      $html = '';
    }
    return $html;
  }

  public function get_woo_gallery_html(){
    $html = '';

    //$sirv_gallery = self::get_post_sirv_data($this->product_id, '_sirv_woo_gallery_data');
    //$sirv_gallery = $this->get_sirv_data($this->product_id, false);
    $sirv_local_data = $this->get_sirv_local_data($this->product_id);
    $sirv_remote_data = $this->get_sirv_remote_data($this->product_id, false);

    $sirv_data = $this->merge_object_data($sirv_local_data->items, $sirv_remote_data->items, true);

    $wc_gallery = $this->parse_wc_gallery($this->product_id);
    $sirv_variations = $this->parse_variations($this->product_id);
    //$all_images = $this->get_all_images_data($sirv_gallery->items, $wc_gallery, $sirv_variations);
    $all_images = $this->get_all_images_data($sirv_data, $wc_gallery, $sirv_variations);

    if( $all_images ){
      $html = $this->get_gallery_html( $all_images );
    }

    return $html;
  }


  public function get_sirv_local_data($product_id){
    return self::get_post_sirv_data($product_id, '_sirv_woo_gallery_data');
  }


  public function get_sirv_remote_data($product_id, $isVariation){
    return $this->get_sirv_view_data($product_id, $isVariation);
  }


  protected function merge_object_data($first_data, $second_data, $isReverse = false){
    $sirv_data = (object) array();
    if( !$isReverse){
      $sirv_data = (object) array_merge((array) $first_data, (array) $second_data);
    }else{
      $sirv_data = (object) array_merge((array) $second_data, (array) $first_data);
    }

    return $sirv_data;
  }


  public function get_sirv_data($product_id, $isVariation){
    $data = array();
    $provider = get_option('SIRV_WOO_CONTENT_PROVIDER');

    if($provider == '1'){
      $data = self::get_post_sirv_data($product_id, '_sirv_woo_gallery_data');
    }else{
      $data = $this->get_sirv_view_data($product_id, $isVariation);
    }

    return $data;
  }


protected function get_sirv_view_data($product_id, $isVariation){

  $data = self::get_post_sirv_data($product_id, '_sirv_woo_viewf_data');

  if( empty($data->items) && !isset($data->cache)){
    $data = $this->parse_view_file($product_id, $isVariation);
  }

  return $data;
}

//add full urlencode func
protected function url_space_escape($str){
	return str_replace(' ', '%20', $str);
}


protected function parse_view_file( $product_id, $isVariation ){
  $data = array('items' => array(), 'id' => $product_id, 'cache' => false);

  $fodlers_structure = $isVariation ? get_option('SIRV_WOO_VIEW_FOLDER_VARIATION_STRUCTURE') : get_option('SIRV_WOO_VIEW_FOLDER_STRUCTURE');

  $prod_path = trim( $this->replace_path_params($product_id, $fodlers_structure, $isVariation), '/' );
  $path = sirv_get_sirv_path( $prod_path );

  $json_data = @file_get_contents( $path . '.view?info' );
  $view_data = @json_decode($json_data);

  $data['cache'] = true;

  if( is_object($view_data) && !empty($view_data->assets) && count($view_data->assets) ){
    self::set_post_sirv_data($product_id, '_sirv_woo_viewf_status', 'SUCCESS', false);

    foreach ($view_data->assets as $index => $asset) {
      if ($asset->type != 'image' && $asset->type != 'spin' && $asset->type != 'video') {
        continue;
      }

      $data['items'][] = $this->convert_view_data($product_id, $asset, $index, $path);
    }

    $this->view_data = $data;

  }else{
    $status = is_object($view_data) ? 'EMPTY' : 'FAILED';
    self::set_post_sirv_data($product_id, '_sirv_woo_viewf_status', $status, false);
  }

  self::set_post_sirv_data($product_id, '_sirv_woo_viewf_data', $data);

  return (object) $data;
}


protected function replace_path_params($product_id, $path, $isVariation){
  $product_str_vars = array(
    '{product-id}',
    '{product-sku}',
    '{category-slug}',
  );

  $variation_str_vars = array(
    '{variation-id}',
    '{variation-sku}'
  );

  $product_vars = array(
    $this->product_id,
    $this->get_product_sku($this->product_id),
  );

  $product_vars[] = stripos($path, '{category-slug}') !== false ? $this->get_category_slug($this->product_id) : '';

  $str_vars = array();
  $vars = array();

  if( $isVariation ){
    $variation_vars = array(
      $product_id,
      $this->get_variation_sku($product_id)
    );

    $vars = array_merge($product_vars, $variation_vars);
    $str_vars = array_merge($product_str_vars, $variation_str_vars);
  }else{
    $str_vars = $product_str_vars;
    $vars = $product_vars;
  }

  return str_replace($str_vars, $vars, $path);
}


protected function get_category_slug($product_id){
    $terms = get_the_terms($product_id, 'product_cat');
    $category = count($terms) ? end($terms) : $terms[0];

    return $category->slug;
}

/*
protected function get_category_slug($product_id){
    $terms = get_the_terms($product_id, 'product_cat');
    $category = count($terms) ? $this->get_sub_category($terms) : $terms[0];

    return $category->slug;
}


protected function get_sub_category($categories){
  $subcategory = '';
  foreach ($categories as $category) {
    if($category->parent !== 0){
      $subcategory = $category;
      break;
    }
  }

  return $subcategory;
}
*/


protected function get_product_sku($product_id){
  $product = new WC_Product($product_id);
  $sku = $product->get_sku();
  $sku = empty($sku) ? 'error-no-sku' : $sku;

  return $sku;
}


protected function get_variation_sku($product_id){
  $variation = new WC_Product_Variation($product_id);
  return $variation->get_sku();
}


protected function get_product_slug($product_id){
  $product = new WC_Product($product_id);
  return $product->get_slug();
}


protected function convert_view_data($product_id, $asset, $index, $path){
  return (object) array(
    'url' => $path . '/' . $asset->name,
    'type' => $asset->type,
    'provider' => 'sirv',
    'order' => $index,
    'viewId' => $product_id
  );
}


  protected function get_all_images_data($sirv_images, $wc_images, $sirv_variations){
    $items = (object) array();
    $order = get_option('SIRV_WOO_CONTENT_ORDER');
    $is_show_all_items = get_option('SIRV_WOO_SHOW_VARIATIONS') == '1' ? true : false;

    if( (empty($sirv_images) && empty($wc_images)) || (empty($sirv_images) && $order == '3') ){
      if( !$is_show_all_items || empty($sirv_variations) ){
        $sirv_images[] = (object) array(
          'url' => wc_placeholder_img_src('full'),
          'type' => 'image',
          'provider' => 'woocommerce',
          'viewId' => $this->product_id
        );
      }

    }

    $items = $this->merge_items($order, $sirv_images, $wc_images);

    $items = (object) array_merge((array) $items, (array) $sirv_variations);

    return $this->fix_order($items);
  }


  protected function fix_order($items){
    foreach ($items as $key => $item) {
      $item->order = $key;
    }

    return $items;
  }


  protected function merge_items($order, $sirv_items, $wc_items){
    $items = (object) array();

    switch ($order) {
      case '1':
        $items = (object) array_merge((array) $sirv_items, (array) $wc_items);
        break;
      case '2':
        $items = (object) array_merge((array) $wc_items, (array) $sirv_items);
        break;
      case '3':

      default:
        $items = $sirv_items;
        break;
    }

    return $items;
  }


  protected function parse_variations($product_id){
    $order = get_option('SIRV_WOO_CONTENT_ORDER');
    $variations_ids = $this->get_product_variations_ids($product_id);
    $all_items = array();

    foreach ($variations_ids as $variation_id) {
      $items = (object) array();
      //$variation = self::get_post_sirv_data($variation_id, '_sirv_woo_gallery_data');
      //$variation = $this->get_sirv_data($variation_id, true);

      $sirv_local_variation = $this->get_sirv_local_data($variation_id);
      $sirv_remote_variation = $this->get_sirv_remote_data($variation_id, true);
      //$variation = $this->merge_object_data($sirv_local_variation->items, $sirv_remote_variation->items, true);
      if (!empty($sirv_local_variation) && !empty($sirv_remote_variation)) {
        $variation = $this->merge_object_data($sirv_local_variation->items, $sirv_remote_variation->items, true);
      } else {
        if (empty($sirv_local_variation) && empty($sirv_remote_variation)) {
          $variation = (object) array();
        } else {
          if (empty($sirv_local_variation)) {
            $variation = $sirv_remote_variation->items;
          } else {
            $variation = $sirv_local_variation->items;
          }
        }
      }

      /* if( !empty($variation->items) ){
        $items = (object) array_merge((array) $items, $variation->items);
      } */
      if( !empty($variation) ){
        $items = (object) array_merge((array) $items, (array) $variation);
      }

      if($order != '3'){
        $wc_variation = $this->parse_wc_variation($variation_id, $product_id);
        if( !empty($wc_variation) ){
          //$items = (object) array_merge($wc_variation, (array) $items);
          $items = $this->merge_items($order, $items, $wc_variation);
        }
      }

      $all_items = array_merge((array) $all_items, (array) $items);
    }

    return $all_items;
  }


  protected function parse_wc_variation($variation_id, $product_id){
    $variation = new WC_Product_Variation( $variation_id );
    $variation_img_id = $variation->get_image_id();

    if(get_option('SIRV_WOO_SHOW_MAIN_VARIATION_IMAGE') == 2){
      $product = new WC_Product($product_id);
      $main_product_img_id = $product->get_image_id();

      if ($variation_img_id == $main_product_img_id) return array();
    }


    return array( $this->get_sirv_item_data($variation_img_id, $variation_id) );
  }


  protected function get_product_variations_ids($product_id){
    $product = new WC_Product_Variable( $product_id );
    $variations = $product->get_available_variations();
    $variations_ids = array();
    foreach ($variations as $variation) {
      $variations_ids[] = $variation['variation_id'];
    }

    return $variations_ids;
  }


  protected function is_default_variation(){
    $product  = new WC_Product($this->product_id);
    return !empty( $product->get_default_attributes() );
  }


  protected function get_default_variation_id(){
    $default_variation_id = -1;
    $product  = new WC_Product($this->product_id);
    $default_attributes = $product->get_default_attributes();
    if(!empty($default_attributes)){
      $product_variable = new WC_Product_Variable( $this->product_id );
      foreach($product_variable->get_available_variations() as $variation_values ){
        foreach($variation_values['attributes'] as $key => $attribute_value ){
            $attribute_name = str_replace( 'attribute_', '', $key );
            $default_value = $product_variable->get_variation_default_attribute($attribute_name);
            if( $default_value == $attribute_value ){
                $is_default_variation = true;
            } else {
                $is_default_variation = false;
                break; // Stop this loop to start next main lopp
            }
        }
        if( $is_default_variation ){
            $default_variation_id = $variation_values['variation_id'];
            break; // Stop the main loop
        }
      }
    }

    return $default_variation_id;
  }


  protected function parse_wc_gallery($product_id){
    $items = array();
    $product = new WC_product($product_id);
    $main_image = $product->get_image_id();
    $gallery = $product->get_gallery_image_ids();

    if(!empty($main_image)) array_unshift($gallery, $main_image);

    foreach ($gallery as $image_id) {
      $items[] = $this->get_sirv_item_data($image_id, $product_id);
    }

    return $items;
  }


  protected function get_sirv_item_data($image_id, $product_id){
    $url = $this->clean_uri_params( wp_get_attachment_image_src($image_id, 'full')[0] );
    $provider = $this->is_sirv_item($url) ? 'sirv' : 'woocommerce';

    return (object) array('url' => $url, 'type'=> 'image', 'provider' => $provider, 'viewId' => $product_id);
  }


  protected function clean_uri_params($url){
    return preg_replace('/\?.*/i', '', $url);
  }


  protected function is_sirv_item($url){
    return stripos($url, 'sirv.com') !== false;
  }


  protected function add_profile($src, $type){
    $url = $src;
    if($type == 'image'){
      $profile = get_option('SIRV_WOO_PRODUCTS_PROFILE');

      if(!empty($profile)){
        $url .= '?profile=' . $profile;
      }
    }
    return $url;
  }

  protected function remove_script_tag($string){
    return preg_replace('/<(\/)*script.*?>/im', '', $string);
  }


  protected function is_all_items_disabled($items){
    $items_count = count((array) $items);
    $dis_items_count = 0;

    $is_all_items_disabled = false;

    $is_all_variations = get_option('SIRV_WOO_SHOW_VARIATIONS') == '1' ? true : false;
    $default_variation_id = $this->get_default_variation_id();
    $default_shows_id = $default_variation_id != -1 ? $default_variation_id : $this->product_id;

    foreach ($items as $item) {
      $is_item_disabled = $this->is_item_disabled($item, $items_count, $is_all_variations, $default_variation_id, $default_shows_id);
      $item->isDisabled = $is_item_disabled;

      if($is_item_disabled) $dis_items_count ++;
    }

    if($items_count == $dis_items_count) $is_all_items_disabled = true;

    return $is_all_items_disabled;
  }


  protected function is_item_disabled($item, $i_count, $is_all_variations, $default_variation_id, $default_shows_id){
      if( ($is_all_variations && $default_variation_id == -1) || $i_count == 1 ) return false;
      else if((int) $item->viewId !== (int) $default_shows_id) return true;
      else return false;
  }


  protected function is_disable_item_str($item, $is_all_items_disabled)
  {
    $disable_item = '';

    if ($item->isDisabled) {
      if ($is_all_items_disabled) {
        if ($item->viewId == $this->product_id) {
          $disable_item = '';
        } else {
          $disable_item = 'data-disabled';
        }
      } else {
        $disable_item = 'data-disabled';
      }
    }
    return $disable_item;
  }


  protected function get_gallery_html($items){
    $items_html = '';

    $mv_custom_options = $this->remove_script_tag( get_option('SIRV_WOO_MV_CUSTOM_OPTIONS') );
    $mv_custom_options_block = ! empty($mv_custom_options) ? '<script>'. $mv_custom_options .'</script>' . PHP_EOL : '';

    $max_height = get_option("SIRV_WOO_MAX_HEIGHT");
    $max_height_style = empty($max_height) ? '' : '<style>.sirv-woo-wrapper .Sirv > .smv{ max-height: '. $max_height .'px; }</style>';

    $is_all_items_disabled = $this->is_all_items_disabled($items);

    $ids_data = array();

    foreach ($items as $item) {
      //$is_item_disabled = $item->isDisabled && ($is_all_items_disabled && $item->viewId != $this->product_id) ? 'data-disabled' : '';
      $is_item_disabled = $this->is_disable_item_str($item, $is_all_items_disabled);
      $src = $item->type == 'online-video' ? $item->videoLink : $item->url;
      $zoom = $item->type == 'image' ? ' data-type="zoom" ' : '';

      if($item->provider !=='woocommerce'){
        $items_html .= '<div data-src="'. $this->add_profile($src, $item->type) .'"'. $zoom .' data-view-id="'. $item->viewId .'" data-order="'. $item->order .'" '. $is_item_disabled .'></div>'. PHP_EOL;
      }else{
        $items_html .= '<img data-src="'. $src .'" data-type="static" data-view-id="'. $item->viewId .'" data-order="'. $item->order .'" '. $is_item_disabled .' />'. PHP_EOL;
      }

      $ids_data[$item->viewId][] = (int) $item->order;
    }

    $json_data_block = '<div style="display: none;" id="sirv-woo-gallery_data_' . $this->product_id . '" data-gallery-json=\''. json_encode($ids_data, JSON_HEX_QUOT | JSON_HEX_APOS) .'\'></div>'. PHP_EOL;

    return $json_data_block .'<div class="Sirv" id="sirv-woo-gallery_'. $this->product_id .'">'. PHP_EOL . $items_html .'</div>'. PHP_EOL . $mv_custom_options_block . $max_height_style;
  }


  protected static function get_post_sirv_data($product_id, $field_id, $isAssociativeArray=false){
    $data = array();

    if (metadata_exists( 'post', $product_id, $field_id )){
      $data = json_decode(get_post_meta( $product_id, $field_id, true ), $isAssociativeArray);
    }

    return $data;
  }


  protected static function set_post_sirv_data($product_id, $field_id, $data, $isJson=true){
    $data = $isJson ? json_encode($data) : $data;
    update_post_meta( $product_id, $field_id, $data );
  }


  public function set_config($type, $config){

  }


  protected function generate_config($config){
    return http_build_query($config, '', ';');
  }

}
?>

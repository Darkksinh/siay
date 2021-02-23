<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Woo_options{

  protected static $profiles = array();
  protected static $options = array(
    'SIRV_WOO_IS_ENABLE' => array(
      'enabled_option' => true,
      'option_name' => 'SIRV_WOO_IS_ENABLE',
      'label' =>'Sirv Media Viewer',
      //'desc' => 'Some text here',
      'type' => 'radio',
      'value' => '',
      'values' => array(
        array(
          'label' => 'Enable',
          'value' => '2'
        ),
        array(
          'label' => 'Disable',
          'value' => '1'
        )

      ),
      'default' => '1',
      'show_status' => true,
      'enabled_value' => '2',
    ),
    'SIRV_WOO_CONTENT_PROVIDER' => array(
      'enabled_option' => false,
      'option_name' => 'SIRV_WOO_CONTENT_PROVIDER',
      'label' =>'Content source',
      'type' => 'radio',
      'value' => '',
      'values' => array(
        array(
          'label' => 'Manual',
          'value' => '1',
          'desc' => 'select content via WooCommerce product admin.',
        ),
        array(
          'label' => 'Automatic',
          'value' => '2',
          'desc' => 'show content based on Sirv folder name.',
        ),
      ),
      'default' => '1',
      'show_status' => false,
    ),
    'SIRV_WOO_VIEW_FOLDER_STRUCTURE' => array(
      'enabled_option' => true,
      'option_name' => 'SIRV_WOO_VIEW_FOLDER_STRUCTURE',
      'label' =>'Product folders',
      'type' => 'input',
      'value' => '',
      'placeholder' => 'products/{product-sku}',
      'desc' => 'Possible variables: {product-sku}, {product-id}',
      'dependence' => array(
        /* 'name' => 'SIRV_WOO_CONTENT_PROVIDER',
        'value' => '1',
        'type' => 'disable' */
      ),
      'default' => 'products/{product-sku}',
    ),
    'SIRV_WOO_VIEW_FOLDER_VARIATION_STRUCTURE' => array(
      'enabled_option' => true,
      'option_name' => 'SIRV_WOO_VIEW_FOLDER_VARIATION_STRUCTURE',
      'label' =>'Variation folders',
      'type' => 'input',
      'value' => '',
      'placeholder' => 'products/{product-sku}-{variation-sku}',
      'desc' => 'Possible variables: {product-sku}, {product-id}, {variation-sku}, {variation-id}',
      'dependence' => array(
        /* 'name' => 'SIRV_WOO_CONTENT_PROVIDER',
        'value' => '1',
        'type' => 'disable' */
      ),
      'default' => 'products/{product-sku}-{variation-sku}',
    ),
    'SIRV_WOO_SHOW_MAIN_VARIATION_IMAGE' => array(
      'enabled_option' => true,
      'option_name' => 'SIRV_WOO_SHOW_MAIN_VARIATION_IMAGE',
      'label' => 'Main variation image',
      'tooltip' => "If variation has no image, show main product image.",
      'type' => 'radio',
      'value' => '',
      'values' => array(
        array(
          'label' => 'Show',
          'value' => '1'
        ),
        array(
          'label' => 'Hide',
          'value' => '2'
        ),
      ),
      'default' => '2',
      'show_status' => false,
    ),
    'SIRV_WOO_CONTENT_ORDER' => array(
      'enabled_option' => true,
      'option_name' => 'SIRV_WOO_CONTENT_ORDER',
      'label' =>'Order of content',
      'type' => 'radio',
      'value' => '',
      'values' => array(
        array(
          'label' => 'Sirv content first',
          'value' => '1'
        ),
        array(
          'label' => 'WooCommerce content first',
          'value' => '2'
        ),
        array(
          'label' => 'Sirv content only',
          'value' => '3'
        ),
      ),
      'default' => '2',
      'show_status' => false,
    ),
    'SIRV_WOO_SHOW_VARIATIONS' => array(
      'enabled_option' => true,
      'option_name' => 'SIRV_WOO_SHOW_VARIATIONS',
      'label' =>'Number of images',
      'type' => 'radio',
      'value' => '',
      'values' => array(
        array(
          'label' => 'Show all product images',
          'value' => '1'
        ),
        array(
          'label' => 'Show images for current variation only',
          'value' => '2'
        )
      ),
      'default' => '2',
      'show_status' => false,
    ),
    'SIRV_WOO_MAX_HEIGHT' => array(
      'enabled_option' => true,
      'option_name' => 'SIRV_WOO_MAX_HEIGHT',
      'label' =>'Max height (px)',
      'type' => 'input',
      'value' => '',
      'placeholder' => 'auto',
      'default' => '',
    ),
    'SIRV_WOO_PRODUCTS_PROFILE' => array(
      'enabled_option' => true,
      'option_name' => 'SIRV_WOO_PRODUCTS_PROFILE',
      'label' =>'Product images profile',
      'tooltip' => 'Apply one of <a target="_blank" href="https://my.sirv.com/#/profiles/">your profiles</a> for watermarks, text and other image customizations. Learn <a target="_blank" href="https://sirv.com/help/articles/dynamic-imaging/profiles/">about profiles</a>.',
      'type' => 'select',
      'select_id' => 'sirv-woo-product-profiles',
      'hidden_id' => 'sirv-woo-product-profiles-val',
      'value' => '',
      'default' => ''
    ),
    'SIRV_WOO_MV_CONTAINER_CUSTOM_CSS' =>array(
      'enabled_option' => true,
      'option_name' => 'SIRV_WOO_MV_CONTAINER_CUSTOM_CSS',
      'label' =>'Container custom CSS',
      //'desc' => 'Some text here',
      'type' => 'textarea',
      'value' => '',
      'placeholder' => 'Add styles to adjust size/position of Sirv Media Viewer container:

width: 49%;
float: left;',
      'tooltip' => 'Add styles to fix any rendering issues from 3rd party CSS',
      'size' => '5',
      'default' => '',
    ),
    'SIRV_WOO_MV_CUSTOM_OPTIONS' => array(
      'enabled_option' => true,
      'option_name' => 'SIRV_WOO_MV_CUSTOM_OPTIONS',
      'label' =>'Media Viewer options',
      //'desc' => 'Some text here',
      'type' => 'textarea',
      'value' => '',
      'placeholder' => "Add custom js options for Media Viewer. e.g.
var SirvOptions = {
  zoom: {
    mode: 'deep'
  }
}",
      'below_desc' => 'Change the zoom, spin, video and thumbnail options with JavaScript. See <a href="https://sirv.com/help/articles/sirv-media-viewer/#options">list of options</a>.',
      'tooltip' => 'Change the zoom, spin, video and thumbnail options with JavaScript. See <a target="_blank" href="https://sirv.com/help/articles/sirv-media-viewer/#options">list of options</a>.',
      'size' => '6',
      'default' => '',
    ),
    /* "unreg_save_button" => array(
      'option_name' => 'save_button',
      'type' => 'button',
      'label' =>'Save Settings',
      'class' => 'sirv-save-settings',
    ),
    "unreg_empty-view-cache" => array(
      'option_name' => 'empty-view-cache',
      'label' =>'Empty Sirv content cache',
      'type' => 'custom',
      'custom_type' => 'radio_and_button',
      'value' => 'all',
      'values' => array(
        array(
          'label' => 'All files',
          'value' => 'all'
        ),
        array(
          'label' => 'Empty files',
          'value' => 'empty'
        ),
        array(
          'label' => 'Missing files',
          'value' => 'missing'
        )
      ),
      'button_val' => 'Empty cache',
      'button_class' => 'sirv-clear-view-cache',
      'radio_class' => '',
      'tooltip' => 'For fast loading, the plugin keeps a list of files in your Sirv account. Empty the cache if files are out of date.',
      'data_provider' => 'sirv_get_view_cache_info',
    ),*/
  );


  public static function get_option_names_list(){
    $names = array();

    foreach (self::$options as $option_name => $option_data) {

      if( stripos($option_name, 'unreg_') !== false || !$option_data['enabled_option'] ) continue;

      $names[] = $option_name;
    }

    return $names;
  }


  public static function register_settings(){
    foreach (self::$options as $option_name => $option_data) {
      if( stripos($option_name, 'unreg_') !== false) continue;
      register_setting( 'sirv-woo-settings-group', $option_name);
      if ( !get_option($option_name) ) update_option($option_name, $option_data['default']);
    }
  }


  public static function render_options($profiles){
    $html = '';
    self::$profiles = $profiles;
    self::update_options_values();

    foreach (self::$options as $option_name => $option_data) {
      if( $option_data['enabled_option'] ){
        $html .= self::get_item($option_data);
      }

    }

    return $html;
  }


  protected static function get_item($data){
    $item = '';
    switch ($data['type']) {
      case 'radio':
        $item = self::render_radio($data);
        break;
      case 'select':
        $item = self::render_select($data);
        break;
      case 'checkbox':
        $item = self::render_checkbox($data);
        break;
      case 'input':
        $item = self::render_input($data);
        break;
      case 'textarea':
        $item = self::render_textarea($data);
        break;
      case 'custom':
        $item = self::render_custom($data);
        break;
      case 'button':
        $item = self::render_button($data);
        break;

      default:
        # code...
        break;
    }

    return $item;
  }


  protected static function render_button($item){
    $html = '
    <tr>
      <th></th>
      <td><input type="submit" name="submit" class="button-primary '. $item['class'] .'" value="'. $item['label'] .'" /></td>
    </tr>';

    return $html;
  }


  protected static function render_custom($data){
    $item = '';
    switch ($data['custom_type']) {
      case 'radio_and_button':
        $item = self::render_radio_and_button($data);
        break;

      default:
        # code...
        break;
    }

    return $item;
  }


  protected static function update_options_values(){
    foreach (self::$options as $option_name => $option_data) {
      if( stripos($option_name, 'unreg_') !== false ) continue;

      $cur_value = !get_option($option_name) ? $option_data['default'] : get_option($option_name);
      self::$options[$option_name]['value'] = $cur_value;
    }
  }


  protected static function render_select($item){
    $html = '
    <tr>
      <th>
        <label>'. $item['label'] .'</label>
      </th>
      <td>
        <select id="'. $item['select_id'] .'">
          <option disabled>Choose profile</option>'
          . self::render_select_items($item) .'
        </select>
        <input type="hidden" id="'. $item['hidden_id'] .'" name="'. $item['option_name'] .'" value="'. $item['value'] .'">
      </td>'. PHP_EOL . self::tooltip($item) .'
    </tr>';

    return $html;
  }


  protected static function render_select_items($data){
    $default_selected = ($data['value'] == '') ? 'selected' : '';
    $select_items = '<option value="" '. $default_selected .'>-</option>';

    foreach (self::$profiles as $profile) {
      $select_items .= "<option value='{$profile}' ". selected( $profile, $data['value'], false ) .">{$profile}</option>". PHP_EOL;
    }

    return $select_items;
  }


  protected static function render_radio($item){
    $html = '
    <tr>
      <th>
        <label>'. $item['label'] .'</label>
      </th>
      <td>' . self::get_radio_items($item) .
      '</td>' . PHP_EOL . self::tooltip($item);

    if($item['show_status']){
      $status_class = $item['value'] == $item['enabled_value'] ? 'sirv-status--enabled' : 'sirv-status--disabled';
      $html .= '
        <td>
          <span class="sirv-status '. $status_class .'"></span>
        </td>
      </tr>' . PHP_EOL;
    }else{
      $html .= '<tr>' . PHP_EOL;
    }

    return $html;
  }


  protected static function get_radio_items($data){
    $radio_items = '';

    foreach ($data['values'] as $radio_item) {
      $label = isset($radio_item['desc']) ? '<b>'. $radio_item['label'] .'</b> - '. $radio_item['desc'] : $radio_item['label'];
      $radio_items .= '<label><input type="radio" name="'. $data['option_name'] .'"
      value="'. $radio_item['value']. '" '. checked( $radio_item['value'], $data['value'], false ) .' >'. $label .'</label><br />'. PHP_EOL;
    }

    return $radio_items;
  }


  protected static function render_checkbox($item){
    $html = '
    <tr>
      <th>
        <label>'. $item['label'] .'</label>
      </th>
      <td>
        <label><input type="checkbox" name="'. $item['option_name'] .'" id="'. $item['option_name'] .'" value="1" '. checked('1', $item['value'], false ) .' >
          <span class="sirv-option-responsive-text">'. $item['desc'] .'</span>
        </label>
      </td>'. PHP_EOL . self::tooltip($item) .'
    </tr>';

    return $html;
  }


  protected static function render_input($item){
    $desc = isset($item['desc']) ? $item['desc'] : '';
    $dependence = self::get_dependence($item);

    $html = '
    <tr '. $dependence['hide'] .'>
      <th>
        <label>'. $item['label'] .'</label>
      </th>
      <td>
        <input type="text" name="'. $item['option_name'] .'" placeholder="'. $item['placeholder'] .'" value="'. $item['value'] .'" '. $dependence['disable'] .'>
        <span class="sirv-option-responsive-text">'. $desc .'</span>
      </td>'. PHP_EOL . self::tooltip($item) .'
    </tr>';

    return $html;
  }


  protected static function render_textarea($item){

    $html = '
    <tr>
      <th>
        <label>'. $item['label'] .'</label>
      </th>
      <td>
        <textarea name="'. $item['option_name'] .'" placeholder="'. $item['placeholder'] .'" value="'. $item['value'] .'" rows="'.$item['size'].'">'. $item['value'] .'</textarea>
      </td>'. PHP_EOL . self::tooltip($item) .'
    </tr>';

    return $html;
  }


  protected static function render_radio_and_button($item){
    if( isset($item['data_provider']) && !empty($item['data_provider']) ){
      $cache_data = call_user_func($item['data_provider']);

      for($i = 0; $i < count($item['values']); $i++ ){
        $label = $item['values'][$i]['label'];
        $value = $item['values'][$i]['value'];

        $item['values'][$i]['label'] = $label . ' (<span class="'. $item['option_name'] .'-'. $value .'">' . $cache_data[$value] . '</span>)';
      }
    }

    $html = '
    <tr>
      <th>
        <label>'. $item['label'] .'</label>
      </th>
      <td>
        '. self::get_radio_items($item) .'
      </td>'. PHP_EOL . self::tooltip($item) .'
    </tr>
    <tr>
      <th></th>
      <td>
        <input type="button" name="'. $item['option_name'] .'" class="button-primary '. $item['button_class'] .'" value="'. $item['button_val'] .'">&nbsp;
        <span class="sirv-traffic-loading-ico" style="display: none;"></span>
        <span class="sirv-show-empty-view-result" style="display: none;"></span>
      </td>
    </tr>';

    return $html;
  }


  protected static function tooltip($item){
    $tooltip = '';
    if( isset($item['tooltip']) ){
      $tooltip= '
      <td>
        <div class="sirv-tooltip">
          <i class="dashicons dashicons-editor-help sirv-tooltip-icon"></i>
          <span class="sirv-tooltip-text sirv-no-select-text">'. $item['tooltip'] .'</span>
        </div>
      </td>
      ';
    }

    return $tooltip;
  }


  protected static function get_dependence($item){
    $dep_html = array('hide' => '', 'disable' => '');
    if( !isset($item['dependence']) || empty($item['dependence']) ) return $dep_html;

    $dep_value = self::$options[$item['dependence']['name']]['value'];

    if($dep_value == $item['dependence']['value']){
      $dep_type = $item['dependence']['type'];
      switch ($dep_type) {
        case 'disable':
          $dep_html['disable'] = 'disabled';
          break;
        case 'hide':
          $dep_html['hide'] = 'style="display: none;"';
          break;

        default:
          # code...
          break;
      }
    }

    return $dep_html;
  }


  public static function render_view_clean_cache(){
    $option =  array(
      'option_name' => 'empty-view-cache',
      'label' =>'Empty Sirv content cache',
      'type' => 'custom',
      'custom_type' => 'radio_and_button',
      'value' => 'all',
      'values' => array(
        array(
          'label' => 'All files',
          'value' => 'all'
        ),
        array(
          'label' => 'Empty files',
          'value' => 'empty'
        ),
        array(
          'label' => 'Missing files',
          'value' => 'missing'
        )
      ),
      'button_val' => 'Empty cache',
      'button_class' => 'sirv-clear-view-cache',
      'radio_class' => '',
      'tooltip' => 'For fast loading, the plugin keeps a list of files in your Sirv account. Empty the cache if files are out of date.',
      'data_provider' => 'sirv_get_view_cache_info',
    );

    return self::render_custom($option);

  }

}

?>

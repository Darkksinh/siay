<?php
namespace SirvElementorWidget\Controls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class SirvControl extends \Elementor\Base_Data_Control{

    public function get_type() {
		return 'sirvcontrol';
	}


	public function enqueue() {
		wp_register_style( 'sirv_style', SIRV_PLUGIN_URL_PATH . 'sirv/css/wp-sirv.css' );
		wp_enqueue_style('sirv_style');
		wp_register_style( 'sirv_mce_style', SIRV_PLUGIN_URL_PATH . 'sirv/css/wp-sirv-shortcode-view.css' );
		wp_enqueue_style('sirv_mce_style');
		wp_register_script( 'sirv_logic', SIRV_PLUGIN_URL_PATH . 'sirv/js/wp-sirv.js', array( 'jquery', 'jquery-ui-sortable' ), '1.1.0');
		wp_localize_script( 'sirv_logic', 'sirv_ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'assets_path' => SIRV_PLUGIN_URL_PATH . 'sirv/assets', 'plugin_path' => SIRV_PLUGIN_PATH, 'sirv_cdn_url' => get_option('SIRV_CDN_URL') ));
		wp_enqueue_script('sirv_logic');
		wp_enqueue_script( 'sirv_logic-md5', SIRV_PLUGIN_URL_PATH . 'sirv/js/wp-sirv-md5.min.js', array(), '1.0.0');
		wp_enqueue_script( 'sirv_modal', SIRV_PLUGIN_URL_PATH . 'sirv/js/wp-sirv-bpopup.min.js', array('jquery'), '1.0.0');
		wp_enqueue_script( 'sirv_modal-logic', SIRV_PLUGIN_URL_PATH . 'sirv/js/wp-sirv-modal.js', array('jquery'), '1.0.0');

		wp_register_style('sirv_frontend_style', SIRV_PLUGIN_URL_PATH . '/sirv/css/sirv-responsive-frontend.css');
		wp_enqueue_style('sirv_frontend_style');

		$isNotEmptySirvOptions = sirv_check_empty_options_on_backend();
		wp_localize_script( 'sirv_modal-logic', 'modal_object', array(
			'media_add_url' =>  SIRV_PLUGIN_URL_PATH . 'sirv/templates/media_add.html',
			'login_error_url' => SIRV_PLUGIN_URL_PATH . 'sirv/templates/login_error.html',
			'featured_image_url' => SIRV_PLUGIN_URL_PATH . 'sirv/templates/featured_image.html',
			'woo_media_add_url' => SIRV_PLUGIN_URL_PATH . 'sirv/templates/woo_media_add.html',
			'isNotEmptySirvOptions' => $isNotEmptySirvOptions, 'sirv_cdn_url' => get_option('SIRV_CDN_URL')));
		wp_enqueue_script('sirv-shortcodes-page', SIRV_PLUGIN_URL_PATH . 'sirv/js/wp-sirv-shortcodes-page.js', array( 'jquery'), '1.0.0');

		wp_enqueue_script( 'sirv_control_manager', plugins_url('/assets/js/sirvControlManager.js', __FILE__), array('jquery'), '1.0.0', true);

		wp_register_style('wp-standard-styles', get_admin_url() . "load-styles.php?c=0&dir=ltr&load[]=dashicons,buttons,media-views,common,forms,dashboard,list-tables,edit,media,nav-menu");
		wp_enqueue_style('wp-standard-styles');

		wp_register_style( 'sirv-elementor-plugin-css', plugins_url('/assets/css/sirv-elementor.css', __FILE__), array(), null, 'all');
		wp_enqueue_style('sirv-elementor-plugin-css');
	}


	public function content_template() {
		$control_uid = $this->get_control_uid();
		?>
		<# if ( data.description ) { #>
			<div class="elementor-control-field-description">{{{ data.description }}}</div>
		<# } #>
		<div>
			<div id="<?php echo $control_uid; ?>" class="sirv-elementor-add-media sirv-modal-click">
				<span class="sirv-elementor-add-media-text">
					<?php echo __( 'Add Sirv Media', 'sirv' ); ?>
				</span>
			</div>
			<div class="sirv-data-elementor"></div>
			<div class="sirv-modal">
				<div class="modal-content"></div>
			</div>
		</div>
		<?php
	}


	/*protected function get_default_settings() {
		return [
			'label_block' => true,
			'sirv_data' => ['test' => 'accepted']
		];
	}*/


	/*public function get_default_value() {
		return [];
	}*/
}

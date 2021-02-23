<?php

namespace SirvElementorWidget;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Plugin {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function widget_styles() {
		//wp_register_style( 'sirv-elementor-plugin-css', plugins_url('/assets/css/sirv-elementor.css', __FILE__) );
		wp_register_style( 'sirv-gallery', SIRV_PLUGIN_URL_PATH . 'sirv/css/wp-sirv-gallery.css' );
		wp_enqueue_style('sirv-gallery');

		wp_register_style('sirv-elementor-block-css', plugins_url('/assets/css/sirv-elementor-block.css', __FILE__));
		wp_enqueue_style('sirv-elementor-block-css');
	}


	public function widget_scripts() {
		$js_type = (int) get_option('SIRV_JS_FILE');
		$js_path = $js_type == 3 ? 'https://scripts.sirv.com/sirvjs/v3/sirv.js' : 'https://scripts.sirv.com/sirv.js';
		wp_register_script('sirv-js', $js_path, array(), false, true);
		wp_enqueue_script('sirv-js');

		wp_register_script('sirv-inject-js', plugins_url('/assets/js/sirv-inject.js', __FILE__), array('jquery'), false, true);
		wp_enqueue_script('sirv-inject-js');
		wp_enqueue_script( 'sirv-gallery-viewer', SIRV_PLUGIN_URL_PATH . 'sirv/js/wp-sirv-gallery.js', array('jquery'), '1.0.0');

	}


	private function include_widgets_files() {
		require_once( __DIR__ . '/SirvWidget.php' );

	}


	private function include_controls_files(){
		require_once( __DIR__ . '/SirvControl.php' );
	}


	public function register_controls(){
		$this->include_controls_files();

		$controls_manager = \Elementor\Plugin::$instance->controls_manager;
		$controls_manager->register_control( 'sirvcontrol', new Controls\SirvControl() );
	}

	public function register_widgets() {
		$this->include_widgets_files();
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new Widgets\SirvWidget() );
	}

	public function __construct() {
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widgets' ] );
		add_action( 'elementor/controls/controls_registered', [ $this, 'register_controls' ] );
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'widget_styles' ] );
		add_action( 'elementor/init', [ $this, 'widget_scripts' ] );
	}
}
// Instantiate Plugin Class
Plugin::instance();

<?php
namespace SirvElementorWidget\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class SirvWidget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'SIRV';
	}


	public function get_title() {
		return __( 'Sirv gallery', 'sirv' );
	}


	public function get_icon() {
		return 'fa fa-picture-o';
	}


	public function get_categories() {
		return [ 'general' ];
	}


	protected function _register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', 'sirv' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'sirv-gallery',
			[
				'label' => __( 'Sirv add media', 'sirv' ),
				'type' => 'sirvcontrol',
				'dynamic' => [
					'active' => true,
				],
				'description' => "You're adding Sirv gallery. Choose your image(s) or spin(s):"
			]
		);

		$this->add_control(
			'sirv-data-string',
			[
				'label' => __( 'View', 'plugin-domain' ),
				'type' => \Elementor\Controls_Manager::HIDDEN,
				'default' => '',
			]
		);

		$this->end_controls_section();

	}


	/**
	 * Render the widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();

		$shData = json_decode($settings['sirv-data-string'], true);


		echo '<div>';

		if(\Elementor\Plugin::$instance->editor->is_edit_mode()){
			echo '<div class="sirv-elementor-click-overlay" style="position: absolute;top: 0;left: 0;bottom: 100%;right: 100%;width: 100%;height: 100%;z-index: 1000000;"></div>';

			if(empty($settings['sirv-data-string'])){
				echo '<div class="sirv-empty-widget"><img class="sirv-empty-widget__img" src="'. SIRV_PLUGIN_URL_PATH .'sirv/assets/logo.svg" /><div class="sirv-empty-widget__text">Sirv gallery</div></div>';
			}
		}

		if(!empty($shData['shortcode']['id'])) echo do_shortcode('[sirv-gallery id="'. $shData['shortcode']['id'] . '"]');

		if(!empty($shData['images'])){
			echo $this->render_sirv_imgs($shData);
		}

		echo '</div>';

		//if(empty($settings['sirv-data-string'])) echo "<br>Hidden field is empty<br>"; else echo "<br>Hidden string: " . $settings['sirv-data-string'] . "<br>";

	}


	protected function render_sirv_imgs($data){
		$placehodler_grey = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAKSURBVAgdY3gPAADxAPAXl1qaAAAAAElFTkSuQmCC";
		$placeholder_grey_params = '?q=1&w=10&colorize.color=efefef';

		$isResponsive = (Boolean) $data['images']['full']['isResponsive'];
		$isLazyLoading = (Boolean) $data['images']['full']['isLazyLoading'];
		$width = $data['images']['full']['width'];
		$align = $data['images']['full']['align'];
		$isLinkToBigImage = (Boolean) $data['images']['full']['linkToBigImage'];
		$isAltCaption = (Boolean) $data['images']['full']['isAltCaption'];

		$sirvClass = $isResponsive ? 'Sirv' : '';

		$this->add_render_attribute('figure', [
			'class' => ['sirv-flx', 'sirv-img-container', $align]
		]);

		if($width){
			//$style = $isResponsive ? "max-width: {$width}px;" : "width: {$width}px;";
			$style = "width: {$width}px;";
			$this->add_render_attribute('figure', 'style', $style);
		}

		if($isResponsive){

			if(!$isLazyLoading) $this->add_render_attribute('sirv_img', 'data-options', 'lazy: false;');
		}

		$this->add_render_attribute('figure__img', [
				'class' => [$sirvClass, 'sirv-img-container__img']
		]);


		$images = '';
		foreach ($data['images']['full']['imagesData'] as $imageData) {
			$fcaption = '';
			if($imageData['caption']){
				$this->add_render_attribute('figure__img', 'alt', $imageData['caption']);
				if($isAltCaption){
					$fcaption = '<figcaption class="sirv-img-container__cap">'. $imageData['caption'] .'</figcaption>';
				}
			}

			//$srcAttr = $isResponsive ? 'src="' . $placehodler_grey .'"' : 'src="' . $imageData['modUrl'] . '"';
			$srcAttr = $isResponsive ? 'src="' . $imageData['origUrl'] . $placeholder_grey_params .'"' : 'src="' . $imageData['modUrl'] . '"';
			$dataSrcAttr = $isResponsive ? ' data-src="' . $imageData['modUrl'] . '"' : '';

			$imgTag = '<img '. $this->get_render_attribute_string( 'figure__img' ) . ' ' . $srcAttr . $dataSrcAttr .'>';
			$build = '<figure '. $this->get_render_attribute_string( 'figure' ) .'>';
			if($isLinkToBigImage) $build .= '<a class="sirv-img-container__link" href="'. $imageData['origUrl'] .'">' . $imgTag . '</a>'; else $build .= $imgTag;
			$build .= $fcaption .'</figure>' . PHP_EOL;

			$images .= $build;

		}


		return $images;

	}

	/**
	 * Render the widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.1.0
	 *
	 * @access protected
	 */
	/*protected function _content_template() {

	}*/

}

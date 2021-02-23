<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Sirv_Gallery_MV
{
    private $params;
    private $items;
    private $captions;
    //private $initialized = false;
    private $inline_css = array();

    public function __construct($params = array(), $items = array(), $captions = array())
    {
        $this->params = array(
            'width'     => 'auto',
            'height'    => 'auto',
            'is_gallery' => false,
            'profile'   => '',
            'default_profile' => get_option('SIRV_SHORTCODES_PROFILES'),
            'link_image' => false,
            'show_caption' => false,
            'thumbnails_height' => 80,
            'apply_zoom' => false,
            'gallery_styles' => '',
            'gallery_align' => '',
            'zgallery_data_options' => array(),
            'zgallery_thumbs_position' => 'bottom',
            'spin_options' => array(),
            'global_options' => array(),
        );

        foreach ($params as $name => $value) {
            $this->params[$name] = $value;
        }

        if (empty($this->params['id'])) {
            $this->params['id'] = substr(uniqid(rand(), true), 0, 10);
        }

        $this->params['id'] = 'sirv-gallery-' . $this->params['id'];


        $this->items = $items;
        $this->captions = $captions;

        return true;
    }

    public function addCss($rule)
    {
        $this->inline_css[] = $rule;
    }

    public function getInlineCss()
    {
        return join("\r\n", $this->inline_css);
    }

    public function fixUrl($url)
    {
        $sirv_cdn_url = get_option('SIRV_CDN_URL');

        $p_url = parse_url($url);
        $m_url = 'https://' . $sirv_cdn_url . $p_url['path'];

        $profile = $this->params['profile'] == ''
            ? $this->params['default_profile'] !== ''
                ? $this->params['default_profile']
                : ''
            : $this->params['profile'];

        if($profile) $m_url .= "?profile=$profile";

        return $m_url;
    }

    public function renderOptions($options){
        return 'data-options="' . $this->optionsToString($options) .'" ';
    }

    public function optionsToString($options){
        $opt_str = '';

        foreach ($options as $key => $value) {
            $opt_str .= "$key:$value;";
        }
        return $opt_str;
    }

    public function getViewerOptions(){
        $videoAutoplay = isset($this->params['zgallery_data_options']['videoAutoplay']) ? $this->params['zgallery_data_options']['videoAutoplay'] : 'false';

        $options = array(
            'thumbnails.position' => $this->params['zgallery_data_options']['thumbnails'],
            'thumbnails.type' => filter_var($this->params['zgallery_data_options']['squareThumbnails'], FILTER_VALIDATE_BOOLEAN) ? 'square' : 'auto',
            'thumbnails.size' => (int) $this->params['thumbnails_height'],
            'fullscreen.always' => $this->params['zgallery_data_options']['fullscreen-only'],
            'contextmenu.enable' => $this->params['zgallery_data_options']['contextmenu'],
            'video.autoplay' => $videoAutoplay
        );

        return $options;
    }

    public function getSpinOptions(){
        //Spin method
        //Autospin
        //Rotation duration (ms)
        $options = array(
            'autospin.duration' => $this->params['spin_options']['autospinSpeed'],

        );

        return $options;
    }

    public function getZoomOptions(){
        //mouse wheel zoom options
        $options = array(
            'wheel' => $this->params['zgallery_data_options']['zoom-on-wheel'],
            'mode' => 'deep',
        );

        return $options;
    }

    function getCaptions(){
        $captions = $this->params['show_caption']? $this->captions : array();

        return json_encode($captions, JSON_HEX_QUOT | JSON_HEX_APOS);
    }

    function fixCaptionPosition($id){
        $thumbsOrientation = $this->params['zgallery_data_options']['thumbnails'];
        $thumbsHeight = (int)$this->params['thumbnails_height'];
        $position = '';
        $width = '';

        if($thumbsOrientation == 'left'){
            $position = 'padding-left:' . ($thumbsHeight + 2) . 'px;';
        }else if($thumbsOrientation == 'right'){
            $position = 'padding-right:' . ($thumbsHeight + 2) . 'px;';
        }

        if (($this->params['width'] != '' && intval($this->params['width']) !== 0) && $thumbsOrientation !=='bottom' ) {
            $width = 'width: ' . $this->params['width'] . 'px;';
        }
        $this->addCss('.sirv-mv-caption.' . $id . "{". $position . $width ."}");

    }

    public function getAlign(){
        $align = $this->params['gallery_align'];
        $align_class = '';
        if($align){
            switch($align){
                case 'sirv-left':
                    $align_class = 'sirv-mv-left';
                    break;
                case 'sirv-center':
                    $align_class = 'sirv-mv-center';
                    break;
                case 'sirv-right':
                    $align_class = 'sirv-mv-right';
                    break;
            }
        }

        return $align_class;
    }


    public function render()
    {
        if ($this->params['width'] != '' && intval($this->params['width']) !== 0) {
            $this->addCss('#' . $this->params['id'] . ' { width: ' . ((preg_match('/%/', $this->params['width'])) ? intval($this->params['width']) . '%' : intval($this->params['width']) . 'px') . ' }');
        } else {
            $this->addCss('#' . $this->params['id'] . ' { min-width: 200px; }');
        }

        $this->fixCaptionPosition($this->params['id']);

        $viewerOptions = $this->renderOptions($this->getViewerOptions());
        $zoomOptions = $this->renderOptions($this->getZoomOptions());
        $spinOptions = $this->renderOptions($this->getSpinOptions());
        $captions = 'data-mv-captions=\'' . $this->getCaptions().'\'';
        $thumbsOrientation = $this->params['zgallery_data_options']['thumbnails'];
        $align = $this->getAlign();
        $captions_html = ($thumbsOrientation == 'bottom' && count($this->items) > 1) ? '' : '<div class="sirv-align-wrapper '. $align .'"><div class="sirv-mv-caption '. $this->params['id']. '"></div></div>';

        $tmp_spinOptions = '';


        $isZoom = $this->params['apply_zoom'];
        $html = '<div class="sirv-align-wrapper ' . $align .'">' . PHP_EOL . '<div id="' . $this->params['id'] . '" '.$captions.' class="Sirv" '. $viewerOptions .'>' . PHP_EOL;

        foreach ($this->items as $i => $item) {
            $caption = htmlspecialchars($item['caption']);
            $item['url'] = $this->fixUrl($item['url']);
            $dataItemId = 'data-item-id="' . $i . '"';
            if (preg_match('/\.(spin|mp4|mpg|mpeg|mov|qt|webm|avi|mp2|mpe|mpv|ogg|m4p|m4v|wmv|flw|swf|avchd)/is', $item['url'])) {
                $tmp_spinOptions = preg_match('/\.spin/is', $item['url']) ? $spinOptions : '';

                $html .= '<div ' . $dataItemId . ' data-src="' . $item['url'] . '" '.$tmp_spinOptions.' alt="'. $caption .'" title="'.$caption.'"></div>' . PHP_EOL;
            }else{
                if($isZoom){
                    $html .= '<div '. $dataItemId .' data-type="zoom" data-src="'. $item['url']. '" '.$zoomOptions.' alt="'.$caption.'" title="'.$caption.'"></div>'.PHP_EOL;
                }else{
                    $html .= '<img '. $dataItemId .' data-src="'. $item['url'] .'">' . PHP_EOL;
                }
            }

        }
        $html .= '</div>'. PHP_EOL . '</div>';

        return $html . $captions_html . '<style type="text/css">' . $this->getInlineCss() . '</style>';
    }
}


?>

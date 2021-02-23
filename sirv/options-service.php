<?php

    defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

    class getValue{

        protected static $jsFile = ['https://scripts.sirv.com/sirv.js',
                        'https://scripts.sirv.com/sirv.nospin.js',
                        'https://scripts.sirv.com/sirvjs/v3/sirv.js',
                        'https://scripts.sirv.com/sirvjs/v3/sirv.full.js',
                        ];

        public static function getOption($optionName){
            $value = '';
            switch ($optionName) {
                case 'SIRV_AWS_HOST':
                    $value = 'http://' . get_option($optionName);
                    break;
                case 'SIRV_JS_FILE':
                    $index = ((int)get_option($optionName)) - 1;
                    $value = self::$jsFile[$index];
                    break;
                default:
                    $value = get_option($optionName);
                    break;
            }

            return $value;

        }

        protected static function getJSPath($index){
            return self::$jsFile[$index];
        }


        public static function getSirvJSPath($optionName){
            $sirvJsType = (int) get_option($optionName);
            $offset = 1;
            if($sirvJsType == '3'){
                $aComponents = self::getJsLoadcomponents();
                if(count($aComponents) == 4) $offset = 0;

            }
            return self::$jsFile[$sirvJsType - $offset];
        }


        public static function getJsLoadcomponents(){
            $load_components = array();
            $data = json_decode(get_option('SIRV_JS_FILE_EXTEND'), true);

            foreach ($data as $component => $isLoad) {
                if ($isLoad) {
                    $load_components[] = $component;
                }
            }
            return $load_components;
        }


        public static function renderComponentsToString($components){
            return ' data-components="' . implode(",", $components) . '" ';
        }
    }
?>

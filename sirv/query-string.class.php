<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class QueryString{
    const TRASH_DIR = '/.Trash';
    const SHARED_DIR = '/Shared';
    const PROFILES_DIR = '/Profiles';
    const WELL_KNOWN_DIR = '/.well-known';
    private $systemDirs = array(self::TRASH_DIR, self::SHARED_DIR, self::PROFILES_DIR, self::WELL_KNOWN_DIR);
    private $query;
    private $compiledQuery = array();

    public function __construct($query){
        $this->query = trim(preg_replace("/[\-\[\]\/\{\}\(\)\s\*\+\?\:\\\^\$\|]/i", "\\\\$0", $query));
    }

    public function getCompiledQuery(){
        return join(' AND ', $this->compiledQuery);
    }

    protected function excludeDir($dir){
        $this->compiledQuery[] = "-dirname.paths:$dir";
    }

    protected function includeDirs(){
        return 'isDirectory:true';
    }

    protected function includeSpins(){
        return 'extension:.spin';
    }

    protected function includeImages(){
        return 'contentType:image';
    }

    protected function includeVideos(){
        return 'contentType:video';
    }

    protected function includePluginContent(){
        $this->compiledQuery[] = "(" . $this->includeDirs() . " OR " . $this->includeSpins() . " OR " . $this->includeImages() . " OR " . $this->includeVideos() . ")";
    }

    protected function compileFolderSearchQuery($folder, $exclude=false, $subfolders=false){
        //folder = folder.replace(/([^\\\/]+|^\\\/)\/+$/, '$1');
        $query = "dirname.raw:$folder";
        if($exclude){
            $query = "-dirname.paths:$folder";
        }else if($subfolders) $query = "dirname.paths:$folder";

        return $query;
    }


    protected function excludeSystemContent(){
        foreach($this->systemDirs as $dir){
            $this->compiledQuery[] = $this->compileFolderSearchQuery("\\" .$dir, true);
        }
    }

    protected function excludeSystemFolders(){
        foreach ($this->systemDirs as $dir) {
            $this->compiledQuery[] = "-filename.raw:\"$dir\"";
        }
    }

    protected function compileBaseSearch(){
        $this->compiledQuery[] = "(basename:$this->query OR basename.raw:$this->query)";
        $this->compiledQuery[] = "-extension:.profile";
    }

    public function getCompiledGlobalSearch(){
        $this->compileBaseSearch();
        $this->includePluginContent();
        $this->excludeSystemContent();
        $this->excludeSystemFolders();


        return $this->getCompiledQuery();
    }
}

?>

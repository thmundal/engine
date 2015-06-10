<?php

require_once("../memcached/MemCachedClass.php");

Class engine extends MemCachedClass {
    /**
     * Initializes the engine
     * 
     * @param string $template  Name of the template
     * @param string $force_reload  Force a new fetch of the file instead of getting it from the cache
     * @param string $getvar  The get-variable to look for the page-id in
     * @param string $jsgetvar  The get-variable to look for javascript-file in
     * @param string $cssgetvar  The get-variable to look for css-file in
     * @param string $imggetvar  The get-variable to look for image-file in
     * 
     * @return engine
     */
    
    private $prefix;
    public function Init($template = "default", $force_reload = false, $getvar = "p", $jsgetvar = "js", $cssgetvar = "css", $imggetvar = "img") {
        $this->prefix = __DIR__."::";
        $this->set($this->prefix."template", $template);
        $this->set($this->prefix."getvar", $getvar);
        $this->set($this->prefix."jsgetvar", $jsgetvar);
        $this->set($this->prefix."cssgetvar", $cssgetvar);
        $this->set($this->prefix."imggetvar", $imggetvar);
        
        
        return $this;
    }

    public function forceReload($f) {
        $this->set($this->prefix."force_reload", $f);
    }
    
    /**
     * Loads a template file and returns the minified HTML generated
     * 
     * @param string $file The path of the file to be loaded
     * 
     * @return string The minified contents of the HTML
     */
    
    private function loadTemplate($file) {
        return $this->minifyHtml(file_get_contents("templates/".$this->get($this->prefix."template")."/_".$file.".html"));
    }
    
    /**
     * Minifies a string of HTML
     * 
     * @param string $html The HTML to be minified
     * 
     * @return string The contents of the minified HTML
     */
    
    private function minifyHtml($html) {
        return preg_replace(['/\>[^\S ]+/s','/[^\S ]+\</s','/(\s)+/s','/<!--[^[if](.|\s)*?-->/'],['>','<','\\1',''],$html);
    }
    
    /**
     * Outputs the contents of the loaded template to the client/browser
     * 
     * @param bool $force_reload  Force a new fetch of the file instead of getting it from the cache
     * 
     * @return void
     */
    
    public function output($force_reload = false) {
        if(!$force_reload) {
            $force_reload = $this->get($this->prefix."force_reload");
        }
		if(isset($_GET[$this->get($this->prefix."jsgetvar")])) {
			echo $this->loadJavascript($_GET[$this->get($this->prefix."jsgetvar")], $force_reload);
		} elseif(isset($_GET[$this->get($this->prefix."cssgetvar")])) {
			echo $this->loadCss($_GET[$this->get($this->prefix."cssgetvar")], $force_reload);			
		} else {
			echo $this->getPage($force_reload);
		}
    }
    
    /**
     * Retrieves the correct page associated with the get-variable passed. Displays a 404 page if not found
     * 
     * @param bool $force_reload Force a new fetch of the file instead of getting it from the cache
     * 
     * @return string Returns the content of the file
     */
    
    public function getPage($force_reload = false) {
        $file = isset($_GET[$this->get($this->prefix."getvar")]) ? $_GET[$this->get($this->prefix."getvar")] : "index";
        
        if(!($content = $this->get($this->prefix."html_data_minified::".$file)) OR $force_reload) {
            if(file_exists("templates/".$this->get($this->prefix."template")."/"."_".$file.".html")) {
                $file_content = $this->loadTemplate($file);
            } else {
                http_response_code(404);
                $file_content = $this->loadTemplate("404");
            }
            $content = $this->loadTemplate("header") . $file_content . $this->loadTemplate("footer");
            $this->set($this->prefix."html_data_minified::".$file, $content);
        }
        return $content;
    }
    /**
     * Function for locking the web-page with a password. You need to pass the password as a string trough the given get-variable in the request URL
     * 
     * @param string $password 
     * @param string $getvar  
     * 
     * @return void
     */
	
    public function lock($password, $getvar = "pw") {
        $access = @$_COOKIE["lock-pw"] || false;
        
        if(array_key_exists($getvar, $_GET)) {
            if($_GET[$getvar] == $password) {
                setcookie("lock-pw", true);
                header("location: /");
                $access = true;
            }
        }
            
        if(!$access) {
            http_response_code(403);
            die("403 Forbidden");
            exit;
            return;
        }
    }
    
	// Javascript
    /**
     * Loads, minifies and returns the contents of a javascript file
     * 
     * @param string $file Path to the file to load
     * @param bool $force_reload  Force a new fetch of the file instead of getting it from the cache
     * 
     * @return string The contents of the compiled javascript
     */
    
	public function loadJavascript($file, $force_reload = false) {
		if(!($content = $this->get($this->prefix."javascript_data_minified::".$file)) OR $force_reload) {
			$content = $this->minifyJavascript(file_get_contents($file));
			$this->set($this->prefix."javascript_data_minified::".$file, "/* Saved in memcached ".date("d.m.Y - H:i:s", time())." */".$content);
		}
		return $content;
	}
	
    /**
     * Minifies a string of javascript
     * 
     * @param string $javascript The javascript that should be minified
     * 
     * @return string The minified javascript
     */
    
	public function minifyJavascript($javascript) {
		$javascript = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/ .*))/", "", $javascript);
		$javascript = str_replace(["\r\n","\r","\t","\n",'  ','    ','     '], '', $javascript);
		$javascript = preg_replace(['(( )+\))','(\)( )+)'], ')', $javascript);
		return $javascript;
	}
	
	// CSS
    /**
     * Loads, minifies and returns the contents of a css file
     * 
     * @param string $file Path to the file to load
     * @param bool $force_reload  Force a new fetch of the file instead of getting it from the cache
     * 
     * @return string The contents of the compiled css
     */
    
	public function loadCss($file, $force_reload = false) {
		if(!($content = $this->get($this->prefix."css_data_minified::".$file)) OR $force_reload) {
			$content = $this->minifyCss(file_get_contents($file));
			$this->set($this->prefix."css_data_minified::".$file, "/* Saved in memcached ".date("d.m.Y - H:i:s", time())." */".$content);
		}
		return $content;
		
	}
	
    /**
     * Minifies a string of css
     * 
     * @param string $css The css that should be minified
     * 
     * @return string The minified css
     */
    
	public function minifyCss($css) {
		$css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
		$css = str_replace(["\r\n","\r","\n","\t",'  ','    ','     '], '', $css);
		$css = preg_replace(['(( )+{)','({( )+)'], '{', $css);
		$css = preg_replace(['(( )+})','(}( )+)','(;( )*})'], '}', $css);
		$css = preg_replace(['(;( )+)','(( )+;)'], ';', $css);
		return $css;
	}
}
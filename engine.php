<?php

require_once("lib/memcached/MemCachedClass.php");
require_once("lib/smarty/libs/Smarty.class.php");
require_once("lib/Util/util.php");

Class engine extends MemCachedClass {
    private $jsfiles = [];
    private $cssfiles = [];
    public $memcached = false;
    private $attrs = [];
    public $version;

    public function __construct($server = ["ip" => "localhost", "port" => 11211]) {
        //parent::__construct($server);
    }
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

    public $smarty;
    public function Init($template = "default", $force_reload = false, $getvar = "p", $jsgetvar = "js", $cssgetvar = "css", $imggetvar = "img") {
        $this->set("template_dir", __DIR__."/templates/");
        $this->set("default_template_dir", __DIR__."/templates/");
        $this->set("template", $template);
        $this->set("getvar", $getvar);
        $this->set("jsgetvar", $jsgetvar);
        $this->set("cssgetvar", $cssgetvar);
        $this->set("imggetvar", $imggetvar);
        $this->set("MC_prefix", __DIR__ . "/html_data_minified::");
        if(!$this->get("root_dir"))
            $this->set("root_dir", __DIR__);          //<---- THIS IS MAGIC, AND CAN BE SCARY!

        $this->version = "0.0.0";
        $this->set("smarty_enable", false);
        $this->smarty = new Smarty();
        $this->set("tmp_vars", []);
        //$this->smarty->plugins_dir[] = "lib/smarty_plugins";
        $this->smarty->addPluginsDir($this->get("root_dir")."/lib/smarty_plugins");

        $this->smarty->loadPlugin('smarty_compiler_switch');
        $this->smarty->registerFilter('post', 'smarty_postfilter_switch');

        return $this;
    }

    public function setVersion($v) {
      $this->version = $v;
    }

    public function get($v) {
      if($this->memcached) return parent::get($v);
      else return arrGet($this->attrs, $v, false);
    }

    public function set($n, $v) {
      if($this->memcached) parent::set($n, $v);
      $this->attrs[$n] = $v;
    }

    public function enableSmarty() {
        $this->set("smarty_enable", true);
    }

    public function setRootDir($dir) {
        $this->set("root_dir", $dir);
    }

    public function forceReload($f) {
        $this->set("force_reload", $f);
    }

    public function setTemplateDir($template_dir) {
        $this->set("template_dir", $template_dir);
    }

    public function addJS($jsfile) {
      array_push($this->jsfiles, $jsfile.'?v='.$this->version);
    }

    public function addCSS($cssfile) {
      array_push($this->cssfiles, $cssfile.'?v='.$this->version);
    }

    public function getJsMarkup() {
      $markup = "";
      foreach($this->jsfiles as $file) {
        $markup .= '<script type="javascript" src="'.$file.'"></script>';
      }
      return $markup;
    }

    public function getCssMarkup() {
      $markup = "";
      foreach($this->cssfiles as $file) {
        $markup .= '<link rel="stylesheet" href="'.$file.' />"';
      }
      return $markup;
    }

    public function getTemplateFile($file) {
        $path = $this->get("template_dir").$this->get("template")."/"."_".$file.".html";
        $test = file_exists($path);

        if(!$test) {
            $path = $this->get("default_template_dir")."default/"."_".$file.".html";
        }

        return $path;
    }

    public function assign($key, $value) {
        $tmpvars = $this->get("tmp_vars");
        $tmpvars[$key] = $value;
        $this->set("tmp_vars", $tmpvars);
    }

    /**
     * Loads a template file and returns the minified HTML generated
     *
     * @param string $file The path of the file to be loaded
     *
     * @return string The minified contents of the HTML
     */

    private function loadTemplate($file) {
        // INSERT SMARTY SUPPORT HERE!
        if(!$this->get("smarty_enable")) {
            return $this->minifyHtml(file_get_contents($this->getTemplateFile($file)));
        } else {
            $this->smarty->assign("engine_js_files_markup", $this->getJsMarkup());
            $this->smarty->assign("engine_js_files_paths", $this->jsfiles);

            $this->smarty->assign("engine_css_files_markup", $this->getCssMarkup());
            $this->smarty->assign("engine_css_files_paths", $this->cssfiles);
            return $this->minifyHTML($this->smarty->fetch($this->getTemplateFile($file)));
        }
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

    public function output($callback = null, $force_reload = false) {
      if(!$force_reload) {
          $force_reload = $this->get("force_reload");
      }
  		if(isset($_GET[$this->get("jsgetvar")])) {
  			echo $this->loadJavascript($_GET[$this->get("jsgetvar")], $force_reload);
  		} elseif(isset($_GET[$this->get("cssgetvar")])) {
  			echo $this->loadCss($_GET[$this->get("cssgetvar")], $force_reload);
  		} else {
  			echo $this->getPage($callback, $force_reload);
  		}
    }

    /**
     * Retrieves the correct page associated with the get-variable passed. Displays a 404 page if not found
     *
     * @param bool $force_reload Force a new fetch of the file instead of getting it from the cache
     *
     * @return string Returns the content of the file
     */

    public function getPage($callback = null, $force_reload = false) {
        $file = isset($_GET[$this->get("getvar")]) ? $_GET[$this->get("getvar")] : "index";

        if(!($content = $this->get($this->get("MC_prefix").$file)) OR $force_reload) {
            call_user_func($callback);
            $this->smarty->assign($this->get("tmp_vars"));

            if(file_exists($this->getTemplateFile($file))) {
                $file_content = $this->loadTemplate($file);
            } else {
                $file_content = $this->loadTemplate("404");
            }
            if(!$this->noheader)
              $content = $this->loadTemplate("header");

            $content .= $file_content;

            if(!$this->nofooter)
              $content .= $this->loadTemplate("footer");

            $this->set($this->get("MC_prefix").$file, $content);
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

    public function redirect($url) {
      header("location: " . $url);
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
		if(!($content = $this->get("javascript_data_minified::".$file)) OR $force_reload) {
            if(!isset($_GET["nominify"]))
                $content = $this->minifyJavascript(file_get_contents($file));
            else {
                $content = file_get_contents($file);
            }
			$this->set("javascript_data_minified::".$file, "/* Saved in memcached ".date("d.m.Y - H:i:s", time())." */".$content);
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
		//$javascript = preg_replace("/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/ .*))/", "", $javascript);
		$javascript = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\)\/\/[^"\'].*))/', '', $javascript);
		$javascript = str_replace(["\r\n","\r","\t", "\n",'  ','    ','     '], '', $javascript);
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
		if(!($content = $this->get("css_data_minified::".$file)) OR $force_reload) {
			$content = $this->minifyCss(file_get_contents($file));
			$this->set("css_data_minified::".$file, "/* Saved in memcached ".date("d.m.Y - H:i:s", time())." */".$content);
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

<?php
chdir("../");
require_once("engine.php");
$engine = new Engine();
header("content-type:text/css; charset=UTF-8");
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (86400 * 30)));
//header("Last-Modified: ". gmdate("D, d M Y H:i:s", filemtime($_GET[$engine->get("cssgetvar")]))." GMT");
$engine->init();
$engine->output();

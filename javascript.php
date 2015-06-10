<?php
chdir("../");
require_once("engine.php");
$engine = new Engine();
$engine->init();
header("content-type:application/javascript; charset=UTF-8");
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + (86400 * 30)));
header("Last-Modified: ". gmdate("D, d M Y H:i:s", filemtime($_GET[$engine->get("jsgetvar")]))." GMT");
$engine->output();
function isUnSupported() {
    return navigator.userAgent.match(/(MSIE 8.0|MSIE 7.0|MSIE 5.0)/);
}

if(isUnSupported()) {
    document.body.innerHTML = 'You are using a very, very old web-browser which is not supported by this site. Please update your browser. We can recommend <a href="http://chrome.google.com">Google Chrome</a>';
    throw new Error("Using an unsupported browser, stopping execution");
}
var callbacks = [];
function l() {
	var css = ["http:\/\/fonts.googleapis.com/css?family=Open+Sans+Condensed:300"].concat(engine.css);;
	// DO NOT LOAD THIS FILE (default.js) IN THE ARRAY! MEGASUPERRECURSIONMONSTER!
	var js = ["http:\/\/code.jquery.com/jquery-2.1.4.min.js"].concat(engine.js);
    
    if(js.indexOf("engine/engine.js") > -1) {
        throw new Error("Fatal error: engine.js included in load will cause an endless loop.");
    }
    
	var count = 0;

	function complete() {
		if((++count)===(css.length + js.length)) {
			callbacks.forEach(function(i) { i.call() });
		}
	}
	css.forEach(function(i) { {var c = document.createElement("link"); c.href = i; c.type = "text/css"; c.rel = "stylesheet"; document.head.appendChild(c); c.onload = complete; } });
	js.forEach(function(i) { {var j = document.createElement("script"); j.src=i; document.head.appendChild(j); j.onload = complete; } }); 
}

var raf = requestAnimationFrame || mozRequestAnimationFrame ||
		  webkitRequestAnimationFrame || msRequestAnimationFrame;
if (raf) { raf(l);}
else {window.addEventListener('load', l);};

function addLoadedCallback(callback) {
	callbacks.push(callback);
}
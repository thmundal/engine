function isUnSupported() {
    return navigator.userAgent.match(/(MSIE 8.0|MSIE 7.0|MSIE 5.0)/);
}

if(isUnSupported()) {
    document.body.innerHTML = 'You are using a very, very old web-browser which is not supported by this site. Please update your browser. We can recommend <a href="https://chrome.google.com">Google Chrome</a>';
    throw new Error("Using an unsupported browser, stopping execution");
}
var callbacks = [];
function l() {
	var css = ["https:\/\/fonts.googleapis.com/css?family=Open+Sans+Condensed:300",
               "https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"].concat(engine.css);;
	// DO NOT LOAD THIS FILE (default.js) IN THE ARRAY! MEGASUPERRECURSIONMONSTER!
	var js = engine.js;

    if(js.indexOf("engine/engine.js") > -1) {
        throw new Error("Fatal error: engine.js included in load will cause an endless loop.");
    }

    var depends = ["https:\/\/code.jquery.com/jquery-3.2.1.min.js", "https:\/\/code.jquery.com/ui/1.12.1/jquery-ui.min.js"];
    var dcount = 0;

    function dependcomplete() {
        if((dcount)==depends.length) {
            console.info("Dependencies loaded, loading scripts");
            var count = 0;

            function complete() {
                if((++count)===(css.length + js.length)) {
                    console.info("All scripts loaded, running callbacks");
                    callbacks.forEach(function(i) { i.call() });
                }
            }
            css.forEach(function(i) { {var c = document.createElement("link"); c.href = i; c.type = "text/css"; c.rel = "stylesheet"; document.head.appendChild(c); c.onload = complete; } });
            js.forEach(function(i) { {var j = document.createElement("script"); j.src=i; document.head.appendChild(j); j.onload = complete; console.info("loading ", i) } });

            return true;
        }

        return false;
    }

    // Load dependencies in sequencial order
    function loadDependicy() {
      if(this != window) {
        console.info(this.src, " loaded");
      }
      if(!dependcomplete()) {
        var i = depends[dcount];
        console.info("loading ", i);
        var j = document.createElement("script"); j.src=i; document.head.appendChild(j); j.onload = loadDependicy;
        dcount++;
      }
    }
    loadDependicy();

    // depends.forEach(function(i) { {var j = document.createElement("script"); j.src=i; document.head.appendChild(j); j.onload = dependcomplete; } });
}

var raf = requestAnimationFrame || mozRequestAnimationFrame ||
		  webkitRequestAnimationFrame || msRequestAnimationFrame;
if (raf) { raf(l);}
else {window.addEventListener('load', l);};

function addLoadedCallback(callback) {
	callbacks.push(callback);
}

addLoadedCallback(function() {
    // Hack-in load bootstrap
    var bs = document.createElement("script");
    bs.src = "https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js";
    document.head.appendChild(bs);
});

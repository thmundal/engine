function isUnSupported() {
    return navigator.userAgent.match(/(MSIE 8.0|MSIE 7.0|MSIE 5.0)/);
}

if(isUnSupported()) {
    document.body.innerHTML = 'You are using a very, very old web-browser which is not supported by this site. Please update your browser. We can recommend <a href="http://chrome.google.com">Google Chrome</a>';
    throw new Error("Using an unsupported browser, stopping execution");
}

var callbacks = [];
function l() {
	var css = ["css/default.css"];
	// DO NOT LOAD THIS FILE (default.js) IN THE ARRAY! MEGASUPERRECURSIONMONSTER!
	var js = ["http:\/\/code.jquery.com/jquery-2.1.4.min.js", "templates/default/js/script.js"];
	var count = 0;

	function complete() {
		if((++count)===(css.length + js.length)) {
			callbacks.forEach(function(i) { i.call() });
		}
	}
	css.forEach(function(i) { {var c = document.createElement("link"); c.href = i; c.type = "text/css"; c.rel = "stylesheet"; document.head.appendChild(c); c.onload = complete; } });
	js.forEach(function(i) { {var j = document.createElement("script"); j.src=i; document.head.appendChild(j); j.onload = complete; } }); 
}

try {
    var raf = requestAnimationFrame || mozRequestAnimationFrame ||
              webkitRequestAnimationFrame || msRequestAnimationFrame;
} catch(e) {
    var raf = false;
}
if (raf) { raf(l);}
else {window.addEventListener('load', l);};

function addLoadedCallback(callback) {
	callbacks.push(callback);
}

addLoadedCallback(function() {
    function loadComplete() {
        if(thingstoload == thingsloaded) {
            document.getElementById("loading").style.display = "none";
        }        
    }
    
    // Load layer images
    var layers = $('.layer');
    var images = $('img');
    
    var thingstoload = images.length;
    var thingsloaded = 0;
    
    layers.each(function(i) {
        var bg = $(this).css("background-image");
        
        if(bg != "none") {
            thingstoload++;
            var url = bg.match(/(url\()+(.*)+(\))/)[2];
            var img = document.createElement("img");
            img.src = url;
            img.onload = function() {
                thingsloaded++;
                loadComplete();
            }
        }
    });
    $(images).one("load", function() {
        thingsloaded++;
        loadComplete();
    }).each(function() {
        if(this.complete) {
            $(this).load();
        }
    });
    
    loadComplete();
});
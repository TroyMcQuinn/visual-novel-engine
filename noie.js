function detectIE() {
    var ua = window.navigator.userAgent;

    var msie = ua.indexOf('MSIE ');
    if (msie > 0) {
        // IE 10 or older => return version number
        return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
    }

    var trident = ua.indexOf('Trident/');
    if (trident > 0) {
        // IE 11 => return version number
        var rv = ua.indexOf('rv:');
        return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
    }

    /*
    var edge = ua.indexOf('Edge/');
    if (edge > 0) {
       // Edge (IE 12+) => return version number
       return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10);
    }
    */

    // other browser
    return false;
}

function detectEdge() {
  var ua = window.navigator.userAgent;
  var edge = ua.indexOf('Edge/');
  if (edge > 0) {
     // Edge (IE 12+) => return version number
     return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10);
  }
  return false;  
}

noie_msg  = "<style type=\"text/css\">#stage, #nav_buttons, #menu{display: none !important;}</style>\n";
noie_msg += "<div style=\"text-align: center; width: 100%; height: 100%; position: fixed; background-color: #FFFFFF; margin: 0px; padding-top: 48px; top: 0px;\">\n";
noie_msg += "<h1>The visual novel does not support Internet Explorer.</h1>\n";
noie_msg += "<h2>Internet Explorer is not a modern standards-compliant web browser.</h2>\n";
noie_msg += "<p>Please download <a href=\"https://www.mozilla.org/en-US/firefox/new/\">Firefox</a> or <a href=\"https://www.google.com/chrome/\">Google Chrome</a>.</p>\n";
noie_msg += "<p>If you are running Windows 10, the latest version of the Edge browser should also work fine.</p>\n";
noie_msg += "<p>In the mean time, you can view the lite / webcomic version of the visual novel <a href=\"static/\">here</a>.</p>\n";
noie_msg += "</div>\n";

if(detectIE()){
  // document.write(noie_msg);
  window.location = 'static/'
}

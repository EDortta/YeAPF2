/**
 * @name YeAPF2
 * @version 2.0
 * @description Yet Another PHP Framework v2
 * @license MIT License
 *
 * (c) 2004-2023 Esteban D.Dortta <dortta@yahoo.com>
 * Website: https://www.yeapf.com
 */

(function() {
    var _onLoadMethods = [],
        _startupStage_ = -1;

    window.addOnLoadManager = function(aFunc) {
        var i = _onLoadMethods.length;
        _onLoadMethods[i] = aFunc;
        if (_startupStage_ == 0) {
            var waitStartupStage1 = function() {
                if (_startupStage_ == 1)
                    aFunc();
                else
                    setTimeout(waitStartupStage1, 150);
            };
            waitStartupStage1();
        } else {
            if (_startupStage_ == 1)
                aFunc();
        }
    };

    function supportsTypeScript() {
        var userAgent = navigator.userAgent;
        var match = userAgent.match(/(Chrome|Firefox|Edge|Safari|Opera)[\/ ]([\d]+)/);
        if (match) {
          var browser = match[1];
          var version = match[2];
          if (browser === "Chrome" && version >= 42) {
            return true;
          } else if (browser === "Firefox" && version >= 47) {
            return true;
          } else if (browser === "Edge" && version >= 14) {
            return true;
          } else if (browser === "Safari" && version >= 9) {
            return true;
          } else if (browser === "Opera" && version >= 40) {
            return true;
          }
        }
        return false;
    }

    if (supportsTypeScript())
      console.log("TypeScript is supported");
    else
      console.warn("TypeScript is not supported");

    function __startup() {
        _startupStage_ = 0;
        for (var i = 0; i < _onLoadMethods.length; i++)
            if (_onLoadMethods.hasOwnProperty(i))
                if (_onLoadMethods[i] !== undefined)
                    _onLoadMethods[i]();
        _startupStage_ = 1;
    }

    function getCurrentScriptURI() {
        return document.currentScript.src;
    }

    function loadScript(scriptName) {
        return new Promise(function(resolve, reject) {
            var script = document.createElement('script');

            script.addEventListener('load', function() {
                console.log(scriptName + ' loaded successfully');
                resolve();
            });

            script.addEventListener('error', function() {
                console.log(scriptName + ' failed to load');
                reject();
            });

            script.src = scriptName;
            document.head.appendChild(script);
        });
    }

    const currentScriptURI = getCurrentScriptURI();
    const baseURI = currentScriptURI.substring(0, currentScriptURI.lastIndexOf('/') + 1);


    const libraries = ['yprototypes', 'ymisc', 'yanalyzer', 'ycolors', 'ydevice', 'ydom', 'ytab-manager'];
    const promises = libraries.map(function(library) {
        const scriptName = baseURI + library + '.js';
        return loadScript(scriptName);
    });

    Promise.all(promises)
        .then(function() {
            console.log('All scripts loaded successfully');
            if (typeof cordova == 'object') {
                document.addEventListener(
                    'deviceready',
                    __startup, false);
            } else {
                window.addEventListener("load", function() {
                    __startup();
                    if (!yDevice.isOnMobile()) {
                        var event = new Event('deviceready');
                        document.dispatchEvent(event);
                    }
                }, false);
            }
        })
        .catch(function() {
            console.log('Some scripts failed to load');
        });

})();
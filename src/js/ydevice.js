class yDevice {
    static getInternetExplorerVersion() {
        /* http://msdn.microsoft.com/en-us/library/ms537509(v=vs.85).aspx
         * Returns the version of Internet Explorer or a -1
         * (indicating the use of another browser).
         */

        var rv = -1; // Return value assumes failure.
        if (navigator.appName == 'Microsoft Internet Explorer') {
            var ua = navigator.userAgent;
            var re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
            if (re.exec(ua) != null)
                rv = parseFloat(RegExp.$1);
        }
        return rv;
    }

    static isInternetExplorer() {
        return (getInternetExplorerVersion() >= 0);
    };

    static IsSafari() {
        var is_safari = navigator.userAgent.toLowerCase().indexOf('safari/') > -1;
        return is_safari;

    }

    static getAndroidVersion(ua) {
        ua = (ua || navigator.userAgent).toLowerCase();
        var match = ua.match(/android\s([0-9\.]*)/);
        return match ? match[1] : false;
    };

    static isOnMobile() {
        var ret = false;
        if (typeof mosync != 'undefined') {
            ret = mosync.isAndroid || mosync.isIOS || mosync.isWindowsPhone;
        } else
            ret = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        return ret;
    };


}
Facebook = new(function () {
    var w = this;
    this.startFacebookConnect = function (b, c, e, f, g) {
        f = f == undefined ? true : f;
        var d = b + (b.indexOf('?') > -1 ? "&" : "?"),
            h = "&";
        if (c) {
            d += h + "scope=" + c;
            h = "&"
        }
        if (e) {
            d += h + "enable_timeline=1";
            h = "&"
        }
        if (g) d += h + "ref_page=" + g;
        w._facebookWindow = window.open(d, "Facebook", "location=0,status=0,width=800,height=400");
        if (f) w._facebookInterval = window.setInterval(this.completeFacebookConnect, 1E3)
    };
    this.completeFacebookConnect = function () {
        if (w._facebookWindow.closed) {
            window.clearInterval(w._facebookInterval);
            window.location.reload()
        }
    };
});
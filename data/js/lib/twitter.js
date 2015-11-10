Twitter = new(function () {
    var w = this;
    this.startTwitterConnect = function (b, c, e, f, g) {
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
        w._twitterWindow = window.open(d, "Twitter", "location=0,status=0,width=800,height=400");
        if (f) w._twitterInterval = window.setInterval(this.completeTwitterConnect, 1E3)
    };
    this.completeTwitterConnect = function () {
        if (w._twitterWindow.closed) {
            window.clearInterval(w._twitterInterval);
            window.location.reload()
        }
    };
});
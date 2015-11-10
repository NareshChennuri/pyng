Instagram = new(function () {
    var w = this;
    this.startInstagramConnect = function (b, c, e, f, g) {
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
        w._instagramWindow = window.open(d, "Instagram", "location=0,status=0,width=800,height=400");
        if (f) w._instagramInterval = window.setInterval(this.completeInstagramConnect, 1E3)
    };
    this.completeInstagramConnect = function () {
        if (w._instagramWindow.closed) {
            window.clearInterval(w._instagramInterval);
            window.location.reload()
        }
    };
});
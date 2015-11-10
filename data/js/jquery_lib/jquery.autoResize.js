/* autoresize jquery */
(function(e, h, f) {
  function g() {
    var a = e(this),
        d = a.height(),
        b = a.data("scrollOffset"),
        c = a.data("minHeight"),
        i = f.scrollTop();
    b = a.height(c).prop("scrollHeight") - b;
    a.height(b);
    f.scrollTop(i);
    d !== b && a.trigger("autoresize:resize", b);
    if(f.op.onResize) {
    	f.op.onResize.call(this);
    }
  }
  var k = "keyup.joAutoresize change.joAutoresize paste.joAutoresize input.joAutoresize cut.joAutoresize keydown.joAutoresize focus.joAutoresize";
  function j() {
	    var a = e(this),
	        d = a.val(),
	        b = a.val("").height(),
	        c = this.scrollHeight;
	    c = c > b ? c - b : 0;
	    a.data("minHeight", b);
	    a.data("scrollOffset", c);
	    a.val(d).unbind('.joAutoresize').bind(k, g);
	    g.call(this);
	  }
  h.autoResize = function(con) {
	  f.op = e.extend({onResize: null}, con);
	  return this.filter("textarea").each(j);
  };
})(jQuery, jQuery.fn, jQuery(window));
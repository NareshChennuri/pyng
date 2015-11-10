(function($) {
	
	var n = {}, r = Array.prototype,
        i = Object.prototype,
        c = i.hasOwnProperty
		h = r.forEach,
		m = r.filter,
		w = r.lastIndexOf,
		u = r.slice,
		p = r.map,
		b = r.indexOf,
		s = Function.prototype,
		x = s.bind;
		

	$.joFunctions = T = function (e) {
		if (e instanceof T) return e;
		if (!(this instanceof T)) return new T(e);
		this._wrapped = e
	};
		
	T.each = function (e, t, r) {
		if (e == null) return;
		if (h && e.forEach === h) e.forEach(t, r);
		else if (e.length === +e.length) {
			for (var i = 0, s = e.length; i < s; i++) if (t.call(r, e[i], i, e) === n) return
		} else for (var o in e) if (T.has(e, o) && t.call(r, e[o], o, e) === n) return
	};
			
	T.reject = function (e, t, n) {
		var r = [];
		return e == null ? r : (T.each(e, function (e, i, s) {
			t.call(n, e, i, s) || (r[r.length] = e)
		}), r)
	};
		
	T.compact = function (e) {
		return T.filter(e, function (e) {
			return !!e
		})
	};
		
	T.filter = T.select = function (e, t, n) {
		var r = [];
		return e == null ? r : m && e.filter === m ? e.filter(t, n) : (T.each(e, function (e, i, s) {
			t.call(n, e, i, s) && (r[r.length] = e)
		}), r)
	};
		
	T.lastIndexOf = function (e, t, n) {
		if (e == null) return -1;
		var r = n != null;
		if (w && e.lastIndexOf === w) return r ? e.lastIndexOf(t, n) : e.lastIndexOf(t);
		var i = r ? n : e.length;
		while (i--) if (e[i] === t) return i;
		return -1
	};
		
	T.delay = function (e, t) {
		var n = u.call(arguments, 2);
		return setTimeout(function () {
			return e.apply(null, n)
		}, t)
	};

	T.defer = function (e) {
		return T.delay.apply(T, [e, 1].concat(u.call(arguments, 1)))
	};
		
	T.each(["concat", "join", "slice"], function (e) {
		var t = r[e];
		T.prototype[e] = function () {
			return F.call(this, t.apply(this, arguments))
		}
	});

	T.keys = function(obj) {
		if (obj !== Object(obj)) throw new TypeError('Invalid object');
		var keys = [];
		for (var key in obj) if (T.has(obj, key)) keys[keys.length] = key;
		return keys;
	};

	T.invert = function(obj) {
		var result = {};
		for (var key in obj) if (T.has(obj, key)) result[obj[key]] = key;
		return result;
	};

	T.has = function(obj, key) {
		return c.hasOwnProperty.call(obj, key);
	};

	// List of HTML entities for escaping.
	var entityMap = {
		escape: {
		  '&': '&amp;',
		  '<': '&lt;',
		  '>': '&gt;',
		  '"': '&quot;',
		  "'": '&#x27;',
		  '/': '&#x2F;'
		}
	};
	entityMap.unescape = T.invert(entityMap.escape);

	// Regexes containing the keys and values listed immediately above.
	var entityRegexes = {
		escape:   new RegExp('[' + T.keys(entityMap.escape).join('') + ']', 'g'),
		unescape: new RegExp('(' + T.keys(entityMap.unescape).join('|') + ')', 'g')
	};
	
	
	T.each(['escape', 'unescape'], function(method) {
		T[method] = function(string) {
		  if (string == null) return '';
		  return ('' + string).replace(entityRegexes[method], function(match) {
			return entityMap[method][match];
		  });
		};
	});
		
	T.bind = function (t, n) {
		var r, i;
		if (t.bind === x && x) return x.apply(t, u.call(arguments, 1));
		if (!T.isFunction(t)) throw new TypeError;
		return i = u.call(arguments, 2), r = function () {
			if (this instanceof r) {
				O.prototype = t.prototype;
				var e = new O,
					s = t.apply(e, i.concat(u.call(arguments)));
				return Object(s) === s ? s : e;
			}
			return t.apply(n, i.concat(u.call(arguments)));
		};
	};
		
	T.contains = T.include = function (e, t) {
		var n = !1;
		return e == null ? n : b && e.indexOf === b ? e.indexOf(t) != -1 : (n = C(e, function (e) {
			return e === t
		}), n)
	};
		
	var P = 0;
	T.uniqueId = function (e) {
		var t = P++;
		return e ? e + t : t
	};
		
	T.extend = function (e) {
		return T.each(u.call(arguments, 1), function (t) {
			for (var n in t) e[n] = t[n]
		}), e
	};
		
	T.isFunction = function (e) {
		return typeof e == "function"
	};

	T.filter = function(obj, iterator, context) {
		var results = [];
		if (obj == null) return results;
		if (Array.prototype.filter && obj.filter === Array.prototype.filter) return obj.filter(iterator, context);
		each(obj, function(value, index, list) {
		  if (iterator.call(context, value, index, list)) results[results.length] = value;
		});
		return results;
	};
	
})(jQuery)
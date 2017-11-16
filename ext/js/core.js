(function() {

	if (!String.prototype.startsWith) {
		String.prototype.startsWith = function(searchString, position) {
			position = position || 0;
			return this.indexOf(searchString, position) === position;
		};
	}

	$.default = function(target) {
		var args = [].slice.call(arguments, 1);
		if (!target) {
			target = {};
		}
		args.forEach( function(arg) {
			for (var key in arg) {
				if (target[key] === undefined) target[key] = arg[key];
			}
		});
		return target;
	};

	$.toJson = function(obj) {
		var json = JSON.stringify(obj).replace(/"/g,'\'');
		return json;
	};
	$.fromJson = function (json) {
		var obj = JSON.parse(json.replace(/'/g,'"'));
		return obj;
	};



	$.fn.dataAttribs = function() {
		var attribs = {};
		$.each( this[0].attributes, function(i, attr) {
			if (attr.name.startsWith('data-')) {
				attribs[attr.name.replace(/^data-/,'')] = attr.value;
			}
		});
		return attribs;
	};

	$.fn.isEmpty = function() {
		return (this.length > 0);
	};

	$.fn.equals = function(element) {
		if (typeof element.selector !== 'undefined') {
			element = element[0];
		}
		return (this[0] === element);
	};

	$.fn.matches = function(element) {
		if (typeof element === 'string') { // selector
			return this.is(element);
		}

		if (typeof element.selector !== 'undefined') {
			element = element[0];
		}
		return (this[0] === element);
	};

	$.fn.prevFirst = function(selector) {
		var el = this;
		while (el.length) {
			el = $(el.prev());
			if (el.matches(selector)) {
				return el;
			}
		}
		return $();
	};

	$.fn.nextFirst = function(selector) {
		var el = this;
		while (el.length) {
			el = $(el.next());
			if (el.matches(selector)) {
				return el;
			}
		}
		return $();
	};


	// container: selector or tags
	// callback: function
	$.fn.onInside = function(event, container, callback) {
		this.on(event, function(e) {
			var target = $(e.target);
			var cont = target.closest(container);
			if (cont.length) {
				callback.call(cont, e);
			}
		});
		return this;
	};

	// container: selector or tags
	// callback: function
	$.fn.onOutside = function(event, container, callback) {
		this.on(event, function(e) {
			var target = $(e.target);
			if (!target.closest(container).length) {
				callback.call(this, e);
			}
		});
		return this;
	};

})();

// state
(function() {

	var lastStateIndex = 0;

	$.state = function(initValue) {

		var value = initValue;

		var changedFuncs = [];
		var funcKeys = [];

		var index = lastStateIndex;
		lastStateIndex++;

		var dependStates = [];
		var locked = false;

		var updateFunc = null;

		function updateChanged() {
			for (var i = 0; i < changedFuncs.length; i++) {
				changedFuncs[i].call(this,this.value);
			}
		}

		return {
			// _index: index,
			value: initValue,
			set: function(val) {
				if (value === val) {
					return this;
				}
				if (locked) {
					console.log('Warning: cyclic dependencies occured. Make sure you have no cycles in dependency links');
					return this;
				}

				locked = true;

				value = val;
				this.value = val;

				updateChanged.call(this);

				locked = false;

				return this;
			},
			/* reset: function() {
				;
			}, */
			depends: function(states, fn) {
				if (typeof fn !== 'function') {
					throw new TypeError('reactState.depends( states, fn ) 2nd parameter must be a function');
					// return this;
				}
				var self = this;

				// remove old relations
				if (dependStates.length > 0) {
					for (var j = 0; j < dependStates.length; j++) {
						dependStates[j].removeChanged(index);
					}
				}

				dependStates = states;

				updateFunc = function() {
					var values = [];
					for (var i = 0; i < states.length; i++) {
						values.push(states[i].value);
					}
					self.set( fn.apply(self,values) );
				};
				updateFunc();

				for (var i = 0; i < states.length; i++) {
					states[i].changed(updateFunc,index);
					// dependsFuncs.push(fn);
				}
				return this;
			},
			removeChanged: function(key) {
				var funcs = [], keys = [];
				for (var i = 0; i < funcKeys.length; i++) {
					if (funcKeys[i] !== key) {
						funcs.push(changedFuncs[i]);
						keys.push(funcKeys[i]);
					}
				}
				if (changedFuncs.length !== funcs.length) {
					changedFuncs = funcs;
					funcKeys = keys;
				}
			},
			changed: function(fn, key) { // , triggerChanged
				if (typeof fn !== 'function') {
					throw new TypeError('reactState.changed(fn) 1st parameter must be a function');
					// return this;
				}
				if (typeof key === 'undefined') {
					key = '';
				}
				if (typeof triggerChanged === 'undefined') {
					triggerChanged = false;
				}

				changedFuncs.push(fn);
				funcKeys.push(key);

				/* if (triggerChanged) {
					updateChanged.call(this);
				} */

				return this;
			},
			triggerChanged: function() {
				updateChanged.call(this);
				return this;
			},
			refresh: function(triggerChanged) {
				// this.value = value;
				if (typeof updateFunc === 'function') {
					updateFunc();
				}
				if (triggerChanged) {
					updateChanged.call(this);
				}
				return this;
			},
			become: function(val, fn) {
				if (typeof fn !== 'function') {
					throw new TypeError('reactState.become(fn) 2nd parameter must be a function');
					// return this;
				}
				var self = this;
				changedFuncs.push( function(v) {
					if (v === val) {
						fn.call(self);
					}
				});
				return this;
			}
		};
	};

})();

// component
(function() {

	$.fn.getComponent = function() {
		return this.data('component');
	};

	$.fn.setComponent = function(component) {
		this.data('component', component);
	};

	$.Component = function(el, component) {

		component.proxyEvent = function(eventName, transformFunc) {
			return function(e) {
				e.element = $(this);
				if (typeof transformFunc === 'function') {
					transformFunc.call(component, e);
				} else if (transformFunc === true) { // blur
					e.element.blur();
				}
				if (component.events[eventName] === undefined) {
					console.log('$.Component error: event "'+eventName+'" not found');
					return;
				}
				return component.events[eventName].call(component, e);
			};
		};

		component.callEvent = function(eventName) {
			component.events[eventName].apply(component, Array.prototype.slice.call(arguments, 1));
		};

		// console.log($.fromJson(el.data('params')));
		// $.default(component.data.params, $.fromJson(el.data('params')));

		if (typeof component.data === 'function') {
			component.data = component.data.call(component, el);
		}

		component.init();

		delete component.init;

		el.setComponent(component);

		return component;

	};

})();
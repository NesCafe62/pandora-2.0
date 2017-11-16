(function() {

	function clamp(val, min, max) {
		return Math.min(Math.max(min, val), max);
	}

	$.Component($('.debugger').first(), {

		data: function(el) {
			var params = {
				el: el,
				defaultHeight: 170,
				heightEl: $('.debugger-height')
			};
			return {
				params: params,

				closed: $.state(false),
				resizing: $.state(false),
				// resizingHeight: $.state(0),
				height: $.state(parseInt($.cookie.get('debugger.height', params.defaultHeight))),
				minimized: $.state($.cookie.get('debugger.minimized') == 1),
				commandLineVisible: $.state(true),
				minHeight: $('.debugger-caption', el).height()
			};
		},

		init: function() {
			var self = this;
			var data = this.data;

			var cont = data.params.el;
			var heightEl = data.params.heightEl;

			var resizingEl = $('.debugger-resizing', cont);

			// data.minHeight = $('.debugger-caption', cont).height();

			var commandLineEl = $('.debugger-command-line', cont);

			var body = $('body');

			var windowEl = $(window);

			$('.button-clear', cont).on('click', this.proxyEvent('clear', true));
			$('.button-close', cont).on('click', this.proxyEvent('close', true));
			$('.button-minimize', cont).on('click', this.proxyEvent('minimize', true));
			$('.button-restore', cont).on('click', this.proxyEvent('restore', true));

			$('.debugger-caption', cont).on('dblclick', this.proxyEvent('toggleMinimize'));

			function getMaxHeight() {
				return Math.max(data.minHeight, windowEl.height() - 100);
			}

			var draggable = $.draggable({
				el: $('.debugger-resize-region', cont),
				onDragStart: function(e) {
					this.startHeight = (data.minimized.value) ? data.minHeight : data.height.value;
					resizingEl.css('height', this.startHeight+'px');
					cont.addClass('resizing');
					body.css('cursor','n-resize');
				},
				onDragMove: function(e) {
					this.height = clamp(this.startHeight - e.offset.y, data.minHeight, getMaxHeight());
					resizingEl.css('height', this.height+'px');
				},
				onDragEnd: function(e) {
					self.callEvent('setHeight', this.height);
					cont.removeClass('resizing');
					body.css('cursor','');
				}
			});

			windowEl.on('resize', function(e) {
				var maxHeight = getMaxHeight();
				if (data.height.value > maxHeight) {
					self.callEvent('setHeight', maxHeight);
				}
			});

			data.height.changed( function(height) {
				cont.css('height', height+'px');
				heightEl.css('height', height+'px');
			}).triggerChanged();

			data.closed.changed( function(closed) {
				if (closed) {
					cont.css('display','none');
					heightEl.css('display','none');
				} else {
					cont.css('display','');
					heightEl.css('display','');
				}
			});

			data.minimized.changed( function(minimized) {
				if (minimized) {
					cont.addClass('minimized');
					heightEl.addClass('minimized');
				} else {
					cont.removeClass('minimized');
					heightEl.removeClass('minimized');
				}
			}).triggerChanged();

			data.commandLineVisible.changed( function(commandLineVisible) {
				if (commandLineVisible) {
					commandLineEl.removeClass('hide');
				} else {
					commandLineEl.addClass('hide');
				}
			});

			this.callEvent('init');
		},

		events: {
			init: function() {
				this.data.commandLineVisible.depends([this.data.minimized, this.data.height], function(minimized, height) {
					return (!minimized && height >= 24*2);
				});
			},
			setHeight: function(height) {
				if (this.data.minimized.value) {
					if (height > this.data.minHeight) {
						this.callEvent('restore');
					}
				} else {
					if (height <= this.data.minHeight) {
						this.callEvent('minimize');
						return;
					}
				}
				$.cookie.set('debugger.height', height);
				this.data.height.set(height);
			},
			clear: function() {
				// ;
			},
			open: function() {
				this.data.closed.set(false);
			},
			close: function() {
				this.data.closed.set(true);
			},
			toggleMinimize: function() {
				if (this.data.minimized.value) {
					this.callEvent('restore');
				} else {
					this.callEvent('minimize');
				}
			},
			minimize: function() {
				$.cookie.set('debugger.minimized', 1);
				this.data.minimized.set(true);
			},
			restore: function() {
				$.cookie.clear('debugger.minimized');
				this.data.minimized.set(false);
				if (this.data.height.value <= this.data.minHeight) {
					this.callEvent('setHeight', this.data.params.defaultHeight);
				}
			}
		}

	});

})();
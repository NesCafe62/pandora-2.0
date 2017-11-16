(function() {

	$.draggable = function(params) {
		var el = params.el;
		var self = {
			el: el,
			dragStarted: false,
			startCursorPos: {x: 0, y: 0}
		};
		el.on('mousedown', function(e) {
			self.startCursorPos = {x: e.clientX, y: e.clientY};
			if (params.onDragStart) {
				e.startCursorPos = {x: e.clientX, y: e.clientY};
				if (params.onDragStart.call(self, e) === false) {
					return;
				}
			}
			self.dragStarted = true;
			if (typeof document.body.setCapture !== 'undefined') {
				document.body.setCapture();
			}
		});
		$(document).on('mouseup', function(e) {
			if (self.dragStarted) {
				self.dragStarted = false;
				if (params.onDragMove || params.onDragEnd) {
					e.offset = {
						x: e.clientX - self.startCursorPos.x,
						y: e.clientY - self.startCursorPos.y
					};
				}
				if (params.onDragMove) {
					params.onDragMove.call(self, e);
				}
				if (params.onDragEnd) {
					params.onDragEnd.call(self, e);
				}
				if (typeof document.body.releaseCapture !== 'undefined') {
					document.body.releaseCapture();
				}
			}
		});
		$(document).on('mousemove', function(e) {
			if (self.dragStarted) {
				if (params.onDragMove) {
					e.offset = {
						x: e.clientX - self.startCursorPos.x,
						y: e.clientY - self.startCursorPos.y
					};
					params.onDragMove.call(self, e);
				}
			}
		});
		return self;
	};

})();
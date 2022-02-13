/*
 * Colorpicker for ZX Spectrum colors
 *
 * Copyright (C) 2014 nyuk
 * Based on sources by Flaute - https://github.com/flaute/bootstrap-colorselector
 */

(function($) {
	"use strict";

	var ZXColorSelector = function(owner, options) {
		this.options = options;
		this.$owner = $(owner);

		if ($(owner).data('selector')) {
			this.selectorType = $(owner).data('selector');
		}
		else if (options.selector) {
			this.selectorType = options.selector;
		}
		else {
			this.selectorType = 'simple';
		}
	
		// create an colors list
		this.colors = [];
		this.colors.push({'value': 0,  'color': '#000000', 'colorB': '#000000', 'title': 'Black' });
		this.colors.push({'value': 1,  'color': '#0000bb', 'colorB': '#0000ff', 'title': 'Blue' });
		this.colors.push({'value': 2,  'color': '#bb0000', 'colorB': '#ff0000', 'title': 'Red' });
		this.colors.push({'value': 3,  'color': '#bb00bb', 'colorB': '#ff00ff', 'title': 'Magenta' });
		this.colors.push({'value': 4,  'color': '#00bb00', 'colorB': '#00ff00', 'title': 'Green' });
		this.colors.push({'value': 5,  'color': '#00bbbb', 'colorB': '#00ffff', 'title': 'Cyan' });
		this.colors.push({'value': 6,  'color': '#bbbb00', 'colorB': '#ffff00', 'title': 'Yellow' });
		this.colors.push({'value': 7,  'color': '#bbbbbb', 'colorB': '#ffffff', 'title': 'White' });
		
		this.initialValue = parseInt($(owner).text());
		
		this._init();
	};

	ZXColorSelector.prototype = {
		constructor : ZXColorSelector,

		_init : function() {
			// insert the colorselector
			if (this.selectorType == 'full') {
				this._makeMarkupFull().insertAfter(this.$owner);
			}
			else {
				this._makeMarkupSimple().insertAfter(this.$owner);
			}

			// hide the owner
			this.$owner.hide();
		},

		// Create basic picker: 0-7 colors
		_makeMarkupSimple: function(){
			// find color by owner value
			var initialColor = '#000000';
			var initialValue = this.initialValue;
			$.each(this.colors, function(i, c) {
				if (c.value == initialValue) initialColor = c.color;
			});
		
			var $markupUl = $("<ul>").addClass("dropdown-menu").addClass("dropdown-caret");
			var $markupDiv = $("<div>").addClass("dropdown").addClass("dropdown-colorselector");
			var $markupSpan = $("<span>").addClass("btn-colorselector").css("background-color", initialColor);
			var $markupA = $("<a>").attr("data-toggle", "dropdown").addClass("dropdown-toggle").attr("href", "#").append($markupSpan);

			$.each(this.colors, function(i, c) {
				// create a-tag
				var $markupA = $("<a>").addClass("color-btn");
				if (c.value == this.initialValue) {
	          		$markupA.addClass("selected");
				}
				$markupA.css("background-color", c.color);
				$markupA.attr("href", "#").attr("data-color", c.color).attr("data-value", c.value).attr("title", c.title);

				// create li-tag
				$markupUl.append($("<li>").append($markupA));
			});

			// append the colorselector
			$markupDiv.append($markupA);
			// append the colorselector-dropdown
			$markupDiv.append($markupUl);

			// register click handler
			$markupUl.on('click.colorselector', $.proxy(this._clickColor, this));
			
			return $($markupDiv);			
		},

		// Create full picker: PAPER + INK + BRIGHT
		_makeMarkupFull: function(){
			// find color by owner value
			var initialPaper = '#000000';
			var initialInk = '#000000';
			var initialValue = this.initialValue;
			$.each(this.colors, function(i, c) {
				if (c.value == (initialValue & 0x07)) {
					initialInk = initialValue & 0x40 ? c.colorB : c.color;
				}

				if (c.value*8 == (initialValue & 0x38)) {
					initialPaper = initialValue & 0x40 ? c.colorB : c.color;
				}
			});
		
			var $paperUl = $("<ul>").addClass("dropdown-menu").addClass("dropdown-caret");
			var $inkUl = $("<ul>").addClass("dropdown-menu").addClass("dropdown-caret");
			var $paperDiv = $("<div>").addClass("dropdown").addClass("dropdown-colorselector");
			var $inkDiv = $("<div>").addClass("dropdown").addClass("dropdown-colorselector");
			var $paperA = $("<a>").attr("data-toggle", "dropdown").addClass("dropdown-toggle").attr("href", "#").append($("<span>").addClass("btn-colorselector").css("background-color", initialPaper));
			var $inkA = $("<a>").attr("data-toggle", "dropdown").addClass("dropdown-toggle").attr("href", "#").append($("<span>").addClass("btn-colorselector").css("background-color", initialInk));

			// Add normal colors
			$.each(this.colors, function(i, c) {
				var $paperA = $("<a>").addClass("color-btn");
				if (c.color == initialPaper) {
	          		$paperA.addClass("selected");
				}
				$paperA.css("background-color", c.color).attr("href", "#").attr("data-color", c.color).attr("data-value", c.value*8).attr("title", c.title);
				$paperUl.append($("<li>").append($paperA));

				var $inkA = $("<a>").addClass("color-btn");
				if (c.color == initialInk) {
	          		$inkA.addClass("selected");
				}
				$inkA.css("background-color", c.color).attr("href", "#").attr("data-color", c.color).attr("data-value", c.value).attr("title", c.title);
				$inkUl.append($("<li>").append($inkA));
			});

			// Add bright colors
			$.each(this.colors, function(i, c) {
				var $paperA = $("<a>").addClass("color-btn");
				if (c.colorB == initialPaper) {
	          		$paperA.addClass("selected");
				}
				$paperA.css("background-color", c.colorB).attr("href", "#").attr("data-color", c.colorB).attr("data-value", c.value*8 + 0x40).attr("title", c.title + ' Bright');
				$paperUl.append($("<li>").append($paperA));

				var $inkA = $("<a>").addClass("color-btn");
				if (c.colorB == initialInk) {
	          		$inkA.addClass("selected");
				}
				$inkA.css("background-color", c.colorB).attr("href", "#").attr("data-color", c.colorB).attr("data-value", c.value + 0x40).attr("title", c.title + ' Bright');
				$inkUl.append($("<li>").append($inkA));
			});
			
			// append the colorselector
			$paperDiv.append($paperA);
			$inkDiv.append($inkA);
			// append the colorselector-dropdown
			$paperDiv.append($paperUl);
			$inkDiv.append($inkUl);

			// register click handler
			$paperUl.on('click.colorselector', $.proxy(this._clickColor, this));
			$inkUl.on('click.colorselector', $.proxy(this._clickColor, this));
			
			return $('<div id="ink-and-paper">').append($paperDiv, $($inkDiv));			
		},
				
		_clickColor : function(e) {
			var a = $(e.target);
			if (!a.is(".color-btn")) return false;

			var value = a.data("value");
			
			// remove old and set new selected color
			a.closest("ul").find("a").removeClass("selected");
			a.closest("ul").find("a[data-value='" + value + "']").addClass("selected");
        	a.closest('.dropdown').find(".btn-colorselector").css("background-color", a.data('color'));

        	

        	if (this.selectorType == 'full') {
            	var bright = a.data("value") & 0x40;
            	
            	this.fixBright(bright, a.closest('div[id="ink-and-paper"]'));

            	value = 0;
            	a.closest('div[id="ink-and-paper"]').find('a.selected').each(function(){
            		value = value + parseInt($(this).data('value')) & 0x3f;
                });
            	value += bright;
            }

        	this.$owner.text(value);
        	
			e.preventDefault();
			return true;
	    },

	    // Fix bright on siblings palettes
	    fixBright : function(bright, obj) {
			obj.find('.dropdown').each(function(){
				var pos = $(this).find('a.selected').closest('li').index();

				if ((pos < 8 && bright) || (pos > 7 && !bright)) {
					// Switch bright/nobright row
					var newPos = bright? pos + 8 : pos - 8;
					$(this).find('a').removeClass('selected');
					$(this).find('li:eq(' + newPos + ')').find('a').addClass('selected');

					var newBG = $(this).find('a.selected').css('background-color');
					$(this).find('.btn-colorselector').css('background-color', newBG);
				}
			});
	    }
	};

	$.fn.zxcolorselector = function(option) {
		var args = Array.apply(null, arguments);
		args.shift();

		return this.each(function() {

			var $this = $(this), data = $this.data("zxcolorselector"), options = $.extend({}, $.fn.zxcolorselector.defaults, $this.data(), typeof option == "object" && option);

			if (!data) {
				$this.data("zxcolorselector", (data = new ZXColorSelector(this, options)));
			}
			if (typeof option == "string") {
				data[option].apply(data, args);
			}
		});
	};

	$.fn.zxcolorselector.Constructor = ZXColorSelector;
})(jQuery, window, document);
/**
 * jQuery FocusPoint; version: 1.1.1
 * Author: http://jonathonmenz.com
 * Source: https://github.com/jonom/jquery-focuspoint
 * Copyright (c) 2014 J. Menz; MIT License
 * @preserve
 */
;
(function($) {

	var defaults = {
		reCalcOnWindowResize: true,
		throttleDuration: 17 //ms - set to 0 to disable throttling
	};

	//Setup a container instance
	var setupContainer = function($el) {
		var imageSrc = $el.find('img').attr('src');
		$el.data('imageSrc', imageSrc);

		resolveImageSize(imageSrc, function(err, dim) {
			$el.data({
				imageW: dim.width,
				imageH: dim.height
			});
			adjustFocus($el);
		});
	};

	//Get the width and the height of an image
	//by creating a new temporary image
	var resolveImageSize = function(src, cb) {
		//Create a new image and set a
		//handler which listens to the first
		//call of the 'load' event.
		$('<img />').one('load', function() {
			//'this' references to the new
			//created image
			cb(null, {
				width: this.width,
				height: this.height
			});
		}).attr('src', src);
	};

	//Create a throttled version of a function
	var throttle = function(fn, ms) {
		var isRunning = false;
		return function() {
			var args = Array.prototype.slice.call(arguments, 0);
			if (isRunning) return false;
			isRunning = true;
			setTimeout(function() {
				isRunning = false;
				fn.apply(null, args);
			}, ms);
		};
	};

	//Calculate the new left/top values of an image
	var calcShift = function(conToImageRatio, containerSize, imageSize, focusSize, toMinus) {
		var containerCenter = Math.floor(containerSize / 2); //Container center in px
		var focusFactor = (focusSize + 1) / 2; //Focus point of resize image in px
		var scaledImage = Math.floor(imageSize / conToImageRatio); //Can't use width() as images may be display:none
		var focus =  Math.floor(focusFactor * scaledImage);
		if (toMinus) focus = scaledImage - focus;
		var focusOffset = focus - containerCenter; //Calculate difference between focus point and center
		var remainder = scaledImage - focus; //Reduce offset if necessary so image remains filled
		var containerRemainder = containerSize - containerCenter;
		if (remainder < containerRemainder) focusOffset -= containerRemainder - remainder;
		if (focusOffset < 0) focusOffset = 0;

		return (focusOffset * -100 / containerSize)  + '%';
	};

	//Re-adjust the focus
	var adjustFocus = function($el) {
		var imageW = $el.data('imageW');
		var imageH = $el.data('imageH');
		var imageSrc = $el.data('imageSrc');

		if (!imageW && !imageH && !imageSrc) {
			return setupContainer($el); //Setup the container first
		}

		var containerW = $el.width();
		var containerH = $el.height();
		var focusX = parseFloat($el.data('focusX'));
		var focusY = parseFloat($el.data('focusY'));
		var $image = $el.find('img').first();

		//Amount position will be shifted
		var hShift = 0;
		var vShift = 0;

		if (!(containerW > 0 && containerH > 0 && imageW > 0 && imageH > 0)) {
			return false; //Need dimensions to proceed
		}

		//Which is over by more?
		var wR = imageW / containerW;
		var hR = imageH / containerH;

		//Reset max-width and -height
		$image.css({
			'max-width': '',
			'max-height': ''
		});

		//Minimize image while still filling space
		if (imageW > containerW && imageH > containerH) {
			$image.css((wR > hR) ? 'max-height' : 'max-width', '100%');
		}

		if (wR > hR) {
			hShift = calcShift(hR, containerW, imageW, focusX);
		} else if (wR < hR) {
			vShift = calcShift(wR, containerH, imageH, focusY, true);
		}

		$image.css({
			top: vShift,
			left: hShift
		});
	};

	var $window = $(window);

	var focusPoint = function($el, settings) {
		var thrAdjustFocus = settings.throttleDuration ?
			throttle(function(){adjustFocus($el);}, settings.throttleDuration)
			: function(){adjustFocus($el);};//Only throttle when desired
		var isListening = false;

		adjustFocus($el); //Focus image in container

		//Expose a public API
		return {

			adjustFocus: function() {
				return adjustFocus($el);
			},

			windowOn: function() {
				if (isListening) return;
				//Recalculate each time the window is resized
				$window.on('resize', thrAdjustFocus);
				return isListening = true;
			},

			windowOff: function() {
				if (!isListening) return;
				//Stop listening to the resize event
				$window.off('resize', thrAdjustFocus);
				isListening = false;
				return true;
			}

		};
	};

	$.fn.focusPoint = function(optionsOrMethod) {
		//Shortcut to functions - if string passed assume method name and execute
		if (typeof optionsOrMethod === 'string') {
			return this.each(function() {
				var $el = $(this);
				$el.data('focusPoint')[optionsOrMethod]();
			});
		}
		//Otherwise assume options being passed and setup
		var settings = $.extend({}, defaults, optionsOrMethod);
		return this.each(function() {
			var $el = $(this);
			var fp = focusPoint($el, settings);
			//Stop the resize event of any previous attached
			//focusPoint instances
			if ($el.data('focusPoint')) $el.data('focusPoint').windowOff();
			$el.data('focusPoint', fp);
			if (settings.reCalcOnWindowResize) fp.windowOn();
		});

	};

	$.fn.adjustFocus = function() {
		//Deprecated v1.2
		return this.each(function() {
			adjustFocus($(this));
		});
	};

})(jQuery);



// Gets focus point coordinates from an image - adapt to suit your needs.

(function($) {
	$(document).ready(function() {

		var defaultImage;
		var $dataAttrInput;
		var $cssAttrInput;
		var $focusPointContainers;
		var $focusPointImages;
		var $helperToolImage;

		//This stores focusPoint's data-attribute values
		var focusPointAttr = {
				x: 0,
				y: 0,
				w: 0,
				h: 0
			};

		//Initialize Helper Tool
		(function() {

			//Initialize Variables
			defaultImage = '';
			$dataAttrInput = $('#Focuspoint_Data');
			$cssAttrInput = $('#Focuspoint_CSS');
			$helperToolImage = $('img.helper-tool-img, img.target-overlay');

		//	Create Grid Elements
		//	for(var i = 1; i < 10; i++) {
		//		$('#Frames').append('<div id="Frame'+i+'" class="focuspoint"><img/></div>');
		// 	}

			//Store focus point containers
			$focusPointContainers = $('.focuspoint');
			$focusPointImages = $('.focuspoint img');

			//Set the default source image
			setImage( defaultImage );

		})();

		/*-----------------------------------------*/

		// function setImage(<URL>)
		// Set a new image to use in the demo, requires URI to an image

		/*-----------------------------------------*/

		function setImage(imgURL) {
			//Get the dimensions of the image by referencing an image stored in memory
			$("<img/>")
				.attr("src", imgURL)
				.load(function() {
					focusPointAttr.w = this.width;
					focusPointAttr.h = this.height;

					//Set src on the thumbnail used in the GUI
					$helperToolImage.attr('src', imgURL);

					//Set src on all .focuspoint images
					$focusPointImages.attr('src', imgURL);

					//Set up initial properties of .focuspoint containers

					/*-----------------------------------------*/
					// Note ---
					// Setting these up with attr doesn't really make a difference
					// added to demo only so changes are made visually in the dom
					// for users inspecting it. Because of how FocusPoint uses .data()
					// only the .data() assignments that follow are necessary.
					/*-----------------------------------------*/
					$focusPointContainers.attr({
						'data-focus-x':focusPointAttr.x,
						'data-focus-y':focusPointAttr.y,
						'data-image-w': focusPointAttr.w,
						'data-image-h': focusPointAttr.h
					});

					/*-----------------------------------------*/
					// These assignments using .data() are what counts.
					/*-----------------------------------------*/
					$focusPointContainers.data('focusX', focusPointAttr.x);
					$focusPointContainers.data('focusY', focusPointAttr.y);
					$focusPointContainers.data('imageW', focusPointAttr.w);
					$focusPointContainers.data('imageH', focusPointAttr.h);



				});
		}

		/*-----------------------------------------*/

		// Update the data attributes shown to the user

		/*-----------------------------------------*/

		function printDataAttr(){
			// Original
			//$dataAttrInput.val('data-focus-x="'+focusPointAttr.x.toFixed(2)+'" data-focus-y="'+focusPointAttr.y.toFixed(2)+'" data-focus-w="'+focusPointAttr.w+'" data-focus-h="'+focusPointAttr.h+'"');
			$dataAttrInput.val(focusPointAttr.x.toFixed(2)+','+focusPointAttr.y.toFixed(2));
		}

		/*-----------------------------------------*/

		// Bind to helper image click event
		// Adjust focus on Click / provides focuspoint and CSS3 properties

		/*-----------------------------------------*/

		$helperToolImage.click(function(e){

			var imageW = $(this).width();
			var imageH = $(this).height();

			//Calculate FocusPoint coordinates
			var offsetX = e.pageX - $(this).offset().left;
			var offsetY = e.pageY - $(this).offset().top;
			var focusX = (offsetX/imageW - 0.5)*2;
			var focusY = (offsetY/imageH - 0.5)*-2;
			focusPointAttr.x = focusX;
			focusPointAttr.y = focusY;

			//Write values to input
			printDataAttr();

			//Update focus point
			updateFocusPoint();

			//Calculate CSS Percentages
			var percentageX = (offsetX/imageW)*100;
			var percentageY = (offsetY/imageH)*100;
			// var backgroundPosition = percentageX.toFixed(0) + '% ' + percentageY.toFixed(0) + '%';
			// var backgroundPositionCSS = 'background-position: ' + backgroundPosition + ';';

			var backgroundPosition = percentageX.toFixed(0) + '%, ' + percentageY.toFixed(0) + '%';
			//var backgroundPositionCSS = 'background-position: ' + backgroundPosition + ';';
			var backgroundPositionCSS = backgroundPosition;


			$cssAttrInput.val(backgroundPositionCSS);

			//Leave a sweet target reticle at the focus point.
			$('.reticle').css({
				'top':percentageY+'%',
				'left':percentageX+'%'
			});
		});

		/*-----------------------------------------*/

		/* Update Helper */
		// This function is used to update the focuspoint

		/*-----------------------------------------*/

		function updateFocusPoint(){
			/*-----------------------------------------*/
			// See note in setImage() function regarding these attribute assignments.
			//TLDR - You don't need them for this to work.
			/*-----------------------------------------*/
			$focusPointContainers.attr({
				'data-focus-x': focusPointAttr.x,
				'data-focus-y': focusPointAttr.y
			});
			/*-----------------------------------------*/
			// These you DO need :)
			/*-----------------------------------------*/
			$focusPointContainers.data('focusX', focusPointAttr.x);
			$focusPointContainers.data('focusY', focusPointAttr.y);
			$focusPointContainers.adjustFocus();
		};
	});









}(jQuery));

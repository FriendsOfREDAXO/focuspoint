
	
	/**
 * jQuery FocusPoint; version: 1.0.3
 * Author: http://jonathonmenz.com
 * Source: https://github.com/jonom/jquery-focuspoint
 * Copyright (c) 2014 J. Menz; MIT License
 * @preserve
 */
;(function($) {
	$.fn.focusPoint = function(options) {
		var settings = $.extend({
			//These are the defaults.
			reCalcOnWindowResize: true,
			throttleDuration: 17 //ms - set to 0 to disable throttling
		}, options);
		return this.each(function() {
			//Initial adjustments
			var container = $(this), isThrottled = false;
			//Replace basic css positioning with more accurate version
			container.removeClass('focus-left-top focus-left-center focus-left-bottom focus-center-top focus-center-center focus-center-bottom focus-right-top focus-right-center focus-right-bottom');
			//Focus image in container
			container.adjustFocus();
			if (settings.reCalcOnWindowResize) {
				//Recalculate each time the window is resized
				$(window).resize(function() {
					//Throttle redraws
					if (settings.throttleDuration > 0){
				    if (isThrottled) { return; }
				    isThrottled = true;
				    setTimeout(function () {
				    	isThrottled = false;
				    	container.adjustFocus();
				    }, settings.throttleDuration);
			    }
					container.adjustFocus();
				});
			}
		});
	};
	$.fn.adjustFocus = function() {

		return this.each(function() {
			//Declare variables at top of scope
			var containerW,
				containerH,
				image,
				imageW,
				imageH,
				self,
				imageTmp,
				wR,
				hR,
				hShift,
				vShift,
				containerCenterX,
				focusFactorX,
				scaledImageWidth,
				focusX,
				focusOffsetX,
				xRemainder,
				containerXRemainder,
				containerCenterY,
				focusFactorY,
				scaledImageHeight,
				focusY,
				focusOffsetY,
				yRemainder,
				containerYRemainder;
			//Collect dimensions
			containerW = $(this).width();
			containerH = $(this).height();
			image = $(this).find('img').first();
			imageW = $(this).data('imageW');
			imageH = $(this).data('imageH');
			//Get image dimensions if not set on container
			if (!imageW || !imageH) {
				self = this;
				imageTmp = new Image();
				imageTmp.onload = function(){
					$(self).data('imageW', this.width);
					$(self).data('imageH', this.height);
					$(self).adjustFocus(); //adjust once image is loaded - may cause a visible jump
				};
				imageTmp.src = image.attr('src');
				return false; //Don't proceed right now, will try again once image has loaded
			}
			if (!(containerW > 0 && containerH > 0 && imageW > 0 && imageH > 0)) {
				//Need dimensions to proceed
				return false;
			}
			//Which is over by more?
			wR = imageW / containerW;
			hR = imageH / containerH;
			//Minimise image while still filling space
			if (imageW > containerW && imageH > containerH) {
				if (wR > hR) {
					image.css('max-width', '');
					image.css('max-height', '100%');
				} else {
					image.css('max-width', '100%');
					image.css('max-height', '');
				}
			} else {
				image.css('max-width', '');
				image.css('max-height', '');
			}
			//Amount position will be shifted
			hShift = 0;
			vShift = 0;
			if (wR > hR) {
				//Container center in px
				containerCenterX = Math.floor(containerW / 2);
				//Focus point of resize image in px
				focusFactorX = (Number($(this).data('focus-x')) + 1) / 2;
				//Can't use width() as images may be display:none
				scaledImageWidth = Math.floor(imageW / hR);
				focusX = Math.floor(focusFactorX * scaledImageWidth);
				//Calculate difference beetween focus point and center
				focusOffsetX = focusX - containerCenterX;
				//Reduce offset if necessary so image remains filled
				xRemainder = scaledImageWidth - focusX;
				containerXRemainder = containerW - containerCenterX;
				if (xRemainder < containerXRemainder){
					focusOffsetX -= containerXRemainder - xRemainder;
				}
				if (focusOffsetX < 0) {
					focusOffsetX = 0;
				}
				//Shift to left
				hShift = focusOffsetX * -1;
			} else if (wR < hR) {
				//Container center in px
				containerCenterY = Math.floor(containerH / 2);
				//Focus point of resize image in px
				focusFactorY = (Number($(this).data('focus-y')) + 1) / 2;
				//Can't use width() as images may be display:none
				scaledImageHeight = Math.floor(imageH / wR);
				focusY = scaledImageHeight - Math.floor(focusFactorY * scaledImageHeight);
				//Calculate difference beetween focus point and center
				focusOffsetY = focusY - containerCenterY;
				//Reduce offset if necessary so image remains filled
				yRemainder = scaledImageHeight - focusY;
				containerYRemainder = containerH - containerCenterY;
				if (yRemainder < containerYRemainder) {
					focusOffsetY -= containerYRemainder - yRemainder;
				}
				if (focusOffsetY < 0) {
					focusOffsetY = 0;
				}
				//Shift to top
				vShift = focusOffsetY * -1;
			}
			image.css('left', hShift + 'px');
			image.css('top', vShift + 'px');
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

					//Run FocusPoint for the first time.
					$('.focuspoint').focusPoint();

					//Update the data attributes shown to the user
					printDataAttr();

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

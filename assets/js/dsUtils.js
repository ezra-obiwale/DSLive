var dsUtils = {
	Page: {
		content:null,
		data:{},
		formId:null,
		/**
	 * Captures the current document element
	 */
		capture: function() {
			this.content = $(document).children().clone(true);
			return this;
		},
		/**
	 * Sets other data to send with the document content
	 */
		setData:function(data) {
			this.data = (typeof(data) == 'object') ? data : {
				data:data
			};
			return this;
		},
		/**
	 * Removes each element of the given array from the dom
	 */
		remove:function(elems) {
			var self = this;
			$.each(elems, function(index, value){
				self.content.find(value).remove();
			});
			return this;
		},
		/**
	 * Replaces the the str with another within the acquired content
	 * Should be called after capture() has been called
	 *
	 * @param str String to replace
	 * @param replace Replace with
	 */
		replace:function(str, replace) {
			if (!str || !replace)
				return this;

			this.content.children('head').html(this.content.children('head').html().replace(str, replace, 'gi'));
			this.content.children('body').html(this.content.children('body').html().replace(str, replace, 'gi'));
			return this;
		},
		/**
	 * Hides each element of the given array in the dom
	 * @param elems array of elements
	 * @return Page
	 */
		hide:function(elems) {
			$.each(elems, function(index, value){
				$(value).hide();
			});
			return this;
		},
		createAccept: function(returnType){
			switch (returnType) {
				case 'json':
					return 'application/json';
					break;
				case 'html':
					return 'text/html';
					break;
				case 'jpg':
				case 'png':
				case 'gif':
				case 'jpeg':
					return 'image/' + returnType
					break;
			}
			return null;
		},
		sendTo: function(url, method, returnType, successFunction, completeFunction) {
			method = (!method) ? 'POST' : method;
			returnType = (!returnType) ? 'html' : returnType;
			this.data.content = this.content;

			$.ajax({
				headers : {
					Accept: this.createAccept(returnType)
				},
				url: url,
				type: method,
				data: this.data,
				dataType: returnType,
				success: function(ret){
					fn = (typeof(successFunction) == 'function') ? successFunction : window[successFunction];
					fn.apply(null, [ret]);
				},
				complete: function(ret){
					fn = (typeof(completeFunction) == 'function') ? completeFunction : window[completeFunction];
					fn.apply(null, [ret]);
				}
			});

			return this;
		},
		/**
	 * Creates form with a hidden element with name as "content"
	 * @param id Id for the form
	 * @param action Action for the form
	 * @param method Method for the form
	 * @return string
	 */
		createForm: function(id, action, method){
			id = (!id) ? '__PAGE_FORM__' : id;
			this.formId = id;
			action = (!action) ? '' : action;
			method = (!method) ? 'POST' : method;

			return '<form target="_blank" method="' + method + '" id="' + id + '" action="' + action + '">\n\
<input id="__PAGE_INPUT__" type="hidden" name="content" value="" />\n\
</form>';
		},
		beforeSend:null,
		/**
	 * Inserts a form with a hidden element into the dom
	 * @param action Form action
	 * @param method Form method
	 * @param submitButtonClass Class of the submit button(s)
	 *				<p>
	 *				Submit buttons may use attributes data-action to specify a different
	 *				action instead of the default set
	 *				</p>
	 * @param beforeSendFunction Function to call before sending. It will receive the Page object as a parameter
	 *				<p>
	 *					The function should return the Page object or boolean FALSE (cancel send)
	 *				</p>
	 * @return Page
	 */
		insertForm: function(action, method, submitButtonClass, beforeSendFunction){
			var self = this;
			$('body').append(self.createForm('__PAGE_FORM__', action, method));
			$('.' + submitButtonClass).click(function(e){
				e.preventDefault();

				self.capture();
				if (beforeSendFunction != null){
					fn = (typeof(beforeSendFunction) == 'function') ? beforeSendFunction : window[beforeSendFunction];
					var page = fn.apply(null, [self])

					if (!page)
						return false;

					self.content = page.content;
				}
				$('#__PAGE_INPUT__').attr('value', self.content.html());

				if ($(this).attr('data-action'))
					$('#' + self.formId).attr('action', $(this).attr('data-action'));

				$('#' + self.formId).submit();
			});
			return this;
		}
	},


	AjaxLoading: {
		defSrc:null,
		enlarge: function(src){
			if (!src)
				src = this.defSrc;

			this.start(src, true).show();
			$('#dsBackground').css('opacity', '.5').css('background-color', '#000');

			var self = this;
			$('#dsBackground, #dsLoaderImage').click(function(){
				self.start(self.defSrc);
			});
		},
		/**
	 * Starts watching out for any ajax action
	 * @param src Path to image file to use as loader. AjaxLoading will not work without it
	 */
		start: function(src, largeImg){
			if (!src)
				return false;
			if (this.defSrc == null)
				this.defSrc = src;

			$('#dsAjaxLoading').remove();

			var loadingDiv = '\
<div id="dsAjaxLoading">\n\
	<div id="dsBackground"></div>\n\
	<img id="dsLoaderImage" src="' + src + '" />\n\
</div>';
			$('body').prepend(loadingDiv);

			$('#dsAjaxLoading').css({
				position:'fixed',
				'z-index':1000,
				height:$(document).height(),
				width:$(document).width(),
				top:0
			});

			$('#dsAjaxLoading > #dsBackground').css({
				position:'fixed',
				height:'inherit',
				width:'inherit',
				opacity:0,
				cursor:'busy'
			});

			var options = {};

			if (largeImg) {
				options.position = 'absolute';
				if ($(window).height() > $(window).width()) {
					options.height = ($(window).width() - 200);
					options.maxWidth = '80%';
					options.left = '2%';
					options.top = ($(window).height() - options.height) / 2;
				}
				else {
					options.width = ($(window).width() - 200);
					options.maxHeight = '80%';
					options.top = '2%';
					options.left = ($(window).width() - options.width) / 2;
				}
			}
			else {
				var max = ($(window).height() > $(window).width()) ?
				($(window).height() / 4) : ($(window).width() / 4);

				var imgH = $('#dsLoaderImage').height(),
				imgW = $('#dsLoaderImage').width();

				if (imgH > imgW) {
					options.height = imgH = max;
				}
				else {
					options.width = imgW = max;
				}

				options.position = 'fixed';
				options.top = ($('#dsAjaxLoading').height() - imgH) / 2;
				options.left = ($('#dsAjaxLoading').width() - imgW) / 2;

			}

			$('#dsAjaxLoading > #dsLoaderImage').css(options);

			$(document).ajaxStart(function(){
				$('#dsAjaxLoading').show();
			}).ajaxStop(function(){
				$('#dsAjaxLoading').hide();
			});

			this.hide();
			return this;
		},
		/**
	* Stops watching for ajax calls
	 */
		stop: function() {
			$('#dsAjaxLoading').remove();
			return this;
		},
		/**
	 * Force show the loader image
	 */
		show: function() {
			$('#dsAjaxLoading').show();
			return this;
		},
		/**
	 * Force hide the loader image
	 */
		hide: function() {
			$('#dsAjaxLoading').hide();
			return this;
		},
		/**
	 * Fetches the ajax loader div
	 */
		div: function() {
			return $('#dsAjaxLoading');
		},
		/**
	 * Fetches the background div
	 */
		bgDiv: function() {
			return $('#dsAjaxLoading > #dsBackground');
		},
		image: function() {
			return $('#dsAjaxLoading > #dsLoaderImage');
		}
	},
	Checks: {
		toggleAllIds:{},
		noOfToggleIds:0,
		funcNone:null,
		funcSome:null,
		funcAll:null,
		addToggleAllId: function(id) {
			this.toggleAllIds[this.noOfToggleIds] = {
				id:id,
				length:$('.' + id).length,
				live:0
			};
			this.noOfToggleIds++;
			return this;
		},
		start: function(noneCheckedFunction, someCheckedFunction, allCheckedFunction) {
			this.liveChecks = this.allChecks;
			this.funcNone = noneCheckedFunction;
			this.funcSome = someCheckedFunction;
			this.funcAll = allCheckedFunction;

			this.live();
		},
		live: function() {
			var self = this;

			$.each(this.toggleAllIds, function(index, obj){
				$('#' + obj.id).click(function(){
					if ($(this).attr('checked')) {
						$('.' + obj.id).attr('checked', 'checked');
						obj.live = obj.length;
					}
					else {
						$('.' + obj.id).removeAttr('checked');
						obj.live = 0;
					}

					self.checkLive();
				});

				$('.' + obj.id).click(function(e){
					if ($(this).attr('checked'))
						obj.live++;
					else
						obj.live--;

					if ( obj.live > 0 &&  obj.live == obj.length) {
						$('#' + obj.id).attr('checked', 'checked');
					}
					else {
						$('#' + obj.id).removeAttr('checked')
					}

					self.checkLive();
				});

			});
		},
		checkLive: function () {
			var all = 0, live = 0;

			$.each(this.toggleAllIds, function (index, obj){
				all += obj.length;
				live += obj.live;
			});

			switch (all - live) {
				case 0:
					this.call(this.funcAll, all);
					break;
				case all:
					this.call(this.funcNone, 0);
					break;
				default:
					this.call(this.funcSome, live);
					break;
			}
		},
		call: function(fn) {
			fn = (typeof(fn) == 'function') ? fn : window[fn];
			fn.apply();
		}
	},
	Toggle: {
		/**
		 * The element that is active
		 */
		active:null,
		/**
	 * Class to attach to active button
	 * @type string Default: btn-default
	 */
		activeCSS:'btn-default',
		/**
	 * Class to attach to inactive buttons
	 * @type string Default: btn-primary
	 */
		inActiveCSS:'btn-primary',
		/**
	 * Function to call when an inactive element is clicked
	 */
		clickInactive: function(e){},
		/**
	 * Function to call when an active element is clicked
	 */
		clickActive: function(e){},
		/**
	 * Starts the Toggle engine
	 * @return void
	 */
		start: function(){
			var self = this;
			$('.ds-toggle').click(function(e) {
				e.preventDefault();
				if ($(this).hasClass('active')){
					self.clickActive.apply(this, [e]);
					return;
				}
				self.active = this;
				$('.ds-toggle.active').removeClass(self.activeCSS).removeClass('disabled active').addClass(self.inActiveCSS);
				$(this).removeClass(self.inActiveCSS).addClass(self.activeCSS).addClass('active disabled');
				self.clickInactive.apply(this, [e]);
			});

			$('.ds-toggle.active').removeClass(this.inActiveCSS).addClass(this.activeClass).addClass('disabled');
			$('.ds-toggle').not('.active').removeClass(this.activeCSS).removeClass('disabled').addClass(this.inActiveCSS);
			this.active = $('.ds-toggle.active');
		}
	}
};

$(function(){
	dsUtils.Page.insertForm('pdf/savetofile', 'post', 'pageToPdf', function(page){
		return page.remove(['.navbar-fixed-top', 'button', 'a', '.dataTables_filter','.dataTables_length', '.dataTables_info', '.dataTables_paginate']);
	});

	dsUtils.AjaxLoading.start('/images/loading.gif').image().css({
		width:'20%'
	});

	dsUtils.Toggle.start();

	$('input[type="date"]').live('focus',function () {
		$(this).datepicker({
			changeYear:true, // allows changing of year from dropdown
			changeMonth:true, // allows changing of month from dropdown
			defaultDate:'default',
			dateFormat:'yy-mm-dd', // default date format
			appendText:'',
			yearRange:'-70:+5'
		});
	});

	$('input[type="time"]').live('focus', function(){
		$(this).timepicker({
			disableTextInput:true,
			step:1,
			timeFormat:"H:i"
		});
	});

	$('.display').dataTable({
		"sPaginationType": "full_numbers"
	});

	$('img').not('#dsLoaderImage').not('.logo').not('.pix').click(function(){
		dsUtils.AjaxLoading.enlarge($(this).attr('src'));
	});
});


/* @deprecated */
var checkedCount = 0;
function toggleCheck(id) {
	var check = $('#' + id);

	if (check.attr('checked')) {
		checkedCount++;
		if (checkedCount == allChecks) {
			$('#checkAll').attr('checked', 'checked');
			allChecked = true;
		}
	}
	else {
		allChecked = false;
		checkedCount--;
		$('#checkAll').removeAttr('checked');

	}

	if (checkedCount > 0) {
		$('#sendMessage').show();
	}
	else {
		$('#sendMessage').hide();
	}
}

var allChecks = 0;
var allChecked = false;
function toggleAll() {
	if (allChecked) {
		$(".check").removeAttr('checked');
		allChecked = false;
		checkedCount = 0;
		$('#sendMessage').hide();
	}
	else {
		$(".check").attr('checked', 'checked');
		allChecked = true;
		checkedCount = allChecks;
		$('#sendMessage').show();
	}
}

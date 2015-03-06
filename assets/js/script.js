window.BPMsgAt_Media_Uploader = (function($, util) {
	var uploader	= false,
		_uploader	= util.uploader || {},
		lang		= util.lang,
		selectors	= util.selectors,
		_l			= {};

	var APP = {
		init: function() {
			var self = this;

			if (!self.get_elements())
				return false;

			//hide file upload if user is sending a notice to all users
			if (_l.$cb_send_notice.length !== 0) {
				_l.$cb_send_notice.change(function(){
					if ($(this).is(':checked')) {
						_l.$form.find(".bp_msgat_ui_wrapper").hide();
					} else {
						_l.$form.find(".bp_msgat_ui_wrapper").show();
					}
				});
			}
			
			setTimeout(function() {
				self.start_uploader();
			}, 10);

			$.ajaxPrefilter(APP.prefilter);
		},
		get_elements: function() {
			if (util.current_action == 'compose') {
				_l.$form = $(selectors.form_message);
			} else {
				_l.$form = $(selectors.form_reply);
			}

			if (_l.$form.length === 0) {
				//There is no post message or post reply form. Bail out
				return false;
			}

			_l.$button = _l.$form.find('input[type="submit"],button[type="submit"]');
			_l.$field_attachment_ids = _l.$form.find('input[name="bp_msgat_attachment_ids"]');
			_l.$upload_button = $('#btn_msgat_upload');
			_l.$cb_send_notice = _l.$form.find('input[name="send-notice"]');

			return true;
		},
		get_query_variable: function(query, variable) {
			if (typeof query == 'undefined' || query == '' || typeof variable == 'undefined' || variable == '')
				return '';

			var vars = query.split("&");

			for (var i = 0; i < vars.length; i++) {
				var pair = vars[i].split("=");

				if (pair[0] == variable)
					return pair[1];
			}
			return(false);
		},
		prefilter: function(options, origOptions, jqXHR) {
			var action = APP.get_query_variable(options.data, 'action');
			if (typeof action == 'undefined' || action != 'messages_send_reply')
				return;

			var new_data = $.extend({}, origOptions.data, {
				bp_msgat_attachment_ids: _l.$field_attachment_ids.val()
			});

			options.data = $.param(new_data);

			options.success = (function(old_success) {
				_l.$field_attachment_ids.val('');//clear it
				$('.msgat-uploaded-file').remove();

				return function(response, txt, xhr) {
					if ($.isFunction(old_success)) {
						old_success(response, txt, xhr);
					}
				}
			})(options.success);
		},
		start_uploader: function() {

			uploader = new plupload.Uploader({
				runtimes: 'html5,silverlight,flash,html4',
				browse_button: 'btn_msgat_upload',
				dragdrop: false,
				max_file_size: _uploader.max_file_size || '5mb',
				multi_selection: _uploader.multiselect || false,
				url: ajaxurl,
				multipart: true,
				multipart_params: {
					action: 'bp_msgat_upload',
					'cookie': encodeURIComponent(document.cookie),
					'_wpnonce': _uploader.nonce
				},
				flash_swf_url: _uploader.swf_url || '',
				silverlight_xap_url: _uploader.silverlight_xap_url || '',
				filters: _uploader.filters,
				init: {
					FilesAdded: function(up, files) {
						//disable submit button
						_l.$button.attr('disabled', 'disabled').addClass('loading');
						//disable browse button
						var org_text = _l.$upload_button.html();
						_l.$upload_button.attr('disabled', 'disabled').addClass('loading').data('org_text', org_text).html(lang['uploading']);

						up.start();
					},
					FileUploaded: function(up, file, info) {
						//enable submit button
						_l.$button.removeAttr('disabled').removeClass('loading');

						//enable browse button
						_l.$upload_button.removeAttr('disabled').removeClass('loading').html(_l.$upload_button.data('org_text'));

						var responseJSON = $.parseJSON(info.response);

						var attachment_ids = _l.$field_attachment_ids.val();
						if (_uploader.multiselect) {
							//@todo: later
						} else {
							attachment_ids = responseJSON.attachment_id;
							//remove previous attachment, if any
							$('.msgat-uploaded-file').remove();

							//add new attachment
							var new_att = "<span class='msgat-uploaded-file'>" + responseJSON.name
									+ "<a href='#' data-attachment_id='" + responseJSON.attachment_id + "' class='remove-uploaded-file' "
									+ " onclick='return window.BPMsgAt_Media_Uploader.removeAttachment(this)' "
									+ " title='" + lang['remove'] + "'>x</a>"
									+ "</span>";
							$('#btn_msgat_upload').after(new_att);
						}

						_l.$field_attachment_ids.val(attachment_ids);
					},
					Error: function(up, err) {
						//enable submit button
						_l.$button.removeAttr('disabled').removeClass('loading');
						//enable browse button
						_l.$upload_button.removeAttr('disabled').removeClass('loading').html(_l.$upload_button.data('org_text'));

						//self.upload_error(err.file, err.code, err.message, up);
						APP.show_upload_error(err.code);
						up.refresh();

						if (_uploader.multiselect) {
							//@todo: later
						} else {
							_l.$field_attachment_ids.val('');
						}
					}
				}
			});

			uploader.init();

		},
		show_upload_error: function(errorCode) {
			switch (errorCode) {
				case plupload.FILE_EXTENSION_ERROR:
					alert(lang.upload_error.file_type);
					break;
				case plupload.FILE_SIZE_ERROR:
					alert(lang.upload_error.file_size);
					break;
				default:
					alert(lang.upload_error.generic);
					break;
			}
		},
		removeAttachment: function(el) {
			$el = $(el);
			/* 
			 * @todo: when multiselect is allowed, only remove current attachment id from hidden field value
			 */
			_l.$field_attachment_ids.val('');

			$el.closest('.msgat-uploaded-file').remove();
			return false;
		},
	} // APP


	var API = {
		setup: function() {
			APP.init();
		},
		removeAttachment: function(el) {
			return APP.removeAttachment(el);
		}
	} // API

	$(document).ready(function() {
		APP.init();
	});

	return API;
}
(
	window.jQuery,
	window.BPMsgAt_Util
));
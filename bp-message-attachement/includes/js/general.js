jQuery(document).ready( function() {
	jQuery("#send_message_form").attr('enctype','multipart/form-data');
	jQuery("input#send_reply_button").unbind('click');
});
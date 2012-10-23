jQuery(document).ready( function() {
	jQuery("#send-reply").attr('enctype','multipart/form-data');
	jQuery("input#send_reply_button").unbind('click');
	jQuery("input#send_reply_button").after('<input type="submit" name="send" value="Send Reply" id="send_reply_button2">');
	jQuery("input#send_reply_button").hide();
});
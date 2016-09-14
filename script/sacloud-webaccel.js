var sacloudojs_connect_test = function($){return function() {
    var data = {
		action: "sacloud_webaccel_connect_test",
		key: $("#sacloud-webaccel-api-key").val(),
		secret: $("#sacloud-webaccel-api-secret").val()
    };

    $.ajax({
        type: 'POST',
        url: ajaxurl,
        data: data,
        success: function (response) {
	    var res = $.parseJSON(response);

            $("html,body").animate({scrollTop: 0}, 1000);
            $("#sacloud-webaccel-flash p").empty().append(res["message"]);
	    if(res["is_error"]) {
    		$("#sacloud-webaccel-flash").addClass("notice-error").removeClass("notice-success");
	    } else {
	    	$("#sacloud-webaccel-flash").removeClass("notice-error").addClass("notice-success");
	    }

	    $('#sacloud-webaccel-flash').show();
        }
        //dataType: 'html'
    });
    $("#selupload_spinner").unbind("ajaxSend");
}}(jQuery);

(function($) {
    $(function () {
        $("#sacloud-webaccel-flash").hide();
    });
})(jQuery);

/**
 * login.js
 * Only used on the public unauthenticated Login page
 *
 * jdayworkplace@gmail.com
 */

$(function() {

    /**
     * repeating requests for admin-status
     */
    function adminStatusCheck(){
        $.ajax({
            url: statusUrl,
            type: 'GET',
            dataType: 'json',
            success: function (data,textStatus, jqXHR) {
                let $el = $('.adminstatus');
                let msg = "";

                if ("PENDING_LOG_OUT_LOCAL" === data.state) {
                    msg = "Auto Logout in (" + data.seconds + ")";
                }else if ( "PENDING_LOG_OUT_REMOTE" === data.state){
                    msg = "Auto Logout in (" + data.seconds + ")";
                }else if ("LOGGED_OUT" === data.state){
                    msg = "Logged out";
                }else if ("PENDING_DELETE" === data.state){
                    msg = "Auto Deleting in (" + data.seconds + ")";
                }else if ("DELETED" === data.state) {
                    window.location.assign(window.location.href);
                }

                let refreshInterval = 5000;
                $el.text(msg);
                if (data.seconds === 0 || data.seconds > 20){
                    $el.removeClass("status-highlight");
                }else{
                    $el.addClass("status-highlight");
                    refreshInterval = 1000;
                }
                setTimeout(adminStatusCheck, refreshInterval);
            }
        })
    }

    /**
     * start live status requests
     */
    $('.livestatusfeed').each(function(){
        setTimeout(adminStatusCheck,100);
    });

});
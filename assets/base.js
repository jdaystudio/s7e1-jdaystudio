/**
 * base.js
 * For all pages, EXCEPT public Login
 *
 * jdayworkplace@gmail.com
 */

$(function() {

    /**
     * repeating requests for user-status
     *
     * @param id
     */
    function userStatusCheck(id){
        $.ajax({
            url: statusUrl + '/' + id,
            type: 'GET',
            dataType: 'json',
            success: function (data,textStatus, jqXHR) {
                let isOurselves = function(id){
                    return data.id === app_uid;
                }

                let $el = $('.userstatus-' + data.id);
                let msg = "";

                // set true if this local user is effected
                let goHome = false;

                if ("PENDING_LOG_OUT_LOCAL" === data.state){
                    msg = "Auto Logout in (" + data.seconds + ")";

                }else if ("PENDING_LOG_OUT_REMOTE" === data.state) {
                    msg = "Auto Logout in (" + data.seconds + ")";
                    goHome = isOurselves(data.id);

                }else if ("LOGGED_OUT" === data.state) {
                    msg = "Logged out";
                    goHome = isOurselves(data.id);

                }else if ("PENDING_DELETE" === data.state){
                    msg = "Auto Deleting in (" + data.seconds + ")";
                    goHome = isOurselves(data.id);

                }else if ("DELETED" === data.state) {
                    goHome = isOurselves(data.id);
                    if (!goHome) {
                        // a user was deleted, not us
                        // we could manipulate the dom etc... but for this example will just refresh
                        window.location.assign(window.location.href);
                    }
                }

                if (goHome){
                    window.location.replace("/login");
                }

                let refreshInterval = 5000;

                $el.text(msg);
                if (data.seconds === 0 || data.seconds > 20){
                    $el.removeClass("status-highlight");
                }else{
                    $el.addClass("status-highlight");
                    refreshInterval = 1000;
                }
                setTimeout(userStatusCheck, refreshInterval, data.id);
            },
            statusCode:{
                403: function(){
                    window.location.replace("/login");
                }
            }
        })
    }

    /**
     * start live status requests, grouped by at 10ms
     */
    $('.livestatusfeed').each(function(){
        let id = $(this).data('id');
        setTimeout(userStatusCheck,100+(id%10), id);
    });

    /**
     * make the non-active/example buttons report but do nothing
     */
    $('a').each(function(){
        let ref = $(this).attr('href');
        if ('' === ref){
            $(this).on('click',function(e){
                e.preventDefault();
                alert("Inactive button for example purposes");
            })
        }
    })
});
/**
 * user-manage.js
 * Some simple code required for this example project
 *
 * jdayworkplace@gmail.com
 */

$(function() {

    /**
     * Capture and respond to the delete user buttons
     */
    $("#user-list .userdelete").on('click',function(e){
        e.preventDefault();
        let id = $('a',$(this)).data('value');
        let name = document.getElementById('userlabel-'+id).innerHTML;

        // safely convert any encoded chars (eg &gt) as we are using the default confirm box
        let ta = document.createElement('textarea');ta.innerHTML = name;name = ta.value;

        if (confirm("Please confirm you want to delete user '"+name+"' ?")){
            $.ajax({
                    url: deleteUrl + '/' + id,
                    type: 'DELETE',
                    success: function () {
                        window.location = listUrl;
                    },
                    error: function () {
                        alert("User Delete Failed");
                    }
            })
        }
    })

});

$(document).ready(function() 
{
    // Ajax request for the form used to add link tags (either host or IP)
    $( "#form_login" ).on( "submit", function( event ) 
    {
      event.preventDefault();
      // Let's hide the box now
      $('#form_login_error_box').hide(100);

      // Username
      var username = $(this).children("input[id='form_username']").val();
      // Password
      var password = $(this).children("input[id='form_password']").val();
      // We have the form data!
      var postdata = { 'username': username, 'password': password };

      // Let's make the AJAX request
      $.ajax({
        url: CI.site_url + "login/check",
        type: 'POST',
        data: postdata,
        dataType: "json",
        statusCode: {
          404: function() {
            alert("Error logging in. Please notify an administrator");
            console.log("Server returned 404");
          },
          403: function() {
            alert("Error logging in. Please notify an administrator");
            console.log("Server returned 403");
          }
        },
        success: function (data)
        {
            // Let's check the object's data
            if (data.status === 200)
                window.location = CI.site_url + "jobs";
            else
            {
              $('#form_login_error_box').html(data.msg);
              $('#form_login_error_box').show(100);
            }
          }

        });
    });

});


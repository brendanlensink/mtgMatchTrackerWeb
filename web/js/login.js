$(document).ready(
  function() {
    $("#login").click(
      function() {
        wordOne = $("#wordOne").val();
        wordTwo = $("#wordTwo").val();
        $.ajax({
          type: "GET",
          url: "http://localhost/mtgmatchtracker/api/api.php/user/checkCode/",
          headers: {
            "authkey":"VO^dqt7*Eq!o",
            "id":"-1",
            "first":wordOne,
            "second":wordTwo,
          },
          dataType: 'json',
          success: function(json) {
            $(login).button("reset");
            console.log(json.message);
            if(json.status == true) {
              $.redirect('index.php', {'id': json.message});
            }else {
              $("#error").fadeIn(1000, function() {
                $("#error").html('<div class="alert alert-danger"> '+json.message+' </div>');
              });
            }
          },
          beforeSend:function() {
            $(login).button("loading");
          }
        });
        return false;
      }
    );
  }
);

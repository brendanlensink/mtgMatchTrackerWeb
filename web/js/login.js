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
            console.log(json)
            if(json.status == true) {
              function redirectPost(url, data) {
                var form = document.createElement('form');
                form.method = 'post';
                form.action = url;
                for (var name in data) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = data[name];
                    form.appendChild(input);
                }
                form.submit();
              }
              redirectPost('view/home.php', { wordOne: wordOne, wordTwo: wordTwo });
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

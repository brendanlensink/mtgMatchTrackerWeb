$(document).ready(
  function() {
    id = $("input#userID").val();
    console.log(id);
    $.ajax({
      type: "GET",
      url: "http://localhost/mtgmatchtracker/api/api.php/matches",
      headers: {
        "authkey":"VO^dqt7*Eq!o",
        "id":id
      },
      dataType: 'json',
      success: function(json) {
        console.log(json)
      }
    });
  }


);

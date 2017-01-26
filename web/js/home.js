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
        if(json.status == true) {
          for (var i=0; i<json.data.length; i++) {
            console.log(json.data[i]);
            var match = json.data[i];
            var table = document.getElementById('recent-table-body');
            var row = table.insertRow(i);
            row.insertCell(0).innerHTML = (new Date(match.datetime * 1000)).toLocaleString();
            row.insertCell(1).innerHTML = match.eventName;
              var format = "Unknown"
              switch(match.format) {
                case "0": format = "Sealed"; break;
                case "1": format = "Draft"; break;
                case "2": format = "Standard"; break;
                case "3": format = "Modern"; break;
                case "4": format = "Legacy"; break;
              }
            row.insertCell(2).innerHTML = format
            row.insertCell(3).innerHTML = match.myDeck+' vs. '+match.theirDeck;
              var wins = 0;var losses = 0;
              if(match[0].result == 1) { wins++; }else{losses++;}
              if(match[1].result == 1) { wins++; }else{losses++;}
              if(('2' in match)) {
                if(match[2].result == 1) { wins++; }else{losses++;}
              }
            row.insertCell(4).innerHTML = wins+'-'+losses;


          }
        }
        // TODO: Handle if the db query fails D:
      }
    });
  }


);

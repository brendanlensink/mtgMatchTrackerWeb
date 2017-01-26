<?php
/**
 * home.php
 * Main logged in page for MTGMatchTracker
 */

  $id = $_SESSION['id'];
?>

<input type="hidden" id="userID" value="<?=$_SESSION['id'];?>"></input>

<div class="container main">
  <div class="row">
    <!-- Header is going to go here -->
  </div>
  <div class="row">
    <div class="col-sm-5">
      <table class="history table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Event Name</th>
            <th>Format</th>
            <th>Decks</th>
            <th>Result</th>
          </tr>
        </thead>
        <tbody id="recent-table-body">
        </tbody>
      </table>
    </div>
    <div class="col-sm-7">
    </div>
  </div>
</div>
<script src="js/home.js" type="text/javascript"></script>

<div class="container main">
  <div class="row">
    <div class="col-sm-12 header">
      <img class="header-logo" src="img/olephantesmall.png" alt="Logo">
    </div>
  </div>
  <div class="row reminder">
    <div class="col-sm-2"></div>
    <div class="col-sm-8">
      <p class="body-text">Enter Your Secret Code To View Your Stats</p>
    </div>
    <div class="col-sm-2"></div>
  </div>
  <div class="row text-field">
    <form class="form" role="form" action="./">
      <div class="row form-group">
        <div class="col-sm-4"></div>
        <div class="col-sm-2">
          <input class="form-control" id="wordOne" name="wordOne" placeholder="Word One">
        </div>
        <div class="col-sm-2">
          <input class="form-control" id="wordTwo" name="wordTwo" placeholder="Word Two">
        </div>
        <div class="col-sm-4"></div>
      </div>
      <div class="row">
        <div class="col-sm-4"></div>
        <div class="col-sm-4">
          <button class="btn login btn-success" type="submit" id="login" name="submit" value="login"
            data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> Checking...">Check Code</button>
        </div>
        <div class="col-sm-4"></div>
      </div>
    </form>
  </div>
  <div id="error">
    <!-- If there's an error we're going to show it here -->
  </div>
</div>
<script src="js/redirect.js" type="text/javascript"></script>
<script src="js/login.js" type="text/javascript"></script>

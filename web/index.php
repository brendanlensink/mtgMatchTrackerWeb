<?php
/**
 * index.php
 * Entry point for the web version of MTGMatchTracker.
 */

  require_once 'config.php';
  session_start();

  // Load the header
  require_once $CONFIG['root'].'view/template/header.php';

  // If the user isn't logged in, we're going to just show a filler screen. If they are logged in we're good.
  if(isset($_SESSION['isLoggedIn']) && $_SESSION['isLoggedIn'] == 1) {
    require_once $CONFIG['root'].'view/home.php';
  }else {
    require_once $CONFIG['root'].'view/login.php';
  }

  // Load the footer
  require_once $CONFIG['root'].'view/template/footer.php';
?>

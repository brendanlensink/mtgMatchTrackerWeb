<?php
/**
 * index.php
 * Entry point for the web version of MTGMatchTracker.
 */

  require_once 'config.php';
  session_start();

  // Load the header
  require_once $CONFIG['root'].'view/template/header.php';

  // Check to make sure we have our secret words and save them to the session to keep track of them
  if( isset($_POST['id']) ) { $_SESSION['id'] = $_POST['id']; }

  // If we somehow got here without the secret words we'll just show the login scene
  if(isset($_SESSION['id'])) {
    require_once $CONFIG['root'].'view/home.php';
  } else {
    require_once $CONFIG['root'].'view/login.php';
  }

  // Load the footer
  require_once $CONFIG['root'].'view/template/footer.php';
?>

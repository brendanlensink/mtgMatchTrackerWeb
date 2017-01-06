<?php
/**
 *  Simple REST API for MTGMatchTracker
 *
 *  @author Brendan Lensink
 *  @version 1.0
 */

require_once 'config_local.php';
require_once 'logic/auth.php';
require_once 'db/match.php';

// Set the defaults for our response

$status = False;
$message = "";
$data = array();

// Get the HTPP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
$input = json_decode(file_get_contents('php://input'), true);

// Check if we're authenticated
$authkey = array_key_exists("HTTP_AUTHKEY", $_SERVER) ? $_SERVER['HTTP_AUTHKEY'] : null;
$userId = array_key_exists("HTTP_ID", $_SERVER) ? $_SERVER['HTTP_ID'] : null;

if($authkey == $CONFIG["auth"] && is_numeric($userId)) {

  // Decide if we're GETting or POSTing
  switch ($method) {
  case 'GET':
    // If it's a get request, figure out what we're looking for
    if(array_key_exists(0, $request)) {
      switch($request[0]) {
      case 'matches':
          // If there was nothing else provided, get all the matches for a user
          if(!array_key_exists(1, $request)) {
            $allMatches = Match::GetAllMatches($userId);
            $matchArray = array();
            foreach($allMatches as $match) {
              // Update the status and data return fields
              $status = True;
              array_push($data, $match->MakeArray());
            }
          } else {
            $match = Match::GetMatchById($request[1]);
            if(is_object($match)) {
              // Update the status and data return fields if we've succeeded
              $status = True;
              array_push($data, $match->MakeArray());
            }else {
              $message = "No match with id: ".$request[1]." found";
              array_push($data, $match);
            }
          }
        break;
      default:
        http_response_code(400);
        $status = False;
        $message = "Incorrect endpoint supplied";
        break;
      }
    } else {
      http_response_code(400);
      $status = False;
      $message = "No endpoint supplied";
    }
    break;
  case 'POST':
    // Make sure we've got the right endpoint
    if(array_key_exists(0, $request)) {
      switch($request[0]) {
      case 'matches':
        // If there's a match id supplied, we're updating an existing record
        if(array_key_exists(1, $request)) {
          // TODO: Update an existing match
        }else {
        // If there was no matchId supplied, we're adding a new match record
            $result = Match::ParseMatch($userId, $input);

            if(is_numeric($result)) {
              $status = true;
            }else {
              http_response_code(400);
              $status = false;
              $message = $result;
            }
        }
        break;
      default:
        $message = "Incorrect endpoint";
        break;
      }
    }else {
      $message = "No endpoint supplied";
    }
    break;
  default:
    $status = false;
    http_response_code(400);
    break;
  }
} else {
  http_response_code(400);
  $status = false;
  $message = "Id was null or no api key supplied";
}

// Finally packaage up the return array and the send it back
$returnArray = array(
  "status" => $status,
  "message" => $message,
  "data" => $data
);

echo json_encode($returnArray);
?>

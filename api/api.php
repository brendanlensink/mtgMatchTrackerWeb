<?php
/**
 *  Simple REST API for MTGMatchTracker
 *
 *  Created by Brendan Lensink, 2016
 */

require_once 'config_local.php';
require_once 'logic/auth.php';
require_once 'db/match.php';

$status = False;
$message = "";
$data = array();

// Step 1: Get the HTPP method, path and body of the request

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'], '/'));
$input = json_decode(file_get_contents('php://input'), true);

// Step 2: Check if we're authenticated

$username = $_SERVER['HTTP_USERNAME'];
$password = $_SERVER['HTTP_PASSWORD'];
$userId = Auth::Login($username, $password);

if(is_numeric($userId)) {

  // Step 3: Decide if we're GETting or POSTing

  switch ($method) {
  case 'GET':

    // Step 4a: If it's a get request, figure out what we're looking format
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
        $message = "No request supplied";
        break;
      }
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
            $status = Match::ParseMatch($userId, $input);
            if(!$status) { http_response_code(400);}
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
  // $allMatches = Match::GetAllMatches($userId);
  // foreach($allMatches as $match) {
  //   print(json_encode($match->MakeArray()));
  // }

  $returnArray = array(
    "status" => $status,
    "message" => $message,
    "data" => $data
  );

  echo json_encode($returnArray);
}


 ?>

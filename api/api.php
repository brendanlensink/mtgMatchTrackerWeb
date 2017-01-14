<?php
/**
 *  Simple REST API for MTGMatchTracker
 *
 *  @author Brendan Lensink
 *  @version 1.0
 */

require_once 'config_local.php';
require_once 'db/match.php';
require_once 'db/identifier.php';

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

if($authkey == $CONFIG["auth"] && $userId != null) {

  // Decide if we're GETting or POSTing
  switch ($method) {
  case 'GET':
    if(array_key_exists(0, $request)) {
      switch($request[0]) {
      case 'matches':
          if(!array_key_exists(1, $request)) {
            $status = True;
            $data = Match::GetAllMatches($userID);
          } else {
            $match = Match::GetMatchById($request[1]);
            $status = True;
            if(is_object($match)) {
              $match = $match->MakeArray();
            } else {
              $message = "No match with id: ".$request[1]." found";
            }
          }
        break;
      case 'user':
        if(!array_key_exists(1, $request)) {
          http_response_code(400);
          $status = False;
          $message = "No command specified";
        } else {
          $param = $request[1];
          $deviceID = array_key_exists("HTTP_DEVICEID", $_SERVER) ? $_SERVER['HTTP_DEVICEID'] : null;
          $firstWord = array_key_exists('HTTP_FIRST', $_SERVER) ? $_SERVER['HTTP_FIRST']: null;
          $secondWord = array_key_exists('HTTP_SECOND', $_SERVER) ? $_SERVER['HTTP_SECOND']: null;

          if($deviceID != null) {
            switch($param) {
            case 'code':
              $code = Identifier::CreateIdentifier($deviceID);
              $status = is_numeric($code);
              $message = $code;
              break;
            case 'checkCode':
              if($firstWord != null && $secondWord != null) {
                $code = Identifier::GetIdenfifierByDeviceID($deviceID);

                if($code != null) {
                  $status = $code->GetCode() == $firstWord." ".$secondWord;
                }else {
                  http_response_code(400);
                  $status = False;
                  $message = "No code for the given device ID";
                }
              }else {
                http_response_code(400);
                $status = False;
                $message = "First word or second word was null ".$firstWord.",".$secondWord;
              }
              break;
            default:
              http_response_code(400);
              $status = false;
              $message = "No action specified for the given endpoint";
              break;
            }
          } else {
            http_response_code(400);
            $status = false;
            $message = "No device ID specified";
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
    if(array_key_exists(0, $request)) {
      switch($request[0]) {
      case 'matches':
        if(array_key_exists(1, $request)) {
        }else {
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

// Finally package up the return array and the send it back
$returnArray = array(
  "status" => $status,
  "message" => $message,
  "data" => $data
);

echo json_encode($returnArray);
?>

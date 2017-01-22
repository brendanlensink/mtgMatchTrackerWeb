<?php
require_once("config_local.php");

require_once("db/match.php");

$eventName = "";
$rel = array("casual","competitive","pro");
$format = array("sealed","draft","standard","modern","legacy");
$myDeck = "Test ";
$theirDeck = "Test ";
$userID = 1;

$start = array(0,1);
$result = array(0,1);
$hand = array(5,6,7);

$matches = array();


for ($i=0; $i < 100; $i++) {
  $gameFormat = array_rand($format);
  $newMatch = array(
    'matchId' => rand(0,1000000),
    'eventName' => 'Test Event',
    'datetime' => rand(1261044445681,1262044445681),
    'rel' => array_rand($rel),
    'format' => $gameFormat,
    'myDeck' => $myDeck." ".$gameFormat,
    'theirDeck' => $theirDeck." ".$gameFormat
  );

  $gameOne = array(
    'game' => '1',
    'start' => array_rand($start),
    'result' => array_rand($result),
    'myHand' => array_rand($hand),
    'theirHand' => array_rand($hand)
  );

  $gameTwo = array(
    'game' => '2',
    'start' => array_rand($start),
    'result' => array_rand($result),
    'myHand' => array_rand($hand),
    'theirHand' => array_rand($hand)
  );

  $newMatch[0] = $gameOne;
  $newMatch[1] = $gameTwo;

  if($gameOne['result'] != $gameTwo['result']) {
    $gameThree = array(
      'game' => '3',
      'start' => array_rand($start),
      'result' => array_rand($result),
      'myHand' => array_rand($hand),
      'theirHand' => array_rand($hand)
    );

    $newMatch[2] = $gameThree;
  }

  echo Match::ParseMatch(1, $newMatch);
}

/***
2015-02-04Magic - 2015/02/04Quantum Games and Cards7-
Format: Standard
1	Win	(+3)	Wratley, Callum
2	Loss	(+0)	Thompson, Cat
3	Win	(+3)	Mosinsky, Logan
4	Loss	(+0)	Howland, Chandller
*/

 ?>

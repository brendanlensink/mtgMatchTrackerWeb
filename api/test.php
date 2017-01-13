<?php
$file = file("helper/words.txt");
$line = $file[array_rand($file)];

$testString = "1131AD6FD-BEE5-4A87-BD14-132FEAD085B6";
$hashedString = crc32($testString);
$unsigned = sprintf("%u\n", $hashedString);
echo $unsigned;
$firstHalf = floor($unsigned/100000);
$secondHalf = $unsigned%100000;

echo $file[$firstHalf%count($file)] . "  " . $file[$secondHalf%count($file)];
 ?>

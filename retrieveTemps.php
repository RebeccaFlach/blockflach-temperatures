<?php

//hello! if you are reading this on github, this is the code that is used to get temperatures from a local
//database. Obviously, it is not functional if not local, and the actual javascript uses a text file of the json,instead of this .
// I put this here to demonstrate how it normally works, and to show the (little) php ive written
//this formats the json in the format that d3 uses with multiline graphs
header("Access-Control-Allow-Origin: *");
$servername = "localhost";
$username = "php_user";
$password = "blockflach";
$database_name = "temperatures";

try {
  $conn = new PDO("mysql:host=$servername;dbname=$database_name", $username, $password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
}


$thermometersFetch = "SELECT id FROM thermometers"; //gets a list of the thermometors, to make easier when new thermometer added
$thermometer_stmt = $conn->prepare($thermometersFetch);
$thermometer_stmt->execute();
$thermometerArray = $thermometer_stmt->fetchAll(PDO::FETCH_ASSOC); //array of list of thermometers

$tempArray = array();
$keyCount = 0; // basically, each datapoint is given a key, so it can be associated with a line segment.

echo("["); // the data is echoed to the page in sections to avoid memory issues as the data grows (happened once before)

foreach($thermometerArray as $thermometer){ // goes through each thermometer
  $keyCount += 1;
  $sql = "SELECT temperature, temperature_date_time, thermometer_id FROM temperature_readings WHERE thermometer_id='" . $thermometer["id"] . "'  ";
//gets all data for one thermometer
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $currentArray = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $previousValue = null;
  $splitIndex = 0;

  foreach($currentArray as $key => $row){//for each datapoint

    if($previousValue) { // avoids error of first data point looking for a previous value that doesnt exist
      if(strtotime($row['temperature_date_time']) - strtotime($previousValue['temperature_date_time']) > 5000){
//checks if there is a big gap in the data. if so, designates it as a new segment
// Avoids long straight lines connecting far apart data points, and shows a gap instead
        $firstPart = array_slice($currentArray, $splitIndex, $key - $splitIndex);
        $splitIndex = $key; //saves when the segment split was, so later everything after that can be echoed
        echo('{"key": '.$keyCount.', "values" : ');
        echo(json_encode($firstPart));
        echo("},");
        $keyCount += 1; //creates a new segment
      }
    }
  $newArray = array("segment" => $keyCount);
  $currentArray[$key] = array_merge($row, $newArray); //adds the segment key to the data point
  $previousValue = $row; // sets the new previous val before looping again
  };

  $firstPart = array_slice($currentArray, $splitIndex, $key - $splitIndex); //echoes everything after the last segment split
  $splitIndex = $key;
  echo('{"key": '.$keyCount.', "values" : ');
  echo(json_encode($firstPart));
  echo("}");


  if($thermometer["id"] < count($thermometerArray)){
    echo(',');
  };

}
echo("]");


?>

<?php
$servername = "localhost";
$username = "php_user";
$password = "blockflach";
$database_name = "temperatures";

try {
  $conn = new PDO("mysql:host=$servername;dbname=$database_name", $username, $password);
  // set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//  echo "Connected successfully";
} catch(PDOException $e) {
//  echo "Connection failed: " . $e->getMessage();
}
$sql = "SELECT * FROM thermometers";
$stmt = $conn->prepare($sql);
$stmt->execute();
$sensorsArray = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($sensorsArray as $row)
{

  $handle = curl_init();

  $url = $row["thermometer_url"];

// Set the url
  curl_setopt($handle, CURLOPT_URL, $url);
// Set the result output to be a string.
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  $timeStamp = date("Y-m-d H:i-s");
  $temperature = curl_exec($handle);
  $sql = "INSERT INTO temperature_readings (thermometer_id, temperature, temperature_date_time) VALUES (:thermometer_id, :temperature, :timeStamp)";
  $valuesArr[":thermometer_id"] = $row["id"];
  $valuesArr[":temperature"] = $temperature;
  $valuesArr[":timeStamp"] = $timeStamp;
  $stmt = $conn->prepare($sql);
  $stmt->execute($valuesArr);
  //echo "New record created successfully";
  curl_close($handle);

  // echo $row["thermometer_name"]." ".$temperature."<br>";

}

?>

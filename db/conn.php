<?php
$servername = "127.0.0.1";
$username = "u216861206_mainsite_1";
$password = "+>v#A!^+&1@";
$db = "u216861206_mainsite_1";

// $servername = "localhost";
// $username = "ramwerde_softsr";
// $password = "%3yM5SyAj)oL";
// $db = "ramwerde_softsr";

// Create connection
$conn = new mysqli($servername, $username, $password, $db);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
// echo "Connected successfully";
?>
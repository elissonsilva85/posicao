<?php
if($_SERVER['SERVER_NAME'] == "localhost")
{
  $servername = "localhost";
  $username   = "root";
  $password   = "";
  $database   = "posicao";
}
else
{
  $servername = "PRODUCTION_SERVER";
  $username   = "USERNAME";
  $password   = "PASSWORD";
  $database   = "DATABASE";
}

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

?>
<?php
$servername = "localhost";
$username = "root";
$dbname = "gestordb";
$conn = new mysqli($servername, $username, "", $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}else{echo "Conexxion correcta";}
?>
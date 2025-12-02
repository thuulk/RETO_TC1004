<?php
session_start();

$user = "admin";
$pass = "1234";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    if ($username === $user && $password === $pass) {
        $_SESSION["username"] = $username;
        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('Usuario o contraseña incorrectos'); window.location='login.html';</script>";
    }
}
?>

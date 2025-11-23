<?php
session_start();
include "conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM usuarios WHERE matricula = ?";
    $stmt = mysqli_prepare($conn,$sql);
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if ($username === $row["matricula"] && $password === $row["contraseña"]) {
        $_SESSION["username"] = $username;
        $_SESSION["contraseña"] = $password;
        $_SESSION["rol"] = $row["rol"];
        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('Usuario o contraseña incorrectos'); window.location='login.html';</script>";
    }
}
?>
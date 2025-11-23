<?php
session_start();
include "conexion.php";
if($_SESSION["rol"] != "admin"){
    header("Location: cerrar_sesion.php");
    exit();
}
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $matricula = $_POST["matricula"];
    $contraseña = $_POST["password"];
    
    if (empty($matricula) || empty($contraseña)){
        echo "<script>alert('Usuario o contraseña vacios'); window.location='registro.html';</script>";
    }

    $sql = "SELECT COUNT(*) as contador FROM usuarios WHERE matricula = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $matricula);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if($row["contador"] > 0){
        echo "<script>alert('Usuario ya registrado'); window.location='registro.html';</script>";
    }
    mysqli_stmt_close($stmt);

    $sqlInsert = "INSERT INTO usuarios(matricula, contraseña, rol) VALUES (?, ?, 'empleado')";
    $stmt2 = mysqli_prepare($conn, $sqlInsert);
    mysqli_stmt_bind_param($stmt2, 'ss', $matricula, $contraseña);
    mysqli_stmt_execute($stmt2);

    if(mysqli_stmt_affected_rows($stmt2) == 0){
        mysqli_stmt_close($stmt2);
        echo "<script>alert('Fallo en la insercion del Usuario'); window.location='registro.html';</script>";
    } else{
        mysqli_stmt_close($stmt2);
        header("Location: registro.html?good=se-registro-bien");
    }



}
?>
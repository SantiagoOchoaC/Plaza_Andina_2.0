<?php
function conectar(){
    $host="localhost";
    $user="root";
    $pass="";

    $bd="plaza_andina";
    $con=mysqli_connect($host,$user,$pass);

    mysqli_select_db($con,$bd);

    return $con;

    // Verificar la conexión
    if (!$con) {
        echo "Error de conexión: " . mysqli_connect_error();
        exit();
    }
}
?>
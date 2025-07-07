<?php
function conectar(){
    $host = "localhost";
    $user = "root";
    $pass = "";
    $bd = "plaza_andina";
    
    // Crear conexión con manejo de errores
    $con = mysqli_connect($host, $user, $pass, $bd);
    
    // Verificar conexión
    if (!$con) {
        die("Error de conexión: " . mysqli_connect_error());
    }
    
    // Establecer charset UTF-8
    mysqli_set_charset($con, "utf8");
    
    return $con;
}

// Función para cerrar conexión de forma segura
function cerrarConexion($con) {
    if ($con) {
        mysqli_close($con);
    }
}

// Función para ejecutar consultas preparadas de forma segura
function ejecutarConsulta($query, $tipos = "", $params = []) {
    $con = conectar();
    
    if ($stmt = mysqli_prepare($con, $query)) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $tipos, ...$params);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        mysqli_stmt_close($stmt);
        cerrarConexion($con);
        
        return $result;
    }
    
    cerrarConexion($con);
    return false;
}
?>
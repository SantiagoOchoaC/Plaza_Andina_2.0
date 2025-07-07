<?php

@include 'config.php';

session_start();

if(isset($_POST['submit'])){

    $name = mysqli_real_escape_string($conn, $_POST['nombre']);
    $pass = ($_POST['contraseña']);
    $cpass = ($_POST['ccontraseña']);
    $user_type = $_POST['rol'];

    $select = " SELECT * FROM empleado WHERE nombre = '$name' && contraseña = '$pass' ";

    $result = mysqli_query($conn, $select);

    if(mysqli_num_rows($result) > 0){

        $row = mysqli_fetch_array($result);

        if($row['rol'] == 'staff'){
            $_SESSION['staff_name'] = $row['nombre'];
            header('location: views/staff.html');

        }elseif($row['rol'] == 'barra'){
            $_SESSION['barra_name'] = $row['nombre'];
            header('location: views/barra.html');

        }elseif($row['rol'] == 'jefemeseros'){
            $_SESSION['jefemeseros_name'] = $row['nombre'];
            header('location: views/jefeMeseros.html');

        }elseif($row['rol'] == 'cocina'){
            $_SESSION['cocina_name'] = $row['nombre'];
            header('location: views/barra.html');

        }elseif($row['rol'] == 'coctelero'){
            $_SESSION['coctelero_name'] = $row['nombre'];
            header('location: views/coctelero.html');

        }elseif($row['rol'] == 'mesero'){
            $_SESSION['mesero_name'] = $row['nombre'];
            header('location: views/mesero.html');

        }
        
    }else{
        $error[] = 'Los datos ingresados no son correctos, por favor intente nuevamente.';
    }

};
?>
<?php

@include 'php/config.php';

// Iniciar la sesión
session_start();

// Lógica de inicio de sesión
if(isset($_POST['submit'])){

    $name = mysqli_real_escape_string($conn, $_POST['nombre']);
    $pass = ($_POST['contraseña']);

    $select = " SELECT * FROM empleado WHERE nombre = '$name' && contraseña = '$pass' ";

    $result = mysqli_query($conn, $select);

    if($result && mysqli_num_rows($result) > 0){

        $row = mysqli_fetch_array($result);

        if($row['rol'] == 'staff'){
            $_SESSION['staff_name'] = $row['nombre'];
            $_SESSION['staff_id'] = $row['identificación'];
            header('location: views/staff.php');

        }elseif($row['rol'] == 'barra'){
            $_SESSION['barra_name'] = $row['nombre'];
            $_SESSION['barra_id'] = $row['identificación'];
            header('location: views/barra.php');

        }elseif($row['rol'] == 'jefemeseros'){
            $_SESSION['jefemeseros_name'] = $row['nombre'];
            $_SESSION['jefemeseros_id'] = $row['identificación'];
            header('location: views/jefeMeseros.php');

        }elseif($row['rol'] == 'cocina'){
            $_SESSION['cocina_name'] = $row['nombre'];
            $_SESSION['cocina_id'] = $row['identificación'];
            header('location: views/cocina.php');

        }elseif($row['rol'] == 'coctelero'){
            $_SESSION['coctelero_name'] = $row['nombre'];
            $_SESSION['coctelero_id'] = $row['identificación'];
            header('location: views/coctelero.php');

        }elseif($row['rol'] == 'mesero'){
            $_SESSION['mesero_name'] = $row['nombre'];
            $_SESSION['mesero_id'] = $row['identificación'];
            header('location: views/mesero.php');

        } elseif($row['rol'] == 'cajero'){
            $_SESSION['cajero_name'] = $row['nombre'];
            $_SESSION['cajero_id'] = $row['identificación'];
            header('location: views/cajero.php');
    

        }elseif($row['rol'] == 'admin'){
            $_SESSION['admin_name'] = $row['nombre'];
            $_SESSION['admin_id'] = $row['identificación'];
            header('location: views/admin.html');

        }
        
    }else{
        $error[] = 'Los datos ingresados no son correctos, por favor intente nuevamente.';
    }

};
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/img/icono.ico">
	<!--Conexion estilos-->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
	<link rel="stylesheet" href="css/estilologin.css">
	<title>Plaza Andina</title>

</head>
<body>
	<form action="" method="post">
		<h1 class="text-center">Inicio de Sesión</h1>
        <?php
        if(isset($error)){
            foreach($error as $error){
                echo '<span class="error-msg">'.$error.'</span>';
            };
        };
        ?>
		<div class="mb-3">
			<label for="nombre" class="form-label">Nombre de usuario</label>
			<input type="text" class="form-control" id="nombre" aria-describedby="emailHelp" name="nombre" required>
		</div>
		<div class="mb-3">
			<label for="contraseña" class="form-label">Contraseña</label>
			<input type="password" class="form-control" id="contraseña" name="contraseña" required>
		</div>
		<button type="submit" name= "submit" class="btn btn-primary">Ingresar</button>
	</form>
</body>
</html>
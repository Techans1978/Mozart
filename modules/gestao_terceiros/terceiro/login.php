<?php
session_start(); // Inicia a sessão
require 'lib/conexao.php'; // Arquivo de conexão com o banco de dados

// Verifica se o usuário já está logado
if (isset($_SESSION['user_id'])) {
    // Redireciona para o dashboard se já estiver logado
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Busca o usuário no banco de dados
    $sql = "SELECT id, password_hash FROM promotores WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verifica a senha
        if (password_verify($password, $user['password_hash'])) {
            // Armazena o ID do usuário na sessão
            $_SESSION['user_id'] = $user['id'];
            header("Location: dashboard.php"); // Redireciona para o dashboard
            exit();
        } else {
            echo "Usuário ou senha incorretos!";
        }
    } else {
        echo "Usuário ou senha incorretos!";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">

        <title>Gestão de Terceiros</title>

        <!-- Bootstrap Core CSS -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- MetisMenu CSS -->
        <link href="css/metisMenu.min.css" rel="stylesheet">

        <!-- Custom CSS -->
        <link href="css/startmin.css" rel="stylesheet">

        <!-- Custom Fonts -->
        <link href="css/font-awesome.min.css" rel="stylesheet" type="text/css">

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>

    <body>

        <div class="container">
            <div class="row">
                <div class="col-md-4 col-md-offset-4">
                    <div class="login-panel panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">Acesso Terceiros Grupo ABC</h3>
                        </div>
                        <div class="panel-body">
							<p><img class="logo-image d-none d-lg-inline-block" src="images/grupo-abc-logo.png" alt="Grupo ABC" width="150px" height="auto"></p>
							<fieldset>
							    <form method="post">
									<div class="form-group">
										<label for="username">E-mail:</label>
										<input class="form-control" type="text" id="username" name="username" required>
									</div>

									<div class="form-group">
										<label for="password">Senha:</label>
										<input class="form-control" type="password" id="password" name="password" required>
									</div>

									<button type="submit" class="btn btn-primary btn-block">Entrar</button>
								</form>
							</fieldset>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- jQuery -->
        <script src="js/jquery.min.js"></script>

        <!-- Bootstrap Core JavaScript -->
        <script src="js/bootstrap.min.js"></script>

        <!-- Metis Menu Plugin JavaScript -->
        <script src="js/metisMenu.min.js"></script>

        <!-- Custom Theme JavaScript -->
        <script src="js/startmin.js"></script>

    </body>

</html>

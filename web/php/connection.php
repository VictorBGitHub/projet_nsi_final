<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>Connection</title>
        <link href="../css/style.css" rel="stylesheet" />
    </head>
    <body>
        <div class="container">
            <form action="../includes/connection.php" method="POST">
                <!-- Titre -->
                <div>
                    <h1>Inscription :</h1>
                </div>
                <!-- Pseudo / Email -->
                <div>
                    <label>Pseudo / Email : </label>
                    <input type="text" name="pseudo_email" />
                </div>
                <!-- Mot de Passe -->
                <div>
                    <label>Password : </label>
                    <input type="text" name="password" />
                </div>
                <!-- Bouton -->
                <div>
                    <input type="submit" value="Ok" class="bouton_form" />
                </div>
            </form>
        </div>
    </body>
</html>
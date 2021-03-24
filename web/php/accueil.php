<?php
// on teste si le visiteur a soumis le formulaire de connexion
// TODO: $_POST['connexion'] jamais définie (et est-ce utile ?)
if (isset($_POST['connexion']) && $_POST['connexion'] == 'Connexion') {
	if (!empty($_POST['login']) && !empty($_POST['pass'])) {
		include_once("../../includes/bdd.php");
		$db = load_db("../../includes/config.json");

		// on teste si une entrée de la base contient ce couple login / pass
		$sql = 'SELECT count(*) FROM utilisateurs WHERE pseudo=? AND mdp=MD5(?)';
		$data = requete_prep($db, $sql, array($_POST['login'], $_POST['pass']));

		$db = null;

		// si on obtient une réponse, alors l'utilisateur est un membre
		if ($data[0] == 1) {
			session_start();
			$_SESSION['login'] = $_POST['login'];
			header('Location: membre.php');
			exit();
		}
		// si on ne trouve aucune réponse, le visiteur s'est trompé soit dans son login, soit dans son mot de passe
		elseif ($data[0] == 0) {
			$erreur = 'Compte non reconnu.';
		}
		// sinon, alors la, il y a un gros problème :)
		else {
			$erreur = 'Probème dans la base de données : plusieurs membres ont les mêmes identifiants de connexion.';
		}
	}
	else {
		$erreur = 'Au moins un des champs est vide.';
	}
}
?>

<html>
	<head>
        <meta charset="utf-8">
        <title> Accueil </title>

        <link href="style_co.css" rel="stylesheet">

    </head>
    <body>
        <form method="POST" action="">

            <section class="login">
                <div class="titre">Maths Quest</div>
                <form action="#" method="post">
                    <div class="bouton">Nom d'utilisateur</div>
                    <input type="text" required title="Username" placeholder="Username" name="login" data-icon="U"></br>
                    </br>
                    <div class="bouton">Mot de passe</div>
                    <input type="password" required title="Password" placeholder="Password" data-icon="x" name="pass">
                    <div class="oubli">
                        <div class="col"><a href="#" title="Retrouver mot de passe">Forgot Password ?</a></div>
                    </div>
                    <a href="#" class="envoyer">Submit</a>
                </form>
            </section>
        </form>
		<?php
			// TODO: Retirer pour la version finale
			if (!empty($erreur)){
				echo($erreur);
			}
		?>
    </body>
</html>
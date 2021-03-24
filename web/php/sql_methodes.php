<?php
/* définition de fonctions en lien avec SQL, appelées depuis les autres parties du programme */
// TODO: /!\ Fonction inutilisée : équivalent à /includes/bdd.php
function sql_connect() {
    // fonction permettant de se connecter à la BDD
    // Les lignes suivantes sont à modifier selon les besoins !
    $port = 3307;
    $dbname = 'projetclasse';
    $identifiant = 'projetclasse'; //cet utilisateur n'a qu'un seul droit : lecture sur base BLOC4
    $motdepasse = 'proj#17CLASSE';
    try
    {
        $sch='mysql:host=localhost;dbname='.$dbname.";port=".$port;
        $bdd = new PDO($sch , $identifiant, $motdepasse);
    }
    catch(Exception $e)
    {
        die('Erreur : '.$e->getMessage());
    }
    return $bdd ;
} /* se connecte à la bdd et renvoie l'objet PDO obtenu */
?>
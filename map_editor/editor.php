<?php
include "../includes/bdd.php";

$db = load_db();

$tx = 0;
$ty = 0;

$requete = "SELECT nom FROM terrain;";


?>
<html>
    <head>
        <meta charset="UTF-8" />
        <title>Editeur de map</title>
        <link href="editor.css" rel="stylesheet" />
    </head>
    <body>
        <!-- header -->
        <div>

            <div>

                <select>

                    <?php

                        // Il faudra peut-être changer les infos de la BDD
                        foreach(requete_prep($bdd, "SELECT id_region, nom_region FROM regions_map;") as $i=>$data){
                            $id = $data["id_region"];
                            $nom = $data["nom_region"];
                            echo "<option onclick='change_map($id)>$nom</option>";
                        }

                    ?>

                </select>

            </div>


        </div>
        <!-- main -->
        <div class="row">

            <!-- map -->

            <div>
                <!-- TODO -->
                <svg viewBox="0 0 100 100" id="kln" style="display:block;margin:auto;background:red;" xmlns="http://www.w3.org/2000/svg">
                    <?php
                        for($y=0; $y<$ty; $y++){
                            for($x=0; $x<$tx; $x++){
                                $cx = $x * $tc;
                                $cy = $y * $tc;
                                echo "<rect x=\"$cx\" y=\"$cy\" width=\"$tc\" height=\"$tc\" style=\"herbe\"></rect>";
                            }
                        }
                    ?>
                </svg>
            </div>

            <!-- tiles menu -->

            <div>

                <!-- TODO -->

            </div>

        </div>
    </body>
</html>
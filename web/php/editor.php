<?php
include_once "../../includes/init.php";
include_once "../../includes/bdd.php";

$db = load_db("../../includes/config.json");

// Pour l'instant, on va rester simple
// après, on pourra utiliser des tokens, des clés des sessions etc...
// Pour l'instant, juste admin
// Par contre, il faudra aussi veiller a ce que le compte ne reste pas trop inactif.
if(!isset($_SESSION["id_admin"])){
    $_SESSION["error"] = "Vous n'êtes pas connecté en tant qu'administrateur !";
    header("Location: admin_connect.php");
    die();
}

$requete = "SELECT * FROM terrain;";
$terrains = array();
$r = requete_prep($db, $requete);
if($r == NULL){
    alert("Il y a eu une erreur !");
}
foreach($r as $i => $data){
    $nom = $data["nom"];
    $img = $data["image_"];
    $terrains[$data["id_terrain"]] = array("nom"=>$nom, "img"=>$img);
}

//


$requete = "SELECT * FROM objets;";
$objets = array();
$r = requete_prep($db, $requete);
if($r == NULL){
    alert("Il y a eu une erreur !");
}
foreach($r as $i => $data){
    $nom = $data["nom"];
    $img = $data["image_"];
    $objets[$data["id_objet"]] = array("id_objet"=>$data["id_objet"], "nom"=>$nom,
                                       "img"=>$img, "z_index"=>$data["z_index"]);
}

//

$liste_regions = array();
foreach(requete_prep($db, "SELECT * FROM regions") as $i=>$data){
    //$liste_regions[$data["nom"]]=$data["id_region"];
    $liste_regions[$data["id_region"]]=$data["nom"];
}

$region_selected = "";
$id_region = 0;
if(isset($_POST["region_selected"])){
    if(in_array($_POST["region_selected"], array_keys($liste_regions))){
        $region_selected = $_POST["region_selected"];
        $id_region = $liste_regions[$region_selected];
    }
}

if(isset($_POST["delete_region"])){
    if(in_array($_POST["delete_region"], array_keys($liste_regions))){
        $idr = $_POST["delete_region"];
        //
        $query = "DELETE FROM regions WHERE id_region=:idr";
        $vars = array(":idr"=>$idr);
        $query2 = "DELETE FROM regions_terrains WHERE id_region=:idr";
        $vars2 = array(":idr"=>$idr);
        $query3 = "DELETE FROM regions_objets WHERE id_region=:idr";
        $vars3 = array(":idr"=>$idr);
        if(!action_prep($db, $query3, $vars3) || !action_prep($db, $query2, $vars2) || !action_prep($db, $query, $vars)){
            alert("Il y a eu une erreur lors de la suppression de la région !");
        }
        else{
            unset($liste_regions[$_POST["delete_region"]]);
        }
    }
    else{
        alert("La région n'existe pas !");
    }
}

if(isset($_POST["new_region"])){
    if($_POST["new_region"]!="" && !in_array($_POST["new_region"], array_values($liste_regions))){
        $query = "INSERT INTO regions SET nom=:nom, tx=100, ty=100;";
        $vars = array(":nom"=>$_POST["new_region"]);
        if(!action_prep($db, $query, $vars)){
            alert("Il y a eu une erreur lors de la création de la région !");
        }
        else{
            $region_selected = $db->lastInsertId();
            $liste_regions[$db->lastInsertId()]=$_POST["new_region"];
        }
    }
    else{
        alert("Une région porte déjà le même nom !");
    }
}

$cases_terrains = array();
$cases_objets = array();

// echo "Post : <br />";
// foreach($_POST as $k => $v){
//     echo "$k = $v <br />";
// }

if(isset($_POST["save_terrain"]) && isset($_POST["delete_terrains"]) && isset($_POST["update_terrains"]) &&
   isset($_POST["new_terrains"])  && isset($_POST["delete_objets"]) && isset($_POST["update_objets"])  &&
   isset($_POST["new_objets"]) ){
    // echo "SAVE TERRAIN ! <br />";
    $idr = $_POST["save_terrain"];
    $region_selected = $idr;
    // Pour changer si on veut passer en requetes préparée, plus de calcul, mais plus de sécurité
    $mode = 0; // normal = 0 sinon préparé = 1
    $delete_t = json_decode($_POST["delete_terrains"], true);
    $delete_o = json_decode($_POST["delete_objets"], true);
    $update_t = json_decode($_POST["update_terrains"], true);
    $update_o = json_decode($_POST["update_objets"], true);
    $new_t = json_decode($_POST["new_terrains"], true);
    $new_o = json_decode($_POST["new_objets"], true);
    $iu_t = $new_t + $update_t;
    $iu_o = $new_o + $update_o;
    /***************** DELETE TERRAINS : *******************/
    if(count($delete_t)>0){
        $req = "DELETE FROM regions_terrains WHERE (x,y,id_region) IN ( ";
        $virgule = false;
        $vars = array();
        // Pour requete_prep:
        $compteur = 0;
        foreach($delete_t as $i => $data){
            if(!$virgule){
                $virgule = true;
            }
            else{
                $req .= ", ";
            }
            // pour requete non préparée
            if($mode == 0){
                $req .= "( " . $data["x"] . ", " . $data["y"] . ", " . $data["id_region"] . " )";
            }
            else{
                $req .= "(:x_$compteur, :y_$compteur, :idr_$compteur)";
                $vars[":x_$compteur"] = $data["x"];
                $vars[":y_$compteur"] = $data["y"];
                $vars[":idr_$compteur"] = $data["id_region"];
                $compteur += 1;
            }
        }
        $req .= " );";
        // echo "delete terrains : $req <br />";
        if(!action_prep($db, $req, $vars)){
            echo "probleme delete terrains <br />";
            die();
        }
    }

    /***************** DELETE OBJETS : *******************/
    if(count($delete_o)>0){
        $req = "DELETE FROM regions_objets WHERE (x,y,id_region) IN ( ";
        $virgule = false;
        $vars = array();
        // Pour requete_prep:
        $compteur = 0;
        foreach($delete_o as $i=>$data){
            if(!$virgule){
                $virgule = true;
            }
            else{
                $req .= ", ";
            }
            // pour requete non préparée
            if($mode == 0){
                $req .= "( " . $data["x"] . ", " . $data["y"] . ", " . $data["id_region"] . " )";
            }
            else{
                $req .= "(:x_$compteur, :y_$compteur, :idr_$compteur)";
                $vars[":x_$compteur"] = $data["x"];
                $vars[":y_$compteur"] = $data["y"];
                $vars[":idr_$compteur"] = $data["id_region"];
                $compteur += 1;
            }
        }
        $req .= " );";
        // echo "delete objets : $req <br />";
        if(!action_prep($db, $req, $vars)){
            echo "probleme delete objets <br />";
            die();
        }
    }

    /***************** INSERT/UPDATE NEW TERRAINS : *******************/
    if(count($iu_t)>0){
        $req = "INSERT INTO regions_terrains (x,y,id_region,id_terrain) VALUES ";
        $virgule = false;
        $vars = array();
        // Pour requete_prep:
        $compteur = 0;
        foreach($iu_t as $i=>$data){
            if(!$virgule){
                $virgule=true;
            }
            else{
                $req .= ", ";
            }
            // pour requete non préparée
            if($mode == 0){
                $req .= "( " . $data["x"] . ", " . $data["y"] . ", " . $data["id_region"] . ", " .
                        $data["id_terrain"] . " )";
            }
            else{
                $req .= "(:x_$compteur, :y_$compteur, :idr_$compteur, :idt_$compteur)";
                $vars[":x_$compteur"] = $data["x"];
                $vars[":y_$compteur"] = $data["y"];
                $vars[":idr_$compteur"] = $data["id_region"];
                $vars[":idt_$compteur"] = $data["id_terrain"];
                $compteur += 1;
            }
        }
        $req .= " ON DUPLICATE KEY UPDATE id_terrain=VALUES(id_terrain);";
        // echo "insert terrains : $req <br />";
        if(!action_prep($db, $req, $vars)){
            echo "probleme insert/update terrains  <br />";
            die();
        }
    }


    /***************** INSERT/UPDATE NEW OBJETS : *******************/
    if(count($iu_o)>0){
        $req = "INSERT INTO regions_objets (x,y,id_region,id_objet) VALUES ";
        $virgule = false;
        $vars = array();
        // Pour requete_prep:
        $compteur = 0;
        foreach($iu_o as $i => $data){
            if(!$virgule){
                $virgule = true;
            }
            else{
                $req .= ", ";
            }
            // pour requete non préparée
            if($mode == 0){
                $req .= "( " . $data["x"] . ", " . $data["y"] . ", " . $data["id_region"] . ", " .
                        $data["id_objet"] . " )";
            }
            else{
                $req .= "(:x_$compteur, :y_$compteur, :idr_$compteur, :ido_$compteur)";
                $vars[":x_$compteur"] = $data["x"];
                $vars[":y_$compteur"] = $data["y"];
                $vars[":idr_$compteur"] = $data["id_region"];
                $vars[":ido_$compteur"] = $data["id_objet"];
                $compteur += 1;
            }
        }
        $req .= " ON DUPLICATE KEY UPDATE id_objet=VALUES(id_objet);";
        // echo "insert objets : $req <br />";
        if(!action_prep($db, $req, $vars)){
            echo "probleme insert/update objets <br />";
            die();
        }
    }

}

if(isset($_POST["import_data"]) && isset($_POST["import_region"])){
    $content = $_POST["import_data"];
    $data = json_decode($content, true);
    $cases_terrains = $data["terrains"];
    $cases_objets = $data["objets"];
    $id_region = $_POST["import_region"];
    // Pour changer si on veut passer en requetes préparée, plus de calcul, mais plus de sécurité
    $mode = 0; // normal = 0 sinon préparé = 1
    // On supprime tout:
    $query = "DELETE FROM regions_terrains WHERE id_region=:idr;";
    $query2 = "DELETE FROM regions_objets WHERE id_region=:idr;";
    $vars = array(":idr"=>$id_region);
    //
    if(!action_prep($db, $query, $vars)){
        echo "probleme delete regions_terrains <br />";
        die();
    }
    if(!action_prep($db, $query2, $vars)){
        echo "probleme delete regions_objets <br />";
        die();
    }
    // On crée tout


    if(count($cases_terrains)>0){
        $req = "INSERT INTO regions_terrains (x,y,id_region,id_terrain) VALUES ";
        $virgule = false;
        $vars = array();
        if($mode == 1){
            $vars[":idr"]=$id_region;
        }
        // Pour requete_prep:
        $compteur = 0;
        foreach($cases_terrains as $i => $data){
            if(!$virgule){
                $virgule = true;
            }
            else{
                $req.=", ";
            }
            // pour requete non préparée
            if($mode == 0){
                $req .= "( " . $data["x"] . ", " . $data["y"] . ", " . $id_region . ", " . $data["id_terrain"] . " )";
            }
            else{
                $req .= "(:x_$compteur, :y_$compteur, :idr, :idt_$compteur)";
                $vars[":x_$compteur"] = $data["x"];
                $vars[":y_$compteur"] = $data["y"];
                $vars[":idt_$compteur"] = $data["id_terrain"];
                $compteur += 1;
            }
        }
        $req .= ";";
        // echo "insert objets : $req <br />";
        if(!action_prep($db, $req, $vars)){
            echo "probleme import insert terrain <br />";
            die();
        }
    }

    if(count($cases_objets)>0){
        $req = "INSERT INTO regions_objets (x,y,id_region,id_objet) VALUES ";
        $virgule = false;
        $vars = array();
        if($mode == 1){
            $vars[":idr"] = $id_region;
        }
        // Pour requete_prep:
        $compteur = 0;
        foreach($cases_objets as $i => $data){
            if(!$virgule){
                $virgule = true;
            }
            else{
                $req .= ", ";
            }
            // pour requete non préparée
            if($mode == 0){
                $req .= "( " . $data["x"] . ", " . $data["y"] . ", " . $id_region . ", " . $data["id_objet"] . " )";
            }
            else{
                $req .= "(:x_$compteur, :y_$compteur, :idr, :ido_$compteur)";
                $vars[":x_$compteur"] = $data["x"];
                $vars[":y_$compteur"] = $data["y"];
                $vars[":ido_$compteur"] = $data["id_objet"];
                $compteur += 1;
            }
        }
        $req .= ";";
        // echo "insert objets : $req <br />";
        if(!action_prep($db, $req, $vars)){
            echo "probleme import insert objets <br />";
            die();
        }
    }

    $region_selected = $id_region;

}

if($region_selected != ""){
    $requested = "SELECT * FROM regions_terrains WHERE id_region=:idr";
    $vars = array(":idr" => $region_selected);
    foreach(requete_prep($db, $requested, $vars) as $i=>$data){
        $x = $data["x"];
        $y = $data["y"];
        $tile = $data["id_terrain"];
        $cases_terrains["$x-$y"] = array("x" => $x, "y" => $y, "id_terrain" => $tile);
    }
    $requested = "SELECT * FROM regions_objets WHERE id_region=:idr";
    $vars = array(":idr" => $region_selected);
    foreach(requete_prep($db, $requested, $vars) as $i => $data){
        $x = $data["x"];
        $y = $data["y"];
        $ido = $data["id_objet"];
        $cases_objets["$x-$y"] = array("x" => $x, "y" =>$y , "id_objet" => $ido);
    }
    script("var nom_region=\"" . $liste_regions[$region_selected] . "\"");
}
else{
    script("var nom_region=\"\"");
}


$jsone = json_encode($terrains);
script("var terrains = JSON.parse('$jsone');");


$jsone = json_encode($objets);
script("var objets = JSON.parse('$jsone');");

if(count($cases_terrains) > 0){
    $jsone = json_encode($cases_terrains);
    script("var cases_terrains = JSON.parse('$jsone');");
}
else{
    script("var cases_terrains = {};");
}


if(count($cases_objets) > 0){
    $jsone = json_encode($cases_objets);
    script("var cases_objets = JSON.parse('$jsone');");
}
else{
    script("var cases_objets = {};");
}

?>
<html>
    <style>
body {
    overflow: hidden;
}

    </style>
    <head>
        <meta charset="UTF-8" />
        <title>Editeur de map</title>
        <link href="../css/editor.css" rel="stylesheet" />
    </head>
    <body>
        <!-- header -->
        <div>

            <div class="row">

                <select id="region_sel" onchange="change_map()">

                    <option value="" <?php if($region_selected == ""){ echo "selected"; } ?>>Aucune</option>
                    <?php

                        // Il faudra peut-être changer les infos de la BDD
                        foreach($liste_regions as $idr => $nom){
                            $sel = "";
                            if($idr == $region_selected){
                                $sel = "selected";
                            }
                            echo "<option value=$idr $sel>" . $liste_regions[$idr] . "</option>";
                        }

                    ?>

                </select>

                <div>
                    <?php

                    if($region_selected != ""){
                        echo "<button onclick=\"delete_region();\">Supprimer la région choisie</button>";
                        echo "<button onclick=\"save_tiles();\">Sauvegarder la région choisie</button>";
                    }

                    ?>
                </div>

                <div class="row">
                    <label>New region</label>
                    <input id="new_region_name" type="text" placeholder="nom de la region">
                    <button onclick="new_region();">Créer</button>
                </div>

                <div class="row">
                    <button onclick="export_region();">Export region</button>
                    <input id="file_import" style="display:none;" type="file" accept=".json">
                    <button onclick="import_region();">Import region</button>
                </div>

            </div>


        </div>
        <!-- main -->
        <div class="row">

            <!-- map -->

            <div style="overflow:auto;width:100%;height:90%;">

            <?php
                if($region_selected!=""){
                    echo "<svg viewBox=\"0 0 100 80\" id=\"viewport\" onmouseleave=\"is_clicking=false;\" style=\"background:white;border:1px solid black;\" xmlns=\"http://www.w3.org/2000/svg\">";
                    $tx = 20;
                    $ty = 16;
                    $tc = 5;
                    $dx = 0;
                    $dy = 0;
                    // terrains
                    for($x = 0; $x < $tx; $x++){
                        for($y = 0; $y < $ty; $y++){
                            $cx = $x * $tc + $dx;
                            $cy = $y * $tc + $dy;
                            $idd = "$x-$y";
                            $src="../imgs/tuiles/vide.png";
                            if(isset($cases_terrains[$idd])){
                                $img = $terrains[$cases_terrains[$idd]["id_terrain"]]["img"];
                                $src = "../imgs/tuiles/$img";
                            }
                            $ct = $tc + 0.15;
                            echo "<image z_index=0 id=\"$x-$y\" xlink:href=\"$src\" x=\"$cx\" y=\"$cy\" width=\"$ct\" height=\"$ct\" onmouseover=\"mo($x,$y);\" onmouseout=\"ml($x,$y);\" class=\"case\"></image>";
                        }
                    }
                    // objets
                    for($x = 0; $x < $tx; $x++){
                        for($y = 0; $y < $ty; $y++){
                            $cx = $x * $tc + $dx;
                            $cy = $y * $tc + $dy;
                            $idd = "$x-$y";
                            $src = "";
                            if(isset($cases_objets[$idd])){
                                $img = $objets[$cases_objets[$idd]["id_objet"]]["img"];
                                $src = "../imgs/objets/$img";
                            }
                            $ct = $tc + 0.15;
                            echo "<image z_index=0 id=\"o_$x-$y\" xlink:href=\"$src\" x=\"$cx\" y=\"$cy\" width=\"$ct\" height=\"$ct\" onmouseover=\"mo($x,$y);\" onmouseout=\"ml($x,$y);\" class=\"case\"></image>";
                        }
                    }
                    echo "</svg>";
                }
                else{
                    echo "<p>Aucune région n'a été choisie</p>";
                }
            ?>
            <div class="row">
                <p>Case hover: <span id="hover_case">aucune</span></p>
                <hr />
                <p>Nombre de modifications: <span id="nb_modifs">0</span></p>
                <hr />
                <b id="alert_modifs" style="color:red; display:none;">Vous avez fait plus de 100 modifs, il faudrait peut-être penser à sauvegarder !</b>
            </div>
            </div>

            <!-- tiles menu -->

            <div style="overflow:scroll;width:100%;height:500px;">

                <!-- tile selected to paint -->
                <div>

                </div>

                <!-- Select tiles -->

                <div class="liste_tiles">

                    <div class="row">
                        <button onclick="set_selection('terrains');">Terrains</button>
                        <button onclick="set_selection('objets');">Objets</button>
                    </div>

                    <div id="terrains">
                        <div class="row"> <input id="search_t" type="text" placeholder="search" onkeypress="search_t();" onchange="search_t();" /> <p>Press Enter to search</p></div>
                        <?php
                            foreach($terrains as $i=>$data){
                                $img = $data["img"];
                                $nom = $data["nom"];
                                $sel = "";
                                if($i == 0){ // au début, l'herbe sera selectionne par defaut
                                    $sel = "liste_element_selectione";
                                }
                                echo "<div value=\"$nom\" id=\"liste_elt_$i\" class=\"liste_terrains liste_element $sel\" onclick=\"select_tile($i);\"><img class=\"img_liste_element\" src=\"../imgs/tuiles/$img\" /><label>$nom</label></div>";
                            }

                        ?>

                    </div>

                    <div id="objets" style="display:none;">
                        <div class="row"> <input id="search_o" type="text" placeholder="search" onkeypress="search_o();" onchange="search_o();" /> <p>Press Enter to search</p></div>
                        <?php
                            foreach($objets as $i=>$data){
                                $img = $data["img"];
                                $nom = $data["nom"];
                                $sel = "";
                                // $ido = $data["id_objet"];
                                echo "<div value=\"$nom\" id=\"liste_obj_$i\" class=\"liste_objets liste_element $sel\" onclick=\"select_objets($i);\"><img class=\"img_liste_element\" src=\"../imgs/objets/$img\" /><label>$nom</label></div>";
                            }

                        ?>

                    </div>

                </div>

            </div>


        </div>
    </body>
</html>
<script>

const id_region = <?php if($region_selected != ""){ echo $region_selected; } else { echo "null"; } ?>;

var dcx = null;
var dcy = null;

var tile_selected = 0;
var tp_selected = "terrains";
var dec_x = 0;
var dec_y = 0;

var is_clicking = false;
var hx=null;
var hy=null;
if(document.getElementById("viewport")){
    var viewport = document.getElementById("viewport");
}
else{
    var viewport = null;
}

var compteur_modif = 0;

var update_t = {};
var new_t = {};
var delete_t = {};

var update_o = {};
var new_o = {};
var delete_o = {};

// TODO: Attention : fonction jamais utilisée
function arrayRemove(arr, value) {
    return arr.filter(function(ele){
        return ele != value;
    });
}

if(viewport != null){

    viewport.addEventListener('mousedown', e => {
        dcx, dcy = null, null;
        if(hx != null && hy != null){
            change_case(hx, hy);
        }
        is_clicking = true;
    });

    viewport.addEventListener('mousemove', e => {
        if (is_clicking === true && (dcx != hx || dcy != hy)) {
            if(hx != null && hy != null){
                change_case(hx,hy);
            }
        }
    });

    viewport.addEventListener('mouseup', e => {
        if (is_clicking === true) {
            is_clicking = false;
        }
    });

}
function mo(cx,cy){
    hx = cx;
    hy = cy;
}

function ml(cx,cy){
    document.getElementById("hover_case").innerHTML = "x : " + (dec_x + cx) + " , y : " + (dec_y + cy);
    if(hx == cx && hy == cy){
        hx = null;
        hy = null;
    }
}


function change_map(){
    var nom = document.getElementById("region_sel").value;
    var f = document.createElement("form");
    f.setAttribute("style","display:none;")
    f.setAttribute("method", "POST");
    f.setAttribute("action", "editor.php");
    var i = document.createElement("input");
    i.setAttribute("name", "region_selected");
    i.value = nom;
    f.appendChild(i);
    document.body.appendChild(f);
    f.submit();
}

function change_case(x, y){
    //
    // console.log(x,y);
    //
    var cx = x + dec_x;
    var cy = y + dec_y;
    dcx, dcy = cx, cy;
    var i = "" + cx + "-" + cy;
    if(tile_selected == 0){
        if(tp_selected == "terrains"){
            if(Object.keys(cases_terrains).includes(i)){
                if(Object.keys(update_t).includes(i)){
                    delete update_t[i];
                    compteur_modif -= 1;
                }
                if(!Object.keys(delete_t).includes(i)){
                    delete_t[i] = {"x": cx, "y": cy, "id_region": id_region};
                    compteur_modif += 1;
                }
                var e = document.getElementById("" + x + "-" + y);
                e.setAttribute("xlink:href", "../imgs/tuiles/vide.png");
            }
            else{
                if(Object.keys(new_t).includes(i)){
                    delete new_t[i];
                    compteur_modif -= 1;
                }
                var e = document.getElementById("" + x + "-" + y);
                e.setAttribute("xlink:href","../imgs/tuiles/vide.png");
            }
        }
        else if(tp_selected == "objets"){
            if(Object.keys(cases_objets).includes(i)){
                if(Object.keys(update_o).includes(i)){
                    delete update_o[i];
                    compteur_modif -= 1;
                }
                if(!Object.keys(delete_o).includes(i)){
                    delete_o[i] = {"x": cx, "y": cy, "id_region": id_region};
                    compteur_modif += 1;
                }
                var e = document.getElementById("o_" + x + "-" + y);
                e.setAttribute("xlink:href","../imgs/objets/rien.png");
            }
            else{
                if(Object.keys(new_o).includes(i)){
                    delete new_o[i];
                    compteur_modif -= 1;
                }
                var e = document.getElementById("o_"+x+"-"+y);
                e.setAttribute("xlink:href","../imgs/objets/rien.png");
            }
        }
    }
    else{
        if(tp_selected=="terrains"){
            if(Object.keys(cases_terrains).includes(i)){
                if(cases_terrains[i]["id_terrain"]!=tile_selected){
                    if(!Object.keys(update_t).includes(i)){
                        compteur_modif += 1;
                    }
                    update_t[i] = {"x":cx, "y":cy, "id_terrain":tile_selected, "id_region": id_region};
                }
            }
            else{
                if(!Object.keys(new_t).includes(i)){
                    compteur_modif += 1;
                }
                new_t[i] = {"x":cx, "y":cy, "id_terrain":tile_selected, "id_region": id_region};
            }
            // cases_terrains[i] = {"x":cx, "y":cy, "id_terrain":tile_selected};
            var e = document.getElementById(""+x+"-"+y);
            e.setAttribute("xlink:href","../imgs/tuiles/"+terrains[tile_selected]["img"]);
        }else if(tp_selected=="objets"){
            // cases_objets[i] = {"x":cx, "y":cy, "id_objet":tile_selected};
            if(Object.keys(cases_objets).includes(i)){
                if(cases_objets[i]["id_objet"] != tile_selected){
                    if(!Object.keys(update_o).includes(i)){
                        compteur_modif += 1;
                    }
                    update_o[i] = {"x": cx, "y": cy, "id_objet": tile_selected, "id_region": id_region};
                }
            }
            else{
                if(!Object.keys(new_o).includes(i)){
                    compteur_modif += 1;
                }
                new_o[i] = {"x": cx, "y": cy, "id_objet": tile_selected, "id_region": id_region};
            }
            var e = document.getElementById("o_" + x + "-" + y);
            e.setAttribute("xlink:href", "../imgs/objets/" + objets[tile_selected]["img"]);
        }
    }
    document.getElementById("nb_modifs").innerHTML = compteur_modif;
    if(compteur_modif >= 100){
        document.getElementById("alert_modifs").style.display = "initial";
    }
    else{
        document.getElementById("alert_modifs").style.display = "none";
    }
}

function aff(){
    var tx = 20;
    var ty = 16;
    var tc = 5;
    for(x = 0; x < tx; x++){
        for(y = 0; y < ty; y++){
            var cx = x + dec_x;
            var cy = y + dec_y;
            var ii = "" + cx + "-" + cy;
            img = "vide.png";
            if(Object.keys(cases_terrains).includes(ii) && !Object.keys(delete_t).includes(ii)){
                if(Object.keys(update_t).includes(ii)){
                    img = terrains[update_t[ii]["id_terrain"]]["img"];
                }else{
                    img = terrains[cases_terrains[ii]["id_terrain"]]["img"];
                }
            }
            if(Object.keys(new_t).includes(ii) ){
                img = terrains[new_t[ii]["id_terrain"]]["img"];
            }
            document.getElementById("" + x + "-" + y).setAttribute("xlink:href","../imgs/tuiles/" + img);
            //
            img = "rien.png"
            if(Object.keys(cases_objets).includes(ii) && !Object.keys(delete_o).includes(ii)){
                if(Object.keys(update_o).includes(ii)){
                    img = objets[update_o[ii]["id_objet"]]["img"];
                }else{
                    img = objets[cases_objets[ii]["id_objet"]]["img"];
                }
            }
            if(Object.keys(new_o).includes(ii) ){
                img = objets[new_o[ii]["id_objet"]]["img"];
            }
            document.getElementById("o_" + x + "-" + y).setAttribute("xlink:href","../imgs/objets/" + img);
        }
    }
}

function new_region(){
    var nom = document.getElementById("new_region_name").value;
    var f = document.createElement("form");
    f.setAttribute("style","display:none;")
    f.setAttribute("method", "POST");
    f.setAttribute("action", "editor.php");
    var i = document.createElement("input");
    i.setAttribute("name", "new_region");
    i.value = nom;
    f.appendChild(i);
    document.body.appendChild(f);
    f.submit();
}

function delete_region(){
    var nom = "<?php echo $region_selected; ?>";
    var f = document.createElement("form");
    f.setAttribute("style","display:none;")
    f.setAttribute("method", "POST");
    f.setAttribute("action", "editor.php");
    var i = document.createElement("input");
    i.setAttribute("name", "delete_region");
    i.value = nom;
    f.appendChild(i);
    document.body.appendChild(f);
    f.submit();
}

function select_tile(id_tile){
    if(id_tile == tile_selected && tp_selected == "terrains"){
        return;
    }
    if(tp_selected == "terrains"){
        var ad = document.getElementById("liste_elt_" + tile_selected);
    }
    else{
        var ad = document.getElementById("liste_obj_" + tile_selected);
    }
    ad.classList.remove("liste_element_selectione");
    var d = document.getElementById("liste_elt_" + id_tile);
    d.classList.add("liste_element_selectione");
    tile_selected = id_tile;
    tp_selected = "terrains";
}


function select_objets(id_tile){
    if(id_tile == tile_selected && tp_selected == "objets"){
        return;
    }
    if(tp_selected == "terrains"){
        var ad = document.getElementById("liste_elt_" + tile_selected);
    }
    else{
        var ad = document.getElementById("liste_obj_" + tile_selected);
    }
    ad.classList.remove("liste_element_selectione");
    var d = document.getElementById("liste_obj_" + id_tile);
    d.classList.add("liste_element_selectione");
    tile_selected = id_tile;
    tp_selected = "objets";
}

function save_tiles(){
    var idr = "<?php echo $region_selected; ?>";
    var f = document.createElement("form");
    f.setAttribute("style","display:none;")
    f.setAttribute("method", "POST");
    f.setAttribute("action", "editor.php");
    var i = document.createElement("input");
    i.setAttribute("name", "save_terrain");
    i.setAttribute("value", idr);
    f.appendChild(i);

    var liste_donnees = [
        ["delete_terrains", delete_t],
        ["update_terrains", update_t],
        ["new_terrains", new_t],

        ["delete_objets", delete_o],
        ["update_objets", update_o],
        ["new_objets", new_o]
    ]

    for([nom,data] of liste_donnees){
        var ii = document.createElement("input");
        ii.setAttribute("name", nom);
        ii.setAttribute("value", JSON.stringify(data));
        // ii.value=JSON.stringify(data);
        f.appendChild(ii);
    }

    document.body.appendChild(f);
    console.log(f);
    f.submit();
}

function set_selection(ii){
    for(i of ["terrains", "objets"]){
        if(i == ii){
            document.getElementById(i).style.display = "initial";
        }
        else{
            document.getElementById(i).style.display = "none";
        }
    }
}

function download_text(filename, text) {
    var element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
    element.setAttribute('download', filename);

    element.style.display = 'none';
    document.body.appendChild(element);

    element.click();

    document.body.removeChild(element);
}

function export_region(){
    var texte = {"terrains": cases_terrains, "objets": cases_objets};
    var texte = JSON.stringify(texte);
    if(compteur_modif == 0 || confirm("Ceci n'exportera pas les dernières modifications non sauvegardées, voulez-vous quand même exporter cette région ?")){
        download_text("exported_region_" + nom_region + "_.json", texte);
    }
}

function handleFileSelect (e) {
    var files = e.target.files;
    if (files.length < 1) {
        alert('select a file...');
        return;
    }
    var file = files[0];
    var reader = new FileReader();
    reader.onload = onFileLoaded;
    reader.readAsDataURL(file);
}

function onFileLoaded (e) {
    var match = /^data:(.*);base64,(.*)$/.exec(e.target.result);
    // var match = e.target.result;
    if (match == null) {
        throw 'Could not parse result'; // should not happen
    }
    var mimeType = match[1];
    var content = atob(match[2]);
    var confirmation = confirm("Êtes vous bien sur de remplacer tout le contenu de la région actuelle par le contenu du fichier ?");
    if(confirmation){
        var f = document.createElement("form");
        f.setAttribute("method", "POST");
        f.setAttribute("action", "editor.php");
        f.style.display = "none";
        var i = document.createElement("input");
        i.setAttribute("name", "import_data");
        i.setAttribute("value", content);
        f.appendChild(i);
        var ii = document.createElement("input");
        ii.setAttribute("name", "import_region");
        ii.setAttribute("value", id_region);
        f.appendChild(ii);
        document.body.appendChild(f);
        f.submit();
    }

}

var fi = document.getElementById("file_import");
fi.onchange = handleFileSelect;

function import_region(){
    fi.click();
}

function search_t(){
    var research = document.getElementById("search_t").value;
    for(el of document.getElementsByClassName("liste_terrains")){
        if(el.getAttribute("value").startsWith(research)){
            el.style.display = "inline-flex";
        }
        else{
            el.style.display = "none";
        }
    }
}

function search_o(){
    var research = document.getElementById("search_o").value;
    for(el of document.getElementsByClassName("liste_objets")){
        if(el.getAttribute("value").startsWith(research)){
            el.style.display = "inline-flex";
        }
        else{
            el.style.display = "none";
        }
    }
}

document.addEventListener('keydown', (event) => {
    const nomTouche = event.key;
    if (nomTouche === 'ArrowUp') {
        dec_y -= 1;
        aff();
    }
    else if (nomTouche === 'ArrowDown') {
        dec_y += 1;
        aff();
    }
    else if (nomTouche === 'ArrowLeft') {
        dec_x -= 1;
        aff();
    }
    else if (nomTouche === 'ArrowRight') {
        dec_x += 1;
        aff();
    }
}, false);

document.addEventListener('keyup', (event) => {
    const nomTouche = event.key;
}, false);

</script>
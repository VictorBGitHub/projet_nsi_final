/**
 *
 * FONCTIONS POUR AFFICHER/METTRE A JOUR LES INFOS SUR LA PAGE
 *
 */


function update_life(vie, vie_tot) {
    document.getElementById("vie_value").innerHTML = "" + vie + "/" + vie_tot;
    document.getElementById("progress_vie").value = vie / vie_tot * 100.0;
}

function update_mana(mana, mana_tot) {
    document.getElementById("mana_value").innerHTML = "" + mana + "/" + mana_tot;
    document.getElementById("progress_mana").value = mana / mana_tot * 100.0;
}

function update_xp(xp, xp_tot) {
    document.getElementById("exp_value").innerHTML = "" + xp + "/" + xp_tot;
    document.getElementById("progress_exp").value = xp / xp_tot * 100.0;
}

function update_niveau(niv) {
    document.getElementById("niveau_profil").value = niv;
}

function update_region_name(name) {
    document.getElementById("region_name").value = name;
}

function update_region_count(num) {
    document.getElementById("region_player_number").value = num;
}

/**
 *
 * KEY INPUTS
 *
 */


document.addEventListener('keydown', (event) => {
    const nomTouche = event.key;
    if (nomTouche === 'ArrowUp') {
        ws_send({ "action": "deplacement", "deplacement": [0, -1] });
    } else if (nomTouche === 'ArrowDown') {
        ws_send({ "action": "deplacement", "deplacement": [0, 1] });
    } else if (nomTouche === 'ArrowLeft') {
        ws_send({ "action": "deplacement", "deplacement": [-1, 0] });
    } else if (nomTouche === 'ArrowRight') {
        ws_send({ "action": "deplacement", "deplacement": [1, 0] });
    }
}, false);

document.addEventListener('keyup', (event) => {
    const nomTouche = event.key;
}, false);
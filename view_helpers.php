<?php
function renderStatus($game, $winner, $playerSymbol) {
    if ($winner) {
        return '<h1 class="gagnant">Le joueur ' . htmlspecialchars($winner) . ' a gagné !</h1>';
    } elseif ($game['status'] === 'egalite') {
        return '<h1 class="egalite">Match nul !</h1>';
    } elseif ($game['status'] === 'attente_joueur_o') {
        return '<h1 class="attente">En attente du joueur O - Partagez le lien pour inviter un adversaire</h1>';
    } elseif ($game['status'] === 'en_cours') {
        $isYourTurn = $game['active_player'] === $playerSymbol;
        if ($isYourTurn) {
            return '<h1>C\'est votre tour (' . htmlspecialchars($playerSymbol) . ')</h1>';
        } else {
            return '<h1>Tour du joueur ' . htmlspecialchars($game['active_player']) . '</h1>';
        }
    }
    return '<h1>État du jeu inconnu</h1>';
}

function renderGrid($grid, $canPlay, $playerSymbol) {
    $output = '';
    for ($i = 0; $i < 3; $i++) {
        $output .= '<div class="row">';
        for ($j = 0; $j < 3; $j++) {
            $cellValue = isset($grid[$i][$j]) ? $grid[$i][$j] : '';
            $isDisabled = ($cellValue !== null) || !$canPlay;
            $output .= sprintf(
                '<button type="submit" class="case %s" name="position" value="%d,%d" %s>%s</button>',
                $isDisabled ? '' : '',
                $i,
                $j,
                $isDisabled ? '' : '',
                htmlspecialchars($cellValue)
            );
        }
        $output .= '</div>';
    }
    return $output;
}


function renderPlayerInfo($isSpectateur, $playerSymbol) {
    if ($isSpectateur) {
        return '<p class="spectateur">Vous êtes spectateur - la partie est complète</p>';
    } else {
        return '<p>Vous jouez les <strong>' . htmlspecialchars($playerSymbol) . '</strong></p>';
    }
}

function renderWaitingMessage($game) {
    if ($game['status'] === 'attente_joueur_o') {
        return '<p class="attente">En attente d\'un deuxième joueur... Partagez cette URL pour inviter quelqu\'un !</p>';
    }
}

function gridHelper($grid) {
    return json_encode($grid);
}

$mustache = new Mustache_Engine([
    'helpers' => [
        'dump' => 'gridHelper'
    ]
]);
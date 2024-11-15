<?php
require_once './vendor/autoload.php';
require_once './config/database.php';
require_once 'game_logic.php';
require_once 'player_management.php';
require_once 'view_helpers.php';

$gameId = $_GET['gameId'] ?? null;
if (!$gameId) {
    setcookie('playerId', '', time() - 3600, "/");
    $gameId = uniqid('game_');
    header("Location: index.php?gameId=" . $gameId);
    exit;
}

initGame($gameId);

if (!isset($_COOKIE['playerId'])) {
    $playerId = uniqid('player_');
    setcookie('playerId', $playerId, time() + (86400 * 30), "/");
} else {
    $playerId = $_COOKIE['playerId'];
}

$playerSymbol = assignPlayer($gameId, $playerId);
$isSpectateur = isSpectator($gameId, $playerId);
$canPlay = !$isSpectateur && canPlay($gameId, $playerSymbol);

$pdo = getDbConnection();
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$gameId]);
$game = $stmt->fetch();
$grid = json_decode($game['grid'], true);

if (isset($_POST['position']) && $canPlay) {
    list($ligne, $colonne) = explode(',', $_POST['position']);
    $ligne = (int)$ligne;
    $colonne = (int)$colonne;

    if ($ligne >= 0 && $ligne < 3 && $colonne >= 0 && $colonne < 3 && $grid[$ligne][$colonne] === null) {
        $grid[$ligne][$colonne] = $playerSymbol;
        $activePlayer = ($playerSymbol === 'X') ? 'O' : 'X';
        
        // Vérifier la victoire
        if (checkVictory($grid, $playerSymbol)) {
            $stmt = $pdo->prepare("UPDATE games SET grid = ?, active_player = ?, status = 'termine' WHERE id = ?");
            $stmt->execute([json_encode($grid), $activePlayer, $gameId]);
        } elseif (isGridFull($grid)) {
            $stmt = $pdo->prepare("UPDATE games SET grid = ?, active_player = ?, status = 'egalite' WHERE id = ?");
            $stmt->execute([json_encode($grid), $activePlayer, $gameId]);
        } else {
            $stmt = $pdo->prepare("UPDATE games SET grid = ?, active_player = ? WHERE id = ?");
            $stmt->execute([json_encode($grid), $activePlayer, $gameId]);
        }
        
        // Rediriger pour éviter la resoumission du formulaire
        header("Location: index.php?gameId=" . $gameId);
        exit;
    }
}

$winner = null;
if (checkVictory($grid, 'X')) {
    $winner = 'X';
    $stmt = $pdo->prepare("UPDATE games SET status = 'termine' WHERE id = ?");
    $stmt->execute([$gameId]);
} elseif (checkVictory($grid, 'O')) {
    $winner = 'O';
    $stmt = $pdo->prepare("UPDATE games SET status = 'termine' WHERE id = ?");
    $stmt->execute([$gameId]);
} elseif (isGridFull($grid)) {
    $stmt = $pdo->prepare("UPDATE games SET status = 'egalite' WHERE id = ?");
    $stmt->execute([$gameId]);
}

if (isset($_POST['reset']) && !$isSpectateur) {
    resetGame($gameId);
    $winner = null;
    // Recharger la grille après réinitialisation
    $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();
    $grid = json_decode($game['grid'], true);
}

// Générer le HTML de la grille avec les données actuelles
$gridHtml = renderGrid($grid, $canPlay, $playerSymbol);

$data = [
    'gameId' => $gameId,
    'playerSymbol' => $playerSymbol,
    'isSpectateur' => $isSpectateur,
    'canPlay' => $canPlay,
    'gridHtml' => $gridHtml,
    'winner' => $winner,
    'game' => $game,
    'playerCount' => getPlayerCount($gameId)
];

$mustache = new Mustache_Engine();
$html = $mustache->render(file_get_contents('templates/index.mustache'), $data);
echo $html;
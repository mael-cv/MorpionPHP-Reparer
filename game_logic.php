<?php
function initGame($gameId) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();
    
    if (!$game) {
        $grid = json_encode([
            [null, null, null],
            [null, null, null],
            [null, null, null]
        ]);
        $activePlayer = 'X';
        $status = 'en_attente';

        $stmt = $pdo->prepare("INSERT INTO games (id, grid, active_player, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$gameId, $grid, $activePlayer, $status]);
    }
}

function updateGameState($gameId, $grid, $activePlayer) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("UPDATE games SET grid = ?, active_player = ? WHERE id = ?");
    $stmt->execute([json_encode($grid), $activePlayer, $gameId]);
}

function checkVictory($grid, $player) {
    for ($i = 0; $i < 3; $i++) {
        if (($grid[$i][0] === $player && $grid[$i][1] === $player && $grid[$i][2] === $player) ||
            ($grid[0][$i] === $player && $grid[1][$i] === $player && $grid[2][$i] === $player)) {
            return true;
        }
    }
    return ($grid[0][0] === $player && $grid[1][1] === $player && $grid[2][2] === $player) ||
           ($grid[0][2] === $player && $grid[1][1] === $player && $grid[2][0] === $player);
}

function isGridFull($grid) {
    foreach ($grid as $row) {
        if (in_array(null, $row)) {
            return false;
        }
    }
    return true;
}

function resetGame($gameId) {
    $pdo = getDbConnection();
    $grid = json_encode([
        [null, null, null],
        [null, null, null],
        [null, null, null]
    ]);
    $stmt = $pdo->prepare("UPDATE games SET grid = ?, active_player = 'X', status = 'en_cours' WHERE id = ?");
    $stmt->execute([$grid, $gameId]);
}
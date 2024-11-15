<?php
function assignPlayer($gameId, $playerId) {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("SELECT * FROM players WHERE id = ?");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch();

    if ($player) {
        return $player['symbol'];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM players WHERE game_id = ?");
    $stmt->execute([$gameId]);
    $playerCount = $stmt->fetchColumn();

    if ($playerCount == 0) {
        $symbol = 'X';
        $status = 'attente_joueur_o';
    } elseif ($playerCount == 1) {
        $symbol = 'O';
        $status = 'en_cours';
        
        $stmt = $pdo->prepare("UPDATE games SET status = ?, active_player = 'X' WHERE id = ?");
        $stmt->execute(['en_cours', $gameId]);
    } else {
        return null; 
    }

    $stmt = $pdo->prepare("INSERT INTO players (id, game_id, symbol) VALUES (?, ?, ?)");
    $stmt->execute([$playerId, $gameId, $symbol]);

    return $symbol;
}

function isSpectator($gameId, $playerId) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM players WHERE game_id = ? AND id = ?");
    $stmt->execute([$gameId, $playerId]);
    $player = $stmt->fetch();

    return !$player;
}

function canPlay($gameId, $playerSymbol) {
    if (!$playerSymbol) return false;
    
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT g.*, 
                          (SELECT COUNT(*) FROM players WHERE game_id = g.id) as player_count 
                          FROM games g WHERE g.id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();

    if ($game['status'] === 'termine' || $game['status'] === 'egalite') {
        return false;
    }

    if ($game['status'] === 'attente_joueur_o') {
        return $playerSymbol === 'X';
    }

    if ($game['status'] === 'en_cours') {
        return $game['active_player'] === $playerSymbol;
    }

    return false;
}

function getPlayerCount($gameId) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM players WHERE game_id = ?");
    $stmt->execute([$gameId]);
    return $stmt->fetchColumn();
}
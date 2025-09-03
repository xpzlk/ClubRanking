<?php
// Inclusion de votre script principal pour récupérer $rankingWithEligibility
require_once 'config.php';
require_once 'rankingEligibility.php';

// Fonction pour déterminer la série d'un joueur basée sur son rang
function getPlayerSerie($rank) {
    if ($rank <= 50) return 'A1';
    if ($rank <= 100) return 'A2';
    if ($rank <= 200) return 'B1';
    if ($rank <= 300) return 'B2';
    if ($rank <= 400) return 'B3';
    return 'C1';
}

// Génération des lignes du tableau avec tous les joueurs
$tableRows = '';
foreach ($rankingWithEligibility['rankings'] as $player) {
    $hasEligibleTeams = !empty($player['eligible_teams']);
    $rowClass = $hasEligibleTeams ? '' : ' class="no-eligible-teams"';
    
    $serie = getPlayerSerie($player['rank']);
    $genderBadge = $player['gender_id'] === 'H' ? 'male' : ($player['gender_id'] === 'F' ? 'female' : 'other');
    $genderText = $player['gender_id'] === 'H' ? 'H' : ($player['gender_id'] === 'F' ? 'F' : '?');
    
    $eligibleTeams = $hasEligibleTeams ? implode("\n", $player['eligible_team_names']) : 'All teams';
    
    $tableRows .= '<tr' . $rowClass . ' data-club="' . htmlspecialchars($player['club_name'] ?? '') . '">
        <td>' . $player['rank'] . '</td>
        <td><span class="serie-badge serie-' . $serie . '">' . $serie . '</span></td>
        <td>' . htmlspecialchars($player['first_name']) . '</td>
        <td>' . htmlspecialchars($player['last_name']) . '</td>
        <td><span class="gender-badge gender-' . $genderBadge . '">' . $genderText . '</span></td>
        <td>' . $player['age'] . '</td>
        <td>' . htmlspecialchars($player['club_name'] ?? 'N/A') . '</td>
        <td>' . $player['current_points'] . '</td>
        <td>' . ($player['club_rank'] ?? 'N/A') . '</td>
        <td>' . nl2br(htmlspecialchars($eligibleTeams)) . '</td>
    </tr>';
}


// Génération de la liste des clubs pour JavaScript
$clubs = [];
foreach ($rankingWithEligibility['rankings'] as $player) {
    if (!empty($player['club_name']) && !in_array($player['club_name'], $clubs)) {
        $clubs[] = $player['club_name'];
    }
}
sort($clubs);
$clubsJson = json_encode($clubs);

// Lecture du template HTML
$template = file_get_contents('template.html');

// Remplacement des placeholders
$html = str_replace('{{TABLE_ROWS}}', $tableRows, $template);
$html = str_replace('{{CLUBS_JSON}}', $clubsJson, $html);
$html = str_replace('{{GENERATION_DATE}}', date('d/m/Y H:i:s'), $html);

// Affichage du HTML
#echo $html;

// Génération du nom de fichier
$filename = 'index.html';
file_put_contents($filename, $html);
?>
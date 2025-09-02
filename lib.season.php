<?php
function getSeason($date = null) {
    // Si aucune date n'est fournie, utiliser la date actuelle
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    // Convertir en timestamp si c'est une chaîne
    if (is_string($date)) {
        $timestamp = strtotime($date);
    } else {
        $timestamp = $date;
    }
    
    $year = (int)date('Y', $timestamp);
    $month = (int)date('n', $timestamp);
    
    // Si on est avant septembre, la saison a commencé l'année précédente
    if ($month < 9) {
        $startYear = $year - 1;
        $endYear = $year;
    } else {
        // Si on est à partir de septembre, la saison se termine l'année suivante
        $startYear = $year;
        $endYear = $year + 1;
    }
    
    // Format AA/AA (2 derniers chiffres)
    $startYearShort = str_pad($startYear % 100, 2, '0', STR_PAD_LEFT);
    $endYearShort = str_pad($endYear % 100, 2, '0', STR_PAD_LEFT);
    
    return $startYearShort . '/' . $endYearShort;
}

/**
 * Retourne 1 si la date est dans la 1ʳᵉ partie de saison (01.09–31.12),
 * 2 si elle est dans la 2ᵉ partie (01.01–31.08).
 *
 * @param null|string|int|\DateTimeInterface $date
 * @param string|null $timezone  Ex. 'Europe/Zurich' (optionnel)
 * @return int  1 ou 2
 */
function getSeasonPart($date = null, ?string $timezone = null): int
{
    // Instancier un objet DateTime à partir des différentes formes possibles
    if ($date instanceof DateTimeInterface) {
        $dt = (new DateTime('now', $timezone ? new DateTimeZone($timezone) : null))
                ->setTimestamp($date->getTimestamp());
    } elseif (is_int($date)) {
        $dt = new DateTime('@' . $date);
        if ($timezone) {
            $dt->setTimezone(new DateTimeZone($timezone));
        }
    } elseif (is_string($date)) {
        $dt = new DateTime($date, $timezone ? new DateTimeZone($timezone) : null);
    } else {
        $dt = new DateTime('now', $timezone ? new DateTimeZone($timezone) : null);
    }

    $month = (int)$dt->format('n'); // 1..12

    // Septembre (9) à Décembre (12) => 1ʳᵉ partie, sinon 2ᵉ partie
    return ($month >= 9) ? 1 : 2;
}
?>
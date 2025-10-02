<?php
// forex_calendar.php - Konwertuje JSON na CSV
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Pobierz dane z Forex Factory
$json = file_get_contents('https://nfs.faireconomy.media/ff_calendar_thisweek.json');
$data = json_decode($json, true);

// Nagłówek CSV
echo "date|country|title|impact|forecast|previous\n";

foreach($data as $event) {
    $line = 
        $event['date'] . "|" . 
        $event['country'] . "|" . 
        str_replace('|', '-', $event['title']) . "|" . // Usuń pipe z tytułów
        $event['impact'] . "|" . 
        ($event['forecast'] ?? '') . "|" . 
        ($event['previous'] ?? '');
    
    echo $line . "\n";
}
?>

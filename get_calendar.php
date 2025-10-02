<?php
// get_calendar.php - Zwraca dane w formacie dla MQL5
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$filename = 'forex_data.csv';

// Jeśli plik nie istnieje, pobierz dane
if (!file_exists($filename)) {
    file_put_contents($filename, file_get_contents('https://nfs.faireconomy.media/ff_calendar_thisweek.csv'));
}

// Odczytaj i zwróć dane
if (file_exists($filename)) {
    echo file_get_contents($filename);
} else {
    echo "ERROR: No data available";
}
?>

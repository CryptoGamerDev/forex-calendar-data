<?php
// update_calendar.php - Automatycznie aktualizuje dane z Forex Factory
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function updateForexData() {
    $csv_url = "https://nfs.faireconomy.media/ff_calendar_thisweek.csv";
    $data = file_get_contents($csv_url);
    
    if ($data === FALSE) {
        return "ERROR: Could not fetch data from Forex Factory";
    }
    
    // Zapisujemy dane do pliku
    file_put_contents('forex_data.csv', $data);
    return "SUCCESS: Data updated at " . date('Y-m-d H:i:s');
}

// Aktualizuj dane
$result = updateForexData();
echo $result;
?>

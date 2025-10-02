<?php
// update_calendar.php - Aktualizuje i filtruje dane dla MT5
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function updateAndFilterForexData() {
    $csv_url = "https://nfs.faireconomy.media/ff_calendar_thisweek.csv";
    $data = file_get_contents($csv_url);
    
    if ($data === FALSE) {
        return "ERROR: Could not fetch data from Forex Factory";
    }
    
    // Zapisz surowe dane
    file_put_contents('forex_data.csv', $data);
    
    // Przetwórz przez filtr
    include 'get_calendar.php'; // Wykorzystamy funkcję filtrującą
    
    if (filterAndCleanData('forex_data.csv', 'forex_data_filtered.csv')) {
        $filteredCount = count(file('forex_data_filtered.csv')) - 1; // minus nagłówek
        $rawCount = count(file('forex_data.csv')) - 1;
        
        return "SUCCESS: Data updated and filtered at " . date('Y-m-d H:i:s') . 
               " ($rawCount -> $filteredCount events)";
    } else {
        return "ERROR: Failed to filter data";
    }
}

// Aktualizuj i filtruj dane
$result = updateAndFilterForexData();
echo $result;
?>

<?php
// update_calendar.php - Aktualizuje i naprawia dane CSV
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function updateAndCleanForexData() {
    $csv_url = "https://nfs.faireconomy.media/ff_calendar_thisweek.csv";
    $raw_data = file_get_contents($csv_url);
    
    if ($raw_data === FALSE) {
        return "ERROR: Could not fetch data from Forex Factory";
    }
    
    file_put_contents('forex_data_raw.csv', $raw_data);
    
    // Użyj tej samej funkcji co w get_calendar.php
    include 'get_calendar.php';
    if (cleanAndCreateCSV()) {
        $raw_count = count(file('forex_data_raw.csv')) - 1;
        $cleaned_count = count(file('forex_data.csv')) - 1;
        
        return "SUCCESS: Data updated and cleaned at " . date('Y-m-d H:i:s') . 
               " ($raw_count -> $cleaned_count events, CSV format fixed)";
    }
    
    return "ERROR: Failed to clean CSV data";
}

echo updateAndCleanForexData();
?>

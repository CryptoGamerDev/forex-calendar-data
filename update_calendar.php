<?php
// update_calendar.php - Aktualizuje i czyści dane dla MT5 (BEZ FILTROWANIA ILOŚCI)
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function updateAndCleanForexData() {
    $csv_url = "https://nfs.faireconomy.media/ff_calendar_thisweek.csv";
    $raw_data = file_get_contents($csv_url);
    
    if ($raw_data === FALSE) {
        return "ERROR: Could not fetch data from Forex Factory";
    }
    
    // Zapisz surowe dane
    file_put_contents('forex_data_raw.csv', $raw_data);
    
    // Przetwórz przez czyszczenie - ZACHOWAJ WSZYSTKIE WIERSZE
    $raw_lines = explode("\n", trim($raw_data));
    $cleaned_content = "Title,Country,Date,Time,Impact,Forecast,Previous\n";
    
    $first_line = true;
    $event_count = 0;
    
    foreach ($raw_lines as $line) {
        if ($first_line) {
            $first_line = false;
            continue;
        }
        
        if (!empty(trim($line))) {
            $fields = str_getcsv($line);
            if (count($fields) >= 8) {
                $event_count++;
                // Przepisz tylko pierwsze 7 kolumn (bez URL)
                $cleaned_content .= implode(',', array_slice($fields, 0, 7)) . "\n";
            }
        }
    }
    
    file_put_contents('forex_data_cleaned.csv', $cleaned_content);
    
    return "SUCCESS: Data updated and cleaned at " . date('Y-m-d H:i:s') . 
           " ($event_count events, URL column removed)";
}

// Aktualizuj i czyść dane
$result = updateAndCleanForexData();
echo $result;
?>

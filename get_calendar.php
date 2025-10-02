<?php
// get_calendar.php - Zwraca OCZYSZCZONE dane w formacie dla MQL5
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$cleaned_filename = 'forex_data.csv';

// Jeśli oczyszczony plik nie istnieje, utwórz go
if (!file_exists($cleaned_filename)) {
    // Pobierz surowe dane
    $raw_data = file_get_contents('https://nfs.faireconomy.media/ff_calendar_thisweek.csv');
    file_put_contents('forex_data_raw.csv', $raw_data);
    
    // Proste czyszczenie w PHP - USUŃ TYLKO KOLUMNĘ URL, ZACHOWAJ WSZYSTKIE WIERSZE
    $raw_lines = explode("\n", trim($raw_data));
    $cleaned_content = "Title,Country,Date,Time,Impact,Forecast,Previous\n";
    
    $first_line = true;
    foreach ($raw_lines as $line) {
        if ($first_line) {
            $first_line = false;
            continue; // Pomijamy oryginalne nagłówki
        }
        
        if (!empty(trim($line))) {
            $fields = str_getcsv($line);
            if (count($fields) >= 8) {
                // Zachowaj WSZYSTKIE wiersze, przepisz tylko pierwsze 7 kolumn
                $cleaned_content .= implode(',', array_slice($fields, 0, 7)) . "\n";
            }
        }
    }
    
    file_put_contents($cleaned_filename, $cleaned_content);
}

// Zwróć oczyszczone dane
if (file_exists($cleaned_filename)) {
    echo file_get_contents($cleaned_filename);
} else {
    // Fallback: zwróć surowe dane
    if (file_exists('forex_data_raw.csv')) {
        echo file_get_contents('forex_data_raw.csv');
    } else {
        echo "ERROR: No data available";
    }
}
?>

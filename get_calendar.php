<?php
// get_calendar.php - Zwraca przefiltrowane dane w formacie dla MQL5
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$filtered_filename = 'forex_data_filtered.csv';

// Jeśli przefiltrowany plik nie istnieje, utwórz go
if (!file_exists($filtered_filename)) {
    // Pobierz surowe dane
    $raw_data = file_get_contents('https://nfs.faireconomy.media/ff_calendar_thisweek.csv');
    file_put_contents('forex_data_raw.csv', $raw_data);
    
    // Proste filtrowanie w PHP
    $raw_lines = explode("\n", $raw_data);
    $filtered_content = "Title,Country,Date,Time,Impact,Forecast,Previous\n";
    
    $first_line = true;
    foreach ($raw_lines as $line) {
        if ($first_line) {
            $first_line = false;
            continue; // Pomijamy nagłówki
        }
        
        $fields = str_getcsv($line);
        if (count($fields) >= 8) {
            $impact = $fields[4];
            $forecast = $fields[5];
            $previous = $fields[6];
            
            // Filtruj: pomiń Low impact z pustymi danymi
            if ($impact === 'Low' && empty($forecast) && empty($previous)) {
                continue;
            }
            
            // Zapisz tylko 7 kolumn
            $filtered_content .= implode(',', [
                $fields[0], // Title
                $fields[1], // Country
                $fields[2], // Date
                $fields[3], // Time
                $impact,
                $forecast,
                $previous
            ]) . "\n";
        }
    }
    
    file_put_contents($filtered_filename, $filtered_content);
}

// Zwróć przefiltrowane dane
if (file_exists($filtered_filename)) {
    echo file_get_contents($filtered_filename);
} else {
    echo "ERROR: No filtered data available";
}
?>

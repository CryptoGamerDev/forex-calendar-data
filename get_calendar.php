<?php
// get_calendar.php - Zwraca PRZEFILTROWANE dane w formacie dla MQL5
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$filename = 'forex_data.csv';
$filtered_filename = 'forex_data_filtered.csv';

function filterAndCleanData($inputFile, $outputFile) {
    if (!file_exists($inputFile)) {
        return false;
    }
    
    $input = fopen($inputFile, 'r');
    $output = fopen($outputFile, 'w');
    
    if (!$input || !$output) {
        return false;
    }
    
    // Nagłówki dla MT5 (bez URL)
    fputcsv($output, ['Title', 'Country', 'Date', 'Time', 'Impact', 'Forecast', 'Previous']);
    
    $firstLine = true;
    while (($data = fgetcsv($input)) !== FALSE) {
        if ($firstLine) {
            $firstLine = false;
            continue; // Pomijamy oryginalne nagłówki
        }
        
        // Sprawdź czy mamy wystarczającą liczbę kolumn
        if (count($data) < 8) continue;
        
        $title = $data[0];
        $country = $data[1];
        $date = $data[2];
        $time = $data[3];
        $impact = $data[4];
        $forecast = $data[5];
        $previous = $data[6];
        
        // FILTROWANIE - zachowuj tylko ważne wydarzenia:
        // 1. Usuń wydarzenia z Impact = Low i pustymi Forecast/Previous
        if ($impact === 'Low' && empty($forecast) && empty($previous)) {
            continue;
        }
        
        // 2. Usuń duplikaty mówców w krótkich odstępach (opcjonalnie)
        // 3. Zachowaj wszystkie z High/Medium impact
        if ($impact === 'High' || $impact === 'Medium' || !empty($forecast) || !empty($previous)) {
            fputcsv($output, [$title, $country, $date, $time, $impact, $forecast, $previous]);
        }
    }
    
    fclose($input);
    fclose($output);
    return true;
}

// Jeśli przefiltrowany plik nie istnieje lub jest starszy niż 1 godzina
if (!file_exists($filtered_filename) || 
    (filemtime($filtered_filename) < time() - 3600)) {
    
    // Jeśli główny plik nie istnieje, pobierz go
    if (!file_exists($filename)) {
        file_put_contents($filename, file_get_contents('https://nfs.faireconomy.media/ff_calendar_thisweek.csv'));
    }
    
    // Filtruj i czyść dane
    filterAndCleanData($filename, $filtered_filename);
}

// Zwróć przefiltrowane dane
if (file_exists($filtered_filename)) {
    echo file_get_contents($filtered_filename);
} else {
    echo "ERROR: No filtered data available";
}
?>

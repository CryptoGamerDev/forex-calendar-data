<?php
// update_calendar.php - Aktualizuje i filtruje dane dla MT5
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');

function updateAndFilterForexData() {
    $csv_url = "https://nfs.faireconomy.media/ff_calendar_thisweek.csv";
    $raw_data = file_get_contents($csv_url);
    
    if ($raw_data === FALSE) {
        return "ERROR: Could not fetch data from Forex Factory";
    }
    
    // Zapisz surowe dane
    file_put_contents('forex_data_raw.csv', $raw_data);
    
    // Przetwórz przez filtr
    $raw_lines = explode("\n", $raw_data);
    $filtered_content = "Title,Country,Date,Time,Impact,Forecast,Previous\n";
    
    $first_line = true;
    $raw_count = 0;
    $filtered_count = 0;
    
    foreach ($raw_lines as $line) {
        if ($first_line) {
            $first_line = false;
            continue;
        }
        
        $raw_count++;
        $fields = str_getcsv($line);
        if (count($fields) >= 8) {
            $impact = $fields[4];
            $forecast = $fields[5];
            $previous = $fields[6];
            
            // Filtruj: pomiń Low impact z pustymi danymi
            if ($impact === 'Low' && empty($forecast) && empty($previous)) {
                continue;
            }
            
            $filtered_count++;
            $filtered_content .= implode(',', [
                $fields[0], $fields[1], $fields[2], $fields[3], 
                $impact, $forecast, $previous
            ]) . "\n";
        }
    }
    
    file_put_contents('forex_data_filtered.csv', $filtered_content);
    
    return "SUCCESS: Data updated and filtered at " . date('Y-m-d H:i:s') . 
           " ($raw_count -> $filtered_count events)";
}

// Aktualizuj i filtruj dane
$result = updateAndFilterForexData();
echo $result;
?>

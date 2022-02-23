<?php
    session_start();
    include('functions.php');
    if(!isAuthenticated()) header('Location: index.php');
    $result = querySQL();
    // https://stackoverflow.com/questions/16251625/how-to-create-and-download-a-csv-file-from-php-script
    // open raw memory as file so no temp files needed, you might run out of memory though
    $f = fopen('php://memory', 'w'); 
    // loop over the input array
    foreach ($result as $line) { 
        // generate csv lines from the inner arrays
        fputcsv($f, $line, ";"); 
    }
    // reset the file pointer to the start of the file
    fseek($f, 0);
    // tell the browser it's going to be a csv file
    header('Content-Type: text/csv');
    // tell the browser we want to save it instead of displaying it
    header('Content-Disposition: attachment; filename="export.csv";');
    // make php send the generated csv lines to the browser
    fpassthru($f);
?>
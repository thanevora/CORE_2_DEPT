<?php
// download_and_install_tcpdf.php
echo "<h2>Installing TCPDF...</h2>";

// Check if we can write to directory
if (!is_writable('.')) {
    die("Error: Directory is not writable. Please check permissions.");
}

// Download TCPDF
$tcpdf_url = 'https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.6.5.zip';
$zip_file = 'tcpdf_download.zip';

echo "Step 1: Downloading TCPDF...<br>";
$zip_content = @file_get_contents($tcpdf_url);

if ($zip_content === false) {
    // Try alternative method
    echo "Trying alternative download method...<br>";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tcpdf_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $zip_content = curl_exec($ch);
    curl_close($ch);
}

if ($zip_content) {
    file_put_contents($zip_file, $zip_content);
    echo "Step 2: Download complete!<br>";
} else {
    die("Failed to download TCPDF. Please download manually from: https://github.com/tecnickcom/TCPDF");
}

if (file_exists($zip_file)) {
    echo "Step 3: Extracting files...<br>";
    
    $zip = new ZipArchive;
    if ($zip->open($zip_file) === TRUE) {
        // Extract to current directory
        $zip->extractTo('./');
        $zip->close();
        
        // Rename folder from TCPDF-6.6.5 to tcpdf
        if (file_exists('TCPDF-6.6.5')) {
            rename('TCPDF-6.6.5', 'tcpdf');
            echo "Step 4: Renamed folder to 'tcpdf'<br>";
        }
        
        // Delete the zip file
        unlink($zip_file);
        
        echo "<h3 style='color: green;'>✅ TCPDF installed successfully!</h3>";
        echo "<p>Location: " . realpath('tcpdf') . "</p>";
        
        // Test if tcpdf.php exists
        if (file_exists('tcpdf/tcpdf.php')) {
            echo "<p style='color: green;'>✓ tcpdf.php found!</p>";
        } else {
            echo "<p style='color: red;'>✗ tcpdf.php not found in expected location.</p>";
            echo "<p>Please check the tcpdf folder structure:</p>";
            $files = scandir('tcpdf');
            echo "<pre>" . print_r($files, true) . "</pre>";
        }
    } else {
        echo "Failed to open ZIP file<br>";
    }
} else {
    echo "ZIP file not found<br>";
}
?>
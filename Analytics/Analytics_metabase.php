<?php
// ------------------ AUTO REFRESH & DATA SYNC ------------------
echo '<meta http-equiv="refresh" content="60">'; // refresh every 60s (you can change interval)

include '../main_connection.php';
session_start();
require '../vendor/autoload.php';

use Firebase\JWT\JWT;

// Analytics DB
$analyticsDB = "rest_analytics";
$analytics = $connections[$analyticsDB] ?? null;
if (!$analytics) die("❌ Analytics DB not found.");

// Source DB mappings
$sourceMappings = [
    "rest_m1_trs" => ["reservations"=>"reservations", "tables"=>"tables"],
    "rest_m2_inventory"=>["inventory_and_stock"=>"inventory_and_stock"],
    "rest_m3_menu"=>["menu"=>"menu"],
    "rest_m4_pos"=>["orders"=>"orders"],
    "rest_m6_kot"=>["kot_orders"=>"kot_orders"],
    "rest_m7_billing_payments"=>["billing_payments"=>"billing_payments"],
    "rest_m8_table_turnover"=>["table_metrics"=>"table_metrics"],
    "rest_m9_wait_staff"=>["wait_staff"=>"wait_staff"],
    "rest_m10_comments_review"=>["customer_feedback"=>"customer_feedback"],
    "rest_m11_event"=>["event_reservations"=>"event_reservations"]
];

foreach ($sourceMappings as $sourceDB => $tablesMap) {
    if (!isset($connections[$sourceDB])) continue;
    $source = $connections[$sourceDB];

    foreach ($tablesMap as $srcTable => $dstTable) {
        $destColsResult = $analytics->query("SHOW COLUMNS FROM `$dstTable`");
        if (!$destColsResult) continue;

        $destCols = [];
        while ($colRow = $destColsResult->fetch_assoc()) {
            $destCols[$colRow['Field']] = $colRow['Type'];
        }

        $result = $source->query("SELECT * FROM `$srcTable`");
        if (!$result || $result->num_rows === 0) continue;

        while ($row = $result->fetch_assoc()) {
            $row = array_intersect_key($row, $destCols);
            if (empty($row)) continue;

            $columns = array_keys($row);
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $updates = implode(',', array_map(fn($col)=>"`$col`=VALUES(`$col`)", $columns));

            $stmt = $analytics->prepare("INSERT INTO `$dstTable` (`".implode('`,`',$columns)."`) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates");
            if (!$stmt) continue;

            $types=''; $values=[];
            foreach ($columns as $col) {
                $val = $row[$col];
                if (is_int($val)) $types.='i';
                elseif (is_double($val) || is_float($val)) $types.='d';
                else $types.='s';
                $values[]=$val;
            }

            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// ------------------ METABASE DASHBOARD EMBED ------------------
$METABASE_SITE_URL = "http://localhost:3000";
$METABASE_SECRET_KEY = "48e2164eee3092ea19c8305a460d4c4d9793374d9ec25295caadba4e31806948";

// Payload: which dashboard to embed
$payload = [
    "resource" => ["dashboard" => 3], // Changed to dashboard ID 3 as per your request
    "params"   => new stdClass(),
    "exp"      => time() + (10 * 60) // 10 minute expiration
];

$token = JWT::encode($payload, $METABASE_SECRET_KEY, 'HS256');
$iframeUrl = $METABASE_SITE_URL . "/embed/dashboard/" . $token . "#bordered=true&titled=true";

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Analytics</title>

  <!-- UI & Styles -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <link rel="stylesheet" href="../CSS/sidebar.css">

  <style>
    .iframe-wrapper {
      position: relative;
      padding-bottom: 56.25%; /* 16:9 */
      height: 0;
      overflow: hidden;
      background: #f8fafc;
      border-radius: 0.75rem;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }
    .iframe-wrapper iframe {
      position: absolute;
      top:0; left:0;
      width:100%; height:100%;
      border:none;
    }
    @media (max-width: 768px) {
      .iframe-wrapper { padding-bottom: 75%; } /* 4:3 mobile */
    }
  </style>
</head>
<body class="min-h-screen">
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <?php include '../sidebarr.php'; ?>

    <!-- Content Area -->
    <div class="flex flex-col flex-1 overflow-auto bg-gray-50">
      <!-- Navbar -->
      <?php include '../navbar.php'; ?>

      <!-- Main Content -->
      <main class="p-6">
    
        
       
          
          <div class="iframe-wrapper">
            <iframe 
              src="<?= $iframeUrl ?>" 
              frameborder="0"
              width="100%"
              height="600"
              allowtransparency
              loading="lazy"
            ></iframe>
          </div>
       
      </main>
    </div>
  </div>

  <script src="../JavaScript/sidebar.js"></script>
</body>
</html>
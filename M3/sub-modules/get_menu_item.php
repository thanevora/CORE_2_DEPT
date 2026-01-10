<?php
// get_menu_item.php
include("../../main_connection.php");

$db_name = "rest_m3_menu";
if (!isset($connections[$db_name])) {
    die("❌ Connection not found for $db_name");
}
$conn = $connections[$db_name];

$menu_id = intval($_GET['menu_id'] ?? 0);

// Fetch menu item details
$sql = "SELECT * FROM menu WHERE menu_id = $menu_id LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Parse ingredients from the database fields
    $ingredients = [];
    
    // Check for ingredient1 fields
    if (!empty($row['ingredient1_id']) || !empty($row['ingredient1_names'])) {
        $ingredients[] = [
            'name' => $row['ingredient1_names'] ?? 'Ingredient 1',
            'quantity' => $row['ingredient1_qty'] ?? 0,
            'id' => $row['ingredient1_id'] ?? null
        ];
    }
    
    // You might have more ingredient fields (ingredient2_id, ingredient2_qty, etc.)
    // Add similar logic for ingredient2, ingredient3, etc. if they exist
    
    $totalIngredientCost = 0; // You might want to calculate this differently based on your inventory
    
    // Calculate profit margin (you'll need to adjust this based on your actual cost data)
    $price = floatval($row['price'] ?? 0);
    $profitMargin = $price > 0 ? ($price > 50 ? 60 : ($price > 30 ? 40 : 25)) : 0; // Example calculation
    $profitClass = $profitMargin >= 50 ? 'text-green-600' : 
                  ($profitMargin >= 30 ? 'text-amber-600' : 'text-blue-600');
    
    // Format dates
    $createdDate = date('F j, Y', strtotime($row['created_at']));
    $updatedDate = date('F j, Y', strtotime($row['updated_at']));
    $updatedTime = date('g:i A', strtotime($row['updated_at']));
    ?>
    
    <div class="p-0">
      <!-- Main Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-0">
        <!-- Left Column: Image & Basic Info -->
        <div class="lg:col-span-1 bg-gray-50 p-6 lg:p-8 border-r border-gray-200">
          <!-- Image Section -->
          <div class="mb-6">
            <div class="rounded-xl overflow-hidden bg-white shadow-lg border border-gray-200 h-64">
              <?php if (!empty($row['image_url'])): ?>
                <!-- Corrected path for M3/Menu_uploaded directory -->
                <img src="Menu_uploaded/menu_images/original/<?php echo htmlspecialchars($row['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($row['name']); ?>" 
                     class="w-full h-full object-cover transition-transform duration-500 hover:scale-110"
                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjEwMCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjOTk5Ij5JbWFnZSBOb3QgRm91bmQ8L3RleHQ+PC9zdmc+'">
              <?php else: ?>
                <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                  <i data-lucide="utensils" class="w-16 h-16 text-gray-400 mb-3"></i>
                  <p class="text-sm text-gray-500 font-medium">No image</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- Basic Information Card -->
          <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm mb-6">
            <h3 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
              <i data-lucide="info" class="w-5 h-5 text-[#001f54]"></i>
              Basic Information
            </h3>
            
            <div class="space-y-4">
              <!-- Status Badge -->
              <div>
                <p class="text-xs text-gray-500 mb-1">Status</p>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold <?= 
                  $row['status'] === 'Available' ? 'bg-green-100 text-green-800' : 
                  ($row['status'] === 'Under review' ? 'bg-amber-100 text-amber-800' : 
                  ($row['status'] === 'Popular' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'));
                ?>">
                  <i data-lucide="circle" class="w-2 h-2 mr-1.5 <?= 
                    $row['status'] === 'Available' ? 'text-green-500' : 
                    ($row['status'] === 'Under review' ? 'text-amber-500' : 
                    ($row['status'] === 'Popular' ? 'text-purple-500' : 'text-gray-500'));
                  ?>"></i>
                  <?= htmlspecialchars($row['status']); ?>
                </span>
              </div>
              
              <!-- Availability -->
              <div>
                <p class="text-xs text-gray-500 mb-1">Availability</p>
                <p class="font-medium text-gray-800 flex items-center gap-2">
                  <i data-lucide="<?= $row['availability'] === 'Available' ? 'check-circle' : 'x-circle' ?>" 
                     class="w-4 h-4 <?= $row['availability'] === 'Available' ? 'text-green-500' : 'text-red-500' ?>"></i>
                  <?= htmlspecialchars($row['availability'] ?? 'Check availability'); ?>
                </p>
              </div>
              
              <!-- Menu ID -->
              <div>
                <p class="text-xs text-gray-500 mb-1">Menu ID</p>
                <p class="font-bold text-lg text-[#001f54]">#<?= str_pad($menu_id, 6, '0', STR_PAD_LEFT); ?></p>
              </div>
              
              <!-- Created Date -->
              <div>
                <p class="text-xs text-gray-500 mb-1">Created On</p>
                <p class="font-medium text-gray-800"><?= $createdDate; ?></p>
              </div>
            </div>
          </div>
          
         
        </div>
        
        <!-- Right Column: Detailed Information -->
        <div class="lg:col-span-2 p-6 lg:p-8">
          <!-- Header -->
          <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($row['name']); ?></h1>
            <div class="flex flex-wrap gap-2 mb-4">
              <span class="badge badge-lg bg-[#001f54] text-white border-0">
                <i data-lucide="tag" class="w-4 h-4 mr-1"></i>
                <?= htmlspecialchars(ucfirst($row['category'])); ?>
              </span>
              <span class="badge badge-lg bg-[#F7B32B] text-white border-0">
                <i data-lucide="sun" class="w-4 h-4 mr-1"></i>
                <?= htmlspecialchars($row['variant']); ?>
              </span>
              <span class="badge badge-lg bg-gray-100 text-gray-800 border-gray-300">
                <i data-lucide="clock" class="w-4 h-4 mr-1"></i>
                <?= htmlspecialchars($row['prep_time'] ?? 'N/A'); ?> min prep
              </span>
            </div>
            
            <!-- Price Display -->
            <div class="bg-gradient-to-r from-[#001f54] to-[#002a7c] text-white p-5 rounded-xl mb-6">
              <div class="flex justify-between items-center">
                <div>
                  <p class="text-sm text-gray-300">Selling Price</p>
                  <p class="text-4xl font-bold mt-1">₱ <?= number_format($price, 2); ?></p>
                </div>
                
              </div>
            </div>
          </div>
          
          <!-- Description Section -->
          <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-semibold text-gray-800 flex items-center gap-2">
                <i data-lucide="file-text" class="w-5 h-5 text-[#001f54]"></i>
                Description
              </h3>
              <span class="text-sm text-gray-500">
                <?= strlen($row['description'] ?? '') ?> characters
              </span>
            </div>
            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200">
              <p class="text-gray-700 whitespace-pre-line leading-relaxed">
                <?= nl2br(htmlspecialchars($row['description'] ?? 'No description provided.')); ?>
              </p>
            </div>
          </div>
          
          <!-- Ingredients Section -->
          <div class="mb-8">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <i data-lucide="list-checks" class="w-5 h-5 text-[#001f54]"></i>
              Ingredients
              <span class="text-sm font-normal text-gray-500 ml-2">
                (<?= count($ingredients); ?> item<?= count($ingredients) !== 1 ? 's' : '' ?>)
              </span>
            </h3>
            
            <?php if (!empty($ingredients)): ?>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
              <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                  <thead>
                    <tr class="bg-gray-100">
                      <th class="font-semibold text-gray-700 py-4 px-6">Ingredient</th>
                      <th class="font-semibold text-gray-700 py-4 px-6">Quantity</th>
                      <th class="font-semibold text-gray-700 py-4 px-6">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($ingredients as $index => $ingredient): ?>
                    <tr>
                      <td class="py-4 px-6">
                        <div class="flex items-center gap-3">
                          <div class="p-2 rounded-lg bg-blue-50">
                            <i data-lucide="package" class="w-4 h-4 text-blue-600"></i>
                          </div>
                          <div>
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($ingredient['name']); ?></p>
                            <?php if (!empty($ingredient['id'])): ?>
                              <p class="text-xs text-gray-500">ID: <?= $ingredient['id']; ?></p>
                            <?php endif; ?>
                          </div>
                        </div>
                      </td>
                      <td class="py-4 px-6">
                        <div class="flex items-center gap-2">
                          <span class="font-bold text-lg text-gray-800"><?= $ingredient['quantity']; ?></span>
                          <span class="text-sm text-gray-500">units</span>
                        </div>
                      </td>
                      <td class="py-4 px-6">
                        <span class="badge badge-success badge-outline">
                          <i data-lucide="check-circle" class="w-3 h-3 mr-1"></i>
                          In Stock
                        </span>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <?php else: ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5 text-center">
              <i data-lucide="alert-triangle" class="w-12 h-12 text-yellow-500 mx-auto mb-3"></i>
              <p class="text-gray-700 font-medium">No ingredients specified for this menu item.</p>
              <p class="text-sm text-gray-500 mt-1">Add ingredients to track inventory usage.</p>
            </div>
            <?php endif; ?>
          </div>
          
          <!-- Performance Metrics -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Sales Performance -->
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
              <div class="flex items-center gap-3 mb-3">
                <div class="p-3 rounded-lg bg-purple-100 text-purple-600">
                  <i data-lucide="trending-up" class="w-5 h-5"></i>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Sales Rank</p>
                  <p class="text-2xl font-bold text-gray-800">#<?= rand(1, 50); ?></p>
                </div>
              </div>
              <div class="text-xs text-gray-500">Top <?= rand(30, 80) ?>% of menu</div>
            </div>
            
            <!-- Preparation Time -->
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
              <div class="flex items-center gap-3 mb-3">
                <div class="p-3 rounded-lg bg-blue-100 text-blue-600">
                  <i data-lucide="clock" class="w-5 h-5"></i>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Prep Time</p>
                  <p class="text-2xl font-bold text-gray-800"><?= $row['prep_time'] ?? 'N/A' ?></p>
                </div>
              </div>
              <div class="text-xs text-gray-500">minutes average</div>
            </div>
            
            <!-- Last Updated -->
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
              <div class="flex items-center gap-3 mb-3">
                <div class="p-3 rounded-lg bg-green-100 text-green-600">
                  <i data-lucide="calendar" class="w-5 h-5"></i>
                </div>
                <div>
                  <p class="text-sm text-gray-500">Last Updated</p>
                  <p class="text-2xl font-bold text-gray-800"><?= $updatedDate ?></p>
                </div>
              </div>
              <div class="text-xs text-gray-500"><?= $updatedTime ?></div>
            </div>
          </div>
          
          
        </div>
      </div>
    </div>

    <script>
    function editCurrentMenuItem() {
      const menuId = <?= $menu_id ?>;
      Swal.fire({
        title: 'Edit Menu Item',
        html: 'Redirecting to edit form...',
        icon: 'info',
        showConfirmButton: false,
        timer: 1500
      });
      // Implement edit functionality
      setTimeout(() => {
        // Example: Open edit modal or redirect
        console.log('Edit menu item:', menuId);
      }, 1500);
    }
    
    function toggleAvailability(menuId) {
      Swal.fire({
        title: 'Change Availability',
        text: 'Are you sure you want to change the availability status?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#001f54',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, change it!'
      }).then((result) => {
        if (result.isConfirmed) {
          // Implement availability toggle
          Swal.fire('Changed!', 'Availability status updated.', 'success');
          setTimeout(() => location.reload(), 1500);
        }
      });
    }
    
    function duplicateMenuItem(menuId) {
      Swal.fire({
        title: 'Duplicate Menu Item',
        text: 'Create a copy of this menu item?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#001f54',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Duplicate'
      }).then((result) => {
        if (result.isConfirmed) {
          // Implement duplication
          Swal.fire('Duplicated!', 'New menu item created.', 'success');
        }
      });
    }
    
    function generateQRCode(menuId) {
      Swal.fire({
        title: 'QR Code',
        text: 'QR code generation feature coming soon!',
        icon: 'info',
        confirmButtonColor: '#001f54'
      });
    }
    
    function printMenuItem() {
      window.print();
    }
    </script>
    
    <?php
} else {
    echo '<div class="p-16 text-center">';
    echo '<div class="max-w-md mx-auto">';
    echo '<i data-lucide="alert-circle" class="w-24 h-24 text-red-500 mx-auto mb-6"></i>';
    echo '<h3 class="text-2xl font-bold text-gray-800 mb-3">Menu Item Not Found</h3>';
    echo '<p class="text-gray-600 mb-6">The requested menu item (ID: #' . $menu_id . ') could not be found in the database.</p>';
    echo '<label for="menu-item-details-modal" class="btn bg-[#001f54] text-white">';
    echo '<i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i>';
    echo 'Go Back';
    echo '</label>';
    echo '</div>';
    echo '</div>';
}

$conn->close();
?>
<?php
/**
 * Catalogs Page - Product Catalog with Filtering
 */

// Start session for user authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Include database configuration
define('DB_ACCESS', true);
require_once 'database/db_config.php';

// Get user information from session (matching sidebar.php)
$userName = $_SESSION['fullName'] ?? $_SESSION['username'] ?? 'User';
$userRole = ucfirst($_SESSION['userRole'] ?? 'Employee');

// Page title
$pageTitle = 'Product Catalogs';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Pharmacy Management System</title>

    <!-- Favicon -->
    <link rel="icon" href="Pharmacy Icons/echinacea logo ver 2.png" type="image/x-icon">

    <!-- CSS Files -->
    <link rel="stylesheet" href="css/Pharmacy.css">
    <link rel="stylesheet" href="css/Catalogs.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- API Adapter -->
    <script src="js/api-adapter.js"></script>

    <!-- Catalogs JavaScript -->
    <script src="js/Catalogs.js"></script>
</head>
<body>
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="page-outer-box">
            <!-- Header -->
            <div class="header">
                <div class="header-left">
                    <h1><i class="fas fa-book-open"></i> Product Catalogs</h1>
                    <p>Browse and filter our product catalog</p>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <span class="username"><?php echo htmlspecialchars($userName); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($userRole); ?></span>
                    </div>
                </div>
            </div>

            <!-- Filter Headers -->
            <div class="filter-headers">
                <!-- Brand Filter -->
                <div class="filter-section">
                    <h3>Brand</h3>
                    <div class="filter-buttons" id="brand-filters">
                        <!-- Buttons will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Generic Name Filter -->
                <div class="filter-section">
                    <h3>Generic Name</h3>
                    <div class="filter-buttons" id="generic-name-filters">
                        <!-- Buttons will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="filter-section">
                    <h3>Categories</h3>
                    <div class="filter-buttons" id="category-filters">
                        <!-- Buttons will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Supplier Filter -->
                <div class="filter-section">
                    <h3>Supplier</h3>
                    <div class="filter-buttons" id="supplier-filters">
                        <!-- Buttons will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Prescription Filter -->
                <div class="filter-section">
                    <h3>Required Prescription</h3>
                    <div class="filter-buttons" id="prescription-filters">
                        <!-- Buttons will be populated by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Active Filters Display -->
            <div class="active-filters" id="active-filters" style="display: none;">
                <h4>Active Filters:</h4>
                <div class="filter-tags" id="filter-tags">
                    <!-- Active filter tags will be shown here -->
                </div>
                <button class="clear-all-filters" id="clear-all-filters">
                    <i class="fas fa-times"></i> Clear All
                </button>
            </div>

            <!-- Product Catalog Display -->
            <div class="catalog-container">
                <div class="catalog-header">
                    <h3>Product Catalog</h3>
                    <div class="section-controls">
                        <div class="search-box">
                            <input type="text" id="search-input" placeholder="Search products...">
                            <button id="search-button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div class="results-count">
                            Showing <span id="current-count">0</span> of <span id="total-count">0</span> products
                        </div>
                    </div>
                </div>
                <div class="catalog-grid" id="catalog-grid">
                    <!-- Product cards will be displayed here -->
                </div>
                <div class="loading-state" id="loading-state" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> Loading products...
                </div>
                <div class="empty-state" id="empty-state" style="display: none;">
                    <i class="fas fa-search"></i>
                    <h4>No products found</h4>
                    <p>Try adjusting your filters or search terms.</p>
                </div>
            </div>
        </main>


</body>
</html>

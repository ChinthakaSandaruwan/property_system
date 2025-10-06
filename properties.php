<?php
// Include database configuration
require_once 'includes/config.php';
require_once 'includes/settings_helper.php';

// Get site name
$site_name = getSiteName();

// Get filters from query parameters
$search = $_GET['search'] ?? '';
$city = $_GET['city'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$bedrooms = $_GET['bedrooms'] ?? '';
$property_type = $_GET['property_type'] ?? '';
$sort = $_GET['sort'] ?? 'created_at DESC';

// Build WHERE clause
$where_conditions = ["status = 'approved'", "is_available = 1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ? OR address LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($city)) {
    $where_conditions[] = "city LIKE ?";
    $params[] = "%{$city}%";
}

if (!empty($min_price)) {
    $where_conditions[] = "rent_amount >= ?";
    $params[] = $min_price;
}

if (!empty($max_price)) {
    $where_conditions[] = "rent_amount <= ?";
    $params[] = $max_price;
}

if (!empty($bedrooms)) {
    $where_conditions[] = "bedrooms = ?";
    $params[] = $bedrooms;
}

if (!empty($property_type)) {
    $where_conditions[] = "property_type = ?";
    $params[] = $property_type;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM properties {$where_clause}";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_properties = $count_stmt->fetch()['total'];

// Pagination
$page = $_GET['page'] ?? 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_properties / $per_page);

// Get properties
$sql = "
    SELECT p.*, u.full_name as owner_name, u.phone as owner_phone 
    FROM properties p 
    LEFT JOIN users u ON p.owner_id = u.id 
    {$where_clause}
    ORDER BY {$sort}
    LIMIT {$per_page} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Properties - <?= htmlspecialchars($site_name) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .properties-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }
        .search-filters {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        .property-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .property-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .property-content {
            padding: 1.5rem;
        }
        .property-price {
            color: #667eea;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        .property-details {
            display: flex;
            gap: 1rem;
            color: #666;
            margin: 1rem 0;
        }
        .property-location {
            color: #888;
            margin-bottom: 1rem;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0;
        }
        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 5px;
            text-decoration: none;
            color: #667eea;
        }
        .pagination .current {
            background: #667eea;
            color: white;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        .back-link:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="properties-page">
        <div class="container">
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            
            <h1 style="color: white; text-align: center; margin-bottom: 2rem;">
                Browse Properties (<?= $total_properties ?> found)
            </h1>
            
            <!-- Search and Filters -->
            <div class="search-filters">
                <form method="GET" action="">
                    <div class="filter-grid">
                        <div>
                            <label>Search</label>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search properties...">
                        </div>
                        <div>
                            <label>City</label>
                            <input type="text" name="city" value="<?= htmlspecialchars($city) ?>" placeholder="Enter city">
                        </div>
                        <div>
                            <label>Min Price</label>
                            <input type="number" name="min_price" value="<?= htmlspecialchars($min_price) ?>" placeholder="Min price">
                        </div>
                        <div>
                            <label>Max Price</label>
                            <input type="number" name="max_price" value="<?= htmlspecialchars($max_price) ?>" placeholder="Max price">
                        </div>
                        <div>
                            <label>Bedrooms</label>
                            <select name="bedrooms">
                                <option value="">Any</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= $bedrooms == $i ? 'selected' : '' ?>><?= $i ?> BR</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label>Property Type</label>
                            <select name="property_type">
                                <option value="">Any Type</option>
                                <option value="apartment" <?= $property_type == 'apartment' ? 'selected' : '' ?>>Apartment</option>
                                <option value="house" <?= $property_type == 'house' ? 'selected' : '' ?>>House</option>
                                <option value="villa" <?= $property_type == 'villa' ? 'selected' : '' ?>>Villa</option>
                                <option value="studio" <?= $property_type == 'studio' ? 'selected' : '' ?>>Studio</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem; justify-content: center;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search Properties
                        </button>
                        <a href="properties.php" class="btn btn-outline">
                            <i class="fas fa-refresh"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Properties Grid -->
            <div class="properties-grid">
                <?php if (!empty($properties)): ?>
                    <?php foreach ($properties as $property): ?>
                        <?php 
                        $images = json_decode($property['images'] ?? '[]', true);
                        $first_image = !empty($images) ? $images[0] : 'placeholder.jpg';
                        ?>
                        <div class="property-card">
                            <img src="uploads/properties/<?= htmlspecialchars($first_image) ?>" 
                                 alt="<?= htmlspecialchars($property['title']) ?>" 
                                 class="property-image" 
                                 onerror="this.src='images/placeholder.svg'"
                            <div class="property-content">
                                <h3><?= htmlspecialchars($property['title']) ?></h3>
                                <div class="property-price">Rs. <?= number_format($property['rent_amount']) ?>/month</div>
                                <div class="property-details">
                                    <span><i class="fas fa-bed"></i> <?= $property['bedrooms'] ?> BR</span>
                                    <span><i class="fas fa-bath"></i> <?= $property['bathrooms'] ?> BA</span>
                                    <?php if ($property['area_sqft']): ?>
                                        <span><i class="fas fa-expand-arrows-alt"></i> <?= number_format($property['area_sqft']) ?> sq ft</span>
                                    <?php endif; ?>
                                </div>
                                <div class="property-location">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['city'] . ', ' . $property['state']) ?>
                                </div>
                                <a href="property_details.php?id=<?= $property['id'] ?>" class="btn btn-primary" style="width: 100%;">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; color: white; padding: 3rem;">
                        <i class="fas fa-search" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>No Properties Found</h3>
                        <p>Try adjusting your search criteria or check back later for new listings.</p>
                        <a href="properties.php" class="btn btn-outline" style="margin-top: 1rem;">Clear Filters</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php
                        $query_params = $_GET;
                        $query_params['page'] = $i;
                        $query_string = http_build_query($query_params);
                        ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?<?= $query_string ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 3rem;">
                <p style="color: white; opacity: 0.9;">
                    Need help finding the perfect property? 
                    <a href="register.php" style="color: white; text-decoration: underline;">Register now</a> 
                    to get personalized recommendations!
                </p>
            </div>
        </div>
    </div>
</body>
</html>
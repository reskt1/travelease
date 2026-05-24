<?php
require '../includes/auth_check.php';
require '../config/database.php';

$pdo->exec("SET SESSION sql_mode = ''");

$allowed_sorts = ['recommended','price_asc','star','review'];
$sort = in_array($_GET['sort'] ?? '', $allowed_sorts) ? $_GET['sort'] : 'recommended';

$destination = trim($_GET['destination'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 5;
$offset      = ($page - 1) * $per_page;

// ✅ Parameter filter baru
$filter_stars      = $_GET['stars']     ?? [];   // array: ['4','5']
$filter_facilities = $_GET['facilities'] ?? [];  // array: ['wifi','pool']
$filter_price_min  = (int)($_GET['price_min'] ?? 0);
$filter_price_max  = (int)($_GET['price_max'] ?? 5000000);
$filter_checkin  = '';
$filter_checkout = '';
$filter_guests   = 1;

$order = match($sort){
    'price_asc' => 'MIN(r.price_per_night) ASC',
    'star'      => 'h.star_rating DESC',
    'review'    => 'h.review_score DESC',
    default     => 'h.id ASC'
};

// ✅ Bangun WHERE clause dinamis
$where_parts = ["1=1"];
$params      = [];

if (!empty($destination)) {
    $where_parts[] = "h.location LIKE ?";
    $params[]      = "%$destination%";
}

// Filter bintang
if (!empty($filter_stars)) {
    $placeholders  = implode(',', array_fill(0, count($filter_stars), '?'));
    $where_parts[] = "h.star_rating IN ($placeholders)";
    foreach ($filter_stars as $s) $params[] = (int)$s;
}

// Filter harga
$where_parts[] = "r.price_per_night BETWEEN ? AND ?";
$params[]      = $filter_price_min;
$params[]      = $filter_price_max;

// Filter fasilitas
foreach ($filter_facilities as $f) {
    $f = trim($f);
    if (!empty($f)) {
        $where_parts[] = "r.facilities LIKE ?";
        $params[]      = "%$f%";
    }
}

$where_sql = implode(' AND ', $where_parts);

// Query count
$count_stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT h.id)
    FROM hotels h
    JOIN rooms r ON r.hotel_id = h.id
    WHERE $where_sql
");
$count_stmt->execute($params);
$total_hotels = (int)$count_stmt->fetchColumn();
$total_pages  = max(1, ceil($total_hotels / $per_page));

// Query utama
$stmt = $pdo->prepare("
    SELECT h.id, h.name, h.location, h.star_rating,
           h.review_score, h.review_count, h.badge, h.image_url,
           MIN(r.price_per_night) AS min_price,
           MIN(r.original_price)  AS original_price,
           MIN(r.stock)           AS min_stock
    FROM hotels h
    JOIN rooms r ON r.hotel_id = h.id
    WHERE $where_sql
    GROUP BY h.id, h.name, h.location, h.star_rating,
             h.review_score, h.review_count, h.badge, h.image_url
    ORDER BY $order
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$hotels = $stmt->fetchAll();

// Fasilitas
$facilities_map = [];
if (!empty($hotels)) {
    $hotel_ids    = array_column($hotels, 'id');
    $placeholders = implode(',', array_fill(0, count($hotel_ids), '?'));
    $fstmt = $pdo->prepare("
        SELECT hotel_id, ANY_VALUE(facilities) as facilities
        FROM rooms WHERE hotel_id IN ($placeholders)
        GROUP BY hotel_id
    ");
    $fstmt->execute($hotel_ids);
    while ($row = $fstmt->fetch()) {
        $facilities_map[$row['hotel_id']] = $row['facilities'];
    }
}

$facility_icons = [
    'wifi'      => ['wifi',           'Free Wi-Fi'],
    'pool'      => ['pool',           'Pool'],
    'breakfast' => ['restaurant',     'Breakfast Inc.'],
    'spa'       => ['spa',            'Full Spa'],
    'gym'       => ['fitness_center', 'Gym'],
    'bar'       => ['local_bar',      'Rooftop Bar'],
    'smarttv'   => ['airplay',        'Smart TV'],
];

function renderStars(int $count): string {
    $stars = '';
    for ($i = 0; $i < $count; $i++) {
        $stars .= '<span class="material-symbols-outlined"
                         style="font-variation-settings: \'FILL\' 1;">star</span>';
    }
    return $stars;
}

function reviewLabel(float $score): string {
    return match(true) {
        $score >= 9.0 => 'Wonderful',
        $score >= 8.5 => 'Excellent',
        $score >= 8.0 => 'Very Good',
        default       => 'Good'
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TravelEase - Hotel Listings</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-primary": "#ffffff","surface-variant": "#dbe4e9",
                        "surface-container": "#e6eff5","primary": "#005f9f",
                        "on-surface": "#141d21","outline-variant": "#bfc7d4",
                        "background": "#f3faff","error": "#ba1a1a",
                        "surface-container-lowest": "#ffffff","on-primary-container": "#fdfcff",
                        "primary-container": "#0078c7","outline": "#707884",
                        "surface-container-highest": "#dbe4e9","on-secondary": "#ffffff",
                        "on-surface-variant": "#3f4752","surface": "#f3faff",
                        "secondary-container": "#ff5e1f","secondary": "#ab3500",
                        "surface-container-low": "#ecf5fb",
                    },
                    spacing: {
                        "md":"16px","xs":"4px","gutter":"20px","lg":"24px",
                        "container-max":"1200px","sm":"12px","xl":"32px","base":"8px"
                    },
                    fontSize: {
                        "headline-md": ["20px",{lineHeight:"28px",fontWeight:"600"}],
                        "label-sm":    ["12px",{lineHeight:"16px",fontWeight:"500"}],
                        "headline-lg": ["32px",{lineHeight:"40px",fontWeight:"700"}],
                        "body-lg":     ["16px",{lineHeight:"24px",fontWeight:"400"}],
                        "body-md":     ["14px",{lineHeight:"20px",fontWeight:"400"}],
                        "label-md":    ["14px",{lineHeight:"20px",fontWeight:"600"}],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;
            vertical-align: middle;
        }
        .raised-card { box-shadow: 0px 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-background text-on-surface">

<!-- Header -->
<header class="bg-surface border-b border-outline-variant shadow-sm top-0 z-50 sticky">
    <div class="flex justify-between items-center w-full px-gutter max-w-container-max mx-auto h-20">
        <div class="font-headline-lg text-headline-lg font-bold text-primary">TravelEase</div>
        <nav class="hidden md:flex items-center gap-xl">
            <a class="text-primary font-bold border-b-2 border-primary pb-1" href="/travelease/hotels/index.php">Hotels</a>
            <!-- <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="#">Flights</a>
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="#">Trains</a>
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="#">Activities</a> -->
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="/travelease/booking/mybooking.php">My Bookings</a>
        </nav>
        <div class="flex items-center gap-md">
            <span class="text-on-surface-variant font-medium">
                Halo, <strong class="text-primary">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </strong>
            </span>
            <a href="/travelease/auth/logout.php"
               class="font-label-md px-lg py-sm rounded-lg border border-primary text-primary hover:bg-surface-container transition-all">
                Logout
            </a>
        </div>
    </div>
</header>

<!-- Search Bar -->
<section class="bg-surface-container-low py-md sticky top-20 z-40 border-b border-outline-variant">
    <div class="max-w-container-max mx-auto px-gutter">
        <form method="GET" action="" id="searchForm">
            <div class="flex flex-col md:flex-row gap-md items-center bg-surface-container-lowest p-sm rounded-xl border border-outline-variant shadow-sm">

                <!-- Destinasi -->
                <div class="flex-1 flex items-center gap-sm px-md border-r border-outline-variant/30">
                    <span class="material-symbols-outlined text-primary">location_on</span>
                    <div class="flex flex-col w-full">
                        <span class="text-label-sm font-label-sm text-on-surface-variant">Destinasi</span>
                        <input class="bg-transparent border-none p-0 text-body-md focus:ring-0 w-full"
                               name="destination" placeholder="Where to?" type="text"
                               value="<?= htmlspecialchars($destination) ?>">
                    </div>
                </div>

                <!-- Tanggal (info saja) -->
                <div class="flex-1 flex items-center gap-sm px-md border-r border-outline-variant/30">
                    <span class="material-symbols-outlined text-primary">calendar_today</span>
                    <div class="flex flex-col">
                        <span class="text-label-sm text-on-surface-variant">Tanggal</span>
                        <span class="text-body-md text-on-surface-variant">Pilih saat booking</span>
                    </div>
                </div>

                <!-- Tamu (info saja) -->
                <div class="flex-1 flex items-center gap-sm px-md">
                    <span class="material-symbols-outlined text-primary">person</span>
                    <div class="flex flex-col">
                        <span class="text-label-sm text-on-surface-variant">Tamu</span>
                        <span class="text-body-md text-on-surface-variant">Pilih saat booking</span>
                    </div>
                </div>

                <button type="submit"
                        class="bg-secondary-container text-on-primary px-xl py-md rounded-lg font-label-md hover:bg-secondary transition-all active:scale-95">
                    Search
                </button>
            </div>
        </form>
    </div>
</section>

<main class="max-w-container-max mx-auto px-gutter py-xl flex flex-col md:flex-row gap-xl">

    <!-- Sidebar Filter -->
    <aside class="w-full md:w-72 flex-shrink-0 space-y-xl">
    <div class="bg-surface-container-lowest p-lg rounded-xl border border-outline-variant shadow-sm">
        <h3 class="font-headline-md text-headline-md text-on-surface mb-lg">Filters</h3>

        <form method="GET" action="" id="filterForm">
            <!-- Pertahankan parameter search -->
            <input type="hidden" name="destination" value="<?= htmlspecialchars($destination) ?>">
            <input type="hidden" name="sort"        value="<?= $sort ?>">

            <!-- Filter Harga -->
            <div class="mb-xl">
                <label class="font-label-md text-label-md text-on-surface block mb-md">
                    Rentang Harga (Per Malam)
                </label>
                <div class="flex gap-sm mb-sm">
                    <input type="number" name="price_min" id="price_min"
                           value="<?= $filter_price_min ?>" min="0" max="5000000" step="50000"
                           class="w-full border border-outline-variant rounded-lg px-sm py-xs text-label-sm focus:ring-primary focus:border-primary">
                    <input type="number" name="price_max" id="price_max"
                           value="<?= $filter_price_max ?>" min="0" max="5000000" step="50000"
                           class="w-full border border-outline-variant rounded-lg px-sm py-xs text-label-sm focus:ring-primary focus:border-primary">
                </div>
                <input id="priceRange" type="range" min="0" max="5000000" step="50000"
                       value="<?= $filter_price_max ?>"
                       class="w-full h-2 bg-surface-variant rounded-full appearance-none cursor-pointer accent-primary">
                <div class="flex justify-between mt-xs">
                    <span class="text-label-sm text-on-surface-variant">
                        Rp <?= number_format($filter_price_min, 0, ',', '.') ?>
                    </span>
                    <span class="text-label-sm text-on-surface-variant" id="priceMaxLabel">
                        Rp <?= number_format($filter_price_max, 0, ',', '.') ?>
                    </span>
                </div>
            </div>

            <!-- Filter Bintang -->
            <div class="mb-xl">
                <label class="font-label-md text-label-md text-on-surface block mb-md">Bintang</label>
                <div class="space-y-sm">
                    <?php foreach ([5, 4, 3] as $star): ?>
                    <label class="flex items-center gap-sm cursor-pointer">
                        <input type="checkbox" name="stars[]" value="<?= $star ?>"
                               <?= in_array((string)$star, $filter_stars) ? 'checked' : '' ?>
                               class="w-5 h-5 rounded border-outline text-primary focus:ring-primary"
                               onchange="document.getElementById('filterForm').submit()">
                        <div class="flex text-secondary-container">
                            <?= renderStars($star) ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Filter Fasilitas -->
            <div class="mb-xl">
                <label class="font-label-md text-label-md text-on-surface block mb-md">Fasilitas</label>
                <div class="space-y-sm">
                    <?php
                    $filter_options = [
                        ['wifi',  'wifi',           'Wi-Fi'],
                        ['pool',  'pool',           'Pool'],
                        ['gym',   'fitness_center', 'Gym'],
                        ['spa',   'spa',            'Spa'],
                        ['breakfast', 'restaurant', 'Breakfast'],
                    ];
                    foreach ($filter_options as [$val, $icon, $label]):
                    ?>
                    <label class="flex items-center justify-between cursor-pointer">
                        <div class="flex items-center gap-sm">
                            <input type="checkbox" name="facilities[]" value="<?= $val ?>"
                                   <?= in_array($val, $filter_facilities) ? 'checked' : '' ?>
                                   class="w-5 h-5 rounded border-outline text-primary focus:ring-primary"
                                   onchange="document.getElementById('filterForm').submit()">
                            <span class="text-body-md text-on-surface-variant"><?= $label ?></span>
                        </div>
                        <span class="material-symbols-outlined text-outline"><?= $icon ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tombol Apply & Reset -->
            <div class="flex gap-sm">
                <button type="submit"
                        class="flex-1 bg-primary text-on-primary py-sm rounded-lg font-label-md hover:bg-primary-container transition-all">
                    Terapkan
                </button>
                <a href="/travelease/hotels/index.php"
                   class="flex-1 border border-outline-variant text-on-surface-variant py-sm rounded-lg font-label-md text-center hover:bg-surface-container transition-all">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Promo (tetap sama) -->
    <div class="bg-primary-container p-lg rounded-xl text-on-primary-container relative overflow-hidden group">
        <div class="relative z-10">
            <h4 class="font-headline-md text-headline-md mb-xs">Unlock 15% Off</h4>
            <p class="text-body-md opacity-90 mb-md">Join TravelEase Rewards for exclusive member prices.</p>
            <button class="bg-on-primary-container text-primary-container px-lg py-sm rounded-lg font-label-md hover:opacity-90 transition-all">
                Sign Up Free
            </button>
        </div>
        <div class="absolute -right-4 -bottom-4 opacity-10 transform group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-[120px]">loyalty</span>
        </div>
    </div>
</aside>

    <!-- Daftar Hotel -->
    <section class="flex-1 space-y-md">

        <!-- Sort & Total -->
        <div class="flex justify-between items-center mb-md">
            <h2 class="font-headline-md text-headline-md text-on-surface">
                <?= $total_hotels ?> Hotel ditemukan di
                <span class="text-primary"><?= htmlspecialchars($destination) ?></span>
            </h2>
            <div class="flex items-center gap-sm">
                <span class="text-label-sm text-on-surface-variant">Sort by:</span>
                <form method="GET" action="">
                    <input type="hidden" name="destination" value="<?= htmlspecialchars($destination) ?>">
                    <input type="hidden" name="page" value="1">
                    <select name="sort" onchange="this.form.submit()"
                            class="bg-surface-container-lowest border border-outline-variant rounded-lg px-md py-xs text-body-md focus:ring-primary">
                        <option value="recommended" <?= $sort === 'recommended' ? 'selected' : '' ?>>Recommended</option>
                        <option value="price_asc"   <?= $sort === 'price_asc'   ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="star"        <?= $sort === 'star'        ? 'selected' : '' ?>>Star Rating</option>
                        <option value="review"      <?= $sort === 'review'      ? 'selected' : '' ?>>Review Score</option>
                    </select>
                </form>
            </div>
        </div>

        <!-- Pesan jika tidak ada hasil -->
        <?php if (empty($hotels)): ?>
            <div class="text-center py-xl text-on-surface-variant">
                <span class="material-symbols-outlined text-[48px] block mb-md">search_off</span>
                <p class="font-headline-md">Tidak ada hotel ditemukan</p>
                <p class="text-body-md mt-sm">Coba cari dengan kata kunci lain</p>
            </div>
        <?php endif; ?>

        <!-- Hotel Cards -->
        <?php foreach ($hotels as $hotel): ?>
        <div class="bg-surface-container-lowest rounded-xl border border-outline-variant flex flex-col md:flex-row overflow-hidden raised-card hover:shadow-md transition-shadow group">

            <!-- Gambar -->
            <div class="md:w-72 h-48 md:h-auto relative overflow-hidden">
                <img alt="<?= htmlspecialchars($hotel['name']) ?>"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                     src="<?= htmlspecialchars($hotel['image_url'] ?? '') ?>">
                <?php if (!empty($hotel['badge'])): ?>
                    <div class="absolute top-4 left-4 bg-primary text-on-primary px-md py-xs rounded-full font-label-sm text-label-sm">
                        <?= htmlspecialchars($hotel['badge']) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Info Hotel -->
            <div class="flex-1 p-lg flex flex-col">
                <div class="flex justify-between items-start">
                    <div>
                        <!-- Nama & Bintang -->
                        <div class="flex items-center gap-sm mb-xs">
                            <h3 class="font-headline-md text-headline-md text-on-surface">
                                <?= htmlspecialchars($hotel['name']) ?>
                            </h3>
                            <div class="flex text-secondary-container scale-75 origin-left">
                                <?= renderStars((int)$hotel['star_rating']) ?>
                            </div>
                        </div>

                        <!-- Lokasi -->
                        <div class="flex items-center gap-xs text-on-surface-variant text-body-md mb-md">
                            <span class="material-symbols-outlined text-sm">location_on</span>
                            <?= htmlspecialchars($hotel['location']) ?>
                        </div>

                        <!-- ✅ FIX: Fasilitas dari $facilities_map, BUKAN query dalam loop -->
                        <div class="flex flex-wrap gap-xs">
                            <?php
                            $raw = $facilities_map[$hotel['id']] ?? '';
                            $items = array_filter(array_map('trim', explode(',', $raw)));
                            foreach ($items as $f):
                                if (!isset($facility_icons[$f])) continue;
                                [$icon, $label] = $facility_icons[$f];
                            ?>
                            <span class="bg-surface-container px-sm py-1 rounded-sm text-label-sm font-label-sm flex items-center gap-xs">
                                <span class="material-symbols-outlined text-[14px]"><?= $icon ?></span>
                                <?= $label ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Review Score -->
                    <div class="text-right flex-shrink-0 ml-md">
                        <div class="flex items-center gap-sm">
                            <div class="text-right">
                                <div class="font-label-md text-label-md text-on-surface">
                                    <?= reviewLabel((float)$hotel['review_score']) ?>
                                </div>
                                <div class="text-label-sm text-on-surface-variant">
                                    <?= number_format($hotel['review_count']) ?> reviews
                                </div>
                            </div>
                            <div class="bg-primary-container text-on-primary-container w-10 h-10 flex items-center justify-center rounded-lg font-bold">
                                <?= $hotel['review_score'] ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Harga & Tombol -->
                <div class="mt-auto pt-lg border-t border-outline-variant/30 flex items-end justify-between">
                    <div>
                        <?php if ((int)$hotel['min_stock'] <= 5): ?>
                            <span class="text-error font-label-sm text-label-sm mb-xs block">
                                Low Stock: hanya <?= (int)$hotel['min_stock'] ?> kamar tersisa!
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-col items-end">
                        <?php if (!empty($hotel['original_price'])): ?>
                            <span class="text-on-surface-variant line-through text-body-md">
                                Rp <?= number_format((float)$hotel['original_price'], 0, ',', '.') ?>
                            </span>
                        <?php endif; ?>
                        <div class="text-secondary-container font-headline-md text-headline-md mb-md">
                            Rp <?= number_format((float)$hotel['min_price'], 0, ',', '.') ?>
                            <span class="text-label-sm text-on-surface-variant font-normal">/ night</span>
                        </div>
                        <a href="/travelease/rooms/detail.php?id=<?= (int)$hotel['id'] ?>"
                           class="bg-primary text-on-primary px-xl py-md rounded-lg font-label-md text-label-md hover:bg-primary-container transition-all active:scale-95">
                            Lihat Kamar
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center pt-xl">
            <nav class="flex items-center gap-xs">
                <?php if ($page > 1): ?>
                    <a href="?destination=<?= urlencode($destination) ?>&sort=<?= $sort ?>&page=<?= $page - 1 ?>"
                       class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?destination=<?= urlencode($destination) ?>&sort=<?= $sort ?>&page=<?= $i ?>"
                       class="w-10 h-10 flex items-center justify-center rounded-lg transition-all
                              <?= $i === $page
                                  ? 'bg-primary text-on-primary font-bold'
                                  : 'border border-outline-variant text-on-surface-variant hover:bg-surface-container' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?destination=<?= urlencode($destination) ?>&sort=<?= $sort ?>&page=<?= $page + 1 ?>"
                       class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>

    </section>
</main>

<!-- Footer -->
<footer class="bg-surface-container-highest mt-20">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-xl px-gutter py-xl max-w-container-max mx-auto">
        <div class="col-span-2 md:col-span-1">
            <div class="font-headline-md font-black text-on-surface mb-md">TravelEase</div>
            <p class="text-body-md text-on-surface-variant">
                © 2024 TravelEase. All rights reserved. Your trusted travel partner.
            </p>
        </div>
        <div>
            <h5 class="font-label-md text-primary mb-md">About</h5>
            <ul class="space-y-sm">
                <li><a class="text-body-md text-on-surface-variant hover:text-primary underline" href="#">About Us</a></li>
                <li><a class="text-body-md text-on-surface-variant hover:text-primary underline" href="#">Careers</a></li>
                <li><a class="text-body-md text-on-surface-variant hover:text-primary underline" href="#">Partner with Us</a></li>
            </ul>
        </div>
        <div>
            <h5 class="font-label-md text-primary mb-md">Support</h5>
            <ul class="space-y-sm">
                <li><a class="text-body-md text-on-surface-variant hover:text-primary underline" href="#">Help Center</a></li>
                <li><a class="text-body-md text-on-surface-variant hover:text-primary underline" href="#">Terms of Service</a></li>
                <li><a class="text-body-md text-on-surface-variant hover:text-primary underline" href="#">Privacy Policy</a></li>
            </ul>
        </div>
        <div>
            <h5 class="font-label-md text-primary mb-md">Booking</h5>
            <ul class="space-y-sm">
                <li><a class="text-body-md text-on-surface-variant hover:text-primary underline" href="#">Hotels</a></li>
                <li><a class="text-body-md text-on-surface-variant hover:text-primary underline" href="#">Flights</a></li>
                <li><a class="text-body-md text-on-surface-variant hover:text-primary underline" href="#">Experience</a></li>
            </ul>
        </div>
    </div>
</footer>

<script>
    // Sync range slider dengan input price_max
    const priceRange    = document.getElementById('priceRange');
    const priceMaxInput = document.getElementById('price_max');
    const priceMaxLabel = document.getElementById('priceMaxLabel');

    priceRange.addEventListener('input', function() {
        priceMaxInput.value = this.value;
        priceMaxLabel.textContent = 'Rp ' + parseInt(this.value).toLocaleString('id-ID');
    });

    priceMaxInput.addEventListener('input', function() {
        priceRange.value = this.value;
        priceMaxLabel.textContent = 'Rp ' + parseInt(this.value).toLocaleString('id-ID');
    });

</script>

</body>
</html>
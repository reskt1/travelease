<?php
require '../includes/auth_check.php';
require '../config/database.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Filter tab
$filter = $_GET['filter'] ?? 'all';

// Query bookings milik user yang login
$where = "WHERE b.user_id = ?";
$params = [$user_id];

if ($filter === 'active') {
    $where .= " AND b.status IN ('pending', 'confirmed')";
} elseif ($filter === 'history') {
    $where .= " AND b.status IN ('cancelled')";
}

$stmt = $pdo->prepare("
    SELECT b.*,
           r.room_type, r.price_per_night,
           h.name AS hotel_name, h.location AS hotel_location,
           h.image_url AS hotel_image, h.star_rating
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN hotels h ON r.hotel_id = h.id
    $where
    ORDER BY b.created_at DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Statistik
$stats_stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
        SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END) AS total_spent
    FROM bookings WHERE user_id = ?
");
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>TravelEase | Pesanan Saya</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary-container": "#0078c7",
                        "outline": "#707884",
                        "secondary": "#ab3500",
                        "on-background": "#141d21",
                        "surface-container-low": "#ecf5fb",
                        "surface-container": "#e6eff5",
                        "on-primary-container": "#fdfcff",
                        "secondary-container": "#ff5e1f",
                        "error": "#ba1a1a",
                        "on-secondary": "#ffffff",
                        "on-surface-variant": "#3f4752",
                        "error-container": "#ffdad6",
                        "on-error-container": "#93000a",
                        "on-surface": "#141d21",
                        "surface": "#f3faff",
                        "on-primary": "#ffffff",
                        "background": "#f3faff",
                        "surface-container-lowest": "#ffffff",
                        "primary": "#005f9f",
                        "secondary-fixed": "#ffdbd0",
                        "on-secondary-container": "#551600",
                        "surface-container-high": "#e0e9ef",
                        "outline-variant": "#bfc7d4",
                        "surface-container-highest": "#dbe4e9"
                    },
                    spacing: {
                        "xl": "32px", "gutter": "20px", "container-max": "1200px",
                        "base": "8px", "xs": "4px", "sm": "12px", "md": "16px", "lg": "24px"
                    },
                    fontSize: {
                        "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "label-sm":    ["12px", {"lineHeight": "16px", "fontWeight": "500"}],
                        "headline-lg": ["32px", {"lineHeight": "40px", "fontWeight": "700"}],
                        "body-lg":     ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "body-md":     ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "label-md":    ["14px", {"lineHeight": "20px", "fontWeight": "600"}],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
        }
        .card-hover { transition: box-shadow 0.2s, transform 0.2s; }
        .card-hover:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.10); transform: translateY(-2px); }
    </style>
</head>
<body class="bg-background text-on-background min-h-screen">

<!-- Header -->
<header class="bg-surface border-b border-outline-variant shadow-sm top-0 z-50 sticky">
    <div class="flex justify-between items-center w-full px-gutter max-w-container-max mx-auto h-20">
        <div class="font-headline-lg text-headline-lg font-bold text-primary">TravelEase</div>
        <nav class="hidden md:flex items-center gap-xl">
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="/travelease/hotels/index.php">Hotels</a>
            <!-- <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="#">Flights</a>
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="#">Trains</a>
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="#">Activities</a> -->
            <a class="text-primary font-bold border-b-2 border-primary pb-1" href="/travelease/booking/mybooking.php">My Bookings</a>
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

<main class="max-w-container-max mx-auto px-gutter py-xl pb-24 md:pb-xl">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-xl">

        <!-- Kiri: Daftar Booking -->
        <div class="lg:col-span-8 space-y-lg">

            <!-- Judul -->
            <div>
                <h1 class="font-headline-lg text-headline-lg text-on-background mb-1">Pesanan Saya</h1>
                <p class="text-body-lg text-on-surface-variant">Kelola dan pantau semua reservasi hotel Anda.</p>
            </div>

            <!-- Tab Filter -->
            <div class="flex items-center border-b border-outline-variant overflow-x-auto whitespace-nowrap">
                <?php
                $tabs = [
                    'all'     => 'Semua Pesanan',
                    'active'  => 'Sedang Berjalan',
                    'history' => 'Riwayat',
                ];
                foreach ($tabs as $key => $label):
                    $active = $filter === $key;
                ?>
                <a href="?filter=<?= $key ?>"
                   class="px-md py-4 border-b-2 text-label-md font-medium transition-colors
                          <?= $active
                              ? 'border-primary text-primary font-bold'
                              : 'border-transparent text-on-surface-variant hover:text-primary' ?>">
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Booking Cards -->
            <div class="space-y-md">

                <?php if (empty($bookings)): ?>
                <div class="text-center py-16 text-on-surface-variant bg-surface-container-lowest rounded-xl border border-outline-variant">
                    <span class="material-symbols-outlined text-[56px] block mb-md text-outline">luggage</span>
                    <p class="text-headline-md font-semibold mb-sm">Belum ada pesanan</p>
                    <p class="text-body-md mb-lg">Yuk mulai rencanakan perjalanan kamu!</p>
                    <a href="/travelease/hotels/index.php"
                       class="bg-primary text-on-primary px-xl py-md rounded-lg text-label-md font-bold hover:bg-primary-container transition-all inline-block">
                        Cari Hotel
                    </a>
                </div>
                <?php endif; ?>

                <?php foreach ($bookings as $b):
                    $nights = (int)((strtotime($b['check_out']) - strtotime($b['check_in'])) / 86400);

                    // Status config
                    $status_config = match($b['status']) {
                        'confirmed' => [
                            'label'      => 'Dikonfirmasi',
                            'dot'        => 'bg-green-500',
                            'badge'      => 'bg-green-50 text-green-700 border-green-200',
                            'card_class' => '',
                            'grayscale'  => '',
                        ],
                        'cancelled' => [
                            'label'      => 'Dibatalkan',
                            'dot'        => 'bg-error',
                            'badge'      => 'bg-error-container text-on-error-container border-error',
                            'card_class' => 'opacity-75',
                            'grayscale'  => 'grayscale',
                        ],
                        default => [ // pending
                            'label'      => 'Menunggu Konfirmasi',
                            'dot'        => 'bg-yellow-500',
                            'badge'      => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                            'card_class' => 'border-yellow-300',
                            'grayscale'  => '',
                        ],
                    };
                ?>
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant
                            p-md md:p-lg flex flex-col md:flex-row gap-md card-hover
                            <?= $status_config['card_class'] ?>">

                    <!-- Gambar Hotel -->
                    <div class="w-full md:w-44 h-36 md:h-auto rounded-lg overflow-hidden flex-shrink-0 <?= $status_config['grayscale'] ?>">
                        <?php if (!empty($b['hotel_image'])): ?>
                            <img src="<?= htmlspecialchars($b['hotel_image']) ?>"
                                 alt="<?= htmlspecialchars($b['hotel_name']) ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full bg-surface-container flex items-center justify-center">
                                <span class="material-symbols-outlined text-[48px] text-outline">hotel</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="flex-grow flex flex-col justify-between">
                        <div class="flex flex-col md:flex-row md:items-start justify-between gap-sm">
                            <div>
                                <!-- Label & ID -->
                                <div class="flex items-center gap-xs mb-1">
                                    <span class="material-symbols-outlined text-primary text-[16px]">hotel</span>
                                    <span class="text-label-sm text-on-surface-variant">
                                        Hotel • ID: #<?= str_pad($b['id'], 8, '0', STR_PAD_LEFT) ?>
                                    </span>
                                </div>

                                <!-- Nama Hotel -->
                                <h3 class="text-headline-md font-semibold text-on-surface mb-xs">
                                    <?= htmlspecialchars($b['hotel_name']) ?>
                                </h3>

                                <!-- Tipe Kamar -->
                                <p class="text-body-md text-on-surface-variant flex items-center gap-xs mb-xs">
                                    <span class="material-symbols-outlined text-[15px]">bed</span>
                                    <?= htmlspecialchars($b['room_type']) ?>
                                </p>

                                <!-- Lokasi -->
                                <p class="text-body-md text-on-surface-variant flex items-center gap-xs mb-xs">
                                    <span class="material-symbols-outlined text-[15px]">location_on</span>
                                    <?= htmlspecialchars($b['hotel_location']) ?>
                                </p>

                                <!-- Tanggal -->
                                <p class="text-body-md text-on-surface-variant flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-[15px]">calendar_today</span>
                                    <?= date('d M Y', strtotime($b['check_in'])) ?>
                                    &rarr;
                                    <?= date('d M Y', strtotime($b['check_out'])) ?>
                                    <span class="text-label-sm bg-surface-container px-sm py-xs rounded-full ml-xs">
                                        <?= $nights ?> malam
                                    </span>
                                </p>
                            </div>

                            <!-- Badge Status -->
                            <span class="inline-flex items-center gap-xs px-md py-1 rounded-full text-label-sm border
                                         <?= $status_config['badge'] ?> self-start flex-shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full <?= $status_config['dot'] ?>"></span>
                                <?= $status_config['label'] ?>
                            </span>
                        </div>

                        <!-- Harga & Aksi -->
                        <div class="mt-md pt-md border-t border-outline-variant flex items-center justify-between">
                            <div>
                                <span class="text-label-sm text-on-surface-variant block">Total Pembayaran</span>
                                <span class="text-headline-md font-bold text-secondary-container">
                                    Rp <?= number_format((float)$b['total_price'], 0, ',', '.') ?>
                                </span>
                            </div>

                            <div class="flex gap-sm">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <span class="text-yellow-600 text-label-sm font-medium flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-[16px]">schedule</span>
                                        Menunggu konfirmasi admin
                                    </span>
                                <?php elseif ($b['status'] === 'confirmed'): ?>
                                    <span class="text-green-600 text-label-sm font-medium flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-[16px]"
                                              style="font-variation-settings:'FILL' 1">check_circle</span>
                                        Booking Terkonfirmasi
                                    </span>
                                <?php else: ?>
                                    <span class="text-outline text-label-sm font-medium flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-[16px]">cancel</span>
                                        Pesanan Dibatalkan
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
        </div>

        <!-- Kanan: Sidebar -->
        <aside class="lg:col-span-4 space-y-lg">

            <!-- User Profile Card -->
            <div class="bg-primary-container text-on-primary-container p-lg rounded-xl shadow-lg relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center gap-md mb-lg">
                        <div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-[32px] text-white">person</span>
                        </div>
                        <div>
                            <h4 class="text-headline-md font-semibold"><?= htmlspecialchars($user_name) ?></h4>
                            <p class="text-label-sm opacity-80">Member TravelEase</p>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md rounded-lg p-md">
                        <div class="flex justify-between items-center">
                            <span class="text-label-sm">Total Pengeluaran</span>
                            <span class="text-label-md font-bold">
                                Rp <?= number_format((float)($stats['total_spent'] ?? 0), 0, ',', '.') ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-white/5 rounded-full"></div>
            </div>

            <!-- Statistik -->
            <div class="bg-surface-container-lowest border border-outline-variant p-lg rounded-xl shadow-sm">
                <h4 class="text-headline-md font-semibold text-on-surface mb-lg">Statistik Pesanan</h4>
                <div class="grid grid-cols-2 gap-md">
                    <div class="p-md bg-surface-container-low rounded-lg text-center">
                        <span class="material-symbols-outlined text-primary mb-1 block">book_online</span>
                        <div class="text-headline-md font-bold text-on-surface"><?= (int)($stats['total'] ?? 0) ?></div>
                        <div class="text-label-sm text-on-surface-variant">Total</div>
                    </div>
                    <div class="p-md bg-surface-container-low rounded-lg text-center">
                        <span class="material-symbols-outlined text-green-500 mb-1 block">check_circle</span>
                        <div class="text-headline-md font-bold text-green-600"><?= (int)($stats['confirmed'] ?? 0) ?></div>
                        <div class="text-label-sm text-on-surface-variant">Dikonfirmasi</div>
                    </div>
                    <div class="p-md bg-surface-container-low rounded-lg text-center">
                        <span class="material-symbols-outlined text-yellow-500 mb-1 block">schedule</span>
                        <div class="text-headline-md font-bold text-yellow-600"><?= (int)($stats['pending'] ?? 0) ?></div>
                        <div class="text-label-sm text-on-surface-variant">Pending</div>
                    </div>
                    <div class="p-md bg-surface-container-low rounded-lg text-center">
                        <span class="material-symbols-outlined text-error mb-1 block">cancel</span>
                        <div class="text-headline-md font-bold text-error"><?= (int)($stats['cancelled'] ?? 0) ?></div>
                        <div class="text-label-sm text-on-surface-variant">Dibatalkan</div>
                    </div>
                </div>
            </div>

            <!-- Promo Banner -->
            <div class="bg-secondary-container text-white p-lg rounded-xl shadow-md relative overflow-hidden group cursor-pointer">
                <div class="relative z-10">
                    <h5 class="text-headline-md font-semibold mb-sm">Temukan Hotel Baru!</h5>
                    <p class="text-body-md mb-lg opacity-90">Ribuan pilihan hotel terbaik menanti perjalanan Anda.</p>
                    <a href="/travelease/hotels/index.php"
                       class="bg-white text-secondary-container px-lg py-sm rounded-lg text-label-md font-bold hover:opacity-90 transition-all inline-block">
                        Cari Hotel
                    </a>
                </div>
                <span class="material-symbols-outlined absolute -right-4 -bottom-4 text-[120px] opacity-20
                             transform -rotate-12 group-hover:scale-110 transition-transform">hotel</span>
            </div>

        </aside>
    </div>
</main>

<!-- Footer -->
<footer class="bg-surface-container-lowest border-t border-outline-variant mt-xl">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-lg px-gutter py-xl max-w-container-max mx-auto">
        <div>
            <div class="text-headline-md font-bold text-primary mb-md">TravelEase</div>
            <p class="text-body-md text-on-surface-variant">Partner perjalanan terpercaya Anda.</p>
        </div>
        <div>
            <h6 class="text-label-md text-on-surface uppercase tracking-wider mb-md">Perusahaan</h6>
            <ul class="space-y-sm">
                <li><a href="#" class="text-body-md text-on-surface-variant hover:text-primary">Tentang Kami</a></li>
                <li><a href="#" class="text-body-md text-on-surface-variant hover:text-primary">Karir</a></li>
            </ul>
        </div>
        <div>
            <h6 class="text-label-md text-on-surface uppercase tracking-wider mb-md">Bantuan</h6>
            <ul class="space-y-sm">
                <li><a href="#" class="text-body-md text-on-surface-variant hover:text-primary">Pusat Bantuan</a></li>
                <li><a href="#" class="text-body-md text-on-surface-variant hover:text-primary">Syarat & Ketentuan</a></li>
            </ul>
        </div>
        <div>
            <h6 class="text-label-md text-on-surface uppercase tracking-wider mb-md">Hubungi Kami</h6>
            <div class="flex gap-md">
                <a href="#" class="w-10 h-10 rounded-full bg-surface-container-low flex items-center justify-center text-primary hover:bg-primary-container hover:text-white transition-all">
                    <span class="material-symbols-outlined text-[20px]">mail</span>
                </a>
                <a href="#" class="w-10 h-10 rounded-full bg-surface-container-low flex items-center justify-center text-primary hover:bg-primary-container hover:text-white transition-all">
                    <span class="material-symbols-outlined text-[20px]">call</span>
                </a>
            </div>
        </div>
    </div>
    <div class="max-w-container-max mx-auto px-gutter py-md border-t border-outline-variant text-center">
        <p class="text-body-md text-on-surface-variant">© 2024 TravelEase. Seluruh hak cipta dilindungi.</p>
    </div>
</footer>

<!-- Bottom Nav Mobile -->
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-surface shadow-2xl z-50 flex items-center justify-around py-3 border-t border-outline-variant">
    <a href="/travelease/hotels/index.php" class="flex flex-col items-center gap-1 text-on-surface-variant">
        <span class="material-symbols-outlined">hotel</span>
        <span class="text-[10px] text-label-sm">Hotels</span>
    </a>
    <a href="/travelease/hotels/index.php" class="flex flex-col items-center gap-1 text-on-surface-variant">
        <span class="material-symbols-outlined">search</span>
        <span class="text-[10px] text-label-sm">Search</span>
    </a>
    <a href="/travelease/bookings/mybookings.php" class="flex flex-col items-center gap-1 text-primary">
        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">confirmation_number</span>
        <span class="text-[10px] font-bold text-label-sm">My Bookings</span>
    </a>
    <a href="#" class="flex flex-col items-center gap-1 text-on-surface-variant">
        <span class="material-symbols-outlined">person</span>
        <span class="text-[10px] text-label-sm">Profile</span>
    </a>
</div>

</body>
</html>
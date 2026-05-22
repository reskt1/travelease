<?php
require '../includes/auth_check.php';
require '../config/database.php';

// Validasi ID hotel dari URL
// (int) memastikan nilainya selalu integer, cegah SQL injection
$hotel_id = (int)($_GET['id'] ?? 0);

// Kalau ID tidak valid, balik ke listing
if ($hotel_id <= 0) {
    header("Location: /travelease/hotels/index.php");
    exit;
}

// Ambil data hotel
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->execute([$hotel_id]);
$hotel = $stmt->fetch();

// Kalau hotel tidak ditemukan
if (!$hotel) {
    header("Location: /travelease/hotels/index.php");
    exit;
}

// Ambil semua kamar hotel ini
$room_stmt = $pdo->prepare("
    SELECT * FROM rooms 
    WHERE hotel_id = ? 
    ORDER BY price_per_night ASC
");
$room_stmt->execute([$hotel_id]);
$rooms = $room_stmt->fetchAll();

// Harga terendah untuk ditampilkan di sidebar
$min_price = !empty($rooms) ? $rooms[0]['price_per_night'] : 0;

// Mapping ikon fasilitas
$facility_icons = [
    'wifi'      => ['wifi',           'Free Wi-Fi'],
    'pool'      => ['pool',           'Pool'],
    'breakfast' => ['restaurant',     'Breakfast'],
    'spa'       => ['spa',            'Full Spa'],
    'gym'       => ['fitness_center', 'Gym'],
    'bar'       => ['local_bar',      'Rooftop Bar'],
    'smarttv'   => ['airplay',        'Smart TV'],
];

// Kumpulkan semua fasilitas unik dari semua kamar
$all_facilities = [];
foreach ($rooms as $room) {
    $items = array_filter(array_map('trim', explode(',', $room['facilities'] ?? '')));
    foreach ($items as $item) {
        $all_facilities[$item] = true; // pakai key supaya tidak duplikat
    }
}
$all_facilities = array_keys($all_facilities);

// Fungsi render bintang
function renderStars(int $count): string {
    $html = '';
    for ($i = 0; $i < $count; $i++) {
        $html .= '<span class="material-symbols-outlined text-secondary-container" 
                        style="font-variation-settings: \'FILL\' 1;">star</span>';
    }
    return $html;
}
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?= htmlspecialchars($hotel['name']) ?> | TravelEase</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
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
                        "surface-container-low": "#ecf5fb","surface-bright": "#f3faff",
                        "surface-container-high": "#e0e9ef",
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
                        "display-lg":  ["48px",{lineHeight:"60px",fontWeight:"700"}],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-sidebar {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(0,95,159,0.1);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;
        }
    </style>
</head>
<body class="bg-background text-on-surface">

<!-- Header -->
<header class="bg-surface border-b border-outline-variant shadow-sm sticky top-0 z-50">
    <nav class="flex justify-between items-center w-full px-gutter max-w-container-max mx-auto h-20">
        <div class="font-headline-lg text-headline-lg font-bold text-primary">TravelEase</div>
        <div class="hidden md:flex items-center space-x-xl">
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="/travelease/hotels/index.php">Hotels</a>
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="#">Flights</a>
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="#">Trains</a>
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="#">Activities</a>
            <a class="text-primary font-bold border-b-2 border-primary pb-1" href="#">My Bookings</a>
        </div>
        <div class="flex items-center gap-md">
            <span class="text-on-surface-variant font-medium hidden md:block">
                Halo, <strong class="text-primary">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </strong>
            </span>
            <a href="/travelease/auth/logout.php"
               class="px-md py-xs font-label-md text-primary hover:bg-surface-container rounded-lg transition-all border border-primary">
                Logout
            </a>
        </div>
    </nav>
</header>

<main class="max-w-container-max mx-auto px-gutter py-xl">

    <!-- Breadcrumb -->
    <nav class="flex items-center gap-xs mb-md text-on-surface-variant">
        <a href="/travelease/hotels/index.php" class="font-label-sm hover:text-primary transition-colors">Hotels</a>
        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        <span class="font-label-sm">
            <?= htmlspecialchars(explode(',', $hotel['location'])[1] ?? $hotel['location']) ?>
        </span>
        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        <span class="font-label-sm text-primary font-bold">
            <?= htmlspecialchars($hotel['name']) ?>
        </span>
    </nav>

    <!-- Judul Hotel -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-md mb-xl">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">
                <?= htmlspecialchars($hotel['name']) ?>
            </h1>
            <div class="flex items-center gap-sm mt-xs">
                <div class="flex items-center bg-primary-container text-on-primary-container px-sm py-xs rounded-lg font-bold">
                    <span class="material-symbols-outlined mr-1 text-[18px]"
                          style="font-variation-settings: 'FILL' 1;">star</span>
                    <?= number_format((float)$hotel['review_score'], 1) ?>
                </div>
                <span class="text-on-surface-variant font-body-md">
                    <?= htmlspecialchars($hotel['location']) ?>
                </span>
            </div>
        </div>
        <div class="flex gap-sm">
            <button class="flex items-center gap-xs px-md py-sm border border-outline rounded-xl hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined">share</span>
                <span class="font-label-md">Share</span>
            </button>
            <button class="flex items-center gap-xs px-md py-sm border border-outline rounded-xl hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined">favorite</span>
                <span class="font-label-md">Save</span>
            </button>
        </div>
    </div>

    <!-- Galeri Foto -->
    <section class="grid grid-cols-1 md:grid-cols-4 grid-rows-2 gap-md h-[400px] md:h-[500px] mb-xl overflow-hidden rounded-xl">
        
        <!-- Foto utama — pakai gambar hotel dari DB -->
        <div class="md:col-span-2 md:row-span-2 relative group cursor-pointer overflow-hidden">
            <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                 src="<?= htmlspecialchars($hotel['image_url'] ?? '') ?>"
                 alt="<?= htmlspecialchars($hotel['name']) ?>">
        </div>

        <!-- Foto kamar — dari rooms, maksimal 4 foto -->
        <?php
        $room_photos = array_slice($rooms, 0, 4);
        $gallery_images = [
            'https://lh3.googleusercontent.com/aida-public/AB6AXuCvrronw03W8BfwJRyggGbddoeAG6-2gAcHHG8lA1wqxRMu4kE2vBAolICVGcT1l4GA7Wv6V0MeT3nRzUW-KkPm17I60pH6-88wXGBtiAnmwyJdH29yfbAdvk7AmR6ZGpbr-XgJKeSlu4yb8c4AyPpdmYNVM71vDxrszn-V4hn2kIb5IE3IVCTqK1pFLQ9yoA4F6Z0N2dPNCXGaNZrFaI_Kf5XFcyEmikXQPcg8PhJgDcx3fZWd4120oTabmVudymIXX48fg_hdiSc',
            'https://lh3.googleusercontent.com/aida-public/AB6AXuB9PsGwrNFD20-bXYtZDH0UGWPHl2itvie5v_UOrNg0nAWbaSTdgzX4OGeMM5kr_u2vLqnHxNYlelZePdORBlWc7ie6y0LdD0RlTScB3ITKnZH_AizilZl9yCdWjx5e3FIkX2ydV6bEcjh3dtH2IALJK-kVAm20Xl_Gii_wdDSjsDBjHnbtDwHZmfOJBeOv28knX-sE_gIg2zvqHEYQKukqfUd0u60oK78fsohJLy-BzhwwF7OiIC75cmiTYI2NXqMda9f9IuZ2LRg',
            'https://lh3.googleusercontent.com/aida-public/AB6AXuD-WIspNEpocwPms6RwU1nwOd12W3Qv03TzbTxl-ZLJpNiXCITRk0gUlvqhdXLginqW98ftt-wALO2RYFIdSlyRnSBsjjv1llaPSko3w_XmlSU5egTsopG1FiOIJazBVD2wivxJCJJJTcMV8r_miNd2NZcF2JAGi6qDH5E52Dkn4eFGfDfRzpxnFHkd_UuXDNiLvfJyJLKx_hoj5By7Xs3M9EivLQzyMxCGggEcp6CwpNn_9t1GgBf6ApKG49OBxXOU9we2J7VlgJE',
            'https://lh3.googleusercontent.com/aida-public/AB6AXuAV0WwhTH2e05FK6jLHJKKD9zpVCcEjfKAuKo7yxkTYO4QlQDWUJCmsnZguANlkSpG-l90YQkRIU0AIyyXrkmJUEtJe2LbMOafVGhpj1qtRAPblOv2KJPotCArQjYg6FVZrPWeQ2wGXMSICxeIe3Wnb5CuZCogWYnP0MiP4EBLMB9zni5msmt0NSYoIyqi9xVFULc7_tgVe8lF_wY0HwyrrMDO-XEmNTS9ajhzkdLS6QJGt4Q2akPZI8dkS2EHt55MBIsGWNtyT8aw',
        ];
        foreach ($gallery_images as $idx => $img_url):
            $is_last = $idx === count($gallery_images) - 1;
        ?>
        <div class="hidden md:block relative group cursor-pointer overflow-hidden">
            <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                 src="<?= $img_url ?>" alt="Gallery <?= $idx + 1 ?>">
            <?php if ($is_last): ?>
                <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="text-on-primary font-bold text-headline-md">+12 Photos</span>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </section>

    <!-- Konten Utama -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-xl relative">

        <!-- Kiri: Detail -->
        <div class="lg:col-span-8 space-y-xl">

            <!-- Fasilitas Populer -->
            <section class="bg-surface-container-low p-lg rounded-xl border border-outline-variant shadow-sm">
                <h2 class="font-headline-md text-headline-md mb-md">Popular Amenities</h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-md">
                    <?php foreach ($all_facilities as $f):
                        if (!isset($facility_icons[$f])) continue;
                        [$icon, $label] = $facility_icons[$f];
                    ?>
                    <div class="flex items-center gap-sm p-sm bg-surface rounded-lg">
                        <span class="material-symbols-outlined text-primary"><?= $icon ?></span>
                        <span class="font-label-md"><?= $label ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Daftar Kamar dari DB -->
            <section class="space-y-md">
                <div class="flex items-center justify-between">
                    <h2 class="font-headline-md text-headline-md">Available Room Types</h2>
                    <?php
                    // Cek apakah ada kamar yang stoknya rendah
                    $low_stock = array_filter($rooms, fn($r) => $r['stock'] <= 3);
                    ?>
                    <?php if (!empty($low_stock)): ?>
                        <span class="text-secondary font-label-md flex items-center gap-1">
                            <span class="material-symbols-outlined text-[18px]">bolt</span>
                            Limited availability!
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (empty($rooms)): ?>
                    <div class="text-center py-xl text-on-surface-variant">
                        <span class="material-symbols-outlined text-[48px] block mb-md">bed</span>
                        <p>Tidak ada kamar tersedia saat ini.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($rooms as $room):
                    $room_facilities = array_filter(
                        array_map('trim', explode(',', $room['facilities'] ?? ''))
                    );

                    // Icon kamar berdasarkan tipe
                    $bed_icon = str_contains(strtolower($room['room_type']), 'suite')
                        ? 'king_bed' : 'bed';
                ?>
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <div class="flex flex-col md:flex-row">

                        <!-- Foto Kamar -->
                        <div class="md:w-1/3 h-48 md:h-auto">
                            <img class="w-full h-full object-cover"
                                 src="<?= htmlspecialchars($room['image_url'] ?? $hotel['image_url'] ?? '') ?>"
                                 alt="<?= htmlspecialchars($room['room_type']) ?>">
                        </div>

                        <!-- Info Kamar -->
                        <div class="flex-1 p-lg flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start">
                                    <h3 class="font-headline-md text-headline-md">
                                        <?= htmlspecialchars($room['room_type']) ?>
                                    </h3>
                                    <div class="text-right">
                                        <div class="text-secondary-container font-headline-md text-headline-md">
                                            Rp <?= number_format((float)$room['price_per_night'], 0, ',', '.') ?>
                                        </div>
                                        <div class="text-on-surface-variant text-label-sm">per night</div>
                                    </div>
                                </div>

                                <!-- Badge Fasilitas Kamar -->
                                <div class="flex flex-wrap gap-sm mt-md">
                                    <div class="flex items-center gap-xs px-sm py-xs bg-surface-variant rounded-full text-label-sm">
                                        <span class="material-symbols-outlined text-[16px]"><?= $bed_icon ?></span>
                                        <?= htmlspecialchars($room['room_type']) ?>
                                    </div>
                                    <?php foreach ($room_facilities as $f):
                                        if (!isset($facility_icons[$f])) continue;
                                        [$icon, $label] = $facility_icons[$f];
                                    ?>
                                    <div class="flex items-center gap-xs px-sm py-xs bg-surface-variant rounded-full text-label-sm">
                                        <span class="material-symbols-outlined text-[16px]"><?= $icon ?></span>
                                        <?= $label ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Stok & Tombol Booking -->
                            <div class="flex items-center justify-between mt-xl">
                                <div class="flex flex-col gap-xs">
                                    <span class="text-on-surface-variant text-label-sm flex items-center gap-1">
                                        <span class="material-symbols-outlined text-green-600 text-[16px]">check_circle</span>
                                        Free cancellation
                                    </span>
                                    <?php if ((int)$room['stock'] <= 3): ?>
                                        <span class="text-error text-label-sm font-bold">
                                            Hanya <?= (int)$room['stock'] ?> kamar tersisa!
                                        </span>
                                    <?php else: ?>
                                        <span class="text-on-surface-variant text-label-sm">
                                            <?= (int)$room['stock'] ?> kamar tersedia
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Tombol Book Now — kirim room_id ke checkout -->
                                <a href="/travelease/booking/checkout.php?room_id=<?= (int)$room['id'] ?>"
                                   class="bg-secondary-container text-on-primary font-label-md px-xl py-md rounded-lg shadow-sm hover:opacity-90 active:scale-95 transition-all">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>

            <!-- Kebijakan -->
            <section class="bg-surface-container p-lg rounded-xl">
                <h2 class="font-headline-md text-headline-md mb-md">Property Policies</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-xl">
                    <div>
                        <h4 class="font-label-md text-primary mb-xs">Cancellation Policy</h4>
                        <p class="text-body-md text-on-surface-variant leading-relaxed">
                            Free cancellation available if cancelled at least 48 hours before
                            check-in. Non-refundable rates may apply for promotional packages.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-label-md text-primary mb-xs">Check-in / Check-out</h4>
                        <p class="text-body-md text-on-surface-variant">
                            Check-in: 3:00 PM – Midnight<br>
                            Check-out: Until 11:00 AM
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <!-- Sidebar Kanan -->
        <aside class="lg:col-span-4">
            <div class="glass-sidebar sticky top-28 p-lg rounded-2xl shadow-xl space-y-md">

                <!-- Harga -->
                <div class="pb-md border-b border-outline-variant">
                    <div class="text-label-sm text-on-surface-variant mb-1 uppercase tracking-wider">
                        Starting from
                    </div>
                    <div class="flex items-baseline gap-xs">
                        <span class="text-4xl font-black text-secondary-container">
                            Rp <?= number_format((float)$min_price, 0, ',', '.') ?>
                        </span>
                        <span class="text-on-surface-variant text-body-md">/ night</span>
                    </div>
                </div>

                <!-- Info Tanggal & Tamu -->
                <div class="space-y-sm">
                    <div class="p-sm bg-white rounded-lg border border-outline-variant">
                        <div class="text-label-sm text-on-surface-variant">DATES</div>
                        <div class="font-label-md">Pilih tanggal di bawah</div>
                    </div>
                    <div class="p-sm bg-white rounded-lg border border-outline-variant">
                        <div class="text-label-sm text-on-surface-variant">GUESTS</div>
                        <div class="font-label-md">2 Adults, 1 Room</div>
                    </div>
                </div>

                <!-- Ringkasan Harga -->
                <div class="space-y-xs pt-md">
                    <div class="flex justify-between text-body-md">
                        <span>Harga per malam</span>
                        <span>Rp <?= number_format((float)$min_price, 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between text-body-md">
                        <span>Service fee</span>
                        <span>Rp <?= number_format((float)$min_price * 0.1, 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between font-bold text-headline-md pt-sm border-t border-outline-variant">
                        <span>Estimasi Total</span>
                        <span>Rp <?= number_format((float)$min_price * 1.1, 0, ',', '.') ?></span>
                    </div>
                </div>

                <!-- Tombol Reservasi — arahkan ke kamar termurah -->
                <?php if (!empty($rooms)): ?>
                <a href="/travelease/booking/checkout.php?room_id=<?= (int)$rooms[0]['id'] ?>"
                   class="block w-full bg-secondary-container text-on-primary font-headline-md py-lg rounded-xl shadow-lg hover:opacity-90 active:scale-95 transition-all text-center">
                    Reserve Your Room
                </a>
                <?php endif; ?>

                <p class="text-center text-label-sm text-on-surface-variant">
                    You won't be charged yet
                </p>

                <!-- Trust Badge -->
                <div class="pt-md mt-md border-t border-outline-variant">
                    <div class="flex items-start gap-sm">
                        <span class="material-symbols-outlined text-primary">verified_user</span>
                        <div class="text-label-sm text-on-surface-variant">
                            <strong>Trust Guarantee:</strong> Best price found online.
                            Secure encrypted checkout.
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</main>

<!-- Footer -->
<footer class="bg-surface-container-highest mt-20">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-xl px-gutter py-xl max-w-container-max mx-auto">
        <div class="col-span-2 md:col-span-1">
            <div class="font-headline-md font-black text-on-surface mb-md">TravelEase</div>
            <p class="text-label-sm text-on-surface-variant">
                Your trusted partner for effortless global travel.
            </p>
        </div>
        <div class="flex flex-col gap-sm">
            <h4 class="font-label-md font-bold text-primary">Company</h4>
            <a class="text-label-sm text-on-surface-variant hover:text-primary" href="#">About Us</a>
            <a class="text-label-sm text-on-surface-variant hover:text-primary" href="#">Careers</a>
        </div>
        <div class="flex flex-col gap-sm">
            <h4 class="font-label-md font-bold text-primary">Legal</h4>
            <a class="text-label-sm text-on-surface-variant hover:text-primary" href="#">Terms of Service</a>
            <a class="text-label-sm text-on-surface-variant hover:text-primary" href="#">Privacy Policy</a>
        </div>
        <div class="flex flex-col gap-sm">
            <h4 class="font-label-md font-bold text-primary">Products</h4>
            <a class="text-label-sm text-on-surface-variant hover:text-primary" href="#">Hotels</a>
            <a class="text-label-sm text-on-surface-variant hover:text-primary" href="#">Flights</a>
        </div>
    </div>
    <div class="border-t border-outline-variant py-md text-center">
        <p class="text-label-sm text-on-surface-variant">© 2024 TravelEase. All rights reserved.</p>
    </div>
</footer>

<!-- Mobile Bottom CTA -->
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-outline-variant p-md flex items-center justify-between z-[60] shadow-lg">
    <div>
        <div class="text-secondary-container font-bold text-headline-md">
            Rp <?= number_format((float)$min_price, 0, ',', '.') ?>
            <span class="text-label-sm text-on-surface-variant font-normal">/night</span>
        </div>
        <div class="text-label-sm text-primary underline">See price details</div>
    </div>
    <?php if (!empty($rooms)): ?>
    <a href="/travelease/booking/checkout.php?room_id=<?= (int)$rooms[0]['id'] ?>"
       class="bg-secondary-container text-on-primary font-label-md px-lg py-md rounded-lg shadow-sm">
        Check Availability
    </a>
    <?php endif; ?>
</div>

</body>
</html>
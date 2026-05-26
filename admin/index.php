<?php
require '../includes/admin_check.php';
require '../config/database.php';


// Ambil semua booking
$bookings = $pdo->query("
    SELECT b.*, 
           u.name AS user_name, u.email AS user_email,
           r.room_type, r.price_per_night,
           h.name AS hotel_name, h.location
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    JOIN hotels h ON r.hotel_id = h.id
    ORDER BY b.created_at DESC
")->fetchAll();

// Hitung statistik
$total_bookings  = count($bookings);
$pending_count   = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
$confirmed_count = count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed'));
$total_revenue   = array_sum(array_column(
    array_filter($bookings, fn($b) => $b['status'] === 'confirmed'),
    'total_price'
));

// Ambil semua hotel
$hotels = $pdo->query("
    SELECT h.*, COUNT(r.id) AS room_count
    FROM hotels h
    LEFT JOIN rooms r ON r.hotel_id = h.id
    GROUP BY h.id
    ORDER BY h.id ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <link rel="icon" type="image/x-png" href="../assets/hotel.png">
    <title>Admin Panel - TravelEase</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;
            vertical-align: middle;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

<!-- Modal Edit Hotel -->
<div id="editHotelModal"
     class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-xl p-8 w-full max-w-lg mx-4 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold">Edit Hotel</h3>
            <button onclick="closeEditModal()"
                    class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
        </div>
        <form method="POST" action="/travelease/admin/actions.php" class="space-y-4">
            <input type="hidden" name="action" value="edit_hotel">
            <input type="hidden" name="hotel_id" id="edit_hotel_id">

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="text-sm font-bold text-gray-600 block mb-1">Nama Hotel</label>
                    <input type="text" name="name" id="edit_name" required
                           class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>
                <div class="col-span-2">
                    <label class="text-sm font-bold text-gray-600 block mb-1">Lokasi</label>
                    <input type="text" name="location" id="edit_location" required
                           class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>
                <div>
                    <label class="text-sm font-bold text-gray-600 block mb-1">Bintang (1-5)</label>
                    <input type="number" name="star_rating" id="edit_star_rating" min="1" max="5" required
                           class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>
                <div>
                    <label class="text-sm font-bold text-gray-600 block mb-1">Skor Review</label>
                    <input type="number" name="review_score" id="edit_review_score" step="0.1" min="0" max="10"
                           class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>

                <div>
                    <label class="text-sm font-bold text-gray-600 block mb-1">Jumlah Review</label>
                    <input type="number" name="review_count" id="edit_review_count" min="0"
                        class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>
                <div>
                    <label class="text-sm font-bold text-gray-600 block mb-1">Badge</label>
                    <input type="text" name="badge" id="edit_badge"
                           class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>
                <div>
                    <label class="text-sm font-bold text-gray-600 block mb-1">URL Gambar</label>
                    <input type="url" name="image_url" id="edit_image_url"
                           class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-bold hover:bg-blue-700 transition-colors">
                    Simpan Perubahan
                </button>
                <button type="button" onclick="closeEditModal()"
                        class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg font-bold hover:bg-gray-50 transition-colors">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Sidebar + Layout -->
<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-[#005f9f] text-white flex flex-col fixed h-full z-40">
        <div class="p-6 border-b border-white/20">
            <h1 class="text-xl font-bold">TravelEase</h1>
            <p class="text-blue-200 text-sm mt-1">Admin Panel</p>
        </div>
        <nav class="flex-1 p-4 space-y-1">
            <a href="#bookings" onclick="showTab('bookings')"
               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors sidebar-link active-link">
                <span class="material-symbols-outlined">book_online</span>
                Kelola Booking
            </a>
            <a href="#hotels" onclick="showTab('hotels')"
               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors sidebar-link">
                <span class="material-symbols-outlined">hotel</span>
                Kelola Hotel
            </a>
            <a href="#rooms" onclick="showTab('rooms')"
               class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors sidebar-link">
                <span class="material-symbols-outlined">bed</span>
                Kelola Kamar
            </a>
        </nav>
        <div class="p-4 border-t border-white/20">
            <p class="text-blue-200 text-sm mb-2">Login sebagai:</p>
            <p class="font-bold"><?= htmlspecialchars($_SESSION['user_name']) ?></p>
            <a href="/travelease/auth/logout.php"
               class="flex items-center gap-2 mt-3 text-blue-200 hover:text-white text-sm transition-colors">
                <span class="material-symbols-outlined text-[18px]">logout</span>
                Logout
            </a>
        </div>
    </aside>

    <!-- Konten Utama -->
    <main class="ml-64 flex-1 p-8">

        <!-- Statistik -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-500 text-sm">Total Booking</p>
                    <span class="material-symbols-outlined text-blue-500">book_online</span>
                </div>
                <p class="text-3xl font-bold text-gray-900"><?= $total_bookings ?></p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-500 text-sm">Menunggu Konfirmasi</p>
                    <span class="material-symbols-outlined text-yellow-500">schedule</span>
                </div>
                <p class="text-3xl font-bold text-yellow-500"><?= $pending_count ?></p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-500 text-sm">Dikonfirmasi</p>
                    <span class="material-symbols-outlined text-green-500">check_circle</span>
                </div>
                <p class="text-3xl font-bold text-green-500"><?= $confirmed_count ?></p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-gray-500 text-sm">Total Revenue</p>
                    <span class="material-symbols-outlined text-purple-500">payments</span>
                </div>
                <p class="text-xl font-bold text-purple-500">
                    Rp <?= number_format($total_revenue, 0, ',', '.') ?>
                </p>
            </div>
        </div>

        <!-- Tab: Kelola Booking -->
        <div id="tab-bookings" class="tab-content">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-bold">Kelola Booking</h2>
                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm font-bold">
                        <?= $pending_count ?> Pending
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">ID</th>
                                <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Tamu</th>
                                <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Hotel & Kamar</th>
                                <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Tanggal</th>
                                <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Total</th>
                                <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Status</th>
                                <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-12 text-gray-400">
                                        <span class="material-symbols-outlined text-[48px] block mb-2">inbox</span>
                                        Belum ada booking
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($bookings as $b): ?>
                            <tr class="hover:bg-gray-50 transition-colors <?= $b['status'] === 'pending' ? 'bg-yellow-50/50' : '' ?>">
                                <td class="px-6 py-4 text-sm font-bold text-gray-500">#<?= $b['id'] ?></td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-sm"><?= htmlspecialchars($b['user_name']) ?></p>
                                    <p class="text-gray-400 text-xs"><?= htmlspecialchars($b['user_email']) ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-sm"><?= htmlspecialchars($b['hotel_name']) ?></p>
                                    <p class="text-gray-400 text-xs"><?= htmlspecialchars($b['room_type']) ?></p>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <p><?= date('d M Y', strtotime($b['check_in'])) ?></p>
                                    <p class="text-gray-400">→ <?= date('d M Y', strtotime($b['check_out'])) ?></p>
                                </td>
                                <td class="px-6 py-4 font-bold text-sm">
                                    Rp <?= number_format((float)$b['total_price'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $status_class = match($b['status']) {
                                        'confirmed'  => 'bg-green-100 text-green-700',
                                        'cancelled'  => 'bg-red-100 text-red-700',
                                        default      => 'bg-yellow-100 text-yellow-700'
                                    };
                                    $status_label = match($b['status']) {
                                        'confirmed'  => 'Dikonfirmasi',
                                        'cancelled'  => 'Dibatalkan',
                                        default      => 'Pending'
                                    };
                                    ?>
                                    <span class="<?= $status_class ?> px-3 py-1 rounded-full text-xs font-bold">
                                        <?= $status_label ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <?php if ($b['status'] === 'pending'): ?>
                                        <form method="POST" action="/travelease/admin/actions.php">
                                            <input type="hidden" name="action" value="confirm_booking">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit"
                                                    class="bg-green-500 text-white px-3 py-1 rounded-lg text-xs font-bold hover:bg-green-600 transition-colors">
                                                Konfirmasi
                                            </button>
                                        </form>
                                        <form method="POST" action="/travelease/admin/actions.php"
                                              onsubmit="return confirm('Batalkan booking ini?')">
                                            <input type="hidden" name="action" value="cancel_booking">
                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                            <button type="submit"
                                                    class="bg-red-100 text-red-600 px-3 py-1 rounded-lg text-xs font-bold hover:bg-red-200 transition-colors">
                                                Batalkan
                                            </button>
                                        </form>
                                        <?php else: ?>
                                            <form method="POST" action="/travelease/admin/actions.php"
                                                onsubmit="return confirm('Hapus booking #<?= $b['id'] ?> ini?')">
                                                <input type="hidden" name="action" value="delete_booking">
                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                <button type="submit"
                                                        class="bg-red-100 text-red-600 px-3 py-1 rounded-lg text-xs font-bold hover:bg-red-200 transition-colors">
                                                    Hapus
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Kelola Hotel -->
        <div id="tab-hotels" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-bold">Kelola Hotel</h2>
                </div>

                <!-- Form Tambah Hotel -->
                <div class="p-6 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-500">add_circle</span>
                        Tambah Hotel Baru
                    </h3>
                    <form method="POST" action="/travelease/admin/actions.php"
                          class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="hidden" name="action" value="add_hotel">
                        <input type="text" name="name" placeholder="Nama Hotel" required
                               class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <input type="text" name="location" placeholder="Lokasi (e.g. Kuta, Bali)" required
                               class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <input type="number" name="star_rating" placeholder="Bintang (1-5)" min="1" max="5" required
                               class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <input type="number" name="review_score" placeholder="Skor Review (e.g. 8.5)" step="0.1" min="0" max="10"
                               class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <input type="text" name="badge" placeholder="Badge (opsional, e.g. Bestseller)"
                               class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <input type="url" name="image_url" placeholder="URL Gambar Hotel"
                               class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <input type="number" name="review_count" placeholder="Jumlah Review (e.g. 500)" min="0"
                               class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <div class="md:col-span-3">
                            <button type="submit"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition-colors">
                                Tambah Hotel
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Daftar Hotel -->
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Hotel</th>
                            <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Lokasi</th>
                            <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Bintang</th>
                            <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Jumlah Kamar</th>
                            <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($hotels as $h): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-bold"><?= htmlspecialchars($h['name']) ?></td>
                            <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($h['location']) ?></td>
                            <td class="px-6 py-4">
                                <div class="flex text-yellow-400">
                                    <?php for ($i = 0; $i < $h['star_rating']; $i++): ?>
                                        <span class="material-symbols-outlined text-[16px]"
                                              style="font-variation-settings: 'FILL' 1;">star</span>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-bold"><?= $h['room_count'] ?> kamar</td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                        <!-- Tombol Edit -->
                                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($h)) ?>)"
                                                class="text-blue-500 hover:text-blue-700 text-sm font-bold transition-colors">
                                            Edit
                                        </button>                            
                                    <form method="POST" action="/travelease/admin/actions.php"
                                      onsubmit="return confirm('Hapus hotel ini dan semua kamarnya?')">
                                    <input type="hidden" name="action" value="delete_hotel">
                                    <input type="hidden" name="hotel_id" value="<?= $h['id'] ?>">
                                    <button type="submit"
                                            class="text-red-500 hover:text-red-700 text-sm font-bold transition-colors">
                                        Hapus
                                    </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: Kelola Kamar -->
        <div id="tab-rooms" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-bold">Kelola Kamar & Stok</h2>
                </div>

                <!-- Form Tambah Kamar -->
                <div class="p-6 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-blue-500">add_circle</span>
                        Tambah Kamar Baru
                    </h3>
                    <form method="POST" action="/travelease/admin/actions.php"
                          class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="hidden" name="action" value="add_room">

                        <select name="hotel_id" required
                                class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            <option value="">Pilih Hotel</option>
                            <?php foreach ($hotels as $h): ?>
                                <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <input type="text" name="room_type" placeholder="Tipe Kamar (e.g. Deluxe King)" required
                               class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <input type="number" name="price_per_night" placeholder="Harga per Malam (Rp)" required
                               class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <input type="number" name="original_price" placeholder="Harga Asli (Rp)"
                               class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <input type="number" name="stock" placeholder="Stok Kamar" required min="0"
                               class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        <input type="text" name="facilities" placeholder="Fasilitas: wifi,pool,breakfast"
                               class="border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">

                        <div class="md:col-span-3">
                            <button type="submit"
                                    class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition-colors">
                                Tambah Kamar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Daftar Kamar & Update Stok -->
                <?php
                $rooms_all = $pdo->query("
                    SELECT r.*, h.name AS hotel_name
                    FROM rooms r
                    JOIN hotels h ON r.hotel_id = h.id
                    ORDER BY h.name, r.room_type
                ")->fetchAll();
                ?>
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Hotel</th>
                            <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Tipe Kamar</th>
                            <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Harga/Malam</th>
                            <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Stok</th>
                            <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Update Stok</th>
                            <th class="text-left px-6 py-4 text-sm font-bold text-gray-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($rooms_all as $r): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-bold"><?= htmlspecialchars($r['hotel_name']) ?></td>
                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($r['room_type']) ?></td>
                            <td class="px-6 py-4 text-sm">Rp <?= number_format((float)$r['price_per_night'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4">
                                <span class="font-bold <?= $r['stock'] <= 3 ? 'text-red-500' : 'text-green-600' ?>">
                                    <?= $r['stock'] ?> kamar
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="/travelease/admin/actions.php"
                                      class="flex items-center gap-2">
                                    <input type="hidden" name="action" value="update_stock">
                                    <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                                    <input type="number" name="stock" value="<?= $r['stock'] ?>" min="0"
                                           class="border border-gray-200 rounded-lg px-3 py-1 w-20 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                                    <button type="submit"
                                            class="bg-blue-500 text-white px-3 py-1 rounded-lg text-xs font-bold hover:bg-blue-600 transition-colors">
                                        Update
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="/travelease/admin/actions.php"
                                      onsubmit="return confirm('Hapus kamar ini?')">
                                    <input type="hidden" name="action" value="delete_room">
                                    <input type="hidden" name="room_id" value="<?= $r['id'] ?>">
                                    <button type="submit"
                                            class="text-red-500 hover:text-red-700 text-sm font-bold">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<script>
    function showTab(tab) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.sidebar-link').forEach(el => el.classList.remove('bg-white/20'));
        document.getElementById('tab-' + tab).classList.remove('hidden');
        event.currentTarget.classList.add('bg-white/20');
    }

    function openEditModal(hotel) {
        document.getElementById('edit_hotel_id').value     = hotel.id;
        document.getElementById('edit_name').value         = hotel.name;
        document.getElementById('edit_location').value     = hotel.location;
        document.getElementById('edit_star_rating').value  = hotel.star_rating;
        document.getElementById('edit_review_score').value = hotel.review_score;
        document.getElementById('edit_review_count').value = hotel.review_count ?? 0;
        document.getElementById('edit_badge').value        = hotel.badge ?? '';
        document.getElementById('edit_image_url').value    = hotel.image_url ?? '';

        const modal = document.getElementById('editHotelModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditModal() {
        const modal = document.getElementById('editHotelModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.getElementById('editHotelModal').addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
</script>

</body>
</html>
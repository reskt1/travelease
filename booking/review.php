<?php
require '../includes/auth_check.php';
require '../config/database.php';

$booking_id = (int)($_GET['booking_id'] ?? 0);

if ($booking_id <= 0) {
    header("Location: /travelease/booking/mybooking.php");
    exit;
}

// Ambil data booking — pastikan milik user yang login
// dan statusnya confirmed
$stmt = $pdo->prepare("
    SELECT b.*, 
           r.room_type,
           h.id AS hotel_id, h.name AS hotel_name, 
           h.location, h.image_url AS hotel_image
    FROM bookings b
    JOIN rooms r   ON b.room_id   = r.id
    JOIN hotels h  ON r.hotel_id  = h.id
    WHERE b.id      = ?
      AND b.user_id = ?
      AND b.status  = 'confirmed'
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

// Kalau booking tidak ditemukan / bukan miliknya / belum confirmed
if (!$booking) {
    header("Location: /travelease/booking/mybooking.php");
    exit;
}

// Cek apakah sudah pernah review booking ini
$check = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ?");
$check->execute([$booking_id]);
if ($check->fetch()) {
    // Sudah review, redirect ke mybooking
    header("Location: /travelease/booking/mybooking.php?already_reviewed=1");
    exit;
}

$errors  = [];
$success = false;

if (isset($_POST['submit_review'])) {
    $rating  = (float)($_POST['rating']  ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    // Validasi
    if ($rating < 1 || $rating > 10) {
        $errors['rating'] = "Rating harus antara 1 sampai 10.";
    }
    if (empty($comment)) {
        $errors['comment'] = "Ulasan tidak boleh kosong.";
    }
    if (strlen($comment) < 10) {
        $errors['comment'] = "Ulasan minimal 10 karakter.";
    }

    if (empty($errors)) {
        // Simpan review
        $pdo->prepare("
            INSERT INTO reviews (user_id, hotel_id, booking_id, rating, comment)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $_SESSION['user_id'],
            $booking['hotel_id'],
            $booking_id,
            $rating,
            $comment
        ]);

        // Update rating hotel secara otomatis dari rata-rata semua review
        $pdo->prepare("
            UPDATE hotels SET review_score = (
                SELECT ROUND(AVG(rating), 1)
                FROM reviews
                WHERE hotel_id = ?
            ),
            review_count = (
                SELECT COUNT(*)
                FROM reviews
                WHERE hotel_id = ?
            )
            WHERE id = ?
        ")->execute([
            $booking['hotel_id'],
            $booking['hotel_id'],
            $booking['hotel_id']
        ]);

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Tulis Ulasan - TravelEase</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;
            vertical-align: middle;
        }
        /* Bintang interaktif */
        .star-rating { display: flex; gap: 8px; flex-direction: row-reverse; justify-content: flex-end; }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 40px; color: #dbe4e9; cursor: pointer;
            transition: color 0.15s;
            font-family: 'Material Symbols Outlined';
            font-variation-settings: 'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;
        }
        .star-rating label::before { content: 'star'; }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #ff5e1f; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- Header -->
<nav class="bg-white border-b border-gray-200 shadow-sm h-16 sticky top-0 z-50">
    <div class="flex justify-between items-center max-w-5xl mx-auto px-6 h-full">
        <a href="/travelease/hotels/index.php" class="text-2xl font-bold text-[#005f9f]">TravelEase</a>
        <div class="flex items-center gap-4">
            <span class="text-gray-500">Halo, <strong class="text-[#005f9f]"><?= htmlspecialchars($_SESSION['user_name']) ?></strong></span>
            <a href="/travelease/auth/logout.php" class="border border-[#005f9f] text-[#005f9f] px-4 py-1 rounded-lg text-sm font-bold hover:bg-blue-50 transition-colors">Logout</a>
        </div>
    </div>
</nav>

<main class="max-w-2xl mx-auto px-6 py-12">

    <?php if ($success): ?>
    <!-- Sukses -->
    <div class="text-center py-12">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-green-500 text-[48px]"
                  style="font-variation-settings: 'FILL' 1;">check_circle</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Ulasan Terkirim!</h1>
        <p class="text-gray-500 mb-8">
            Terima kasih sudah berbagi pengalamanmu di
            <strong><?= htmlspecialchars($booking['hotel_name']) ?></strong>.
            Rating hotel sudah diperbarui secara otomatis.
        </p>
        <div class="flex gap-4 justify-center">
            <a href="/travelease/booking/mybooking.php"
               class="bg-[#005f9f] text-white px-6 py-3 rounded-xl font-bold hover:opacity-90 transition-all">
                Lihat Pesanan Saya
            </a>
            <a href="/travelease/hotels/index.php"
               class="border border-[#005f9f] text-[#005f9f] px-6 py-3 rounded-xl font-bold hover:bg-blue-50 transition-all">
                Cari Hotel Lain
            </a>
        </div>
    </div>

    <?php else: ?>

    <!-- Form Review -->
    <div class="mb-8">
        <a href="/travelease/booking/mybooking.php"
           class="text-[#005f9f] text-sm flex items-center gap-1 hover:underline mb-4">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>
            Kembali ke Pesanan Saya
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Tulis Ulasan</h1>
        <p class="text-gray-500 mt-1">Bagikan pengalamanmu menginap di hotel ini</p>
    </div>

    <!-- Info Hotel -->
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm mb-6">
        <div class="flex gap-4 p-5">
            <img src="<?= htmlspecialchars($booking['hotel_image']) ?>"
                 alt="<?= htmlspecialchars($booking['hotel_name']) ?>"
                 class="w-20 h-20 object-cover rounded-xl flex-shrink-0">
            <div>
                <h2 class="font-bold text-gray-900"><?= htmlspecialchars($booking['hotel_name']) ?></h2>
                <p class="text-gray-500 text-sm flex items-center gap-1 mt-1">
                    <span class="material-symbols-outlined text-[16px]">location_on</span>
                    <?= htmlspecialchars($booking['location']) ?>
                </p>
                <p class="text-gray-500 text-sm flex items-center gap-1 mt-1">
                    <span class="material-symbols-outlined text-[16px]">king_bed</span>
                    <?= htmlspecialchars($booking['room_type']) ?>
                </p>
                <p class="text-gray-400 text-xs mt-2">
                    <?= date('d M Y', strtotime($booking['check_in'])) ?> →
                    <?= date('d M Y', strtotime($booking['check_out'])) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-6">

        <!-- Rating Bintang Interaktif -->
        <div>
            <label class="block font-bold text-gray-900 mb-3">
                Rating Keseluruhan
                <span class="text-red-500">*</span>
            </label>

            <!-- Slider rating 1-10 -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-400 text-sm">Buruk</span>
                    <div id="rating-display"
                         class="text-3xl font-black text-[#ff5e1f] min-w-[60px] text-center">
                        <?= isset($_POST['rating']) ? $_POST['rating'] : '—' ?>
                    </div>
                    <span class="text-gray-400 text-sm">Sempurna</span>
                </div>

                <input type="range" name="rating" id="rating-slider"
                       min="1" max="10" step="0.5"
                       value="<?= htmlspecialchars($_POST['rating'] ?? '5') ?>"
                       class="w-full h-3 rounded-full appearance-none cursor-pointer"
                       style="accent-color: #ff5e1f;"
                       oninput="updateRating(this.value)">

                <div class="flex justify-between text-xs text-gray-400">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <span><?= $i ?></span>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Label rating -->
            <div id="rating-label" class="mt-2 text-center">
                <span class="text-sm font-bold px-3 py-1 rounded-full bg-gray-100 text-gray-600"
                      id="rating-text">Pilih rating</span>
            </div>

            <?php if (isset($errors['rating'])): ?>
                <p class="text-red-500 text-sm flex items-center gap-1 mt-2">
                    <span class="material-symbols-outlined text-[16px]">error</span>
                    <?= $errors['rating'] ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Kategori Penilaian (visual saja) -->
        <div>
            <label class="block font-bold text-gray-900 mb-3">Penilaian per Kategori</label>
            <div class="grid grid-cols-2 gap-3">
                <?php
                $categories = [
                    ['kebersihan',  'Kebersihan',   'cleaning_services'],
                    ['lokasi',      'Lokasi',        'location_on'],
                    ['pelayanan',   'Pelayanan',     'support_agent'],
                    ['fasilitas',   'Fasilitas',     'hotel'],
                ];
                foreach ($categories as [$key, $label, $icon]):
                ?>
                <div class="border border-gray-200 rounded-xl p-3">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="material-symbols-outlined text-[#005f9f] text-[18px]"><?= $icon ?></span>
                        <span class="text-sm font-bold text-gray-700"><?= $label ?></span>
                    </div>
                    <input type="range" name="cat_<?= $key ?>"
                           min="1" max="10" step="1" value="5"
                           class="w-full h-2 rounded-full"
                           style="accent-color: #005f9f;"
                           oninput="document.getElementById('cat_<?= $key ?>_val').textContent = this.value">
                    <div class="text-right text-xs text-gray-500 mt-1">
                        <span id="cat_<?= $key ?>_val">5</span>/10
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Komentar -->
        <div>
            <label class="block font-bold text-gray-900 mb-2">
                Ulasan Kamu
                <span class="text-red-500">*</span>
            </label>
            <textarea name="comment" rows="5"
                      placeholder="Ceritakan pengalamanmu menginap di sini. Apa yang kamu suka? Ada yang perlu diperbaiki?"
                      class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#005f9f]/20 focus:border-[#005f9f] resize-none transition-all"><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
            <div class="flex justify-between mt-1">
                <?php if (isset($errors['comment'])): ?>
                    <p class="text-red-500 text-sm flex items-center gap-1">
                        <span class="material-symbols-outlined text-[16px]">error</span>
                        <?= $errors['comment'] ?>
                    </p>
                <?php else: ?>
                    <p class="text-gray-400 text-xs">Minimal 10 karakter</p>
                <?php endif; ?>
                <p class="text-gray-400 text-xs" id="char-count">0 karakter</p>
            </div>
        </div>

        <!-- Submit -->
        <button type="submit" name="submit_review"
                class="w-full bg-[#ff5e1f] text-white py-4 rounded-xl font-bold text-base hover:opacity-90 transition-all active:scale-95 flex items-center justify-center gap-2">
            <span class="material-symbols-outlined"
                  style="font-variation-settings: 'FILL' 1;">rate_review</span>
            Kirim Ulasan
        </button>

        <p class="text-center text-xs text-gray-400">
            Ulasan kamu akan langsung memperbarui rating hotel secara otomatis
        </p>

    </form>
    <?php endif; ?>

</main>

<script>
    // Update tampilan rating dari slider
    const labels = {
        1: '😞 Sangat Buruk', 2: '😕 Buruk', 3: '😐 Cukup Buruk',
        4: '🙁 Di Bawah Rata-rata', 5: '😊 Cukup',
        6: '🙂 Lumayan', 7: '😀 Bagus', 8: '😄 Sangat Bagus',
        9: '🤩 Luar Biasa', 10: '🏆 Sempurna'
    };

    function updateRating(val) {
        document.getElementById('rating-display').textContent = val;
        const rounded = Math.round(val);
        document.getElementById('rating-text').textContent = labels[rounded] || '';

        // Warna berdasarkan nilai
        const display = document.getElementById('rating-display');
        if (val >= 8)      display.style.color = '#22c55e';
        else if (val >= 6) display.style.color = '#f59e0b';
        else               display.style.color = '#ef4444';
    }

    // Init saat load
    const slider = document.getElementById('rating-slider');
    if (slider) updateRating(slider.value);

    // Hitung karakter ulasan
    const textarea = document.querySelector('textarea[name="comment"]');
    const charCount = document.getElementById('char-count');
    if (textarea) {
        textarea.addEventListener('input', () => {
            charCount.textContent = textarea.value.length + ' karakter';
        });
    }
</script>

</body>
</html>
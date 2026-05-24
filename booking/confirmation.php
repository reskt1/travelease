<?php
require '../includes/auth_check.php';
require '../config/database.php';

$success    = isset($_GET['success']);
$booking_id = (int)($_GET['id'] ?? 0);

if ($success) {
    // Ambil data booking untuk halaman sukses
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_type, r.price_per_night,
               h.name AS hotel_name, h.location, h.image_url AS hotel_image
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN hotels h ON r.hotel_id = h.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch();

} else {
    // Kalau tidak ada draft, balik ke hotel
    if (empty($_SESSION['booking_draft'])) {
        header("Location: /travelease/hotels/index.php");
        exit;
    }

    $draft = $_SESSION['booking_draft'];

    $stmt = $pdo->prepare("
        SELECT r.*, h.name AS hotel_name, h.location, h.image_url AS hotel_image, h.star_rating
        FROM rooms r
        JOIN hotels h ON r.hotel_id = h.id
        WHERE r.id = ?
    ");
    $stmt->execute([$draft['room_id']]);
    $room = $stmt->fetch();

    if (!$room) {
        header("Location: /travelease/hotels/index.php");
        exit;
    }

    // Proses saat tombol Konfirmasi diklik
    if (isset($_POST['confirm'])) {
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_price, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $draft['room_id'],
            $draft['check_in'],
            $draft['check_out'],
            $draft['guests'],
            $draft['total_price'],
        ]);

        $booking_id = $pdo->lastInsertId();

        $pdo->prepare("UPDATE rooms SET stock = stock - 1 WHERE id = ?")
            ->execute([$draft['room_id']]);

        unset($_SESSION['booking_draft']);

        header("Location: /travelease/booking/confirmation.php?success=1&id=" . $booking_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TravelEase - Konfirmasi Booking</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#005f9f", "on-primary": "#ffffff",
                        "primary-container": "#0078c7", "on-primary-container": "#fdfcff",
                        "secondary-container": "#ff5e1f", "secondary": "#ab3500",
                        "surface": "#f3faff", "surface-container-lowest": "#ffffff",
                        "surface-container": "#e6eff5", "surface-container-low": "#ecf5fb",
                        "on-surface": "#141d21", "on-surface-variant": "#3f4752",
                        "outline-variant": "#bfc7d4", "outline": "#707884",
                        "error": "#ba1a1a", "background": "#f3faff",
                    },
                    spacing: {
                        "xs":"4px","sm":"12px","md":"16px","lg":"24px","xl":"32px","gutter":"20px","container-max":"1200px"
                    },
                    fontSize: {
                        "headline-lg": ["32px",{lineHeight:"40px",fontWeight:"700"}],
                        "headline-md": ["20px",{lineHeight:"28px",fontWeight:"600"}],
                        "label-md":    ["14px",{lineHeight:"20px",fontWeight:"600"}],
                        "label-sm":    ["12px",{lineHeight:"16px",fontWeight:"500"}],
                        "body-md":     ["14px",{lineHeight:"20px",fontWeight:"400"}],
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
    </style>
</head>
<body class="bg-background text-on-surface min-h-screen">

<!-- Header -->
<header class="bg-surface border-b border-outline-variant shadow-sm sticky top-0 z-50">
    <nav class="flex justify-between items-center w-full px-gutter max-w-container-max mx-auto h-20">
        <div class="font-headline-lg text-headline-lg font-bold text-primary">TravelEase</div>
        <div class="flex items-center gap-md">
            <span class="text-on-surface-variant hidden md:block">
                Halo, <strong class="text-primary"><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
            </span>
            <a href="/travelease/auth/logout.php"
               class="px-md py-xs text-primary border border-primary rounded-lg hover:bg-surface-container transition-all text-label-md font-bold">
                Logout
            </a>
        </div>
    </nav>
</header>

<main class="max-w-container-max mx-auto px-gutter py-xl">

    <?php if ($success): ?>
    <!-- ✅ Halaman Sukses -->
    <div class="max-w-lg mx-auto text-center py-xl">
        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-lg">
            <span class="material-symbols-outlined text-green-600 text-[56px]"
                  style="font-variation-settings:'FILL' 1">check_circle</span>
        </div>
        <h1 class="text-headline-lg font-bold text-on-surface mb-sm">Booking Berhasil!</h1>
        <p class="text-on-surface-variant mb-xs">Nomor Booking</p>
        <p class="text-2xl font-black text-primary mb-lg">#<?= str_pad($booking_id, 8, '0', STR_PAD_LEFT) ?></p>
        <p class="text-body-md text-on-surface-variant mb-xl">
            Booking kamu sudah masuk dan menunggu konfirmasi dari admin.
            Cek status booking di halaman <strong>My Bookings</strong>.
        </p>
        <div class="flex flex-col sm:flex-row gap-md justify-center">
            <a href="/travelease/booking/mybooking.php"
               class="bg-primary text-on-primary px-xl py-md rounded-xl font-bold hover:bg-primary-container transition-all">
                Lihat My Bookings
            </a>
            <a href="/travelease/hotels/index.php"
               class="border border-primary text-primary px-xl py-md rounded-xl font-bold hover:bg-surface-container transition-all">
                Cari Hotel Lain
            </a>
        </div>
    </div>

    <?php else: ?>
    <!-- 📋 Halaman Konfirmasi -->

    <!-- Progress Steps -->
    <div class="flex items-center justify-center mb-xl">
        <div class="flex items-center w-full max-w-2xl">
            <div class="flex flex-col items-center relative">
                <div class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center font-bold z-10">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings:'FILL' 1">check</span>
                </div>
                <span class="absolute -bottom-6 text-label-sm font-bold text-green-600 whitespace-nowrap">Data Tamu</span>
            </div>
            <div class="flex-1 h-1 bg-primary mx-2"></div>
            <div class="flex flex-col items-center relative">
                <div class="w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center font-bold z-10">2</div>
                <span class="absolute -bottom-6 text-label-sm font-bold text-primary whitespace-nowrap">Konfirmasi</span>
            </div>
            <div class="flex-1 h-1 bg-primary mx-2"></div>
            <div class="flex flex-col items-center relative">
                <div class="w-10 h-10 rounded-full bg-surface-container text-on-surface-variant flex items-center justify-center font-bold z-10">3</div>
                <span class="absolute -bottom-6 text-label-sm text-on-surface-variant whitespace-nowrap">Selesai</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-xl mt-12">

        <!-- Kiri: Ringkasan -->
        <div class="lg:col-span-8 space-y-lg">

            <!-- Info Hotel -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden">
                <div class="flex flex-col md:flex-row">
                    <div class="md:w-48 h-40 md:h-auto flex-shrink-0">
                        <img src="<?= htmlspecialchars($room['hotel_image'] ?? '') ?>"
                             alt="<?= htmlspecialchars($room['hotel_name']) ?>"
                             class="w-full h-full object-cover">
                    </div>
                    <div class="p-lg flex-1">
                        <h2 class="text-headline-md font-bold mb-xs"><?= htmlspecialchars($room['hotel_name']) ?></h2>
                        <p class="text-on-surface-variant flex items-center gap-xs mb-sm text-body-md">
                            <span class="material-symbols-outlined text-[16px]">location_on</span>
                            <?= htmlspecialchars($room['location']) ?>
                        </p>
                        <p class="text-on-surface-variant flex items-center gap-xs text-body-md">
                            <span class="material-symbols-outlined text-[16px]">bed</span>
                            <?= htmlspecialchars($room['room_type']) ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Detail Booking -->
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm p-lg">
                <h3 class="text-headline-md font-bold mb-lg flex items-center gap-sm">
                    <span class="material-symbols-outlined text-primary">event_note</span>
                    Detail Booking
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-md mb-lg">
                    <div class="bg-surface-container-low p-md rounded-lg">
                        <p class="text-label-sm text-on-surface-variant mb-xs uppercase">Check-in</p>
                        <p class="font-bold text-on-surface"><?= date('d M Y', strtotime($draft['check_in'])) ?></p>
                    </div>
                    <div class="bg-surface-container-low p-md rounded-lg">
                        <p class="text-label-sm text-on-surface-variant mb-xs uppercase">Check-out</p>
                        <p class="font-bold text-on-surface"><?= date('d M Y', strtotime($draft['check_out'])) ?></p>
                    </div>
                    <div class="bg-surface-container-low p-md rounded-lg">
                        <p class="text-label-sm text-on-surface-variant mb-xs uppercase">Durasi</p>
                        <p class="font-bold text-on-surface"><?= $draft['nights'] ?> Malam</p>
                    </div>
                    <div class="bg-surface-container-low p-md rounded-lg">
                        <p class="text-label-sm text-on-surface-variant mb-xs uppercase">Tamu</p>
                        <p class="font-bold text-on-surface"><?= $draft['guests'] ?> Orang</p>
                    </div>
                </div>

                <!-- Data Tamu -->
                <h3 class="text-headline-md font-bold mb-md flex items-center gap-sm">
                    <span class="material-symbols-outlined text-primary">person</span>
                    Data Tamu
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                    <div>
                        <p class="text-label-sm text-on-surface-variant">Nama Lengkap</p>
                        <p class="font-bold"><?= htmlspecialchars($draft['first_name'] . ' ' . $draft['last_name']) ?></p>
                    </div>
                    <div>
                        <p class="text-label-sm text-on-surface-variant">Email</p>
                        <p class="font-bold"><?= htmlspecialchars($draft['email']) ?></p>
                    </div>
                    <div>
                        <p class="text-label-sm text-on-surface-variant">Telepon</p>
                        <p class="font-bold"><?= htmlspecialchars($draft['phone']) ?></p>
                    </div>
                    <?php if (!empty($draft['notes'])): ?>
                    <div class="md:col-span-2">
                        <p class="text-label-sm text-on-surface-variant">Permintaan Khusus</p>
                        <p class="font-bold"><?= htmlspecialchars($draft['notes']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex flex-col sm:flex-row gap-md">
                <a href="/travelease/booking/checkout.php?room_id=<?= $draft['room_id'] ?>"
                   class="flex-1 border border-outline text-on-surface-variant px-xl py-md rounded-xl font-bold text-center hover:bg-surface-container transition-all">
                    ← Kembali & Edit
                </a>
                <form method="POST" action="" class="flex-1">
                    <button type="submit" name="confirm"
                            class="w-full bg-secondary-container text-on-primary px-xl py-md rounded-xl font-bold hover:opacity-90 active:scale-95 transition-all flex items-center justify-center gap-sm shadow-lg">
                        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">check_circle</span>
                        Konfirmasi Booking
                    </button>
                </form>
            </div>

        </div>

        <!-- Kanan: Ringkasan Harga -->
        <aside class="lg:col-span-4">
            <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-md p-lg sticky top-28">
                <h3 class="text-headline-md font-bold mb-lg">Ringkasan Harga</h3>
                <div class="space-y-sm">
                    <div class="flex justify-between text-body-md text-on-surface-variant">
                        <span>Rp <?= number_format((float)$room['price_per_night'], 0, ',', '.') ?> x <?= $draft['nights'] ?> malam</span>
                        <span>Rp <?= number_format($draft['subtotal'], 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between text-body-md text-on-surface-variant">
                        <span>Service fee (10%)</span>
                        <span>Rp <?= number_format($draft['service_fee'], 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between items-center pt-md border-t border-outline-variant">
                        <span class="text-headline-md font-bold">Total</span>
                        <span class="text-headline-md font-black text-secondary-container">
                            Rp <?= number_format($draft['total_price'], 0, ',', '.') ?>
                        </span>
                    </div>
                </div>
                <div class="mt-lg pt-lg border-t border-outline-variant">
                    <div class="flex items-start gap-sm text-label-sm text-on-surface-variant">
                        <span class="material-symbols-outlined text-primary text-[18px]">info</span>
                        Booking berstatus <strong>Pending</strong> sampai dikonfirmasi oleh admin.
                    </div>
                </div>
            </div>
        </aside>
    </div>
    <?php endif; ?>

</main>

<!-- Footer -->
<footer class="bg-surface-container mt-xl border-t border-outline-variant">
    <div class="max-w-container-max mx-auto px-gutter py-lg text-center">
        <p class="text-label-sm text-on-surface-variant">© 2024 TravelEase. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
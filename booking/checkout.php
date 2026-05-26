<?php
require '../includes/auth_check.php';
require '../config/database.php';

$success    = false;
$booking_id = null;

// ✅ Ambil room_id dari POST kalau ada, fallback ke GET
$room_id = (int)($_POST['room_id'] ?? $_GET['room_id'] ?? 0);

if ($room_id <= 0) {
    header("Location: /travelease/hotels/index.php");
    exit;
}

// Ambil data kamar beserta hotel
$stmt = $pdo->prepare("
    SELECT r.*, h.name AS hotel_name, h.location, h.image_url AS hotel_image
    FROM rooms r
    JOIN hotels h ON r.hotel_id = h.id
    WHERE r.id = ?
");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    header("Location: /travelease/hotels/index.php");
    exit;
}

$errors = [];

if (isset($_POST['booking'])) {

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $email      = trim($_POST['email']      ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $check_in   = $_POST['check_in']        ?? '';
    $check_out  = $_POST['check_out']       ?? '';
    $guests     = (int)($_POST['guests']    ?? 1);
    $notes      = trim($_POST['notes']      ?? '');

    // Validasi
    if (empty($first_name)) $errors['first_name'] = "Nama depan wajib diisi.";
    if (empty($last_name))  $errors['last_name']  = "Nama belakang wajib diisi.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Email tidak valid.";
    }
    if (empty($phone))     $errors['phone']     = "Nomor telepon wajib diisi.";
    if (empty($check_in))  $errors['check_in']  = "Tanggal check-in wajib diisi.";
    if (empty($check_out)) $errors['check_out'] = "Tanggal check-out wajib diisi.";

    if (empty($errors['check_in']) && empty($errors['check_out'])) {
        $in  = new DateTime($check_in);
        $out = new DateTime($check_out);
        if ($out <= $in) {
            $errors['check_out'] = "Check-out harus setelah check-in.";
        }
    }

    if (empty($errors)) {
    $nights      = $in->diff($out)->days;
    $subtotal    = $room['price_per_night'] * $nights;
    $service_fee = $subtotal * 0.1;
    $total_price = $subtotal + $service_fee;

    // ✅ Simpan ke session dulu, jangan insert ke DB
    $_SESSION['booking_draft'] = [
        'room_id'     => $room_id,
        'first_name'  => $first_name,
        'last_name'   => $last_name,
        'email'       => $email,
        'phone'       => $phone,
        'check_in'    => $check_in,
        'check_out'   => $check_out,
        'guests'      => $guests,
        'notes'       => $notes,
        'nights'      => $nights,
        'subtotal'    => $subtotal,
        'service_fee' => $service_fee,
        'total_price' => $total_price,
    ];

    header("Location: /travelease/booking/confirmation.php");
    exit;
}
}

// Estimasi preview
$preview_nights   = 3;
$preview_subtotal = $room['price_per_night'] * $preview_nights;
$preview_fee      = $preview_subtotal * 0.1;
$preview_service_fee  = $preview_fee;
$preview_total    = $preview_subtotal + $preview_fee;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <link rel="icon" type="image/x-png" href="../assets/hotel.png">
    <title>TravelEase - Booking & Checkout</title>
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
                        "surface-container-low": "#ecf5fb","surface-bright": "#f3faff",
                    },
                    spacing: {
                        "md":"16px","xs":"4px","gutter":"20px","lg":"24px",
                        "container-max":"1200px","sm":"12px","xl":"32px","base":"8px"
                    },
                    fontSize: {
                        "headline-md": ["20px",{lineHeight:"28px",fontWeight:"600"}],
                        "label-sm":    ["12px",{lineHeight:"16px",fontWeight:"500"}],
                        "headline-lg": ["32px",{lineHeight:"40px",fontWeight:"700"}],
                        "body-md":     ["14px",{lineHeight:"20px",fontWeight:"400"}],
                        "label-md":    ["14px",{lineHeight:"20px",fontWeight:"600"}],
                        "headline-lg-mobile": ["24px",{lineHeight:"32px",fontWeight:"700"}],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;
        }
        .form-input {
            width: 100%;
            padding: 16px;
            border: 1px solid #bfc7d4;
            border-radius: 0.5rem;
            background: #f3faff;
            color: #141d21;
            transition: all 0.2s;
            outline: none;
        }
        .form-input:focus {
            border-color: #005f9f;
            box-shadow: 0 0 0 2px rgba(0,95,159,0.1);
        }
        .form-input.error {
            border-color: #ba1a1a;
            background: #fff8f8;
        }
    </style>
</head>
<body class="bg-background text-on-surface">

<!-- Header -->
<header class="bg-surface border-b border-outline-variant shadow-sm sticky top-0 z-50">
    <nav class="flex justify-between items-center w-full px-gutter max-w-container-max mx-auto h-20">
        <div class="font-headline-lg text-headline-lg font-bold text-primary">TravelEase</div>
        <div class="hidden md:flex items-center space-x-xl">
            <a class="text-primary font-bold border-b-2 border-primary pb-1" href="/travelease/hotels/index.php">Hotels</a>
            <!-- <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="#">Flights</a>
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="#">Trains</a>
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="#">Activities</a> -->
            <a class="text-on-surface-variant font-medium hover:text-primary transition-colors" href="/travelease/booking/mybooking.php">My Bookings</a>
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

<main class="pt-32 pb-xl px-gutter max-w-container-max mx-auto">

    <!-- Pesan Sukses Booking -->
    <?php if ($success): ?>
        <div class="flex items-center gap-md bg-green-50 border border-green-400 rounded-xl px-lg py-md mb-xl">
            <span class="material-symbols-outlined text-green-600 text-[32px]">check_circle</span>
            <div>
                <p class="font-headline-md text-green-800">Booking Berhasil!</p>
                <p class="text-body-md text-green-700">
                    Booking #<?= $booking_id ?> untuk <strong><?= htmlspecialchars($room['hotel_name']) ?></strong>
                    — <?= htmlspecialchars($room['room_type']) ?> sudah dikonfirmasi.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Progress Steps -->
    <div class="flex items-center justify-center mb-xl">
        <div class="flex items-center w-full max-w-2xl">
            <div class="flex flex-col items-center relative">
                <div class="w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center font-bold z-10">1</div>
                <span class="absolute -bottom-6 text-label-sm font-bold text-primary whitespace-nowrap">Data Tamu</span>
            </div>
            <div class="flex-1 h-1 bg-primary mx-2"></div>
            <div class="flex flex-col items-center relative">
                <div class="w-10 h-10 rounded-full bg-surface-container-highest text-on-surface-variant flex items-center justify-center font-bold z-10">2</div>
                <span class="absolute -bottom-6 text-label-sm text-on-surface-variant whitespace-nowrap">Pembayaran</span>
            </div>
            <div class="flex-1 h-1 bg-surface-container-highest mx-2"></div>
            <div class="flex flex-col items-center relative">
                <div class="w-10 h-10 rounded-full bg-surface-container-highest text-on-surface-variant flex items-center justify-center font-bold z-10">3</div>
                <span class="absolute -bottom-6 text-label-sm text-on-surface-variant whitespace-nowrap">Konfirmasi</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-xl mt-12">

        <!-- Kiri: Form -->
        <div class="md:col-span-8 space-y-xl">
            <section class="bg-surface-container-lowest p-xl rounded-xl border border-outline-variant shadow-sm">
                <h2 class="font-headline-md text-headline-md text-primary mb-lg flex items-center gap-sm">
                    <span class="material-symbols-outlined">person</span>
                    Informasi Tamu
                </h2>

                <form method="POST" action="" id="bookingForm">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">

                        <!-- Nama Depan -->
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-on-surface-variant">Nama Depan</label>
                            <input type="text" name="first_name"
                                   placeholder="e.g. John"
                                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                                   class="form-input <?= isset($errors['first_name']) ? 'error' : '' ?>">
                            <?php if (isset($errors['first_name'])): ?>
                                <p class="text-error text-label-sm flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-sm">error</span>
                                    <?= $errors['first_name'] ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Nama Belakang -->
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-on-surface-variant">Nama Belakang</label>
                            <input type="text" name="last_name"
                                   placeholder="e.g. Doe"
                                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                                   class="form-input <?= isset($errors['last_name']) ? 'error' : '' ?>">
                            <?php if (isset($errors['last_name'])): ?>
                                <p class="text-error text-label-sm flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-sm">error</span>
                                    <?= $errors['last_name'] ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Email -->
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-on-surface-variant">Alamat Email</label>
                            <input type="email" name="email"
                                   placeholder="john.doe@example.com"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   class="form-input <?= isset($errors['email']) ? 'error' : '' ?>">
                            <?php if (isset($errors['email'])): ?>
                                <p class="text-error text-label-sm flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-sm">error</span>
                                    <?= $errors['email'] ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Telepon -->
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-on-surface-variant">Nomor Telepon</label>
                            <div class="flex gap-sm">
                                <select class="form-input w-24">
                                    <option>+62</option>
                                    <option>+1</option>
                                    <option>+44</option>
                                    <option>+61</option>
                                </select>
                                <input type="tel" name="phone"
                                       placeholder="812 3456 7890"
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                       class="form-input flex-1 <?= isset($errors['phone']) ? 'error' : '' ?>">
                            </div>
                            <?php if (isset($errors['phone'])): ?>
                                <p class="text-error text-label-sm flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-sm">error</span>
                                    <?= $errors['phone'] ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Check-in -->
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-on-surface-variant">Tanggal Check-in</label>
                            <input type="date" name="check_in"
                                   min="<?= date('Y-m-d') ?>"
                                   value="<?= htmlspecialchars($_POST['check_in'] ?? '') ?>"
                                   class="form-input <?= isset($errors['check_in']) ? 'error' : '' ?>">
                            <?php if (isset($errors['check_in'])): ?>
                                <p class="text-error text-label-sm flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-sm">error</span>
                                    <?= $errors['check_in'] ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Check-out -->
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-on-surface-variant">Tanggal Check-out</label>
                            <input type="date" name="check_out"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                   value="<?= htmlspecialchars($_POST['check_out'] ?? '') ?>"
                                   class="form-input <?= isset($errors['check_out']) ? 'error' : '' ?>">
                            <?php if (isset($errors['check_out'])): ?>
                                <p class="text-error text-label-sm flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-sm">error</span>
                                    <?= $errors['check_out'] ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Jumlah Tamu -->
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-on-surface-variant">Jumlah Tamu</label>
                            <select name="guests" class="form-input">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($_POST['guests'] ?? 2) == $i ? 'selected' : '' ?>>
                                        <?= $i ?> <?= $i === 1 ? 'Tamu' : 'Tamu' ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Permintaan Khusus -->
                        <div class="md:col-span-2 flex flex-col gap-xs">
                            <label class="font-label-md text-on-surface-variant">
                                Permintaan Khusus (Opsional)
                            </label>
                            <textarea name="notes" rows="4"
                                      placeholder="Late check-in, high floor, quiet room, etc."
                                      class="form-input resize-none"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                            <p class="text-label-sm text-outline">
                                Requests are subject to availability upon check-in.
                            </p>
                        </div>

                    </div>

                    <!-- Error stok -->
                    <?php if (isset($errors['stock'])): ?>
                        <div class="flex items-center gap-sm bg-red-50 border border-error rounded-lg px-md py-sm mt-lg">
                            <span class="material-symbols-outlined text-error">error</span>
                            <p class="text-error text-body-md"><?= $errors['stock'] ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Tombol Submit -->
                    <div class="mt-lg">
                        <button type="submit" name="booking"
                                class="w-full py-md bg-secondary-container text-on-primary rounded-xl font-bold text-lg hover:opacity-90 transition-all active:scale-95 shadow-lg flex items-center justify-center gap-md">
                            <span class="material-symbols-outlined"
                                  style="font-variation-settings: 'FILL' 1;">lock</span>
                            Lanjut ke Pembayaran
                        </button>
                    </div>

                </form>
            </section>

            <!-- Kebijakan Pembatalan -->
            <section class="bg-surface-container-lowest p-xl rounded-xl border border-outline-variant shadow-sm border-l-4 border-l-primary-container">
                <h3 class="font-headline-md text-headline-md text-primary mb-md flex items-center gap-sm">
                    <span class="material-symbols-outlined">verified_user</span>
                    Kebijakan Gratis Pembatalan
                </h3>
                <p class="text-on-surface-variant">
                    Kamu bisa membatalkan secara gratis sebelum 48 jam dari waktu check-in.
                    Setelah itu, booking menjadi non-refundable.
                </p>
            </section>
        </div>

        <!-- Kanan: Sidebar Ringkasan -->
        <div class="md:col-span-4">
            <div class="sticky top-28 space-y-lg">
                <div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-md overflow-hidden">

                    <!-- Gambar Hotel -->
                    <div class="relative h-48">
                        <img class="w-full h-full object-cover"
                             src="<?= htmlspecialchars($room['hotel_image'] ?? '') ?>"
                             alt="<?= htmlspecialchars($room['hotel_name']) ?>">
                        <div class="absolute top-4 left-4 bg-primary text-on-primary px-sm py-xs rounded-full text-label-sm font-bold">
                            Hotel
                        </div>
                    </div>

                    <div class="p-xl">
                        <!-- Info Hotel & Kamar -->
                        <h3 class="font-headline-md text-headline-md text-on-surface mb-xs">
                            <?= htmlspecialchars($room['hotel_name']) ?>
                        </h3>
                        <p class="text-on-surface-variant flex items-center gap-xs mb-lg">
                            <span class="material-symbols-outlined text-primary text-sm">king_bed</span>
                            <?= htmlspecialchars($room['room_type']) ?>
                        </p>

                        <!-- Tanggal Check-in/out -->
                        <div class="flex justify-between items-start mb-lg p-md bg-surface-container rounded-lg">
                            <div>
                                <p class="text-label-sm text-outline uppercase">Check-in</p>
                                <p class="font-bold text-on-surface">
                                    <?= !empty($_POST['check_in'])
                                        ? date('d M Y', strtotime($_POST['check_in']))
                                        : 'Pilih tanggal' ?>
                                </p>
                            </div>
                            <div class="w-px h-10 bg-outline-variant"></div>
                            <div class="text-right">
                                <p class="text-label-sm text-outline uppercase">Check-out</p>
                                <p class="font-bold text-on-surface">
                                    <?= !empty($_POST['check_out'])
                                        ? date('d M Y', strtotime($_POST['check_out']))
                                        : 'Pilih tanggal' ?>
                                </p>
                            </div>
                        </div>

                        <!-- Ringkasan Harga -->
                        <div class="space-y-sm border-t border-outline-variant pt-lg mb-lg">
                            <?php
                            // Hitung ulang jika tanggal sudah diisi
                            if (!empty($_POST['check_in']) && !empty($_POST['check_out'])) {
                                $in       = new DateTime($_POST['check_in']);
                                $out      = new DateTime($_POST['check_out']);
                                $nights   = max(1, $in->diff($out)->days);
                                $subtotal = $room['price_per_night'] * $nights;
                                $fee      = $subtotal * 0.1;
                                $total    = $subtotal + $fee;
                            } else {
                                $nights   = $preview_nights;
                                $subtotal = $preview_subtotal;
                                $fee      = $preview_service_fee;
                                $total    = $preview_total;
                            }
                            ?>
                            <div class="flex justify-between text-on-surface-variant text-body-md">
                                <span>Rp <?= number_format((float)$room['price_per_night'], 0, ',', '.') ?> x <?= $nights ?> malam</span>
                                <span>Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                            </div>
                            <div class="flex justify-between text-on-surface-variant text-body-md">
                                <span>Service fee (10%)</span>
                                <span>Rp <?= number_format($fee, 0, ',', '.') ?></span>
                            </div>
                            <div class="flex justify-between items-center pt-md border-t border-dashed border-outline-variant">
                                <span class="font-headline-md">Total Harga</span>
                                <div class="text-right">
                                    <span class="font-headline-lg text-secondary">
                                        Rp <?= number_format($total, 0, ',', '.') ?>
                                    </span>
                                    <p class="text-label-sm text-outline">Sudah termasuk pajak</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Submit di Sidebar -->
                        <button form="bookingForm" type="submit" name="booking"
                                class="w-full py-md bg-secondary-container text-on-primary rounded-xl font-bold text-lg hover:opacity-90 transition-all active:scale-95 shadow-lg flex items-center justify-center gap-md">
                            <span class="material-symbols-outlined"
                                  style="font-variation-settings: 'FILL' 1;">lock</span>
                            Lanjut ke Pembayaran
                        </button>

                        <p class="text-center text-label-sm text-outline mt-md flex items-center justify-center gap-xs">
                            <span class="material-symbols-outlined text-[16px]">security</span>
                            Secure 256-bit SSL encrypted payment
                        </p>
                    </div>
                </div>

                <!-- Bantuan -->
                <div class="bg-surface-container p-lg rounded-xl flex items-start gap-md">
                    <span class="material-symbols-outlined text-primary text-[32px]">support_agent</span>
                    <div>
                        <p class="font-bold text-on-surface">Butuh bantuan?</p>
                        <p class="text-body-md text-on-surface-variant">
                            Hubungi tim kami 24/7 di +62 800 234 5678
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- Footer -->
<footer class="bg-surface-container-highest border-t border-outline-variant mt-20">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-xl px-gutter py-xl max-w-container-max mx-auto">
        <div class="col-span-2 md:col-span-1">
            <span class="font-headline-md font-black text-on-surface block mb-md">TravelEase</span>
            <p class="text-label-sm text-on-surface-variant">
                © 2024 TravelEase. All rights reserved.
            </p>
        </div>
        <div>
            <h4 class="font-label-md text-on-surface font-bold mb-md">Company</h4>
            <ul class="space-y-sm">
                <li><a class="text-on-surface-variant hover:text-primary" href="#">About Us</a></li>
                <li><a class="text-on-surface-variant hover:text-primary" href="#">Careers</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-label-md text-on-surface font-bold mb-md">Destinations</h4>
            <ul class="space-y-sm">
                <li><a class="text-on-surface-variant hover:text-primary" href="#">Hotels</a></li>
                <li><a class="text-on-surface-variant hover:text-primary" href="#">Flights</a></li>
            </ul>
        </div>
        <div>
            <h4 class="font-label-md text-on-surface font-bold mb-md">Support</h4>
            <ul class="space-y-sm">
                <li><a class="text-on-surface-variant hover:text-primary" href="#">Help Center</a></li>
                <li><a class="text-on-surface-variant hover:text-primary" href="#">Privacy Policy</a></li>
            </ul>
        </div>
    </div>
</footer>

<!-- Update harga otomatis saat tanggal berubah -->
<script>
    const checkIn  = document.querySelector('input[name="check_in"]');
    const checkOut = document.querySelector('input[name="check_out"]');
    const pricePerNight = <?= (float)$room['price_per_night'] ?>;

    function updatePrice() {
        if (!checkIn.value || !checkOut.value) return;

        const inDate  = new Date(checkIn.value);
        const outDate = new Date(checkOut.value);

        if (outDate <= inDate) return;

        const nights   = Math.round((outDate - inDate) / (1000 * 60 * 60 * 24));
        const subtotal = pricePerNight * nights;
        const fee      = subtotal * 0.1;
        const total    = subtotal + fee;

        // Format Rupiah
        const fmt = (n) => 'Rp ' + n.toLocaleString('id-ID');

        // Update tampilan
        document.querySelectorAll('.price-nights')[0].textContent =
            fmt(pricePerNight) + ' x ' + nights + ' malam';
        document.querySelectorAll('.price-subtotal')[0].textContent = fmt(subtotal);
        document.querySelectorAll('.price-fee')[0].textContent      = fmt(fee);
        document.querySelectorAll('.price-total')[0].textContent    = fmt(total);

        // Pastikan check-out tidak sebelum check-in
        checkOut.min = checkIn.value;
    }

    checkIn.addEventListener('change', updatePrice);
    checkOut.addEventListener('change', updatePrice);
</script>

</body>
</html>
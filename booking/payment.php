<?php
require '../includes/auth_check.php';
require '../config/database.php';

// Ambil booking_id dari session yang disimpan di checkout
$booking_id = (int)($_SESSION['pending_booking_id'] ?? 0);

if ($booking_id <= 0) {
    header("Location: /travelease/hotels/index.php");
    exit;
}

// Ambil data booking
$stmt = $pdo->prepare("
    SELECT b.*, r.room_type, r.price_per_night,
           h.name AS hotel_name, h.image_url AS hotel_image, h.location
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN hotels h ON r.hotel_id = h.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: /travelease/hotels/index.php");
    exit;
}

$errors  = [];
$success = false;

if (isset($_POST['pay'])) {

    $payment_method = $_POST['payment_method'] ?? '';
    $card_number    = trim($_POST['card_number']    ?? '');
    $card_name      = trim($_POST['card_name']      ?? '');
    $expiry         = trim($_POST['expiry']          ?? '');
    $cvv            = trim($_POST['cvv']             ?? '');

    // Validasi metode pembayaran
    $allowed_methods = ['credit_card', 'debit_card', 'transfer', 'ewallet'];
    if (!in_array($payment_method, $allowed_methods)) {
        $errors['payment_method'] = "Pilih metode pembayaran.";
    }

    // Validasi kartu jika metode kartu
    if (in_array($payment_method, ['credit_card', 'debit_card'])) {

        // Hapus spasi dari nomor kartu
        $card_number_clean = preg_replace('/\s+/', '', $card_number);

        if (strlen($card_number_clean) !== 16 || !ctype_digit($card_number_clean)) {
            $errors['card_number'] = "Nomor kartu harus 16 digit.";
        }
        if (empty($card_name)) {
            $errors['card_name'] = "Nama pemegang kartu wajib diisi.";
        }
        if (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
            $errors['expiry'] = "Format kadaluarsa MM/YY.";
        }
        if (strlen($cvv) < 3 || !ctype_digit($cvv)) {
            $errors['cvv'] = "CVV tidak valid.";
        }
    }

    // Kalau tidak ada error, update status booking
    if (empty($errors)) {
        $pdo->prepare("
            UPDATE bookings SET status = 'confirmed' WHERE id = ?
        ")->execute([$booking_id]);

        // Hapus session pending
        unset($_SESSION['pending_booking_id']);

        // Simpan booking_id untuk halaman konfirmasi
        $_SESSION['confirmed_booking_id'] = $booking_id;

        header("Location: /travelease/booking/confirmation.php");
        exit;
    }
}

// Hitung malam
$in     = new DateTime($booking['check_in']);
$out    = new DateTime($booking['check_out']);
$nights = $in->diff($out)->days;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TravelEase - Pembayaran</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "on-primary": "#ffffff","surface-variant": "#dbe4e9",
                        "surface-container": "#e6eff5","primary": "#005f9f",
                        "on-surface": "#141d21","outline-variant": "#bfc7d4",
                        "background": "#f3faff","error": "#ba1a1a",
                        "surface-container-lowest": "#ffffff","primary-container": "#0078c7",
                        "outline": "#707884","surface-container-highest": "#dbe4e9",
                        "on-surface-variant": "#3f4752","surface": "#f3faff",
                        "secondary-container": "#ff5e1f","secondary": "#ab3500",
                        "surface-container-low": "#ecf5fb",
                    },
                    spacing: {
                        "md":"16px","xs":"4px","gutter":"20px","lg":"24px",
                        "container-max":"1200px","sm":"12px","xl":"32px"
                    },
                    fontSize: {
                        "headline-md": ["20px",{lineHeight:"28px",fontWeight:"600"}],
                        "label-sm":    ["12px",{lineHeight:"16px",fontWeight:"500"}],
                        "headline-lg": ["32px",{lineHeight:"40px",fontWeight:"700"}],
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
        }
        .form-input {
            width: 100%; padding: 14px 16px;
            border: 1px solid #bfc7d4; border-radius: 0.5rem;
            background: #f3faff; color: #141d21;
            transition: all 0.2s; outline: none;
        }
        .form-input:focus { border-color: #005f9f; box-shadow: 0 0 0 2px rgba(0,95,159,0.1); }
        .form-input.error { border-color: #ba1a1a; background: #fff8f8; }
        .method-card {
            border: 2px solid #bfc7d4; border-radius: 0.75rem;
            padding: 16px; cursor: pointer; transition: all 0.2s;
        }
        .method-card:has(input:checked) {
            border-color: #005f9f; background: #e6eff5;
        }
    </style>
</head>
<body class="bg-background text-on-surface">

<!-- Header -->
<nav class="bg-surface border-b border-outline-variant shadow-sm h-20 fixed top-0 w-full z-50">
    <div class="flex justify-between items-center w-full px-gutter max-w-container-max mx-auto h-full">
        <a href="/travelease/hotels/index.php"
           class="font-headline-lg text-headline-lg font-bold text-primary">TravelEase</a>
        <div class="flex items-center gap-md">
            <span class="text-on-surface-variant hidden md:block">
                Halo, <strong class="text-primary"><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
            </span>
            <a href="/travelease/auth/logout.php"
               class="px-md py-xs rounded-lg text-primary font-label-md border border-primary hover:bg-surface-container transition-colors">
                Logout
            </a>
        </div>
    </div>
</nav>

<main class="pt-32 pb-xl px-gutter max-w-container-max mx-auto">

    <!-- Progress Steps -->
    <div class="flex items-center justify-center mb-xl">
        <div class="flex items-center w-full max-w-2xl">
            <!-- Step 1 selesai -->
            <div class="flex flex-col items-center relative">
                <div class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center font-bold z-10">
                    <span class="material-symbols-outlined text-[20px]"
                          style="font-variation-settings: 'FILL' 1;">check</span>
                </div>
                <span class="absolute -bottom-6 text-label-sm text-green-600 whitespace-nowrap font-bold">Data Tamu</span>
            </div>
            <div class="flex-1 h-1 bg-primary mx-2"></div>
            <!-- Step 2 aktif -->
            <div class="flex flex-col items-center relative">
                <div class="w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center font-bold z-10">2</div>
                <span class="absolute -bottom-6 text-label-sm text-primary font-bold whitespace-nowrap">Pembayaran</span>
            </div>
            <div class="flex-1 h-1 bg-surface-container-highest mx-2"></div>
            <!-- Step 3 belum -->
            <div class="flex flex-col items-center relative">
                <div class="w-10 h-10 rounded-full bg-surface-container-highest text-on-surface-variant flex items-center justify-center font-bold z-10">3</div>
                <span class="absolute -bottom-6 text-label-sm text-on-surface-variant whitespace-nowrap">Konfirmasi</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-xl mt-12">

        <!-- Kiri: Form Pembayaran -->
        <div class="md:col-span-8 space-y-xl">
            <section class="bg-surface-container-lowest p-xl rounded-xl border border-outline-variant shadow-sm">
                <h2 class="font-headline-md text-headline-md text-primary mb-lg flex items-center gap-sm">
                    <span class="material-symbols-outlined">payment</span>
                    Metode Pembayaran
                </h2>

                <form method="POST" action="" id="paymentForm">

                    <!-- Pilih Metode -->
                    <?php if (isset($errors['payment_method'])): ?>
                        <p class="text-error text-label-sm mb-md flex items-center gap-xs">
                            <span class="material-symbols-outlined text-sm">error</span>
                            <?= $errors['payment_method'] ?>
                        </p>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-md mb-xl">

                        <label class="method-card flex flex-col items-center gap-sm text-center">
                            <input type="radio" name="payment_method" value="credit_card"
                                   class="sr-only" <?= ($_POST['payment_method'] ?? '') === 'credit_card' ? 'checked' : '' ?>>
                            <span class="material-symbols-outlined text-primary text-[32px]">credit_card</span>
                            <span class="font-label-md text-on-surface">Kartu Kredit</span>
                        </label>

                        <label class="method-card flex flex-col items-center gap-sm text-center">
                            <input type="radio" name="payment_method" value="debit_card"
                                   class="sr-only" <?= ($_POST['payment_method'] ?? '') === 'debit_card' ? 'checked' : '' ?>>
                            <span class="material-symbols-outlined text-primary text-[32px]">account_balance_wallet</span>
                            <span class="font-label-md text-on-surface">Kartu Debit</span>
                        </label>

                        <label class="method-card flex flex-col items-center gap-sm text-center">
                            <input type="radio" name="payment_method" value="transfer"
                                   class="sr-only" <?= ($_POST['payment_method'] ?? '') === 'transfer' ? 'checked' : '' ?>>
                            <span class="material-symbols-outlined text-primary text-[32px]">account_balance</span>
                            <span class="font-label-md text-on-surface">Transfer Bank</span>
                        </label>

                        <label class="method-card flex flex-col items-center gap-sm text-center">
                            <input type="radio" name="payment_method" value="ewallet"
                                   class="sr-only" <?= ($_POST['payment_method'] ?? '') === 'ewallet' ? 'checked' : '' ?>>
                            <span class="material-symbols-outlined text-primary text-[32px]">smartphone</span>
                            <span class="font-label-md text-on-surface">E-Wallet</span>
                        </label>

                    </div>

                    <!-- Form Detail Kartu (tampil jika kartu dipilih) -->
                    <div id="card-form" class="space-y-lg <?= in_array($_POST['payment_method'] ?? '', ['credit_card','debit_card']) ? '' : 'hidden' ?>">

                        <div class="p-md bg-surface-container rounded-lg flex items-center gap-sm mb-md">
                            <span class="material-symbols-outlined text-primary">security</span>
                            <p class="text-label-sm text-on-surface-variant">
                                Data kartu kamu dienkripsi dengan SSL 256-bit. Kami tidak menyimpan nomor kartu.
                            </p>
                        </div>

                        <!-- Nomor Kartu -->
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-on-surface-variant">Nomor Kartu</label>
                            <div class="relative">
                                <input type="text" name="card_number" id="card_number"
                                       placeholder="0000 0000 0000 0000"
                                       maxlength="19"
                                       value="<?= htmlspecialchars($_POST['card_number'] ?? '') ?>"
                                       class="form-input <?= isset($errors['card_number']) ? 'error' : '' ?>"
                                       style="padding-right: 50px;">
                                <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-outline">
                                    credit_card
                                </span>
                            </div>
                            <?php if (isset($errors['card_number'])): ?>
                                <p class="text-error text-label-sm flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-sm">error</span>
                                    <?= $errors['card_number'] ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Nama di Kartu -->
                        <div class="flex flex-col gap-xs">
                            <label class="font-label-md text-on-surface-variant">Nama Pemegang Kartu</label>
                            <input type="text" name="card_name"
                                   placeholder="Sesuai nama di kartu"
                                   value="<?= htmlspecialchars($_POST['card_name'] ?? '') ?>"
                                   class="form-input <?= isset($errors['card_name']) ? 'error' : '' ?>">
                            <?php if (isset($errors['card_name'])): ?>
                                <p class="text-error text-label-sm flex items-center gap-xs">
                                    <span class="material-symbols-outlined text-sm">error</span>
                                    <?= $errors['card_name'] ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Expiry & CVV -->
                        <div class="grid grid-cols-2 gap-md">
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-md text-on-surface-variant">Kadaluarsa</label>
                                <input type="text" name="expiry"
                                       placeholder="MM/YY" maxlength="5"
                                       value="<?= htmlspecialchars($_POST['expiry'] ?? '') ?>"
                                       class="form-input <?= isset($errors['expiry']) ? 'error' : '' ?>"
                                       id="expiry">
                                <?php if (isset($errors['expiry'])): ?>
                                    <p class="text-error text-label-sm flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-sm">error</span>
                                        <?= $errors['expiry'] ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col gap-xs">
                                <label class="font-label-md text-on-surface-variant">CVV</label>
                                <input type="password" name="cvv"
                                       placeholder="•••" maxlength="4"
                                       class="form-input <?= isset($errors['cvv']) ? 'error' : '' ?>">
                                <?php if (isset($errors['cvv'])): ?>
                                    <p class="text-error text-label-sm flex items-center gap-xs">
                                        <span class="material-symbols-outlined text-sm">error</span>
                                        <?= $errors['cvv'] ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Info Transfer Bank -->
                    <div id="transfer-form" class="<?= ($_POST['payment_method'] ?? '') === 'transfer' ? '' : 'hidden' ?>">
                        <div class="p-lg bg-surface-container rounded-xl space-y-md">
                            <p class="font-label-md text-on-surface">Transfer ke rekening berikut:</p>
                            <div class="space-y-sm">
                                <div class="flex justify-between">
                                    <span class="text-on-surface-variant">Bank</span>
                                    <span class="font-bold">BCA</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-on-surface-variant">No. Rekening</span>
                                    <span class="font-bold">1234 5678 90</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-on-surface-variant">Atas Nama</span>
                                    <span class="font-bold">PT TravelEase Indonesia</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-on-surface-variant">Jumlah Transfer</span>
                                    <span class="font-bold text-secondary">
                                        Rp <?= number_format((float)$booking['total_price'], 0, ',', '.') ?>
                                    </span>
                                </div>
                            </div>
                            <p class="text-label-sm text-outline">
                                Setelah transfer, konfirmasi pembayaran dalam 1x24 jam.
                            </p>
                        </div>
                    </div>

                    <!-- Info E-Wallet -->
                    <div id="ewallet-form" class="<?= ($_POST['payment_method'] ?? '') === 'ewallet' ? '' : 'hidden' ?>">
                        <div class="p-lg bg-surface-container rounded-xl">
                            <p class="font-label-md text-on-surface mb-md">Pilih E-Wallet:</p>
                            <div class="grid grid-cols-3 gap-md">
                                <?php foreach (['GoPay', 'OVO', 'Dana'] as $wallet): ?>
                                <label class="method-card flex flex-col items-center gap-xs text-center">
                                    <input type="radio" name="ewallet_type" value="<?= strtolower($wallet) ?>" class="sr-only">
                                    <span class="material-symbols-outlined text-primary">smartphone</span>
                                    <span class="font-label-md"><?= $wallet ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Bayar -->
                    <div class="mt-xl">
                        <button type="submit" name="pay"
                                class="w-full py-md bg-secondary-container text-on-primary rounded-xl font-bold text-lg hover:opacity-90 transition-all active:scale-95 shadow-lg flex items-center justify-center gap-md">
                            <span class="material-symbols-outlined"
                                  style="font-variation-settings: 'FILL' 1;">lock</span>
                            Bayar Rp <?= number_format((float)$booking['total_price'], 0, ',', '.') ?>
                        </button>
                        <p class="text-center text-label-sm text-outline mt-md flex items-center justify-center gap-xs">
                            <span class="material-symbols-outlined text-[16px]">security</span>
                            Secure 256-bit SSL encrypted payment
                        </p>
                    </div>

                </form>
            </section>
        </div>

        <!-- Kanan: Ringkasan Booking -->
        <div class="md:col-span-4">
            <div class="sticky top-28 bg-surface-container-lowest rounded-xl border border-outline-variant shadow-md overflow-hidden">

                <div class="relative h-40">
                    <img class="w-full h-full object-cover"
                         src="<?= htmlspecialchars($booking['hotel_image']) ?>"
                         alt="<?= htmlspecialchars($booking['hotel_name']) ?>">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                    <div class="absolute bottom-4 left-4">
                        <p class="text-white font-bold text-headline-md">
                            <?= htmlspecialchars($booking['hotel_name']) ?>
                        </p>
                        <p class="text-white/80 text-label-sm">
                            <?= htmlspecialchars($booking['room_type']) ?>
                        </p>
                    </div>
                </div>

                <div class="p-lg space-y-md">

                    <!-- Tanggal -->
                    <div class="flex justify-between text-body-md">
                        <div>
                            <p class="text-label-sm text-on-surface-variant">CHECK-IN</p>
                            <p class="font-bold"><?= date('d M Y', strtotime($booking['check_in'])) ?></p>
                        </div>
                        <span class="material-symbols-outlined text-outline">arrow_forward</span>
                        <div class="text-right">
                            <p class="text-label-sm text-on-surface-variant">CHECK-OUT</p>
                            <p class="font-bold"><?= date('d M Y', strtotime($booking['check_out'])) ?></p>
                        </div>
                    </div>

                    <div class="border-t border-outline-variant pt-md space-y-sm">
                        <div class="flex justify-between text-body-md text-on-surface-variant">
                            <span>Rp <?= number_format((float)$booking['price_per_night'], 0, ',', '.') ?> x <?= $nights ?> malam</span>
                            <span>Rp <?= number_format((float)$booking['price_per_night'] * $nights, 0, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between text-body-md text-on-surface-variant">
                            <span>Service fee (10%)</span>
                            <span>Rp <?= number_format((float)$booking['price_per_night'] * $nights * 0.1, 0, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between font-bold text-headline-md pt-sm border-t border-outline-variant">
                            <span>Total</span>
                            <span class="text-secondary">
                                Rp <?= number_format((float)$booking['total_price'], 0, ',', '.') ?>
                            </span>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</main>

<script>
    // Tampilkan/sembunyikan form sesuai metode pembayaran
    const methods = document.querySelectorAll('input[name="payment_method"]');
    const cardForm     = document.getElementById('card-form');
    const transferForm = document.getElementById('transfer-form');
    const ewalletForm  = document.getElementById('ewallet-form');

    methods.forEach(method => {
        method.addEventListener('change', () => {
            cardForm.classList.add('hidden');
            transferForm.classList.add('hidden');
            ewalletForm.classList.add('hidden');

            if (['credit_card','debit_card'].includes(method.value)) {
                cardForm.classList.remove('hidden');
            } else if (method.value === 'transfer') {
                transferForm.classList.remove('hidden');
            } else if (method.value === 'ewallet') {
                ewalletForm.classList.remove('hidden');
            }
        });
    });

    // Format nomor kartu otomatis: 0000 0000 0000 0000
    document.getElementById('card_number')?.addEventListener('input', function() {
        let val = this.value.replace(/\D/g, '').substring(0, 16);
        this.value = val.replace(/(.{4})/g, '$1 ').trim();
    });

    // Format expiry otomatis: MM/YY
    document.getElementById('expiry')?.addEventListener('input', function() {
        let val = this.value.replace(/\D/g, '').substring(0, 4);
        if (val.length >= 2) val = val.substring(0,2) + '/' + val.substring(2);
        this.value = val;
    });
</script>

</body>
</html>
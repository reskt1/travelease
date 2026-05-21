<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: /travelease/hotels/index.php");
    exit;
}

require '../config/database.php';

$errors = [];
$old    = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $agree    = $_POST['agree'] ?? '';

    $old = ['name' => $name, 'email' => $email];

    // Validasi
    if (empty($name)) {
        $errors['name'] = "Nama wajib diisi.";
    }

    if (empty($email)) {
        $errors['email'] = "Email wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format email tidak valid.";
    }

    if (strlen($password) < 8) {
        $errors['password'] = "Password minimal 8 karakter.";
    }

    if (empty($agree)) {
        $errors['agree'] = "Kamu harus menyetujui syarat & ketentuan.";
    }

    // Cek email duplikat
    if (empty($errors['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors['email'] = "Email sudah terdaftar.";
        }
    }


    // SESUDAH - redirect ke login dulu
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hashed]);

        // Tidak set session, langsung suruh login
        header("Location: /travelease/auth/login.php?registered=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TravelEase | Daftar</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-primary": "#ffffff",
                        "primary": "#005f9f",
                        "on-surface": "#141d21",
                        "outline-variant": "#bfc7d4",
                        "background": "#f3faff",
                        "error": "#ba1a1a",
                        "surface-container-lowest": "#ffffff",
                        "primary-container": "#0078c7",
                        "outline": "#707884",
                        "on-surface-variant": "#3f4752",
                        "surface": "#f3faff",
                        "secondary-container": "#ff5e1f",
                        "surface-container": "#e6eff5",
                    },
                    spacing: {
                        "md": "16px", "xs": "4px", "gutter": "20px",
                        "lg": "24px", "sm": "12px", "xl": "32px",
                    },
                    fontSize: {
                        "headline-md": ["20px", {lineHeight:"28px", fontWeight:"600"}],
                        "label-sm":    ["12px", {lineHeight:"16px", fontWeight:"500"}],
                        "headline-lg": ["32px", {lineHeight:"40px", fontWeight:"700"}],
                        "body-md":     ["14px", {lineHeight:"20px", fontWeight:"400"}],
                        "label-md":    ["14px", {lineHeight:"20px", fontWeight:"600"}],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .auth-card { box-shadow: 0px 8px 24px rgba(0,0,0,0.12); }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="bg-background min-h-screen flex items-center justify-center p-md md:p-lg">
<main class="w-full max-w-[1200px] flex items-center justify-center">

    <div class="bg-surface-container-lowest auth-card rounded-xl overflow-hidden flex flex-col md:flex-row w-full max-w-[1000px] min-h-[600px]">

        <!-- Kiri: Branding -->
        <div class="hidden md:block w-1/2 relative bg-primary overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img alt="Peaceful traveler"
                    class="w-full h-full object-cover opacity-60 mix-blend-multiply"
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCaomegDE09d7UH5NHh0MGcYHsipCh8LDwNLgGVzfwJ3rN4BIoZ4wczgvI02WK3P5e3Oju42XntORKmwlbMlCVIO0PCBVB9hfeQx_9dywqvxLMfF1Z-eCFkbFxfrBr1Xaix9VZNHWNIiGBaYa5zkkeXUV6XuOF2J8A1INZqcWEgE03z4Hqm8NCQmwcnL10nfRxzjHEaX7ycSCajD4J4hB3IkfhWl0jP-RsI92Hp0_NdcYDfFc1Z2B6KxyElVEegKyfVin9zdW7Prlw">
            </div>
            <div class="relative z-10 p-xl h-full flex flex-col justify-between">
                <div>
                    <h1 class="font-headline-lg text-headline-lg text-on-primary">TravelEase</h1>
                    <p class="text-on-primary mt-sm opacity-90 text-sm">Your trusted partner for seamless journeys around the globe.</p>
                </div>
                <div class="space-y-md">
                    <div class="flex items-center gap-md bg-white/10 backdrop-blur-md p-md rounded-lg border border-white/20">
                        <span class="material-symbols-outlined text-on-primary">verified_user</span>
                        <span class="font-label-md text-label-md text-on-primary">Jaminan Pemesanan Aman</span>
                    </div>
                    <div class="flex items-center gap-md bg-white/10 backdrop-blur-md p-md rounded-lg border border-white/20">
                        <span class="material-symbols-outlined text-on-primary">support_agent</span>
                        <span class="font-label-md text-label-md text-on-primary">Bantuan Premium 24/7</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanan: Form Register -->
        <div class="w-full md:w-1/2 p-lg md:p-xl flex flex-col justify-center">

            <!-- Tab navigasi -->
            <div class="flex border-b border-outline-variant mb-xl">
                <a href="login.php"
                   class="flex-1 py-md text-center font-label-md text-label-md text-on-surface-variant cursor-pointer transition-all">
                   Login
                </a>
                <a href="register.php"
                   class="flex-1 py-md text-center font-label-md text-label-md cursor-pointer transition-all"
                   style="border-bottom: 2px solid #005f9f; color: #005f9f; font-weight: 700;">
                   Daftar
                </a>
            </div>

            <h2 class="font-headline-md text-headline-md text-on-surface mb-md">Join TravelEase</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mb-lg">Start your journey with us today. Create a free account.</p>

            <form method="POST" action="" class="space-y-md">

                <!-- Nama -->
                <div class="space-y-xs">
                    <label class="font-label-sm text-label-sm text-on-surface-variant">Nama Lengkap</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline text-sm">person</span>
                        <input
                            type="text"
                            name="name"
                            value="<?= htmlspecialchars($old['name']) ?>"
                            placeholder="John Doe"
                            class="w-full pl-xl pr-md py-md rounded-lg border <?= isset($errors['name']) ? 'border-error' : 'border-outline-variant' ?> focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all font-body-md text-body-md outline-none">
                    </div>
                    <?php if (isset($errors['name'])): ?>
                        <p class="text-error font-label-sm text-label-sm flex items-center gap-xs">
                            <span class="material-symbols-outlined text-sm">error</span>
                            <?= $errors['name'] ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Email -->
                <div class="space-y-xs">
                    <label class="font-label-sm text-label-sm text-on-surface-variant">Alamat Email</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline text-sm">mail</span>
                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($old['email']) ?>"
                            placeholder="name@company.com"
                            class="w-full pl-xl pr-md py-md rounded-lg border <?= isset($errors['email']) ? 'border-error' : 'border-outline-variant' ?> focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all font-body-md text-body-md outline-none">
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <p class="text-error font-label-sm text-label-sm flex items-center gap-xs">
                            <span class="material-symbols-outlined text-sm">error</span>
                            <?= $errors['email'] ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Password -->
                <div class="space-y-xs">
                    <label class="font-label-sm text-label-sm text-on-surface-variant">Kata Sandi</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline text-sm">lock</span>
                        <input
                            type="password"
                            name="password"
                            placeholder="Min. 8 characters"
                            class="w-full pl-xl pr-md py-md rounded-lg border <?= isset($errors['password']) ? 'border-error' : 'border-outline-variant' ?> focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all font-body-md text-body-md outline-none">
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <p class="text-error font-label-sm text-label-sm flex items-center gap-xs">
                            <span class="material-symbols-outlined text-sm">error</span>
                            <?= $errors['password'] ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Checkbox Syarat & Ketentuan -->
                <div class="space-y-xs">
                    <div class="flex items-start gap-sm py-sm">
                        <input
                            type="checkbox"
                            name="agree"
                            id="agree"
                            class="mt-1 rounded border-outline-variant text-primary focus:ring-primary h-4 w-4">
                        <label for="agree" class="font-label-sm text-label-sm text-on-surface-variant">
                            Saya setuju dengan
                            <a href="#" class="text-primary hover:underline">Syarat & Ketentuan</a>
                            dan
                            <a href="#" class="text-primary hover:underline">Kebijakan Privasi</a>
                        </label>
                    </div>
                    <?php if (isset($errors['agree'])): ?>
                        <p class="text-error font-label-sm text-label-sm flex items-center gap-xs">
                            <span class="material-symbols-outlined text-sm">error</span>
                            <?= $errors['agree'] ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Tombol Submit -->
                <button
                    type="submit"
                    name="register"
                    class="w-full bg-secondary-container text-on-primary font-label-md text-label-md py-md rounded-lg hover:opacity-90 transition-all flex items-center justify-center gap-sm active:scale-95 shadow-md">
                    Buat Akun
                </button>

            </form>

            <div class="mt-xl text-center">
                <p class="font-label-sm text-label-sm text-on-surface-variant">© 2024 TravelEase. All rights reserved.</p>
            </div>

        </div>
    </div>
</main>
</body>
</html>
<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: /travelease/hotels/index.php");
    exit;
}

require '../config/database.php';

$error = '';

if (isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';  // ← sudah benar, tidak ada typo

    if (empty($email) || empty($password)) {
        $error = "Email dan password harus diisi.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);           // ← pakai [$email] bukan ['email']
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: /travelease/hotels/index.php");
            exit;
        } else {
            $error = "Email atau password tidak valid.";
        }
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>TravelEase | Login</title>
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
                        "surface-variant": "#dbe4e9",
                        "surface-container": "#e6eff5",
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
                        "on-primary-container": "#fdfcff",
                        "surface-container-high": "#e0e9ef",
                    },
                    spacing: {
                        "md": "16px", "xs": "4px", "gutter": "20px",
                        "lg": "24px", "sm": "12px", "xl": "32px",
                    },
                    fontSize: {
                        "headline-md": ["20px", {lineHeight:"28px", fontWeight:"600"}],
                        "label-sm":    ["12px", {lineHeight:"16px", fontWeight:"500"}],
                        "headline-lg": ["32px", {lineHeight:"40px", fontWeight:"700"}],
                        "body-lg":     ["16px", {lineHeight:"24px", fontWeight:"400"}],
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
                    <p class="font-body-lg text-body-lg text-on-primary mt-sm opacity-90">Your trusted partner for seamless journeys around the globe.</p>
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

        <!-- Kanan: Form Login -->
        <div class="w-full md:w-1/2 p-lg md:p-xl flex flex-col justify-center">

            <!-- Tab navigasi -->
            <div class="flex border-b border-outline-variant mb-xl">
                <a href="login.php"
                   class="flex-1 py-md text-center font-label-md text-label-md cursor-pointer transition-all"
                   style="border-bottom: 2px solid #005f9f; color: #005f9f; font-weight: 700;">
                   Login
                </a>
                <a href="register.php"
                   class="flex-1 py-md text-center font-label-md text-label-md text-on-surface-variant cursor-pointer transition-all">
                   Daftar
                </a>
            </div>

            <h2 class="font-headline-md text-headline-md text-on-surface mb-md">Selamat datang kembali</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mb-lg">Silakan masuk ke akun Anda</p>

            <!-- Pesan sukses setelah register -->
            <?php if (isset($_GET['registered'])): ?>
                <div class="flex items-center gap-sm bg-green-50 border border-green-400 rounded-lg px-md py-sm mb-md">
                    <span class="material-symbols-outlined text-green-600 text-sm">check_circle</span>
                    <p class="font-body-md text-body-md text-green-700">
                        Akun berhasil dibuat! Silakan login.
                    </p>
                </div>
            <?php endif; ?>

            <!-- Pesan error -->
            <?php if ($error): ?>
                <div class="flex items-center gap-sm bg-red-50 border border-error rounded-lg px-md py-sm mb-md">
                    <span class="material-symbols-outlined text-error text-sm">error</span>
                    <p class="font-body-md text-body-md text-error">
                        <?= htmlspecialchars($error) ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form method="POST" action="" class="space-y-md">

                <div class="space-y-xs">
                    <label class="font-label-sm text-label-sm text-on-surface-variant">Alamat Email</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline text-sm">mail</span>
                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            placeholder="name@company.com"
                            class="w-full pl-xl pr-md py-md rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all font-body-md text-body-md outline-none">
                    </div>
                </div>

                <div class="space-y-xs">
                    <div class="flex justify-between items-center">
                        <label class="font-label-sm text-label-sm text-on-surface-variant">Kata Sandi</label>
                        <a class="font-label-sm text-label-sm text-primary hover:underline" href="#">Lupa Kata Sandi?</a>
                    </div>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline text-sm">lock</span>
                        <input
                            type="password"
                            name="password"
                            placeholder="••••••••"
                            class="w-full pl-xl pr-md py-md rounded-lg border border-outline-variant focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all font-body-md text-body-md outline-none">
                    </div>
                </div>

                <button
                    type="submit"
                    name="login"
                    class="w-full bg-primary text-on-primary font-label-md text-label-md py-md rounded-lg hover:bg-primary-container transition-all flex items-center justify-center gap-sm active:scale-95">
                    Masuk
                </button>

            </form>

            <!-- Divider -->
            <div class="relative my-xl">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-outline-variant"></div>
                </div>
                <div class="relative flex justify-center text-label-sm">
                    <span class="px-md bg-surface-container-lowest text-on-surface-variant font-label-sm">
                        Atau masuk dengan
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-md">
                <button class="flex items-center justify-center gap-sm py-md px-md border border-outline-variant rounded-lg hover:bg-surface-container transition-all active:scale-95">
                    <img alt="Google" class="w-5 h-5" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDAysgrHbz_p2dYCo-YF0_k809KonUIMMng6UvnqJo31jQAyp9liStgzthIKbQ0oce8wXpmrRAECpZkkBcr4aznlTs0iXwW2iiL7Y0HWFQTkuOTF70aQkr9Q-jQfmaW1m-qGWsInV6ClfzTD3Eb_bt9_obj3QHWaY_8HQgEAA6W4s3ZwP7bgZbXu86RariMWthCggJSMZgH2JPm-q_gLnyjmnW5ybCHZotP8g1eww7cTyfMBFuo2OcwEEV7om9jnXqMgjrHGTamkgs">
                    <span class="font-label-sm text-label-sm text-on-surface">Google</span>
                </button>
                <button class="flex items-center justify-center gap-sm py-md px-md border border-outline-variant rounded-lg hover:bg-surface-container transition-all active:scale-95">
                    <span class="material-symbols-outlined text-blue-600">social_leaderboard</span>
                    <span class="font-label-sm text-label-sm text-on-surface">Facebook</span>
                </button>
            </div>

            <div class="mt-xl text-center">
                <p class="font-label-sm text-label-sm text-on-surface-variant">© 2024 TravelEase. All rights reserved.</p>
            </div>

        </div>
    </div>
</main>
</body>
</html>
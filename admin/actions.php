<?php
require '../includes/admin_check.php';
require '../config/database.php';

$action = $_POST['action'] ?? '';

switch ($action) {

    // Konfirmasi booking
    case 'confirm_booking':
        $id = (int)($_POST['booking_id'] ?? 0);
        $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")
            ->execute([$id]);
        break;

    // Batalkan booking — kembalikan stok
    case 'cancel_booking':
        $id = (int)($_POST['booking_id'] ?? 0);

        // Ambil room_id dulu untuk kembalikan stok
        $stmt = $pdo->prepare("SELECT room_id FROM bookings WHERE id = ?");
        $stmt->execute([$id]);
        $booking = $stmt->fetch();

        if ($booking) {
            $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")
                ->execute([$id]);
            // Kembalikan stok
            $pdo->prepare("UPDATE rooms SET stock = stock + 1 WHERE id = ?")
                ->execute([$booking['room_id']]);
        }
        break;
        
    // Hapus booking
    case 'delete_booking':
    $id = (int)($_POST['booking_id'] ?? 0);

    // Ambil room_id dulu
    $stmt = $pdo->prepare("SELECT room_id, status FROM bookings WHERE id = ?");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();

    if ($booking) {
        // Kembalikan stok jika booking confirmed
        if ($booking['status'] === 'confirmed') {
            $pdo->prepare("UPDATE rooms SET stock = stock + 1 WHERE id = ?")
                ->execute([$booking['room_id']]);
        }
        $pdo->prepare("DELETE FROM bookings WHERE id = ?")->execute([$id]);
    }
    break;

    // Tambah hotel baru
    case 'add_hotel':
        $pdo->prepare("
            INSERT INTO hotels (name, location, star_rating, review_score, badge, image_url, review_count)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            trim($_POST['name']            ?? ''),
            trim($_POST['location']        ?? ''),
            (int)($_POST['star_rating']    ?? 3),
            (float)($_POST['review_score'] ?? 8.0),
            trim($_POST['badge']           ?? '') ?: null,
            trim($_POST['image_url']       ?? ''),
            (int)($_POST['review_count']   ?? 0), // ← tambah ini
        ]);
    break;

    // Hapus hotel
    case 'delete_hotel':
        $id = (int)($_POST['hotel_id'] ?? 0);
        // Hapus rooms dulu karena ada foreign key
        $pdo->prepare("DELETE FROM rooms WHERE hotel_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM hotels WHERE id = ?")->execute([$id]);
        break;

    // Edit hotel
    case 'edit_hotel':
        $pdo->prepare("
            UPDATE hotels 
            SET name = ?, location = ?, star_rating = ?, 
                review_score = ?, review_count = ?, badge = ?, image_url = ?
            WHERE id = ?
        ")->execute([
            trim($_POST['name']             ?? ''),
            trim($_POST['location']         ?? ''),
            (int)($_POST['star_rating']     ?? 3),
            (float)($_POST['review_score']  ?? 8.0),
            (int)($_POST['review_count']    ?? 0),
            trim($_POST['badge']            ?? '') ?: null,
            trim($_POST['image_url']        ?? ''),
            (int)($_POST['hotel_id']        ?? 0),
        ]);
    break;
    
    //review hotel
    case 'update_review':
    $id = (int)($_POST['hotel_id'] ?? 0);
    $pdo->prepare("
        UPDATE hotels SET review_score = ?, review_count = ? WHERE id = ?
    ")->execute([
        (float)($_POST['review_score'] ?? 0),
        (int)($_POST['review_count']   ?? 0),
        $id,
    ]);
    break;
    // Tambah kamar
    case 'add_room':
        $pdo->prepare("
            INSERT INTO rooms (hotel_id, room_type, price_per_night, original_price, stock, facilities)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            (int)($_POST['hotel_id']       ?? 0),
            trim($_POST['room_type']       ?? ''),
            (float)($_POST['price_per_night'] ?? 0),
            (float)($_POST['original_price']  ?? 0) ?: null,
            (int)($_POST['stock']          ?? 0),
            trim($_POST['facilities']      ?? ''),
        ]);
        break;

    // Update stok kamar
    case 'update_stock':
        $pdo->prepare("UPDATE rooms SET stock = ? WHERE id = ?")
            ->execute([
                (int)($_POST['stock']   ?? 0),
                (int)($_POST['room_id'] ?? 0),
            ]);
        break;

    // Hapus kamar
    case 'delete_room':
        $id = (int)($_POST['room_id'] ?? 0);
        $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
        break;
}

// Kembali ke admin panel
header("Location: /travelease/admin/index.php");
exit;
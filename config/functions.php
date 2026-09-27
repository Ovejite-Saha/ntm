<?php
// config/functions.php — utility functions: image cropping/resizing, sanitation, mail

require_once __DIR__ . '/db.php';

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function set_flash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return [];
}

/**
 * Crop and resize an uploaded image to a square thumbnail (for profile photos).
 * Enforces max file size 1MB. Saves as the given destination path.
 * Only JPG, PNG, JPEG formats allowed.
 * Returns true on success, or an error message string on failure.
 */
function process_profile_image($fileTmp, $destPath) {
    // Safety check
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng')) {
        return 'GD library is not enabled. Please enable extension=gd in php.ini and restart Apache.';
    }

    $maxSize = 1048576; // 1MB
    $allowed = ['image/jpeg', 'image/jpg', 'image/png'];

    if ($fileTmp === '' || !file_exists($fileTmp)) {
        return 'No file uploaded.';
    }

    $info = @getimagesize($fileTmp);
    if ($info === false) {
        return 'Invalid image file.';
    }
    if (!in_array($info['mime'], $allowed)) {
        return 'Only JPG, PNG, JPEG formats allowed.';
    }

    // Check source file size; we'll compress after resizing
    $srcSize = filesize($fileTmp);
    if ($srcSize > $maxSize * 3) {
        return 'Image too large.';
    }

    $srcW = $info[0];
    $srcH = $info[1];
    $mime = $info['mime'];

    // Create source image resource
    if ($mime === 'image/png') {
        $src = imagecreatefrompng($fileTmp);
    } else {
        $src = imagecreatefromjpeg($fileTmp);
    }
    if (!$src) {
        return 'Failed to process image.';
    }

    // Crop to square: take center crop
    $size = min($srcW, $srcH);
    $offsetX = (int)(($srcW - $size) / 2);
    $offsetY = (int)(($srcH - $size) / 2);

    $thumbSize = 256; // 256x256 square
    $dst = imagecreatetruecolor($thumbSize, $thumbSize);
    imagecopyresampled($dst, $src, 0, 0, $offsetX, $offsetY, $thumbSize, $thumbSize, $size, $size);

    // Save with compression, reducing quality until under 1MB
    $quality = 90;
    $tmpDest = $destPath . '.tmp';
    while ($quality >= 20) {
        imagejpeg($dst, $tmpDest, $quality);
        if (filesize($tmpDest) <= $maxSize) {
            break;
        }
        $quality -= 10;
    }
    imagedestroy($src);
    imagedestroy($dst);

    if (!file_exists($tmpDest) || filesize($tmpDest) > $maxSize) {
        @unlink($tmpDest);
        return 'Could not compress image under 1MB.';
    }

    // Determine final extension (always .jpg after compression)
    $finalPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.jpg', $destPath);
    if ($finalPath !== $destPath && file_exists($destPath)) {
        @unlink($destPath);
    }
    if (!rename($tmpDest, $finalPath)) {
        @unlink($tmpDest);
        return 'Failed to save image.';
    }
    return $finalPath;
}

/**
 * Resize a tour image keeping aspect ratio. Max 3MB.
 * Only JPG, PNG, JPEG allowed.
 * Returns the destination path on success, or error message string.
 */
function process_tour_image($fileTmp, $destPath) {
    // Safety check
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng')) {
        return 'GD library is not enabled. Please enable extension=gd in php.ini and restart Apache.';
    }

    $maxSize = 3145728; // 3MB
    $maxDim = 1600; // max width/height
    $allowed = ['image/jpeg', 'image/jpg', 'image/png'];

    if (!file_exists($fileTmp)) {
        return 'No file uploaded.';
    }

    $info = @getimagesize($fileTmp);
    if ($info === false) {
        return 'Invalid image file.';
    }
    if (!in_array($info['mime'], $allowed)) {
        return 'Only JPG, PNG, JPEG formats allowed.';
    }

    $srcW = $info[0];
    $srcH = $info[1];
    $mime = $info['mime'];

    if ($mime === 'image/png') {
        $src = imagecreatefrompng($fileTmp);
    } else {
        $src = imagecreatefromjpeg($fileTmp);
    }
    if (!$src) {
        return 'Failed to process image.';
    }

    // Calculate new dimensions preserving aspect ratio
    $newW = $srcW;
    $newH = $srcH;
    if ($srcW > $maxDim || $srcH > $maxDim) {
        $ratio = $srcW / $srcH;
        if ($srcW > $srcH) {
            $newW = $maxDim;
            $newH = (int)($maxDim / $ratio);
        } else {
            $newH = $maxDim;
            $newW = (int)($maxDim * $ratio);
        }
    }

    $dst = imagecreatetruecolor($newW, $newH);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

    $quality = 85;
    $tmpDest = $destPath . '.tmp';
    while ($quality >= 25) {
        imagejpeg($dst, $tmpDest, $quality);
        if (filesize($tmpDest) <= $maxSize) {
            break;
        }
        $quality -= 10;
    }
    imagedestroy($src);
    imagedestroy($dst);

    if (!file_exists($tmpDest) || filesize($tmpDest) > $maxSize) {
        @unlink($tmpDest);
        return 'Could not compress image under 3MB.';
    }

    $finalPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.jpg', $destPath);
    if ($finalPath !== $destPath && file_exists($destPath)) {
        @unlink($destPath);
    }
    if (!rename($tmpDest, $finalPath)) {
        @unlink($tmpDest);
        return 'Failed to save image.';
    }
    return $finalPath;
}

/**
 * Send contact email using PHP mail() function.
 */
function send_contact_mail($to, $from, $subject, $message) {
    $headers = "From: " . $from . "\r\n";
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, $subject, $message, $headers);
}

/**
 * Get all tours, optionally filtered by status.
 */
function get_tours($status = null) {
    $conn = db();
    if ($status) {
        $stmt = $conn->prepare("SELECT * FROM tours WHERE status = ? ORDER BY tour_year DESC, tour_date DESC");
        $stmt->bind_param('s', $status);
    } else {
        $stmt = $conn->prepare("SELECT * FROM tours ORDER BY tour_year DESC, tour_date DESC");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $tours = [];
    while ($row = $result->fetch_assoc()) {
        $tours[] = $row;
    }
    $stmt->close();
    return $tours;
}

function get_tour_images($tourId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM tour_images WHERE tour_id = ? ORDER BY uploaded_at DESC");
    $stmt->bind_param('i', $tourId);
    $stmt->execute();
    $result = $stmt->get_result();
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    $stmt->close();
    return $images;
}

function get_slideshow_images() {
    $conn = db();
    $result = $conn->query("SELECT ti.*, t.tour_name, t.tour_year FROM tour_images ti JOIN tours t ON ti.tour_id = t.id WHERE ti.is_slideshow = 1 ORDER BY ti.uploaded_at DESC");
    $images = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
    }
    return $images;
}

function get_admin($id) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();
    return $admin;
}

function get_user($id) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * Sanitize a string for use as a folder name (tour_name_year).
 */
function sanitize_folder_name($name, $year) {
    $clean = preg_replace('/[^A-Za-z0-9_\-]/', '_', $name);
    return $clean . '_' . $year;
}
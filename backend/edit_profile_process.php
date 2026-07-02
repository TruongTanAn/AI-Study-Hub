<?php
require_once __DIR__ . '/../includes/utf8_helper.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Vui long dang nhap'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];

$fullName = trim(to_utf8($_POST['full_name'] ?? ''));
$email = trim($_POST['email'] ?? '');
$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

if ($fullName === '' || $email === '' || $currentPassword === '') {
    $_SESSION['error_message'] = 'Vui long dien day du cac thong tin bat buoc (ho ten, email, mat khau hien tai).';
    header('Location: ../pages/edit_profile.php');
    exit;
}

if (mb_strlen($fullName) < 2) {
    $_SESSION['error_message'] = 'Ho ten qua ngan (toi thieu phai co 2 ky tu).';
    header('Location: ../pages/edit_profile.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error_message'] = 'Dia chi Email khong dung dinh dang.';
    header('Location: ../pages/edit_profile.php');
    exit;
}

$sql = "SELECT password, avatar FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$userResult = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userResult || !password_verify($currentPassword, $userResult['password'])) {
    $_SESSION['error_message'] = 'Mat khau hien tai khong chinh xac. Khong the cap nhat thong tin.';
    header('Location: ../pages/edit_profile.php');
    exit;
}

$currentAvatar = $userResult['avatar'];

$sqlEmail = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
$stmtEmail = $conn->prepare($sqlEmail);
$stmtEmail->bind_param('si', $email, $userId);
$stmtEmail->execute();
$emailResult = $stmtEmail->get_result();
if ($emailResult->num_rows > 0) {
    $_SESSION['error_message'] = 'Dia chi Email nay da duoc su dung boi mot tai khoan khac.';
    $stmtEmail->close();
    header('Location: ../pages/edit_profile.php');
    exit;
}
$stmtEmail->close();

$newAvatarName = $currentAvatar;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['avatar']['tmp_name'];
    $fileName = $_FILES['avatar']['name'];
    $fileSize = $_FILES['avatar']['size'];

    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    if (in_array($fileExtension, $allowedExtensions)) {
        $maxSize = 2 * 1024 * 1024;
        if ($fileSize <= $maxSize) {
            $uploadFileDir = '../uploads/avatars/';

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }

            $newAvatarName = 'avatar_' . $userId . '_' . time() . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newAvatarName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                if ($currentAvatar !== 'default.png' && !empty($currentAvatar)) {
                    $oldAvatarPath = $uploadFileDir . $currentAvatar;
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
            } else {
                $_SESSION['error_message'] = 'Da co loi xay ra khi di chuyen file tai len vao thu muc luu tru.';
                header('Location: ../pages/edit_profile.php');
                exit;
            }
        } else {
            $_SESSION['error_message'] = 'Dung luong anh dai dien toi da la 2MB.';
            header('Location: ../pages/edit_profile.php');
            exit;
        }
    } else {
        $_SESSION['error_message'] = 'Dinh dang file khong hop le. Chi chap nhan cac duoi: JPG, JPEG, PNG, GIF.';
        header('Location: ../pages/edit_profile.php');
        exit;
    }
}

$passwordUpdated = false;
$passwordHash = '';
if ($newPassword !== '') {
    if (mb_strlen($newPassword) < 6) {
        $_SESSION['error_message'] = 'Mat khau moi phai tu 6 ky tu tro len.';
        header('Location: ../pages/edit_profile.php');
        exit;
    }
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $passwordUpdated = true;
}

if ($passwordUpdated) {
    $sqlUpdate = "UPDATE users SET full_name = ?, email = ?, avatar = ?, password = ? WHERE user_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param('ssssi', $fullName, $email, $newAvatarName, $passwordHash, $userId);
} else {
    $sqlUpdate = "UPDATE users SET full_name = ?, email = ?, avatar = ? WHERE user_id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param('sssi', $fullName, $email, $newAvatarName, $userId);
}

if ($stmtUpdate->execute()) {
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $email;
    $_SESSION['success_message'] = 'Cap nhat thong tin ca nhan thanh cong!';
    $stmtUpdate->close();
    header('Location: ../pages/profile.php');
    exit;
} else {
    $_SESSION['error_message'] = 'Loi he thong: Khong the cap nhat thong tin trong database.';
    $stmtUpdate->close();
    header('Location: ../pages/edit_profile.php');
    exit;
}

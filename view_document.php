<?php
require_once __DIR__ . '/includes/functions.php';

$docId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($docId <= 0) {
    http_response_code(400);
    exit('Invalid document reference.');
}

$stmt = $pdo->prepare("SELECT id, application_id, file_path, file_name, file_type FROM uploaded_documents WHERE id = ?");
$stmt->execute([$docId]);
$doc = $stmt->fetch();

if (!$doc) {
    http_response_code(404);
    exit('Document not found.');
}

$allowed = isAdminLoggedIn();
if (!$allowed) {
    $tracked = isset($_SESSION['tracked_app_ids']) && is_array($_SESSION['tracked_app_ids']) ? $_SESSION['tracked_app_ids'] : [];
    $allowed = in_array((int) $doc['application_id'], array_map('intval', $tracked), true);
}

if (!$allowed) {
    http_response_code(403);
    exit('You are not authorised to view this document.');
}

$projectRoot = __DIR__;
$uploadsRoot = realpath($projectRoot . '/uploads');
$full = realpath($projectRoot . '/' . ltrim($doc['file_path'], '/'));

if ($full === false || $uploadsRoot === false || strpos($full, $uploadsRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($full)) {
    http_response_code(404);
    exit('File is missing on disk.');
}

$mimes = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
$ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
$mime = $mimes[$ext] ?? 'application/octet-stream';

$download = isset($_GET['download']);
$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $doc['file_name']);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($full));
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $safeName . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');

readfile($full);

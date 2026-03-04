<?php

header('Content-Type: application/json; charset=utf-8');

function json_error($message, $status = 400) {
  http_response_code($status);
  echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

$folder = isset($_GET['folder']) ? trim((string) $_GET['folder']) : '';
if ($folder === '') {
  json_error('Missing folder parameter.');
}

if (strpos($folder, '/images/galeriak/') !== 0) {
  json_error('Invalid folder path.');
}

$publicRoot = realpath(__DIR__ . '/..');
if ($publicRoot === false) {
  json_error('Public root not found.', 500);
}

$galleryRoot = realpath($publicRoot . $folder);
$allowedRoot = realpath($publicRoot . '/images/galeriak');
if ($galleryRoot === false || $allowedRoot === false || strpos($galleryRoot, $allowedRoot) !== 0) {
  json_error('Gallery folder not found.');
}

$fullPath = $galleryRoot . DIRECTORY_SEPARATOR . 'full';
$thumbPath = $galleryRoot . DIRECTORY_SEPARATOR . 'thumb';
if (!is_dir($fullPath) || !is_dir($thumbPath)) {
  json_error('Missing full or thumb directory.');
}

$files = scandir($fullPath);
if ($files === false) {
  json_error('Failed to read gallery files.', 500);
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'avif'];
$filtered = [];

foreach ($files as $file) {
  $fullFilePath = $fullPath . DIRECTORY_SEPARATOR . $file;
  if (!is_file($fullFilePath)) {
    continue;
  }

  $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
  if (!in_array($extension, $allowedExtensions, true)) {
    continue;
  }

  $filtered[] = $file;
}

natcasesort($filtered);
$sorted = array_values($filtered);
$preview = array_slice($sorted, 0, 9);

$images = [];
foreach ($preview as $index => $file) {
  $src = $folder . '/full/' . $file;
  $thumb = $folder . '/thumb/' . $file;
  $thumbFilePath = $thumbPath . DIRECTORY_SEPARATOR . $file;

  $item = [
    'src' => $src,
    'alt' => 'Galéria kép ' . ($index + 1),
  ];

  if (is_file($thumbFilePath)) {
    $item['thumb'] = $thumb;
  }

  $images[] = $item;
}

echo json_encode(
  [
    'folder' => $folder,
    'total' => count($sorted),
    'images' => $images,
  ],
  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

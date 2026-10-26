<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
date_default_timezone_set('Asia/Jakarta');

$dataFile = __DIR__ . '/../data/sites.json';
$defaultFile = __DIR__ . '/../data/default_sites.json';

// Inisialisasi file sites.json jika belum ada
if (!file_exists($dataFile)) {
    if (file_exists($defaultFile)) {
        copy($defaultFile, $dataFile);
    } else {
        file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT));
    }
}

function getSites($file) {
    $raw = @file_get_contents($file);
    if ($raw !== false) {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function saveSites($file, $sites) {
    return file_put_contents($file, json_encode($sites, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// GET: Tampilkan semua website
if ($method === 'GET' && empty($action)) {
    $sites = getSites($dataFile);
    echo json_encode([
        'success' => true,
        'count' => count($sites),
        'data' => $sites
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Tindakan POST
$input = $_POST;
if (empty($input)) {
    $rawInput = file_get_contents('php://input');
    if ($rawInput) {
        $parsed = json_decode($rawInput, true);
        if (is_array($parsed)) {
            $input = $parsed;
        }
    }
}

$action = $input['action'] ?? $action;

if ($action === 'add') {
    $name = trim($input['name'] ?? '');
    $url = trim($input['url'] ?? '');
    $region = trim($input['region'] ?? 'Jawa Timur');

    if (empty($name) || empty($url)) {
        echo json_encode(['success' => false, 'message' => 'Nama dan URL wajib diisi']);
        exit;
    }

    if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
        $url = 'http://' . $url;
    }

    $sites = getSites($dataFile);
    $maxId = 0;
    foreach ($sites as $s) {
        if (isset($s['id']) && $s['id'] > $maxId) {
            $maxId = $s['id'];
        }
    }

    $newSite = [
        'id' => $maxId + 1,
        'name' => $name,
        'url' => $url,
        'region' => $region
    ];

    $sites[] = $newSite;
    saveSites($dataFile, $sites);

    echo json_encode([
        'success' => true,
        'message' => 'Website berhasil ditambahkan',
        'site' => $newSite
    ]);
    exit;
}

if ($action === 'edit') {
    $id = (int)($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $url = trim($input['url'] ?? '');
    $region = trim($input['region'] ?? '');

    if (!$id || empty($name) || empty($url)) {
        echo json_encode(['success' => false, 'message' => 'ID, Nama, dan URL wajib diisi']);
        exit;
    }

    if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
        $url = 'http://' . $url;
    }

    $sites = getSites($dataFile);
    $found = false;
    foreach ($sites as &$s) {
        if ($s['id'] === $id) {
            $s['name'] = $name;
            $s['url'] = $url;
            if (!empty($region)) {
                $s['region'] = $region;
            }
            $found = true;
            break;
        }
    }

    if ($found) {
        saveSites($dataFile, $sites);
        echo json_encode(['success' => true, 'message' => 'Website berhasil diperbarui']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Website tidak ditemukan']);
    }
    exit;
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID website tidak valid']);
        exit;
    }

    $sites = getSites($dataFile);
    $filtered = array_values(array_filter($sites, function($s) use ($id) {
        return $s['id'] !== $id;
    }));

    saveSites($dataFile, $filtered);
    echo json_encode(['success' => true, 'message' => 'Website berhasil dihapus']);
    exit;
}

if ($action === 'reset') {
    if (file_exists($defaultFile)) {
        copy($defaultFile, $dataFile);
        echo json_encode(['success' => true, 'message' => 'Daftar website berhasil direset ke default awal']);
    } else {
        echo json_encode(['success' => false, 'message' => 'File default_sites.json tidak ditemukan']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);

<?php
// Same-origin save endpoint for the panel (phones + messaged).
// Lives under /panel/ which is HTTP Basic Auth protected, so only logged-in users reach it.
// GET  ?file=messaged|phones  -> returns the stored JSON ({} if none)
// POST {file:"messaged"|"phones", data:{...}} -> overwrites that store
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
$dir = __DIR__ . '/data';
if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
$allowed = ['messaged', 'phones'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $file = isset($_GET['file']) ? $_GET['file'] : '';
  if (!in_array($file, $allowed, true)) { http_response_code(400); echo '{}'; exit; }
  $p = "$dir/$file.json";
  echo (is_file($p) ? file_get_contents($p) : '{}');
  exit;
}

if ($method === 'POST') {
  $body = json_decode(file_get_contents('php://input'), true);
  $file = isset($body['file']) ? $body['file'] : '';
  if (!in_array($file, $allowed, true)) { http_response_code(400); echo '{"ok":false,"error":"bad file"}'; exit; }
  if (!isset($body['data']) || !is_array($body['data'])) { http_response_code(400); echo '{"ok":false,"error":"bad data"}'; exit; }
  // cast to object so an empty store serializes as {} (not []) and round-trips cleanly
  $ok = file_put_contents("$dir/$file.json", json_encode((object)$body['data'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
  echo ($ok !== false ? '{"ok":true}' : '{"ok":false,"error":"write failed"}');
  exit;
}

http_response_code(405);
echo '{"ok":false,"error":"method"}';

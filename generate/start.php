<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$urls = array();
$size = null;
$error = null;
if (isset($_REQUEST['size'])) {
  $size = intval($_REQUEST['size']);
  if ($size < 4 || $size > 4096) {
    $error = "Invalid dictionary target size";
  }
} else {
  $error = "Target dictionary size not specified";
}
if (isset($_REQUEST['urls'])) {
  $urllist = explode("\n", $_REQUEST['urls']);
  foreach ($urllist as $url) {
    $url = trim($url);
    if (strlen($url)) {
      $parsed = parse_url($url);
      if (isset($parsed) &&
          is_array($parsed) &&
          isset($parsed['scheme']) &&
          isset($parsed['host']) &&
          isset($parsed['path'])) {
        $urls[] = $url;
      } else {
        $error = "Invalid URL: " . $url;
      }
    }
  }
}
if (count($urls) < 2 || count($urls) > 100) {
  $error = "There must be between 2 and 100 URLs in the list for testing to work";
}

if (!isset($error)) {
  $id = GenerateTestId();
  if (!is_dir(__DIR__ . '/data/')) {
    mkdir(__DIR__ . '/data/');
  }
  $dir = __DIR__ . '/data/' . $id;
  mkdir($dir);
  $info = array("urls" => $urls, "size" => $size, "ua" => $_SERVER['HTTP_USER_AGENT'], "test" => false);
  if (isset($_REQUEST['ua'])) {
    $info['ua'] = $_REQUEST['ua'];
  }
  if (isset($_REQUEST['test']) && $_REQUEST['test'] === "1") {
    $info['test'] = true;
  }
  file_put_contents("$dir/info.json", json_encode($info));
  // Redirect to the processing page for the test
  $processing = "https://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['PHP_SELF']) . "/process.php?id=$id";
  header("Location: " . $processing);
  die();
} else {
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Shared Brotli Dynamic Dictionary Tester - Error</title>
    <style>
      body {
        font-family: Arial, Helvetica, sans-serif;
      }
    </style>
  </head>
  <body>
    <h1>Error</h1>
    <p>There was an error with the requested test:</p>
    <p>
<?php
echo(htmlspecialchars($error));
?>
    </p>
    <p>Please go back and try again</p>
  </body>
</html>
<?php
}

function GenerateTestId() {
  $id = null;
  while (!isset($id)) {
    $id = bin2hex(random_bytes(20));
    if (is_dir(__DIR__ . '/data/' . $id)) {
      $id = null;
    }
  }
  return $id;
}
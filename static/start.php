<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

$info = array('ua' => $_SERVER['HTTP_USER_AGENT']);
$error = null;
$keys = array('old', 'new');
foreach($keys as $key){
  if (isset($_REQUEST[$key])) {
    $url = trim($_REQUEST[$key]);
    if (strlen($url)) {
      $parsed = parse_url($url);
      if (isset($parsed) &&
          is_array($parsed) &&
          isset($parsed['scheme']) &&
          isset($parsed['host']) &&
          isset($parsed['path'])) {
        $info[$key] = $url;
      }
    }
  }
}

if (!isset($info['old']) || !isset($info['new'])) {
  $error = "Invalid URL provided. Both URLs must be provided.";
}

if (!isset($error)) {
  $id = GenerateTestId();
  $dir = __DIR__ . '/data/' . $id;
  mkdir($dir);
  file_put_contents("$dir/info.json", json_encode($info));
  // Redirect to the processing page for the test
  $processing = "https://use-as-dictionary.com/static/process.php?id=$id";
  header("Location: " . $processing);
  die();
} else {
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Shared Brotli Static Dictionary Tester - Error</title>
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
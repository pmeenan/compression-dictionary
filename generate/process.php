<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$info = array();
if (is_file(__DIR__ . "/data/$id/info.json")) {
  $info = json_decode(file_get_contents(__DIR__ . "/data/$id/info.json"), true);
}
if (isset($info) && is_array($info) && isset($info['done']) && $info['done']) {
  $done = "https://test.patrickmeenan.com/shared-brotli/result.php?id=$id";
  header("Location: " . $done);
  die();
}
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
    <script>
<?php
      echo ("const id='$id';\n");
      echo ('const info=' . json_encode($info) . ';');
?>
    </script>
  </head>
  <body>
    <h1>Processing Test</h1>
    <p>Current Status: <span id="status">Fetching Urls...</span></p>
    <script>
      <?php
      readfile(__DIR__ . '/process.js');
      ?>
    </script>
  </body>
</html>
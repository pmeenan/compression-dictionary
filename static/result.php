<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$info = array();
$dir = __DIR__ . "/data/$id";
chdir($dir);
if (is_file("info.json")) {
  $info = json_decode(file_get_contents("info.json"), true);
}
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Shared Brotli Dynamic Dictionary Tester - Result</title>
    <style>
      body {
        font-family: Arial, Helvetica, sans-serif;
      }
      table {
          border-collapse: collapse;
      }
      th, td {
        text-align: center;
        padding: 8px;
      }
      tr:nth-child(even) {background: #EEE}
      tr:nth-child(odd) {background: #FFF}
    </style>
  </head>
  <body>
  <h1>Brotli Shared Dictionary Compression Results</h1>
    <p>This test tested the effectiveness of using a previous version of a file as a compression dictionary for the new version of the file
      when using brotli compression. One of the use cases for this could be when delivering web app updates, allowing for just the delta
      to be delivered.
    </p>
    <p>Old URL:
      <?php
      echo('<pre>' . htmlspecialchars($info['old']) . '</pre>');
      ?>
    </p>
    <p>New URL:
      <?php
      echo('<pre>' . htmlspecialchars($info['new']) . '</pre>');
      ?>
    </p>
    <p>The test fetched each of the URLs using an anonymous connection with no cookies and the user agent string:</p>
    <?php
    $ua = isset($info['ua']) ? $info['ua'] : 'Not set';
    echo "<p><pre>" . htmlspecialchars($ua) . "</pre></p>";
    ?>
    <p>Then it used the old version of the resource as a dictionary when compressing the new version to see how much smaller an upgrade would be than downloading the whole thing.</p>
    <?php
    if (isset($info['comp']) && is_array($info['comp']) && isset($info['comp']['original']) && isset($info['comp']['br']) && isset($info['comp']['br-d'])) {
      $original = $info['comp']['original'];
      $o = number_format($original);
      if ($original > 0) {
        $br = $info['comp']['br'];
        $sbr = $info['comp']['br-d'];
        $gzip = $info['comp']['gzip'];
        $g = number_format($gzip);
        $br_relative = 100 - intval(round((floatval($br) / floatval($original)) * 100.0));
        $sbr_relative = 100 - intval(round((floatval($sbr) / floatval($original)) * 100.0));
        $br_sbr = intval(round((floatval($sbr) / floatval($br)) * 100.0));
        $br_sbr_r = 100 - $br_sbr;
        $br = number_format($br);
        $sbr = number_format($sbr);
        echo "<p>Uncompressed: <b>$o</b></p>";
        echo "<p>Gzip 9: <b>$g</b></p>";
        echo "<p>Brotli 11: <b>$br</b> ($br_relative% smaller than uncompressed)</p>";
        echo "<p>Brotli 11 with dictionary: <b>$sbr</b> ($sbr_relative% smaller than uncompressed, $br_sbr_r% smaller than Brotli 11 alone)</p>";
      } else {
        echo("<p>Error: zero-length file.</p>\n");
      }
    } else {
      echo("<p>Error: Data Missing.</p>\n");
    }
    ?>
    <p><a href="/static/">Run a new test</a></p>
  </body>
</html>

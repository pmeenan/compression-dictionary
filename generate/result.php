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
    <p>The test fetched each of the URLs using an anonymous connection with no cookies and the requested user agent string:</p>
    <?php
    $ua = isset($info['ua']) ? $info['ua'] : 'Not set';
    echo "<p><pre>" . htmlspecialchars($ua) . "</pre></p>";
    ?>
    <p>Then, for each HTML response, it generated an HTML dictionary using the responses from all of the other URLs (excluding the URL being evaluated) to see how well a dictionary generated from the other pages would compress the page being tested.</p>
    <h1>Summary</h1>
    <p>Brotli supports a compression scale from 1 to 11 and Zstandard uses a scale from 1 to 19.</p>
    <p>Brotli starts using external dictionaries for compression at level 5.
      Below those levels, dictionary-based compression is equivalent to not using dictionaries. The bulk of the savings
      for dictionary-based compression with brotli is already realized at level 5.
    </p>
    <p>Zstandard supports external dictionaries starting at level 1. The bulk of the savings for using dictionary-based
      compression with Zstandard is usually seen by level 3 with incremental gains beyond that.
    </p>
    <?php
    $count = 0;
    $min = null;
    $max = null;
    $total = 0;
    foreach ($info['results'] as $i => $entry) {
      if (isset($entry['comp']) &&
          is_array($entry['comp']) &&
          isset($entry['comp']['original']) &&
          isset($entry['comp']['br']['5']) &&
          isset($entry['comp']['br-d']['5'])) {
        $br = $entry['comp']['br']['5'];
        $sbr = $entry['comp']['br-d']['5'];
        $s = 100 - intval(round((floatval($sbr) / floatval($br)) * 100.0));
        $total += $s;
        $count += 1;
        if (!isset($min) || $s < $min) {
          $min = $s;
        }
        if (!isset($max) || $s > $max) {
          $max = $s;
        }
      }
    }
    if ($count > 0) {
      $avg = intval(round(floatval($total) / floatval($count)));
      echo("<p>Using a custom {$info['size']} KB compression dictionary with brotli for the requested pages resulted in HTML that was $min% to $max% smaller than brotli alone ($avg% average).</p>");
    }
    if (is_file("dictionary.dat")) {
      $size = number_format(filesize("dictionary.dat"));
      $br_size = is_file("dictionary.dat.br") ? number_format(filesize("dictionary.dat.br")) : 0;
      echo "<p>A dictionary was created using all of the provided URLs for future use. You can download it <a href='data/$id/dictionary.dat'>here</a>. It is $size bytes ($br_size compressed with brotli 11).</p>";
    }
    echo '<p><a href="/generate/">Run a new test</a></p>';
    if (!isset($info['test']) || $info['test']) {
      echo "<h1>Details</h1>\n";
      foreach ($info['results'] as $i => $entry) {
        if (isset($entry['comp']) && is_array($entry['comp']) && isset($entry['comp']['original']) && isset($entry['comp']['br']) && isset($entry['comp']['br-d'])) {
          echo "<h2>" . htmlspecialchars($entry['url']) . "</h2>\n";
          $original = $entry['comp']['original'];
          $o = number_format($original);
          $schemes = array(
            'Gzip' => 'gzip',
            'Brotli' => 'br',
            'Zstandard' => 'zstd'
          );
          if (isset($entry['comp']['gzip']['9']) && isset($entry['comp']['zstd-d']['3'])) {
            $gz = $entry['comp']['gzip']['9'];
            $zstd = $entry['comp']['zstd-d']['3'];
            $rel = number_format((floatval($zstd) / floatval($gz)) * 100.0, 0);
            echo "<p>Zstandard level 3 with a dictionary is $rel% the size of gzip 9.</p>";
          }
          if (isset($entry['comp']['gzip']['9']) && isset($entry['comp']['zstd-d']['10'])) {
            $gz = $entry['comp']['gzip']['9'];
            $zstd = $entry['comp']['zstd-d']['10'];
            $rel = number_format((floatval($zstd) / floatval($gz)) * 100.0, 0);
            echo "<p>Zstandard level 10 with a dictionary is $rel% the size of gzip 9.</p>";
          }
          foreach ($schemes as $scheme => $key) {
            echo "<h3>$scheme Compression</h3>\n<table>\n";
            echo "<tr><th>Compression Level</th><th>Original Size</th><th>Compressed</th><th>Relative Size</th><th>With Dictionary</th><th>Relative to Original</th><th>Relative to Compressed</th></tr>\n";
            for ($level = 1; $level <= 19; $level++) {
              if ($original > 0 && isset($entry['comp']["$key"]["$level"])) {
                $comp = $entry['comp']["$key"]["$level"];
                $comp_relative = intval(round((floatval($comp) / floatval($original)) * 100.0));
                $dict = 'N/A';
                $dict_relative = 'N/A';
                $comp_dict = 'N/A';
                $comp_dict_r = 'N/A';
                if (isset($entry['comp']["$key-d"]["$level"])) {
                  $dict = $entry['comp']["$key-d"]["$level"];
                  $dict_relative = intval(round((floatval($dict) / floatval($original)) * 100.0));
                  $comp_dict = intval(round((floatval($dict) / floatval($comp)) * 100.0));
                  $comp_dict_r = 100 - $comp_dict;
                  $dict = number_format($dict);
                }
                $comp = number_format($comp);
                echo("<tr><td>$scheme $level</td><td>$o</td><td>$comp</td><td>$comp_relative%</td><td>$dict</td><td>$dict_relative%</td><td>$comp_dict% ($comp_dict_r% smaller)</td></tr>\n");
              }
            }
            echo "</table>";
          }
        }
      }
    }
    ?>
  </body>
</html>
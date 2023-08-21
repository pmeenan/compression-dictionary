<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Content-Type: application/json");
set_time_limit(600);
$end_time = time() + 1;
$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
$info = array();
$result = array("done" => false);
$error = null;
$status = null;
$dir = __DIR__ . "/data/$id";
if (is_file("$dir/info.json")) {
  $info = json_decode(file_get_contents("$dir/info.json"), true);
  $fp = fopen("$dir/info.json", 'r');
  if ($fp && flock($fp, LOCK_EX)) {
    if (isset($info) && isset($info['urls']) && !isset($info['error'])) {
      if (!isset($info['done'])) {
        if (!isset($info['results'])) {
          $info['results'] = array();
          $count = 0;
          foreach ($info['urls'] as $url) {
            $count++;
            if ($count > 100) { break; }
            $info['results'][] = array('url' => $url);
          }
        }
        FetchUrls();
        CreateDictionary();
        if ($info['test']) {
          CreateTestDictionaries();
          TestCompression();
        }
      }
    } elseif(isset($info['error'])) {
      $error = $info['error'];
    } else {
      $error = "Invalid test";
    }

    if (isset($error)) {
      $result['error'] = $error;
      $result['done'] = true;
      $info['done'] = true;
    } elseif (isset($status)) {
      $result['status'] = $status;
    } else {
      $result['done'] = true;
      $info['done'] = true;
    }

    fclose($fp);

    if (isset($info)) {
      file_put_contents("$dir/info.json", json_encode($info));
    }
  } else {
    $result['status'] = 'Busy...';
  }
} else {
  $result['error'] = "Test not found";
}

echo(json_encode($result));

function FetchUrls() {
  global $status;
  global $error;
  global $info;
  global $dir;
  global $end_time;
  if (isset($status) || isset($error)) { return; }
  foreach ($info['results'] as $i => $entry) {
    if (!isset($entry['body'])) {
      $file = "$dir/$i-body.dat";
      if (!is_file($file)) {
        $url = $entry['url'];
        $fp = fopen($file, 'w+');
        if ($fp) {
          $ch = curl_init($url);
          $ua = isset($info['ua']) ? $info['ua'] : $_SERVER['HTTP_USER_AGENT'];
          $headers = array(
            "User-Agent: $ua"
          );
          curl_setopt($ch, CURLOPT_TIMEOUT, 600);
          curl_setopt($ch, CURLOPT_FAILONERROR, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
          curl_setopt($ch, CURLOPT_ENCODING , '');
          curl_setopt($ch, CURLOPT_FILE, $fp);
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
          if (curl_exec($ch) === false) {
            $error = "Error fetching $url";
          }
          curl_close($ch);
          fclose($fp);
        }
      }
      $info['results'][$i]['body'] = $file;
      $count = count($info['results']);
      $index = $i + 1;
      $status = "Downloaded $index of $count URLs...";
      if (time() > $end_time) { return; }
    }
  }
}

function CreateDictionary() {
  global $status;
  global $error;
  global $info;
  global $dir;
  $size = null;
  if (isset($info['size'])) {
    $size = intval($info['size']) * 1024;
  } else {
    $error = "Invalid size";
  }
  if (isset($status) || isset($error)) { return; }
  chdir($dir);
  // see if an overall dictionary needs to be created
  if (!is_file('dictionary.dat')) {
    $command = "dictionary_generator --target_dict_len=$size dictionary.dat *-body.dat";
    $output = null;
    $result = null;
    if (exec($command, $output, $result) === false || $result !== 0) {
      $error = "Error creating dictionary";
    }
    // Brotli-compress it
    exec("brotli -q 11 -o dictionary.dat.br dictionary.dat", $output, $result);
    $status = "Generated dictionary from all URLs...";
    return;
  }
}

function CreateTestDictionaries() {
  global $status;
  global $error;
  global $info;
  global $dir;
  global $end_time;
  $size = null;
  if (isset($info['size'])) {
    $size = intval($info['size']) * 1024;
  } else {
    $error = "Invalid size";
  }
  if (isset($status) || isset($error)) { return; }
  chdir($dir);
  // Generate the individual dictionaries
  foreach ($info['results'] as $i => $entry) {
    if ($i < 20 && !isset($entry['dict'])) {
      $dict = "$i-dict.dat";
      if (!is_file($dict)) {
        $body = "$i-body.dat";
        // Rename the body that we are using so it isn't included in the dictionary
        rename($body, "$body.tmp");
        $command = "dictionary_generator --target_dict_len=$size $dict *-body.dat";
        $output = null;
        $result = null;
        if (exec($command, $output, $result) === false || $result !== 0) {
          $error = "Error creating dictionary";
        }
        rename("$body.tmp", $body);
      }
      $info['results'][$i]['dict'] = $dict;
      $count = count($info['results']);
      $index = $i + 1;
      $status = "Created $index of $count dictionaries for testing effectiveness...";
      if (time() > $end_time) { return; }
    }
  }
}

function TestCompression() {
  global $status;
  global $error;
  global $info;
  global $dir;
  global $end_time;
  if (isset($status) || isset($error)) { return; }
  foreach ($info['results'] as $i => $entry) {
    if ($i < 20 && !isset($entry['comp'])) {
      chdir($dir);
      $body = "$i-body.dat";
      $dict = "$i-dict.dat";
      if (is_file($body) && is_file($dict)) {
        $comp = array(
          'original' => filesize($body),
          'br' => array(),
          'br-d' => array(),
          'zstd' => array(),
          'zstd-d' => array()
        );
        $levels = array(1, 5, 11);
        foreach ($levels as $level) {
          $output = null;
          $result = null;
          $tmp = "tmp.dat";
          if (is_file($tmp)) {unlink($tmp);}
          if (exec("brotli -q $level -o $tmp $body", $output, $result) === false || $result !== 0) {
            $error = "Error brotli compressing body";
            break 2;
          }
          $comp['br']["$level"] = filesize($tmp);
          if (is_file($tmp)) {unlink($tmp);}
          if (exec("brotli -q $level -D $dict -o $tmp $body", $output, $result) === false || $result !== 0) {
            $error = "Error brotli dictionary compressing body";
            break 2;
          }
          $comp['br-d']["$level"] = filesize($tmp);
          if (is_file($tmp)) {unlink($tmp);}
        }
        $levels = array(1, 2, 3, 10, 19);
        foreach ($levels as $level) {
          $output = null;
          $result = null;
          $tmp = "tmp.dat";
          if (is_file($tmp)) {unlink($tmp);}
          if (exec("zstd -$level $body -o $tmp", $output, $result) === false || $result !== 0) {
            $error = "Error Zstandard compressing body";
            break 2;
          }
          $comp['zstd']["$level"] = filesize($tmp);
          if (is_file($tmp)) {unlink($tmp);}
          if (exec("zstd -$level -D $dict $body -o $tmp", $output, $result) === false || $result !== 0) {
            $error = "Error Zstandard dictionary compressing body";
            break 2;
          }
          $comp['zstd-d']["$level"] = filesize($tmp);
          if (is_file($tmp)) {unlink($tmp);}
        }
        $info['results'][$i]['comp'] = $comp;
        $count = count($info['results']);
        $index = $i + 1;
        $status = "Tested dictionary compression effectiveness on $index of $count pages...";
      } else {
        $error = "Missing body or dictionary";
      }
      if (time() > $end_time) { return; }
    }
  }
}

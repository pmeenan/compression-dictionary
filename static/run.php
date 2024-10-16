<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Content-Type: application/json");
set_time_limit(600);

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
    if (isset($info) && isset($info['old']) && isset($info['new']) && !isset($info['error'])) {
      if (!isset($info['done'])) {
        FetchUrls();
        TestCompression();
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
  global $id;
  global $dir;
  if (isset($status) || isset($error)) { return; }
  $keys = array('old', 'new');
  foreach($keys as $key){
    $file = "$dir/$key.dat";
    if (!is_file($file)) {
      $url = $info[$key];
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
      $status = "Downloaded $key version...";
      break;
    }
  }
}

function TestCompression() {
  global $status;
  global $error;
  global $info;
  global $id;
  global $dir;
  if (isset($status) || isset($error)) { return; }
  if (!isset($info['comp'])) {
    chdir($dir);
    $body = "new.dat";
    $dict = "old.dat";
    if (is_file($body) && is_file($dict)) {
      $comp = array(
        'original' => filesize($body)
      );
      $output = null;
      $result = null;
      $levels = array(1, 3, 5, 7, 11);
      $tmp = "tmp.dat";
      foreach ($levels as $level) {
        if (is_file($tmp)) {unlink($tmp);}
        if (exec("brotli -q $level -o $tmp $body", $output, $result) === false || $result !== 0) {
          $error = "Error compressing body";
        }
        $comp['br'][strval($level)] = filesize($tmp);
        if (is_file($tmp)) {unlink($tmp);}
        if (exec("brotli -q $level -D $dict -o $tmp $body", $output, $result) === false || $result !== 0) {
          $error = "Error dictionary-compressing body";
        }
        $comp['br-d'][strval($level)] = filesize($tmp);
      }
      $levels = array(1, 2, 3, 5, 7, 10, 19, 22);
      foreach ($levels as $level) {
        if (is_file($tmp)) {unlink($tmp);}
        if (exec("zstd --ultra -$level --long $body -o $tmp", $output, $result) === false || $result !== 0) {
          $error = "Error compressing body";
        }
        $comp['zstd'][strval($level)] = filesize($tmp);
        if (is_file($tmp)) {unlink($tmp);}
        if (exec("zstd --ultra -$level --long -D $dict $body -o $tmp", $output, $result) === false || $result !== 0) {
          $error = "Error dictionary-compressing body";
        }
        $comp['zstd-d'][strval($level)] = filesize($tmp);
      }
      if (is_file($tmp)) {unlink($tmp);}
      if (exec("gzip -k -9 -c $body > $tmp", $output, $result) === false || $result !== 0) {
        $error = "Error dictionary-compressing body";
      }
      $comp['gzip'] = filesize($tmp);
      if (is_file($tmp)) {unlink($tmp);}
      $info['comp'] = $comp;
      $status = "Tested compression...";
    } else {
      $error = "Missing body or dictionary";
    }
  }
}

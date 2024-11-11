<!DOCTYPE html>
<html>
  <head>
    <title>Compression Dictionary Transport Dynamic Dictionary Tester</title>
    <style>
      body {
        font-family: Arial, Helvetica, sans-serif;
      }
    </style>
  </head>
  <body>
    <h1>Compression Dictionary Transport Dynamic Dictionary Tester</h1>
    <p>This will generate a custom dictionary for <a href="https://github.com/google/brotli">brotli</a> and
      <a href="https://github.com/facebook/zstd">Zstandard</a> compression (primarily for use with
      <a href="https://github.com/WICG/compression-dictionary-transport">Compression Dictionary Transport</a> HTTP compression).</p>
    <p>Given a list of (up to 100) URLs it will download the contents of the URLs and use Brotli's
      <a href="https://github.com/google/brotli/blob/master/research/dictionary_generator.cc">dictionary_generator</a>
      to generate a "raw" dictionary from the contents of the files. The raw dictionary is suitable for use
      with either Brotli or Zstandard compression.
    </p>
    <p>It will also (optionally) test the effectiveness of dictionary-based compression for up to 20 of the URLs that you supplied. It does this by generating
      a new dictionary for each URL using only the other URLs (generating separate dictionaries for every page tested).</p>
    <p>The URLs to be tested will be fetched anonymously (no cookies) using the User Agent string specified so they must be publicly available.</p>
    <p>Please provide full URLs including the scheme (i.e. https://www.example.com/page1)</p>
    <form action="start.php" method="post">
      <p>
        <label for="urls">List of URLs to use (one per line, 100 max):</label><br>
        <textarea id="urls" name="urls" rows="12" cols="160"></textarea>
      </p>
      <p>
        <input type="checkbox" id="test" name="test" value="1" checked>
        <label for="test"> Test compression effectiveness after generating dictionary (will test only the first 20 URLs)</label><br>
      </p>
      <p>
        <input type="checkbox" id="slow" name="slow" value="1">
        <label for="slow"> Rate-limit the fetches to one every sew seconds to reduce the risk of being blocked.</label><br>
      </p>
      <p>
        <label for="size">Dictionary Size (in KB):</label>
        <input type="number" id="size" name="size" min="4" max="4096" value="1024">
      </p>
      <p>
        <label for="ua">User Agent String:</label>
        <?php
        $ua = htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
        echo "<input type='text' id='ua' name='ua' size='160' value='$ua'>\n";
        ?>
      </p>
      <input type="submit" value="Submit">
    </form>
  </body>
</html>

<!DOCTYPE html>
<html>
  <head>
    <title>Shared Brotli Static Dictionary Tester</title>
    <style>
      body {
        font-family: Arial, Helvetica, sans-serif;
      }
    </style>
  </head>
  <body>
    <h1>Shared Brotli Static Dictionary Tester</h1>
    <p>This will evaluate the effectiveness of using a previous version of a file as a dictionary for <a href="https://github.com/google/brotli">brotli compression</a>.</p>
    <p>Given 2 URLs, one for the previous version of a file to use as a dictionary and one for the new version of a file, it will compress the new
      file with brotli compression using the previous version as an external dictionary (brotli -D) and compare the results
      to compressing the new file with brotli alone.
    </p>
    <p>There is a version of this test for use with dynamic content (i.e. HTML) <a href="/">here</a>.</p>
    <p>The URLs to be tested will be fetched anonymously (no cookies) using the User Agent string of your current browser so they must be publicly available.</p>
    <p>Please provide full URLs including the scheme (i.e. https://www.example.com/bundle.js?v=1)</p>
    <p>If you do not have access to both versions of the file, the Internet Archive's <a href="https://archive.org/web/">Wayback machine</a> can be useful for
    accessing previous versions (open a previous version of the page with dev tools open and observe the URLs requested).</p>
    <form action="start.php" method="post">
      <p>
        <label for="old">Previous version URL:</label><br>
        <input type="text" id="old" name="old" size="160">
      </p>
      <p>
        <label for="new">New version URL:</label><br>
        <input type="text" id="new" name="new" size="160">
      </p>
      <input type="submit" value="Submit">
    </form>
  </body>
</html>

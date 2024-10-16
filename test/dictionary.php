<?php
header("Cache-Control: public, max-age=31536000");
header('Use-As-Dictionary: match="/*"');
header('Content-Type: text/plain; charset=UTF-8');
readfile(__DIR__ . '/dictionary.txt');

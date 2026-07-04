#!/bin/bash
set -e

# echo "Waiting for DB..."
# until php -r '
#   $c = new mysqli(getenv("DB_HOST"), getenv("DB_USER"), getenv("DB_PASS"));
#   exit($c->connect_error ? 1 : 0);
# ' 2>/dev/null; do
#   echo "DB not ready, retrying..."
#   sleep 3
# done

 echo "Running init.sql..."
 php -r '
   $conn = new mysqli(getenv("DB_HOST"), getenv("DB_USER"), getenv("DB_PASS"), getenv("DB_NAME"));
   if ($conn->connect_error) { die("Connect failed: " . $conn->connect_error . "\n"); }
   $sql = file_get_contents("/var/www/html/init.sql");
   $sql = preg_replace("/CREATE DATABASE.*?;\n/i", "", $sql);
   $sql = preg_replace("/USE.*?;\n/i", "", $sql);
   foreach (array_filter(array_map("trim", explode(";", $sql))) as $stmt) {
     if (!$conn->query($stmt)) echo "WARN: " . $conn->error . "\n";
   }
   echo "Init complete.\n";
 '

echo "Starting Apache..."
exec apache2 -D FOREGROUND
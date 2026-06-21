<?php
require_once __DIR__ . "/config/db.php";

$sql = file_get_contents(__DIR__ . "/init.sql");

if ($conn->multi_query($sql)) {
    echo "init.sql executed successfully";
} else {
    echo "Error: " . $conn->error;
}
?>
EOF 

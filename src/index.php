<?php
include "cors.php";

cors();
header("Content-Type: text/plain");

echo "
POST /api/user
GET /api/item
POST /api/item
DELETE /api/item
";
?>

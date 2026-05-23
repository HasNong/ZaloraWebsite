<?php
require 'config/db.php';
$categories = $database->getReference('category')->getSnapshot()->getValue();
print_r($categories);

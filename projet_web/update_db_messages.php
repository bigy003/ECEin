<?php
require 'config/config.php';
try {
    $pdo = getDB();
    $pdo->exec("ALTER TABLE messages ADD COLUMN fichier_joint VARCHAR(255) NULL AFTER contenu;");
    echo "Column 'fichier_joint' added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
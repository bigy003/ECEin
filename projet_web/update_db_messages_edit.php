<?php
require 'config/config.php';
try {
    $pdo = getDB();
    $pdo->exec("ALTER TABLE messages ADD COLUMN est_supprime TINYINT(1) DEFAULT 0 AFTER fichier_joint;");
    $pdo->exec("ALTER TABLE messages ADD COLUMN est_modifie TINYINT(1) DEFAULT 0 AFTER est_supprime;");
    echo "Columns added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
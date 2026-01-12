<?php
/**
 * Configuration et connexion PostgreSQL
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// CONFIGURATION - MODIFIEZ ICI
$host = 'localhost';
$port = '5432';
$dbname = 'LogiPlus';
$user = 'postgres';
$password = '....'; // ⬅️ CHANGEZ ICI

// CONNEXION
try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET search_path TO logistix, public");
} catch(PDOException $e) {
    die("
    <div style='background:#fee;padding:40px;border-left:4px solid red;margin:20px;border-radius:8px'>
        <h2 style='color:red'>❌ Erreur de connexion PostgreSQL</h2>
        <p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <hr style='margin:20px 0'>
        <p><strong>Vérifications:</strong></p>
        <ul>
            <li>PostgreSQL est démarré</li>
            <li>Le mot de passe est correct (ligne 13)</li>
            <li>La base 'logistix' existe</li>
        </ul>
    </div>");
}

/**
 * Fonction helper pour exécuter des requêtes de manière sécurisée
 */
function executeQuery($pdo, $sql) {
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Erreur SQL: " . $e->getMessage());
        return [];
    }
}
?>
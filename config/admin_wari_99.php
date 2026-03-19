<?php
// admin_wari_99.php
require 'db.php';
// Ajoute ici une protection par mot de passe simple si tu veux

if (isset($_POST['new_id'])) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO wari_licences (commande_id) VALUES (?)");
    $stmt->execute([$_POST['new_id']]);
    echo "<p style='color:green;'>Numéro " . htmlspecialchars($_POST['new_id']) . " ajouté avec succès !</p>";
}
?>

<form method="POST">
    <h3>Ajouter un numéro de commande autorisé</h3>
    <input type="text" name="new_id" placeholder="Ex: TXN12345678" required>
    <button type="submit">Autoriser ce numéro</button>
</form>

<hr>
<h3>Dernières licences ajoutées :</h3>
<ul>
    <?php
    $stmt = $pdo->query("SELECT * FROM wari_licences ORDER BY date_creation DESC LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "<li>" . $row['commande_id'] . " - <strong>" . $row['statut'] . "</strong></li>";
    }
    ?>
</ul>
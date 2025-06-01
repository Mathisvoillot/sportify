<?php
$plain_password = 'MonMotDePasseAdmin'; 
$hash = password_hash($plain_password, PASSWORD_DEFAULT);
echo '<pre>Mot de passe en clair : ' . htmlspecialchars($plain_password) . "\n";
echo 'Hash généré         : ' . htmlspecialchars($hash) . "</pre>";

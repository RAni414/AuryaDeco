<?php
// Exemple de configuration. Normalement, install.php crée config.php pour vous.
// Pour le faire à la main : copiez ce fichier sous le nom config.php et remplissez-le.
return [
    'db_host'  => 'localhost',   // serveur MySQL (souvent localhost)
    'db_port'  => 3306,
    'db_name'  => 'auryadeco',   // nom de la base
    'db_user'  => 'root',        // utilisateur MySQL
    'db_pass'  => '',            // mot de passe MySQL
    'base_url' => '',            // laisser vide (détection automatique)
    'timezone' => 'Africa/Tunis',
    'debug'    => false,         // true pour afficher les erreurs PHP (jamais en ligne)
];

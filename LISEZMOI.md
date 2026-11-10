# Boutique AuryaDeco : installation

Site de vente en ligne en PHP et MySQL, sans WordPress :

- **Boutique** : accueil avec choix par hauteur, catalogue avec filtres, fiches produits avec tailles, panier, commande avec paiement à la livraison et gouvernorats tunisiens.
- **Administration** (`/admin`) : tableau de bord, commandes avec statuts, produits avec photos, tailles, prix, promos, stock et frais de livraison, avis clients, réglages de la boutique, comptes administrateurs.
- **Avis clients** : vous ajoutez vous-même chaque avis (nom, note, commentaire, photo) et il s'affiche sur la page d'accueil.
- **Livraison par produit** : chaque produit a son propre tarif de livraison.

Il faut un serveur avec **PHP 7.4 ou plus récent** (8.x conseillé) et **MySQL 5.7+ ou MariaDB 10.3+**. Tous les hébergements web classiques conviennent.

---

## 1. Tester sur votre PC (Windows)

1. Installez **Laragon** (gratuit, le plus simple) depuis laragon.org, ou **XAMPP** depuis apachefriends.org.
2. Décompressez le dossier `auryadeco` :
   - Laragon : dans `C:\laragon\www\`
   - XAMPP : dans `C:\xampp\htdocs\`
3. Démarrez les services :
   - Laragon : bouton **Démarrer tout**.
   - XAMPP : ouvrez le panneau XAMPP et cliquez **Start** devant **Apache** et **MySQL**.
4. Ouvrez votre navigateur à l'adresse **http://localhost/auryadeco/**. L'assistant d'installation s'affiche.
5. Remplissez le formulaire :
   - Serveur : `localhost`, port `3306`
   - Nom de la base : `auryadeco` (elle est créée automatiquement)
   - Utilisateur : `root`, mot de passe : vide
   - Choisissez votre identifiant et votre mot de passe administrateur.
6. Cliquez **Installer la boutique**. C'est prêt :
   - Boutique : http://localhost/auryadeco/
   - Administration : http://localhost/auryadeco/admin/

Sur votre PC, vous pouvez garder `install.php`. En ligne, il faudra le supprimer.

---

## 2. Mettre en ligne chez votre hébergeur

Les noms des menus varient selon l'hébergeur (cPanel, Plesk, panneau maison), mais les étapes sont les mêmes.

### a. Créer la base de données
Dans le panneau de l'hébergeur, rubrique **Bases de données MySQL** :
1. Créez une base (ex. `monsite_auryadeco`).
2. Créez un utilisateur avec un mot de passe solide.
3. Donnez à cet utilisateur **tous les privilèges** sur la base.
Notez les quatre informations : serveur (souvent `localhost`), nom de la base, utilisateur, mot de passe.

### b. Envoyer les fichiers
1. Ouvrez le **Gestionnaire de fichiers** du panneau (ou un logiciel FTP comme FileZilla).
2. Allez dans le dossier du site : souvent `public_html` (ou `www`, `httpdocs`).
3. Envoyez le **contenu** du dossier `auryadeco` directement dans `public_html` : le fichier `index.php` doit se trouver dans `public_html`, pas dans un sous-dossier. Astuce : envoyez le fichier zip puis utilisez **Extraire** dans le gestionnaire de fichiers.
4. Vérifiez que le dossier `uploads` est inscriptible (droits **755**, ou **775** si l'envoi de photos échoue).

### c. Lancer l'installation
1. Ouvrez **https://votre-domaine.tn/install.php** (ou l'adresse temporaire donnée par l'hébergeur).
2. Entrez les informations de la base créée à l'étape a, puis votre identifiant et mot de passe administrateur.
3. **Supprimez ensuite `install.php`** du serveur. C'est important pour la sécurité.

---

## 3. Relier votre nom de domaine

Votre domaine doit « pointer » vers le serveur de l'hébergeur. Deux méthodes, à faire chez le bureau d'enregistrement où vous avez réservé le nom :

- **Serveurs DNS (le plus simple)** : remplacez les serveurs DNS du domaine par ceux que vous donne l'hébergeur (ex. `ns1.hebergeur.com`, `ns2.hebergeur.com`).
- **Enregistrement A** : gardez vos DNS et créez un enregistrement **A** pour `@` et un pour `www`, avec l'**adresse IP** du serveur fournie par l'hébergeur.

Ensuite, dans le panneau de l'hébergeur, **ajoutez le domaine** à votre hébergement s'il n'y est pas déjà (rubrique Domaines). La propagation prend de quelques minutes à 48 heures.

### Activer le HTTPS (cadenas)
1. Dans le panneau, activez le **certificat SSL gratuit** (Let's Encrypt, AutoSSL ou « SSL/TLS »).
2. Une fois que https://votre-domaine.tn fonctionne, ouvrez le fichier `.htaccess` à la racine et retirez les `#` devant les 3 lignes sous « Forcer HTTPS ».

Le fichier `.htaccess` est caché sur certains gestionnaires de fichiers : activez « Afficher les fichiers cachés ».

---

## 4. Premiers réglages dans l'administration

Connectez-vous sur **/admin** puis :

1. **Réglages** : nom de la boutique, numéro WhatsApp (chiffres seulement, ex. `21620123456`), téléphone, e-mail, adresse, frais de livraison et seuil de livraison offerte. Indiquez aussi l'e-mail qui reçoit les nouvelles commandes.
2. **Produits** : modifiez ou supprimez les produits d'exemple, ajoutez les vôtres avec leurs photos. Le stock est facultatif : laissez-le vide pour ne pas le suivre.
3. **Commandes** : chaque commande arrive avec le statut « Nouvelle ». Appelez ou écrivez au client (bouton WhatsApp), puis passez-la en « Confirmée », « Expédiée » et « Livrée ». « Annulée » remet les articles en stock.

---

## 5. Sauvegardes

Faites une sauvegarde au moins une fois par semaine :
- **La base** : dans phpMyAdmin, sélectionnez la base puis **Exporter** (format SQL).
- **Les photos** : téléchargez le dossier `uploads`.

Beaucoup d'hébergeurs proposent aussi des sauvegardes automatiques : activez-les.

---

## 6. Avis clients

Dans l'administration, menu **Avis clients** :
1. Cliquez sur **Ajouter un avis**.
2. Renseignez le nom du client, sa ville (facultatif), la note de 1 à 5, son commentaire, et une photo (facultatif : le client avec sa plante, ou une capture de son message).
3. Cochez « Afficher sur la page d'accueil » puis enregistrez.

Les avis apparaissent dans un **carrousel** « Ce que disent nos clients » sur la page d'accueil (9 maximum, dans l'ordre de la liste). Les visiteurs le font défiler avec les flèches, les points, le glisser du doigt ou de la souris, ou les touches du clavier ; il avance aussi tout seul jusqu'à ce qu'on le touche. Un avis avec photo s'affiche en carte blanche avec sa photo, un avis sans photo en carte verte citation. Les flèches de la liste changent l'ordre, « Visible / Masqué » cache un avis sans le supprimer, et un clic sur la photo l'agrandit pour les visiteurs. Tant qu'aucun avis n'est visible, la section n'apparaît pas.

N'ajoutez que de vrais avis de vos clients.

## 7. Frais de livraison par produit

Dans la fiche d'un produit (**Produits → Modifier**), rubrique **Livraison** :
- **Un montant** (ex. 25) : ce produit coûte 25 TND de livraison, par article.
- **0** : livraison offerte sur ce produit.
- **Vide** : le tarif par défaut des Réglages s'applique.

Dans le panier, les frais de chaque article sont **additionnés** (frais du produit x quantité). Si le sous-total atteint le seuil de « livraison offerte à partir de » (Réglages), la livraison est gratuite. Mettez ce seuil à 0 pour le désactiver. Le client voit le tarif sur chaque fiche produit, dans le panier et dans le récapitulatif de commande.

---

## 8. Mettre à jour une boutique déjà installée

Si vous avez déjà installé une version précédente :
1. **Ne supprimez pas** le dossier du site.
2. Décompressez la nouvelle archive au même endroit (par exemple `C:\xampp\htdocs`) et choisissez **Remplacer les fichiers dans la destination**.
3. Votre `config.php`, vos photos (`uploads`), vos produits et vos commandes sont conservés : l'archive ne contient aucun de ces éléments.
4. Ouvrez simplement le site : la base de données se met à jour toute seule au premier chargement (nouvelle colonne de livraison et table des avis).

---

## Problèmes fréquents

- **« Connexion à la base de données impossible »** : vérifiez `config.php` (serveur, nom de la base, utilisateur, mot de passe) et que l'utilisateur a bien les droits sur la base.
- **Page blanche ou erreur 500** : mettez temporairement `'debug' => true` dans `config.php` pour voir l'erreur, puis remettez `false`.
- **Les photos ne s'envoient pas** : donnez les droits 775 au dossier `uploads`. Pour les photos lourdes, l'hébergeur doit accepter au moins 10 Mo par envoi (`upload_max_filesize`).
- **Les e-mails de commande n'arrivent pas** : certains hébergeurs bloquent la fonction `mail()`. Les commandes restent visibles dans l'administration.
- **Mot de passe administrateur oublié** : supprimez `config.php`, relancez `install.php` avec les mêmes informations de base et le même identifiant : le mot de passe est remplacé, les produits et commandes sont conservés.

---

## Organisation des fichiers

```
auryadeco/
├── index.php          accueil
├── boutique.php       catalogue et recherche
├── produit.php        fiche produit
├── panier.php         panier
├── commande.php       formulaire de commande
├── merci.php          confirmation
├── page.php           contact, livraison, professionnels
├── install.php        assistant d'installation (à supprimer en ligne)
├── config.php         créé à l'installation (accès à la base)
├── admin/             administration
├── includes/          code commun, structure de la base (schema.sql)
├── assets/            styles, scripts, illustrations
└── uploads/           photos des produits
```

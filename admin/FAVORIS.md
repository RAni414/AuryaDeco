# Mes favoris — AuryaDeco

La fonctionnalité est accessible sur toutes les pages publiques par le cœur
avec compteur dans l’en-tête, y compris sur mobile :

`http://127.0.0.1:8081/includes/favoris.php`

Le point d’entrée public est placé dans le dossier PHP existant `includes`,
accessible sur ce projet comme les routes JSON de l’assistant. Le calcul des
liens est adapté pour conserver les URL publiques correctes en racine et en
sous-dossier, avec ou sans `base_url` configuré.

## Fonctionnement

- Cœur sur les cartes du catalogue, les suggestions, la fiche produit et les
  cartes produit de l’assistant déco. Bouton séparé des liens, sans navigation
  ni rechargement. `aria-pressed`, libellé Ajouter/Retirer et confirmation discrète.
- Page dédiée : mêmes cartes et médias que la boutique, prix et disponibilité
  relus depuis MySQL. Un produit actif en rupture reste visible et retirable.
  Un produit supprimé ou désactivé est retiré après validation, avec information.
- Ajout au panier : formulaire existant `panier.php`, token CSRF, sélection de
  variante obligatoire si plusieurs existent et quantité explicite. La logique
  serveur du panier contrôle toujours la variante, le stock et le prix actuel.
- Liste vide : « Vous n’avez pas encore de favoris. » et Découvrir la boutique.
- Cœurs et compteur synchronisés après chargement, navigation, retour à une page
  en cache et événement `storage` entre onglets de la même origine.

## Conservation et comptes

La clé `auryadeco:favorites:v1` du localStorage contient uniquement un tableau
d’identifiants numériques. Limite technique : 200 produits. Aucun prix, photo,
token de session ou détail personnel n’est conservé dans cette liste.

Les favoris sont propres à ce navigateur et à l’origine du site. `localhost`
et `127.0.0.1`, des ports différents ou des navigateurs différents ont chacun
leur propre stockage. Aucune synchronisation entre appareils.

Le code et la base existants ne possèdent pas de comptes clients : seuls les
administrateurs peuvent se connecter. Les administrateurs ne sont pas traités
comme des clients. Aucun système de comptes, table de favoris ou migration SQL
n’a été ajouté ; fusion à la connexion et favoris privés de compte ne s’appliquent
donc pas à cette installation.

JSON invalide, doublons et identifiants hors limites sont nettoyés. Si le stockage
est bloqué, le cœur reste utilisable en mémoire uniquement pendant la page en
cours, avec un avertissement explicite. Ces favoris temporaires sont perdus à
la navigation ; l’interface ne prétend pas les avoir enregistrés durablement.
En cas d’erreur réseau, la liste locale est conservée et le client peut réessayer.

## Sécurité

L’API accepte uniquement POST JSON, limité à 8 Ko et 200 identifiants entiers
positifs. Le token CSRF est vérifié dans la session. Les identifiants sont
validés côté serveur et les requêtes du catalogue sont préparées. Aucun prix
du navigateur n’est utilisé. Les textes du catalogue sont échappés avant de
générer les cartes HTML. Les réponses JSON sont `no-store`.

## Lancement Windows / XAMPP

MySQL démarré et `config.php` existant configuré, ouvrir PowerShell dans `admin` :

```powershell
.\start-local.ps1
```

Ouvrir `http://127.0.0.1:8081/`, puis Ctrl+F5. Aucun service IA ni clé API n’est
nécessaire pour les favoris. Si Apache/XAMPP sert déjà le projet, utiliser son URL
habituelle : le lien de l’en-tête pointe vers le bon dossier automatiquement.

## Tests

Vérifications PHP de validation et d’affichage, sans écriture commerciale :

```powershell
C:\xampp\php\php.exe -n -d extension_dir=C:\xampp\php\ext -d extension=mbstring tests/favorites_service.php
python tests/favorites_http.py
node tests/favorites_client.mjs
```

Les tests couvrent l’ajout, le retrait, les libellés accessibles, le compteur,
le rechargement, la navigation, le stockage limité aux identifiants, les onglets,
la liste vide, les données invalides, le stockage indisponible, les erreurs
serveur, le CSRF, les produits supprimés/indisponibles et le choix de variante.
Le test JavaScript exécute le vrai script avec des adaptateurs DOM/stockage/réseau
contrôlés ; il ne remplace pas une vérification visuelle dans Chrome.

Test dans le Chrome installé, sans dépendance Node supplémentaire, avec un profil
temporaire séparé et sans interaction avec les données de votre navigateur :

```powershell
node tests/favorites_browser.mjs
```

Ce parcours teste aussi l’ajout réel dans un panier de session de test et produit
des captures 390, 768 et 1440 px dans `admin/verification-favoris`, plus un rapport.
Son exécution depuis cette session a été refusée par la fenêtre d’autorisation :
les captures réelles et la vérification visuelle restent à effectuer depuis votre
terminal. Les contrôles PHP/HTTP/JavaScript simulés ont passé.

## Fichiers

Modifiés :
- `includes/helpers.php` : boutons, cartes réutilisables et base URL des routes publiques.
- `includes/header.php` : accès et compteur ; chargement des styles.
- `includes/footer.php` : script, configuration CSRF et confirmation accessible.
- `assets/js/deco.js` : identifiant produit des cartes de l’assistant.

Ajoutés :
- `includes/favoris.php`, `includes/favorites-api.php`, `includes/favorites-service.php`.
- `assets/js/favorites.js`, `assets/css/favorites.css`.
- `admin/tests/favorites_service.php`, `favorites_http.py`, `favorites_client.mjs`,
  `favorites_browser.mjs`, `browser-harness.mjs`.
- `admin/FAVORIS.md`.

Aucune migration SQL. Aucun produit, prix, stock ou commande modifié.

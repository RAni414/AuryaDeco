# Cartes produits et galerie

Les cartes communes de l’accueil, de la boutique et des favoris affichent les photos de `product_images` dans l’ordre `position, id`. La première est visible par défaut ; la deuxième apparaît au survol. Une photo unique reste affichée. Sans photo, l’illustration existante est conservée.

Dans Administration → Produits → Modifier → Photos, ajoutez plusieurs fichiers, choisissez la photo principale et renseignez l’ordre des autres photos. La photo principale garde la priorité ; la suivante sera celle du survol. Les nouvelles photos sont ajoutées à la fin : enregistrez puis ajustez leur ordre si nécessaire. Aucune migration SQL n’est nécessaire et aucune photo existante n’est remplacée par cette modification.

La barre d’actions utilise le bouton favoris existant. Le panier ouvre l’aperçu pour confirmer l’ajout ; avec plusieurs variantes, une sélection explicite est obligatoire. Le formulaire poste vers `panier.php` avec le jeton CSRF existant : le serveur vérifie toujours le stock et les données actuelles. L’aperçu montre les prix actuels au chargement de la page.

Le dialogue natif gère Échap, la modalité et le retour du focus. Ses contrôles restent accessibles au clavier. Les photos peuvent être parcourues avec les flèches de l’aperçu ou les indicateurs des cartes. Sur écran tactile, un balayage horizontal change la photo ; le défilement vertical et le pincement restent autorisés. Les animations sont supprimées avec la préférence de réduction des mouvements.

## Fichiers de production

- `includes/helpers.php` : cartes, galerie et données de l’aperçu.
- `includes/header.php` et `includes/footer.php` : chargement des nouveaux fichiers.
- `assets/css/product-cards.css` : cadrage, fondu, actions, dialogue et mobile.
- `assets/js/product-cards.js` : galerie, balayage, aperçu, sélection de variante et focus.
- `admin/produit.php` : ordre des photos.

## Vérifications

Depuis `admin`, exécuter `C:\xampp\php\php.exe tests\product_cards.php`, `C:\xampp\php\php.exe tests\favorites_service.php`, `node tests/favorites_client.mjs` et `node --check ../assets/js/product-cards.js`.

Le scénario navigateur `node tests/product_cards_browser.mjs` utilise le serveur local sur le port 8000 et un profil Chrome temporaire. Il ajoute uniquement des images SVG de test dans le DOM du navigateur, sans modifier le catalogue ni les fichiers photo. Son exécution nécessite l’autorisation de lancer Chrome.

## Diagnostic du survol

Le diagnostic de la base locale (`tests/photo_diagnostic.php`) a trouvé 21 produits actifs et aucun produit avec deux photos. Les produits triés par nombre de photos décroissant ont tous zéro photo : Palmier Raphis (ID 1, slug `raphis`), Kentia (ID 2) et Ficus Benjamina (ID 3). La carte du Palmier Raphis ne contient donc aucune balise `card-photo` ; elle utilise l’illustration de secours. Il n’y a pas deux URL de photos à comparer ou à tester pour ce produit.

Le rendu commun vient de `includes/helpers.php` (`load_products`, `product_grid`, `product_carousel`, `product_card`). `index.php` et `boutique.php` appellent `product_grid`. Les favoris passent par `includes/favorites-api.php` puis `includes/favorites-service.php` et `product_card`. La requête sur `product_images` ne limite pas les résultats et respecte `position, id`.

Le survol est maintenant une règle CSS directe, activée seulement pour les galeries ayant au moins deux photos. Il fonctionne sans dépendre du chargement JavaScript. Les fichiers CSS/JS ont de nouvelles versions de cache. L’administration indique le nombre de photos et signale les photos manquantes. Les champs d’ajout multiple et d’ordre existants sont conservés.

Pour un test réel : ouvrir `/admin/produit.php?id=1`, ajouter deux vues distinctes du véritable Palmier Raphis, choisir la principale, enregistrer, puis ouvrir `/boutique.php?q=Raphis`. Ne pas utiliser des photos d’un autre produit. L’outil `tests/photo_diagnostic_browser.mjs` peut relever le CSS et les photos des pages réelles sans modifier les données. Son lancement a été refusé dans cette session : aucune validation visuelle avec deux vraies photos n’est revendiquée.

À vérifier visuellement avec les vraies photos : survol puis sortie de carte, produit avec une seule photo, barre au clavier, fermeture par Échap, variantes avant panier, favoris, balayage horizontal et défilement vertical sur téléphone. Le catalogue local ne contenait aucune photo téléversée au moment de cette modification. Le test navigateur n’a pas été exécuté car son lancement n’a pas été autorisé.

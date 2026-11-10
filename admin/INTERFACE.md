# Ajustements de l’interface AuryaDeco

Le téléphone public et WhatsApp ont été enregistrés dans les paramètres :
`+216 93 091 290` / `21693091290`. Aucun prix, produit, stock ou mécanisme
de commande n’a été modifié.

## Changements appliqués

- `includes/helpers.php` : détection des numéros vides ou d’exemple pour leur affichage.
- `includes/header.php` : téléphone et livraison offerte issus des paramètres,
  chargement des styles publics et identification des pages panier/commande.
- `includes/footer.php`, `includes/deco-widget.php` : un seul déclencheur
  « Mon assistant déco », regroupé avec WhatsApp, placé en dessous.
- `assets/css/home.css` : bannière d’environ 70 svh sur ordinateur, titre réduit
  via clamp, largeur de texte contenue, voile principalement à gauche,
  cadrages distincts et commande vidéo en bas à gauche.
- `assets/css/interface.css`, `assets/js/interface.js` : groupe flottant compact
  sur mobile, masquage temporaire en cas de collision avec des commandes ou
  pendant la saisie ; sur panier/commande, groupe dans le flux après le pied
  de page afin de ne pas recouvrir les formulaires. Il ne s’agit pas d’un deuxième bouton.
- `assets/js/deco.js` : état ouvert/fermé et masquage des boutons derrière le chat.
- `assets/js/shop.js` : libellé du menu mobile et fermeture avec Échap.

## Nos univers : activation restante

`includes/home-universes.php` est prêt. Le remplacement de la section dans
`index.php` a été refusé par les permissions de la session, donc ce composant
n’est pas encore utilisé par la page d’accueil.

Dans `index.php`, remplacer uniquement le bloc de section « Nos univers » par :

```php
<?php require __DIR__ . '/includes/home-universes.php'; ?>
```

Conserver `$tiles`, les requêtes et les autres sections. Ce composant maintient
les bonnes catégories et toute la carte cliquable, ajoute « Découvrir → »,
supprime le soulignement permanent, garde l’asymétrie sur ordinateur et
affiche les quatre cartes dans leur ordre sur mobile.

Le composant utilise, si elles existent et sont valides, ces photos facultatives :

- `assets/images/univers-plantes.jpg`
- `assets/images/univers-pots.jpg`
- `assets/images/univers-tombantes.jpg`
- `assets/images/univers-accessoires.jpg`

Il cherche sinon de vraies photos des produits de la catégorie dans le catalogue.
Aucun lien d’image manquante n’est émis. Si aucune photo ne convient, il garde
l’illustration. À la vérification, seule la couverture vidéo est disponible dans
les médias du projet ; elle n’est pas réutilisée comme photo de produit ou de pot.

## Vérification

Syntaxe PHP et JavaScript vérifiée. Les quatre liens de catégories, la recherche,
le panier, les nouveaux fichiers CSS/JS et l’API de l’assistant répondent HTTP 200.
Le HTML contient un seul déclencheur d’assistant et le bon lien WhatsApp.

Les règles responsive couvrent 390, 768 et 1440 px. La validation visuelle réelle
à ces trois largeurs reste à faire : le navigateur automatisé ne démarre pas
dans cet environnement Windows. Ne pas confondre ces contrôles HTTP et de syntaxe
avec une vérification visuelle. Vérifier en particulier le cadrage de la vidéo
verticale sur écran large et les boutons flottants au défilement.

Prévisualisation locale : `http://127.0.0.1:8081/`, puis Ctrl+F5.

## Finalisation depuis votre terminal

Les nouvelles demandes d’activation et de téléchargement ont été refusées par
les fenêtres d’autorisation. Le navigateur intégré a aussi échoué au démarrage.
Pour exécuter les étapes dans votre propre terminal PowerShell, depuis `admin` :

```powershell
.\terminer-interface.ps1
```

Le serveur 8081 doit être lancé (`.\start-local.ps1` dans un autre terminal).
Le script sauvegarde index.php, active le composant, télécharge quatre photos
d’ambiance Pexels réellement identifiées, puis lance le Chrome installé en mode
headless pour contrôler 390, 768 et 1440 px. Il conserve les photos déjà présentes.
Les sources sont dans `assets/images/SOURCES.md`. Aucun produit n’est modifié.

Les captures accueil, catégories et assistant, ainsi que le rapport JSON, seront
enregistrés dans `admin/verification`. Elles doivent encore être examinées
visuellement après cette exécution : des tests de géométrie ne remplacent pas
la lecture des captures. La création de ces captures n’est pas revendiquée avant
que le script ait réussi. Les scripts ont été vérifiés syntaxiquement.

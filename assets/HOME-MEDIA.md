# Médias de l’accueil AuryaDeco

Fichiers à fournir, relatifs à la racine du site :

- `assets/videos/hero-deco.mp4`
- `assets/images/hero-deco-poster.jpg`

Filmer un intérieur lumineux avec des plantes et des pots, avec un cadrage fixe
et des mouvements très doux. Prévoir de l’espace libre à gauche pour le texte.
Exporter une boucle courte (8 à 15 secondes), MP4 H.264, sans piste audio,
environ 1920 × 1080, avec un objectif de 3 à 5 Mo maximum et fast start.
Exporter une couverture JPEG du même cadrage, idéalement sous 300 Ko.

Les fichiers absents ne génèrent aucune URL média dans le HTML. Sans médias,
la bannière affiche un dégradé vert. Sans JavaScript ou en mode réduction des
animations, elle affiche la couverture fixe (ou le dégradé si elle est absente).

## Activation restant à effectuer dans index.php

La modification de ce fichier a été refusée par les permissions de la session.
Les fichiers préparés ne sont donc pas encore activés.

1. Remplacer le bloc `$rulerLinks` et sa boucle par `$homePage = true;`,
   avant le chargement de `includes/header.php`.
2. Remplacer la section `<section class="hero">…</div></section>` par :

```php
<?php require __DIR__ . '/includes/home-hero.php'; ?>
```

3. Fermer le conteneur ouvert par ce composant avec `</div>` juste avant
   le chargement de `includes/footer.php`.

Conserver toutes les requêtes, boucles et sections suivantes.

Après activation et ajout des médias, vérifier visuellement l’accueil sur mobile
et ordinateur, le refus d’autoplay, le bouton pause/lecture, le mode sans JavaScript
et le réglage système de réduction des animations.

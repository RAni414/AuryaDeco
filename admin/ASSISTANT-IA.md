# Aurya — Gemini, conversation et analyse photo

Le chatbot utilise désormais uniquement **Google Gemini** pour les conseils.
Les anciennes clés et configurations OpenAI ne servent plus à la conversation.
Les photomontages sont désactivés côté serveur, même si une ancienne clé image
est encore présente. Le catalogue, le panier, les favoris, les photos et la
nouvelle conversation de l’interface sont conservés.

## Nouvelle clé et offre gratuite

Si une clé a été publiée dans une conversation, la remplacer dans Google AI Studio
et désactiver l’ancienne. Ne pas coller la nouvelle clé dans une conversation,
un fichier du projet ou une commande PowerShell en clair.

Utiliser un projet Google AI Studio sur le **Free Tier**, sans compte de facturation
associé. Le code ne configure pas Google Cloud, n’active pas la facturation et ne
fait aucun appel à un outil payant. Il ne peut pas vérifier le niveau de facturation
de votre projet à partir d’une simple clé : une clé d’un projet déjà facturé peut
être facturée par Google. Ne pas activer la facturation pour contourner un quota.

Modèle par défaut : `gemini-3.1-flash-lite`, acceptant texte et images et proposé
dans l’offre gratuite de Google lors de la consultation de sa documentation.
`GEMINI_MODEL` peut sélectionner `gemini-3.1-flash-lite` ou
`gemini-2.5-flash-lite`. Les autres noms sont refusés afin de ne pas sélectionner
accidentellement un modèle Pro ou de génération d’images. Vérifier périodiquement
la disponibilité et les quotas dans le tableau de bord ; aucun basculement
automatique vers un autre modèle ou fournisseur n’est effectué.

## Procédure PowerShell exacte pour Windows

1. Démarrer MySQL dans XAMPP. Garder le `config.php` existant.
2. Si un serveur utilise déjà `127.0.0.1:8080`, l’arrêter par Ctrl+C dans son terminal.
   Le lanceur ne tue aucun processus existant.
3. Ouvrir PowerShell et exécuter :

```powershell
Set-Location -LiteralPath 'C:\Users\MSI\Desktop\ahmed ben tiba\auryadeco-site (1)\auryadeco\admin'
.\start-gemini.ps1
```

4. À l’invite masquée, saisir la **nouvelle** clé. Elle n’est pas enregistrée dans
   le script ou l’historique des commandes. Seul le processus PowerShell courant
   et son enfant PHP reçoivent `GEMINI_API_KEY`. La variable est supprimée à l’arrêt.
5. Ouvrir `http://127.0.0.1:8080/`, faire Ctrl+F5 puis ouvrir « Mon assistant déco ».

Le script configure précisément :

- Site : `C:\Users\MSI\Desktop\ahmed ben tiba\auryadeco-site (1)\auryadeco`.
- PHP : `C:\xampp\php\php.exe`.
- Sessions : `C:\Users\MSI\auryadeco-sessions`, créé si nécessaire.
- Variables du processus : `GEMINI_API_KEY`, `GEMINI_MODEL`, `AURYA_SESSION_DIR`.
- Paramètres PHP : `session.save_path`, GD, uploads temporaires, limites 5/6 Mo.
- Écoute : `127.0.0.1:8080`, sans exposition sur le réseau.

Le dossier de sessions est hors du dossier public. Le bootstrap respecte
`AURYA_SESSION_DIR`. Pour changer le modèle autorisé :

```powershell
.\start-gemini.ps1 -Model gemini-2.5-flash-lite
```

Alternative manuelle équivalente, sans clé en clair dans le script :

```powershell
$sessionDir = 'C:\Users\MSI\auryadeco-sessions'
New-Item -ItemType Directory -Force -Path $sessionDir | Out-Null
$secret = Read-Host 'Nouvelle clé Gemini Free Tier' -AsSecureString
try {
    $env:GEMINI_API_KEY = [System.Net.NetworkCredential]::new('', $secret).Password.Trim()
    $env:GEMINI_MODEL = 'gemini-3.1-flash-lite'
    $env:AURYA_SESSION_DIR = $sessionDir
    & 'C:\xampp\php\php.exe' -d extension=gd -d "session.save_path=$sessionDir" -d "upload_tmp_dir=$env:TEMP" -d upload_max_filesize=5M -d post_max_size=6M -d display_errors=0 -d display_startup_errors=0 -S 127.0.0.1:8080 -t 'C:\Users\MSI\Desktop\ahmed ben tiba\auryadeco-site (1)\auryadeco'
} finally {
    Remove-Item Env:\GEMINI_API_KEY -ErrorAction SilentlyContinue
    $secret.Dispose()
}
```

Une erreur de syntaxe déjà détectée dans le `php.ini` global, ligne 1379, peut
apparaître au terminal. Corriger cette ligne séparément dans XAMPP. Vérifier aussi
les extensions `curl`, `gd`, `fileinfo`, `mbstring`, `pdo_mysql`, `pdo_sqlite`
et les certificats CA pour cURL. La vérification TLS n’est jamais désactivée.

## Traitement et limites

La route existante `includes/deco-api.php` reste utilisée. PHP appelle le REST
`/v1beta/models/{model}:generateContent`. La clé vient exclusivement de
`getenv('GEMINI_API_KEY')` et passe dans l’en-tête `x-goog-api-key`, jamais dans
l’URL, le JavaScript, le JSON retourné ou les journaux de l’application.

Les messages courants, jusqu’à trois échanges antérieurs et les données nécessaires
du catalogue sont envoyés à chaque appel. Le contexte est isolé par session PHP,
borné à six messages texte, et remis à zéro par Nouvelle conversation. Un changement
depuis l’ancien fournisseur efface uniquement l’ancien contexte de l’assistant,
sans toucher au panier, aux favoris ou à la session administrateur.

Les photos sont des JPEG/PNG/WebP de 5 Mo et 12 mégapixels maximum, côtés entre
100 et 6 000 pixels. Fileinfo, dimensions et décodage GD sont contrôlés ; orientation
EXIF si disponible, réencodage JPEG sans métadonnées et côté long de 1 600 pixels
maximum. Les données sont envoyées en `inlineData`, sans upload persistant chez
Google via Files API. AuryaDeco ne conserve ni photo ni base64 en session ou en base ;
les fichiers temporaires PHP sont supprimés en `finally`, y compris en erreur.

Le client doit accepter l’envoi à Google avant de soumettre la photo. L’interface
indique qu’avec l’offre gratuite, Google peut utiliser les contenus pour améliorer
ses produits selon ses conditions. Éviter les données privées. Après navigation ou
suppression, une nouvelle analyse visuelle nécessite de joindre à nouveau la photo.

Les textes présents dans les images et les descriptions sont des données non fiables,
jamais des instructions système. L’IA doit demander les mesures manquantes et la
lumière pour une plante naturelle, sans les inventer. Elle ne promet pas de montage.
Les variantes recommandées sont filtrées parmi les vrais candidats côté serveur.
PHP relit prix, stock et pots compatibles ; aucun prix de l’IA ou du navigateur ne
remplace le catalogue. La boutique vend des plantes artificielles et le précise
pour une demande naturelle. Les ajouts au panier restent des clics explicites.

Délais : connexion 10 s, appel 45 s, sans retry automatique. Réponse JSON structurée,
1 400 tokens maximum et 6 000 caractères de conseil au plus. Pas d’outil, de recherche,
de grounding, de génération d’image, de cache payant ou de repli OpenAI.

Limites locales : 6 appels par session / 10 min, 12 par IP / heure, 120 par boutique /
heure, plus 3 par boutique / minute et 20 par boutique / 24 h. Ces plafonds protègent
l’application ; ils ne prétendent pas être les quotas officiels de votre compte.
Le dépassement Gemini (HTTP 429) est signalé clairement, sans passage au payant.
Les erreurs du fournisseur ne sont pas relayées brutes, pour ne révéler aucun secret.

## Vérifications reproductibles

Sans réseau, avec réponses fournisseur simulées explicitement :

```powershell
C:\xampp\php\php.exe -n -d extension_dir=C:\xampp\php\ext -d extension=mbstring tests/gemini_provider.php
```

Ce test couvre plusieurs messages, photo inline, contexte borné et isolé entre visiteurs,
filtrage des produits, clé absente, modèle interdit, HTTP 429/403/404/500, réponse
incomplète/invalide et délai. Les valeurs de clé de test sont des placeholders fictifs.
Ces réponses de tests ne sont jamais utilisées dans la route de production.

Contrôle HTTP sans appel IA réel (clé absente ou seules validations locales) :

```powershell
python tests/gemini_http.py --base http://127.0.0.1:8080
python tests/gemini_sessions.py
```

Après configuration de votre nouvelle clé Free Tier, test réel explicite consommant
deux demandes de votre quota gratuit, avec la couverture vidéo locale comme photo d’essai :

```powershell
python tests/gemini_http.py --base http://127.0.0.1:8080 --live
```

Le test réel demande une conversation texte, une analyse photo et une recommandation
du vrai catalogue, puis contrôle l’historique, l’isolation et la nouvelle discussion.
Si le quota est dépassé, il signale l’erreur : il ne produit aucune fausse réussite.
Le scénario quota est vérifié par simulation, sans épuiser volontairement le quota Google.

À la livraison : syntaxe, tests fournisseur simulés et cas HTTP clé absente vérifiés.
Un serveur PHP temporaire réel a aussi confirmé le respect de `AURYA_SESSION_DIR`,
la persistance du CSRF et l’isolation des sessions de deux visiteurs.
Les appels Google réels n’ont pas été effectués avec la clé publiée dans la conversation ;
ils restent à lancer avec votre nouvelle clé saisie localement. Aucun résultat de test
simulé n’est présenté comme une réponse Gemini réellement reçue.

## Fichiers de cette intégration

- Ajout : `includes/gemini-service.php`, `admin/start-gemini.ps1`,
  `admin/tests/gemini_provider.php`, `admin/tests/gemini_http.py`, `admin/tests/gemini_sessions.py`.
- Modifiés : `includes/deco-service.php`, `includes/deco-api.php`,
  `includes/deco-simulation.php`, `includes/bootstrap.php`, `includes/deco-widget.php`,
  `includes/footer.php`, `assets/js/deco.js`, ce guide et les anciens points d’entrée
  de tests `deco_provider.php`, `deco_smoke.py`, `deco_simulation.php`.
- Aucun changement SQL, de prix, de stock ou de commande.

## Documentation officielle consultée

- Modèle : https://aistudio.google.com/docs/models/gemini-3.1-flash-lite
- Gratuité et données : https://aistudio.google.com/docs/pricing#gemini-3.1-flash-lite
- REST : https://aistudio.google.com/api-reference/generate-content/generate-content
- Clés : https://aistudio.google.com/docs/api-key
- Images : https://aistudio.google.com/docs/image-understanding
- Facturation : https://aistudio.google.com/docs/billing

<?php
require __DIR__ . '/../includes/admin.php';
require_admin();
require_once __DIR__ . '/../includes/seo.php';
$values = [
    'seo_site_url' => seo_base(),
    'seo_home_title' => settings()['seo_home_title'] ?? 'Plantes artificielles et pots déco en Tunisie | ' . setting('shop_name'),
    'seo_home_description' => settings()['seo_home_description'] ?? 'Découvrez les plantes artificielles, cache-pots et accessoires déco AuryaDeco. Prix en TND, livraison en Tunisie et paiement à la livraison.',
    'seo_google_verification' => settings()['seo_google_verification'] ?? '',
];
$error = '';
if (is_post()) {
    csrf_check();
    foreach ($values as $key => $value) $values[$key] = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
    $url = parse_url($values['seo_site_url']);
    if (!filter_var($values['seo_site_url'], FILTER_VALIDATE_URL) || ($url['scheme'] ?? '') !== 'https' || isset($url['user']) || isset($url['pass']) || isset($url['query']) || isset($url['fragment'])) {
        $error = 'Indiquez l’adresse HTTPS publique du site, sans paramètres.';
    } elseif ($values['seo_home_title'] === '' || mb_strlen($values['seo_home_title']) > 120 || $values['seo_home_description'] === '' || mb_strlen($values['seo_home_description']) > 300) {
        $error = 'Renseignez un titre (120 caractères maximum) et une description (300 caractères maximum).';
    } elseif ($values['seo_google_verification'] !== '' && !preg_match('/^[a-zA-Z0-9_-]{10,200}$/D', $values['seo_google_verification'])) {
        $error = 'Collez uniquement le code de vérification Google, sans la balise HTML.';
    } else {
        $values['seo_site_url'] = rtrim($values['seo_site_url'], '/');
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $save = $pdo->prepare('INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)');
            foreach ($values as $key => $value) $save->execute([$key, $value]);
            $pdo->commit();
            flash('Les réglages de référencement ont été enregistrés.');
            redirect('admin/referencement.php');
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('SEO settings: ' . $e->getMessage());
            $error = 'Impossible d’enregistrer les réglages. Réessayez.';
        }
    }
}
admin_header('Référencement Google', 'referencement');
?>
<h1>Référencement Google</h1>
<p class="hint">Les fiches produits reprennent automatiquement leur nom, description, photos et prix pour Google. Soignez ces informations dans Produits. Ces réglages facilitent l’indexation, sans garantir une position dans les résultats.</p>
<?php if ($error): ?><p role="alert"><?= e($error) ?></p><?php endif; ?>
<form class="fs narrow" method="post">
  <?= csrf_field() ?>
  <label class="field">Adresse publique du site<input name="seo_site_url" type="url" value="<?= e($values['seo_site_url']) ?>" required></label>
  <label class="field">Titre de l’accueil dans les moteurs de recherche<input name="seo_home_title" value="<?= e($values['seo_home_title']) ?>" maxlength="120" required></label>
  <label class="field">Description de l’accueil<textarea name="seo_home_description" rows="4" maxlength="300" required><?= e($values['seo_home_description']) ?></textarea></label>
  <label class="field">Code Google Search Console (facultatif)<input name="seo_google_verification" value="<?= e($values['seo_google_verification']) ?>" maxlength="200"></label>
  <p class="hint">Dans Search Console, ajoutez une propriété de type « Préfixe de l’URL » pour votre adresse HTTPS. Choisissez la vérification par balise HTML et collez ici seulement la valeur de « content ». Après publication de ce réglage, validez dans Google.</p>
  <button class="btn" type="submit">Enregistrer le référencement</button>
</form>
<div class="fs narrow">
  <h2>Après le déploiement</h2>
  <ol>
    <li>Ouvrez <a href="https://search.google.com/search-console" target="_blank" rel="noopener">Google Search Console</a> et validez votre site.</li>
    <li>Dans « Sitemaps », envoyez <code>sitemap.xml</code>.</li>
    <li>Dans « Inspection de l’URL », demandez l’indexation de l’accueil et de vos principales fiches produits.</li>
  </ol>
  <p><a href="<?= e(seo_url('sitemap.xml')) ?>" target="_blank" rel="noopener">Voir le sitemap en ligne</a> · <a href="<?= e(seo_url('robots.txt')) ?>" target="_blank" rel="noopener">Voir robots.txt</a></p>
  <p class="hint">Le sitemap et les règles Apache sont actifs après déploiement sur le serveur. Google peut prendre plusieurs jours ou semaines pour explorer les pages.</p>
</div>
<?php require __DIR__ . '/plant-seo-audit.php'; ?>
<?php admin_footer();

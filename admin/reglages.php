<?php
require __DIR__ . '/../includes/admin.php';
require_admin();
$fields = [
    'shop_name' => ['Nom de la boutique', 'text'],
    'whatsapp' => ['Numéro WhatsApp (chiffres seulement, ex. 21620123456)', 'text'],
    'phone' => ['Téléphone affiché', 'text'],
    'email' => ['E-mail affiché', 'email'],
    'notify_email' => ['E-mail qui reçoit les nouvelles commandes (facultatif)', 'email'],
    'address' => ['Adresse', 'text'],
    'hours' => ['Horaires', 'text'],
    'free_shipping' => ['Livraison offerte à partir de (TND, 0 pour jamais)', 'number'],
    'shipping_fee' => ['Frais de livraison par défaut (TND), pour les produits sans tarif propre', 'number'],
    'topbar' => ['Message du bandeau en haut', 'text'],
    'hero_title' => ["Titre de la page d'accueil", 'text'],
    'hero_text' => ['Texte sous le titre', 'textarea'],
    'about' => ['Présentation dans le pied de page', 'textarea'],
];
if (is_post()) {
    csrf_check();
    $st = db()->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
    foreach ($fields as $k => [$label, $type]) {
        $v = trim((string)($_POST[$k] ?? ''));
        if ($type === 'number') $v = (string)max(0, (float)str_replace(',', '.', $v));
        if ($k === 'whatsapp') $v = preg_replace('/\D/', '', $v);
        $st->execute([$k, $v]);
    }
    flash('Réglages enregistrés.');
    redirect('admin/reglages.php');
}
admin_header('Réglages', 'reglages');
?>
<h1>Réglages de la boutique</h1>
<form method="post" class="fs narrow">
  <?= csrf_field() ?>
  <?php foreach ($fields as $k => [$label, $type]): ?>
    <label class="field"><?= e($label) ?>
      <?php if ($type === 'textarea'): ?><textarea name="<?= $k ?>" rows="3"><?= e(setting($k)) ?></textarea>
      <?php else: ?><input type="<?= $type ?>" name="<?= $k ?>" value="<?= e(setting($k)) ?>"<?= $type === 'number' ? ' step="0.001" min="0"' : '' ?>><?php endif; ?>
    </label>
  <?php endforeach; ?>
  <button class="btn">Enregistrer les réglages</button>
</form>
<?php admin_footer();

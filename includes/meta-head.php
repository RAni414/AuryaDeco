<?php
require_once __DIR__ . '/meta-tracking.php';
$metaEvents = [];
$metaHost = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
$metaProduction = in_array($metaHost, ['auryadeco.tn', 'www.auryadeco.tn'], true);
$metaSensitive = in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['compte.php', 'avis.php'], true);
if ($metaProduction && !$metaSensitive && ($_COOKIE['aurya_marketing'] ?? '') === 'yes') {
    $metaEvents = meta_tracking_events(get_defined_vars());
    meta_send_events($metaEvents);
}
?>
<meta name="facebook-domain-verification" content="c736g0oi8d7e6q9cx7ucf1gi1jn5cp">
<?php if ($metaProduction && !$metaSensitive): ?>
<script src="<?= e(url('assets/js/meta-tracking.js')) ?>?v=1" defer data-meta-events="<?= e(json_encode($metaEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"></script>
<?php endif; ?>

<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

if (is_post()) {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($act === 'active') {
        db()->prepare('UPDATE reviews SET active = 1 - active WHERE id = ?')->execute([$id]);
        flash("Visibilité de l'avis modifiée.");
    } elseif ($act === 'delete') {
        $st = db()->prepare('SELECT photo FROM reviews WHERE id = ?');
        $st->execute([$id]);
        $f = $st->fetchColumn();
        if ($f) @unlink(UPLOAD_DIR . '/' . basename($f));
        db()->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
        flash('Avis supprimé.');
    } elseif ($act === 'up' || $act === 'down') {
        // Renumérote tous les avis dans l'ordre affiché, puis échange avec le voisin.
        $ids = db()->query('SELECT id FROM reviews ORDER BY position ASC, id DESC')->fetchAll(PDO::FETCH_COLUMN);
        $i = array_search((string)$id, array_map('strval', $ids), true);
        $j = $act === 'up' ? $i - 1 : $i + 1;
        if ($i !== false && isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        }
        $up = db()->prepare('UPDATE reviews SET position = ? WHERE id = ?');
        foreach ($ids as $pos => $rid) $up->execute([$pos, $rid]);
    }
    redirect('admin/avis.php');
}

$list = db()->query('SELECT * FROM reviews ORDER BY position ASC, id DESC')->fetchAll();
$shown = count(array_filter($list, fn($r) => $r['active']));
admin_header('Avis clients', 'avis');
?>
<div class="shead"><h1>Avis clients</h1><a class="btn" href="<?= e(url('admin/avi.php')) ?>">Ajouter un avis</a></div>
<p class="hint"><?= count($list) ?> avis, dont <?= $shown ?> affiché<?= $shown > 1 ? 's' : '' ?> dans le carrousel de la page d'accueil, dans l'ordre de cette liste (jusqu'à 9). Utilisez les flèches pour les ordonner. Un avis avec photo s'affiche en carte blanche, un avis sans photo en carte verte.</p>

<?php if (!$list): ?>
  <div class="empty">Vous n'avez pas encore d'avis. Ajoutez le premier avec la photo et le commentaire d'un client.<br><br><a class="btn" href="<?= e(url('admin/avi.php')) ?>">Ajouter un avis</a></div>
<?php else: ?>
<div class="atable-wrap"><table class="at">
  <thead><tr><th>Photo</th><th>Client</th><th>Note</th><th>Commentaire</th><th>Visible</th><th class="r">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($list as $i => $r): ?>
    <tr class="<?= $r['active'] ? '' : 'inactive' ?>">
      <td><div class="th"><?php if ($r['photo']): ?><img class="ph" src="<?= e(url('uploads/' . $r['photo'])) ?>" alt=""><?php else: ?><span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($r['name'], 0, 1))) ?></span><?php endif; ?></div></td>
      <td><a class="nm" href="<?= e(url('admin/avi.php?id=' . $r['id'])) ?>"><?= e($r['name']) ?></a><div class="sub"><?= e($r['city']) ?></div></td>
      <td><?= stars_html((int)$r['rating']) ?></td>
      <td class="clip"><?= e(mb_strlen($r['comment']) > 110 ? mb_substr($r['comment'], 0, 110) . '…' : $r['comment']) ?></td>
      <td><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="sbtn" name="action" value="active"><?= $r['active'] ? 'Visible' : 'Masqué' ?></button></form></td>
      <td class="r"><div class="acts">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="sbtn" name="action" value="up" aria-label="Monter" <?= $i === 0 ? 'disabled' : '' ?>>↑</button></form>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="sbtn" name="action" value="down" aria-label="Descendre" <?= $i === count($list) - 1 ? 'disabled' : '' ?>>↓</button></form>
        <a class="sbtn" href="<?= e(url('admin/avi.php?id=' . $r['id'])) ?>">Modifier</a>
        <form method="post" data-confirm="Supprimer l'avis de « <?= e($r['name']) ?> » ?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="sbtn danger" name="action" value="delete">Supprimer</button></form>
      </div></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
<?php admin_footer();

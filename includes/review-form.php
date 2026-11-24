<?php
$reviewValues = $reviewValues ?? ['name' => '', 'city' => '', 'rating' => '', 'comment' => ''];
$reviewError = $reviewError ?? '';
$reviewSuccess = $_SESSION['review_success'] ?? '';
unset($_SESSION['review_success']);
?>
<section class="customer-review" id="laisser-avis" aria-labelledby="customer-review-title">
  <div class="wrap customer-review__layout">
    <div class="customer-review__intro">
      <span class="customer-review__stars" aria-hidden="true">★★★★★</span>
      <h2 id="customer-review-title">Votre avis compte.</h2>
      <p>Une plante qui a trouvé sa place, une déco qui vous ressemble ? Partagez votre expérience avec AuryaDeco.</p>
      <p class="customer-review__note">Votre avis sera visible après validation par notre équipe.</p>
    </div>
    <form class="customer-review__form" method="post" enctype="multipart/form-data" action="<?= e(url('includes/avis.php')) ?>">
      <?= csrf_field() ?>
      <?php if ($reviewError): ?><p class="customer-review__error" role="alert"><?= e($reviewError) ?></p><?php endif; ?>
      <?php if ($reviewSuccess): ?><p class="customer-review__success" role="status"><?= e($reviewSuccess) ?></p><?php endif; ?>
      <div class="customer-review__row">
        <label>Votre nom <span aria-hidden="true">*</span><input name="name" value="<?= e($reviewValues['name']) ?>" autocomplete="name" maxlength="120" required></label>
        <label>Votre ville <span class="customer-review__optional">(facultatif)</span><input name="city" value="<?= e($reviewValues['city']) ?>" autocomplete="address-level2" maxlength="80"></label>
      </div>
      <label>Votre note <span aria-hidden="true">*</span><select name="rating" required><option value="">Choisir une note</option><?php for ($rating = 5; $rating >= 1; $rating--): ?><option value="<?= $rating ?>" <?= (string)$reviewValues['rating'] === (string)$rating ? 'selected' : '' ?>><?= $rating ?> / 5 — <?= str_repeat('★', $rating) ?></option><?php endfor; ?></select></label>
      <label>Votre avis <span aria-hidden="true">*</span><textarea name="comment" rows="4" maxlength="800" required placeholder="Racontez-nous votre expérience…"><?= e($reviewValues['comment']) ?></textarea></label>
      <div class="review-photo">
        <label>Ajouter une photo <span class="customer-review__optional">(facultatif)</span><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-review-photo aria-describedby="review-photo-help"></label>
        <p class="customer-review__note" id="review-photo-help">JPG, PNG ou WebP · 5 Mo maximum. Après une erreur d’envoi, sélectionnez à nouveau votre photo.</p>
        <p class="review-photo__error" data-review-photo-error role="alert" hidden></p>
        <div class="review-photo__preview" data-review-photo-preview hidden>
          <img alt="Aperçu de votre photo" data-review-photo-image>
          <button class="btn ghost" type="button" data-review-photo-remove>Retirer la photo</button>
        </div>
      </div>
      <div class="customer-review__trap" aria-hidden="true"><label>Site internet<input name="website" tabindex="-1" autocomplete="off"></label></div>
      <p class="customer-review__note">800 caractères maximum. Votre nom, votre ville, votre commentaire et votre photo pourront être publiés. Évitez de partager vos coordonnées personnelles.</p>
      <button class="btn" type="submit">Envoyer mon avis</button>
    </form>
  </div>
</section>

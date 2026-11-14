<div class="review-entry" data-review-entry>
  <a class="btn review-open" href="<?= e(url('includes/avis.php')) ?>" data-review-open>Donner mon avis</a>
</div>
<dialog class="review-dialog" id="review-dialog" aria-labelledby="customer-review-title">
  <button class="review-dialog__close" type="button" data-review-close aria-label="Fermer le formulaire d’avis">×</button>
  <?php require __DIR__ . '/review-form.php'; ?>
</dialog>

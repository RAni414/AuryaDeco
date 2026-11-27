<aside class="black-friday-banner" aria-label="Black Friday : livraison partout en Tunisie">
  <div class="black-friday-banner__track">
    <?php for ($copy = 0; $copy < 2; $copy++): ?>
    <div class="black-friday-banner__group"<?= $copy ? ' aria-hidden="true"' : '' ?>>
      <?php for ($repeat = 0; $repeat < 4; $repeat++): ?>
      <span<?= $repeat && !$copy ? ' aria-hidden="true"' : '' ?>><strong>BLACK FRIDAY</strong><span class="black-friday-banner__dot" aria-hidden="true">·</span>Livraison partout en Tunisie</span>
      <?php endfor; ?>
    </div>
    <?php endfor; ?>
  </div>
</aside>

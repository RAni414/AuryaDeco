<button class="deco-launch" type="button" aria-label="Mon assistant déco" aria-controls="deco-panel" aria-expanded="false" hidden><span aria-hidden="true">🌿</span><span class="deco-launch-label">Mon assistant déco</span></button>
<section class="deco-panel" id="deco-panel" role="dialog" aria-modal="false" aria-labelledby="deco-title" data-endpoint="<?= e(url('includes/deco-api.php')) ?>" hidden>
  <header class="deco-head"><div><span>LE CONSEIL AURYADECO</span><h2 id="deco-title">Aurya — Mon assistant déco</h2></div><button type="button" data-deco-close aria-label="Fermer l’assistant">×</button></header>
  <div class="deco-scroll">
    <p class="deco-mode" data-deco-mode role="status">Chargement de l’assistant…</p>
    <p class="deco-intro">Bonjour ! Envoyez votre question ou une photo de votre pièce : je vous conseille où placer vos plantes.</p>
    <div class="deco-conversation" role="log" aria-label="Conversation de décoration" aria-live="polite" aria-relevant="additions"></div>
    <div class="deco-recommendations" aria-label="Sélection du catalogue"></div>
    <form class="deco-form">
      <input type="hidden" name="room" value="">
      <input type="hidden" name="style" value="">
      <input type="hidden" name="budget" value="">
      <input type="hidden" name="dimensions" value="">
      <input type="hidden" name="max_height" value="">
      <label>Choisir une plante<select name="selection" disabled><option value="">Chargement du catalogue…</option></select></label>
      <div class="deco-photo-actions"><button type="button" data-deco-import>Importer une photo</button><button type="button" data-deco-camera>Prendre une photo</button></div>
      <input class="deco-file" type="file" accept="image/jpeg,image/png,image/webp" data-deco-file tabindex="-1" aria-label="Importer une photo de votre intérieur">
      <input class="deco-file" type="file" accept="image/*" capture="environment" data-deco-capture tabindex="-1" aria-label="Photographier votre intérieur">
      <p class="deco-hint">JPEG, PNG ou WebP · 25 Mo maximum. La photo est automatiquement réduite avant envoi.</p>
      <div class="deco-preview" hidden><img alt="Aperçu de la photo sélectionnée"><div class="deco-photo-actions"><button type="button" data-deco-replace>Remplacer</button><button type="button" data-deco-remove>Supprimer la photo</button></div></div>
      <label>Votre message<textarea name="message" maxlength="1800" rows="2" placeholder="Où placer cette plante sans gêner le passage ?"></textarea></label>
      <p class="deco-privacy" data-deco-privacy>Votre photo reste dans votre navigateur tant que vous n’envoyez pas la demande.</p>
      <label class="deco-consent"><input type="checkbox" name="consent" required> <span data-deco-consent>J’accepte le traitement de ma demande.</span></label>
      <button class="deco-submit" type="submit" disabled>Envoyer</button>
      <p class="deco-status" data-deco-status role="status"></p>
      <button class="deco-reset" type="button" data-deco-reset>Nouvelle conversation</button>
    </form>
  </div>
</section>

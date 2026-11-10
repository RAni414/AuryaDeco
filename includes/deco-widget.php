<button class="deco-launch" type="button" aria-label="Mon assistant déco" aria-controls="deco-panel" aria-expanded="false" hidden><span aria-hidden="true">🌿</span><span class="deco-launch-label">Mon assistant déco</span></button>
<section class="deco-panel" id="deco-panel" role="dialog" aria-modal="false" aria-labelledby="deco-title" data-endpoint="<?= e(url('includes/deco-api.php')) ?>" hidden>
  <header class="deco-head"><div><span>LE CONSEIL AURYADECO</span><h2 id="deco-title">Aurya — Mon assistant déco</h2></div><button type="button" data-deco-close aria-label="Fermer l’assistant">×</button></header>
  <div class="deco-scroll">
    <p class="deco-mode" data-deco-mode role="status">Chargement de l’assistant…</p>
    <p class="deco-intro">Où placer ma plante ? Aurya analyse votre photo avec Gemini et vous conseille à partir du catalogue réel. Cette conversation ne génère pas de photomontage.</p>
    <div class="deco-conversation" role="log" aria-label="Conversation de décoration" aria-live="polite" aria-relevant="additions"></div>
    <div class="deco-recommendations" aria-label="Sélection du catalogue"></div>
    <form class="deco-form">
      <div class="deco-step" data-deco-step="1">
        <p class="deco-step-title">1 · Parlons de votre pièce</p>
        <label>Type de pièce<select name="room" required><option value="">Choisir une pièce</option><option>Salon</option><option>Chambre</option><option>Bureau</option><option>Entrée</option><option>Terrasse</option><option>Autre</option></select></label>
        <label>Style souhaité<select name="style" required><option value="">Choisir un style</option><option>Moderne</option><option>Naturel</option><option>Minimaliste</option><option>Méditerranéen</option><option>À définir ensemble</option></select></label>
        <label>Budget total plante + pot (TND, hors livraison)<input name="budget" type="number" min="0.001" max="1000000" step="0.001" placeholder="Facultatif, par exemple 500"></label>
        <button class="deco-submit" type="button" data-deco-next>Continuer avec ma photo</button>
      </div>
      <div class="deco-step" data-deco-step="2" hidden>
      <button type="button" class="deco-reset" data-deco-back>← Modifier ma pièce et mon budget</button>
      <p class="deco-step-title">2 · Votre plante et votre espace</p>
      <label>Votre plante du catalogue<select name="selection" disabled><option value="">Chargement du catalogue…</option></select></label>
      <input type="hidden" name="kind" value="artificielle">
      <p class="deco-hint">Notre boutique propose uniquement des plantes artificielles, sans entretien.</p>
      <label>Dimensions utiles (si connues)<input name="dimensions" maxlength="300" placeholder="Hauteur de la plante, largeur du pot, espace libre…"></label>
      <label>Hauteur maximale souhaitée pour la plante (cm)<input name="max_height" type="number" min="1" max="1000000" placeholder="Facultatif"></label>
      <div class="deco-photo-actions"><button type="button" data-deco-import>Importer une photo</button><button type="button" data-deco-camera>Prendre une photo</button></div>
      <input class="deco-file" type="file" accept="image/jpeg,image/png,image/webp" data-deco-file tabindex="-1" aria-label="Importer une photo de votre intérieur">
      <input class="deco-file" type="file" accept="image/*" capture="environment" data-deco-capture tabindex="-1" aria-label="Photographier votre intérieur">
      <p class="deco-hint">JPEG, PNG ou WebP · 5 Mo maximum · 12 mégapixels maximum. Convertissez les photos HEIC en JPEG.</p>
      <div class="deco-preview" hidden><img alt="Aperçu de la photo sélectionnée"><div class="deco-photo-actions"><button type="button" data-deco-replace>Remplacer</button><button type="button" data-deco-remove>Supprimer la photo</button></div></div>
      <label>Votre question<textarea name="message" maxlength="1800" rows="3" required placeholder="Où placer cette plante sans gêner le passage ?"></textarea></label>
      <p class="deco-privacy" data-deco-privacy>Votre photo reste dans votre navigateur tant que vous n’envoyez pas la demande.</p>
      <label class="deco-consent"><input type="checkbox" name="consent" required> <span data-deco-consent>J’accepte le traitement de ma demande.</span></label>
      <button class="deco-submit" type="submit" disabled>Demander conseil</button>
      </div>
      <p class="deco-status" data-deco-status role="status"></p>
      <button class="deco-reset" type="button" data-deco-reset>Nouvelle conversation</button>
    </form>
  </div>
</section>

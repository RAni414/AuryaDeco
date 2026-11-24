<fieldset class="fs"><legend>Caractéristiques pour le client</legend>
  <p class="hint">Renseignez uniquement les informations confirmées pour ce produit. Les champs laissés vides ne créent aucune promesse d’usage extérieur ou de résistance aux UV.</p>
  <div class="row">
    <label class="field">Utilisation<select name="usage_place"><option value="">Non précisée</option><?php foreach (['interieur' => 'Intérieur', 'exterieur' => 'Extérieur', 'mixte' => 'Intérieur et extérieur'] as $key => $label): ?><option value="<?= $key ?>" <?= ($p['usage_place'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
    <label class="field">Matière précise<input name="material_text" maxlength="120" value="<?= e($p['material_text'] ?? '') ?>" placeholder="Ex. Résine, ciment, feuillage textile…"></label>
  </div>
  <label class="field">Contenu livré<input name="included_text" maxlength="200" value="<?= e($p['included_text'] ?? '') ?>" placeholder="Ex. Un cache-pot, sans plante"></label>
  <label class="field">Conseils d’entretien<input name="care_text" maxlength="200" value="<?= e($p['care_text'] ?? '') ?>" placeholder="Ex. Nettoyer avec un chiffon doux"></label>
</fieldset>

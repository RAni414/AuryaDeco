$path = Join-Path $PSScriptRoot '..\boutique.php'
$content = [System.IO.File]::ReadAllText($path)
$before = '$pageTitle = $title;'
$after = @'
require_once __DIR__ . '/includes/plant-category-content.php';
$plantCategoryContent = plant_category_is_main($_GET) ? plant_category_content() : null;
if ($plantCategoryContent) $title = $plantCategoryContent['title'];
$pageTitle = $title;
'@
$heading = '  <h1 class="ptitle"><?= e($title) ?></h1>'
$intro = @'
  <h1 class="ptitle"><?= e($title) ?></h1>
  <?php if ($plantCategoryContent): ?><p class="plant-category-intro"><?= e($plantCategoryContent['description']) ?></p><?php endif; ?>
'@
if (!$content.Contains('/includes/plant-category-content.php')) {
    if (!$content.Contains($before) -or !$content.Contains($heading)) { throw 'Unexpected boutique.php structure; no changes applied.' }
    $content = $content.Replace($before, $after).Replace($heading, $intro)
    [System.IO.File]::WriteAllText($path, $content, (New-Object System.Text.UTF8Encoding($false)))
}

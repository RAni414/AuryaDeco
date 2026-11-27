<?php
function plant_category_is_main(array $query): bool
{
    return ($query['cat'] ?? '') === 'plante' && empty($query['q']) && empty($query['taille']) && empty($query['mat']) && empty($query['promo']);
}

function plant_category_content(): array
{
    return [
        'title' => 'Plantes artificielles en Tunisie',
        'seo_title' => 'Plantes artificielles en Tunisie – Prix et collection | AuryaDeco',
        'description' => 'Découvrez nos plantes artificielles pour décorer votre intérieur : bambous, oliviers, palmiers et autres modèles. Consultez les tailles et les prix en TND, avec livraison partout en Tunisie.',
    ];
}

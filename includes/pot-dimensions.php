<?php
/** Read old and new pot dimension labels without changing stored catalogue data. */
function pot_dimensions(array $variant): array
{
    $dimensions = [];
    $label = trim((string)($variant['label'] ?? ''));
    $pattern = '/(?:\b(?<axis>[HDL])\s*[:=\-]?\s*(?<value>\d+(?:[.,]\d+)?)\s*(?:cm)?|(?<suffix_value>\d+(?:[.,]\d+)?)\s*(?:cm\s*)?(?<suffix_axis>[HDL])\b)/iu';
    preg_match_all($pattern, $label, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);
    foreach ($matches as $match) {
        $axis = strtoupper($match['axis'] ?? $match['suffix_axis']);
        if ($axis === 'L' && !str_contains(mb_strtolower($match[0]), 'cm')) continue;
        $value = (float)str_replace(',', '.', $match['value'] ?? $match['suffix_value']);
        if ($value > 0) $dimensions[$axis] = $value;
    }
    if (!$dimensions && preg_match('/^\s*(?:Ø|⌀)\s*(\d+(?:[.,]\d+)?)\s*(?:cm)?\s*$/u', $label, $diameter)) $dimensions['D'] = (float)str_replace(',', '.', $diameter[1]);
    foreach (['H' => 'height', 'D' => 'diameter', 'L' => 'width'] as $axis => $field) {
        if (!isset($dimensions[$axis]) && isset($variant[$field]) && is_numeric($variant[$field]) && (float)$variant[$field] > 0) $dimensions[$axis] = (float)$variant[$field];
    }
    return $dimensions;
}

function pot_size_input_label(array $row): string
{
    $dimensions = [];
    foreach (['H' => 'dimension_h', 'D' => 'dimension_d', 'L' => 'dimension_l'] as $axis => $field) {
        $raw = $row[$field] ?? '';
        if (!is_scalar($raw)) throw new InvalidArgumentException('Saisissez des dimensions valides en cm.');
        $raw = trim((string)$raw);
        if ($raw === '') continue;
        if (!preg_match('/^\d+(?:[.,]\d{1,2})?$/D', $raw) || (float)str_replace(',', '.', $raw) <= 0 || (float)str_replace(',', '.', $raw) > 99999.99) throw new InvalidArgumentException('Les dimensions H, D et L doivent être des nombres positifs en cm (deux décimales maximum).');
        $dimensions[$axis] = (float)str_replace(',', '.', $raw);
    }
    if ($dimensions) return pot_dimension_text($dimensions);
    return pot_size_label($row);
}

function pot_dimension_number(float $number): string
{
    return rtrim(rtrim(number_format($number, 2, ',', ''), '0'), ',');
}

function pot_dimension_text(array $dimensions): string
{
    $parts = [];
    foreach (['H', 'D', 'L'] as $axis) if (isset($dimensions[$axis])) $parts[] = $axis . ' ' . pot_dimension_number($dimensions[$axis]) . ' cm';
    return implode(' · ', $parts);
}

function pot_unique_size_labels(array $variants): array
{
    $labels = [];
    foreach ($variants as $variant) {
        $label = pot_size_label($variant);
        if ($label !== '') $labels[$label] = $label;
    }
    return array_values($labels);
}

<?php
function review_date_html(array $review): string
{
    $raw = $review['created_at'] ?? '';
    if (!is_string($raw) || $raw === '') return '';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $raw);
    if (!$date || $date->format('Y-m-d H:i:s') !== $raw) return '';
    $months = [1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return '<time class="review-date" datetime="' . $date->format('Y-m-d') . '">' . $date->format('j') . ' ' . $months[(int)$date->format('n')] . ' ' . $date->format('Y') . '</time>';
}

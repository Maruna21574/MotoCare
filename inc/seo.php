<?php
// Search engine helpers: structured data about the business for Google (local search).

/** JSON-LD <script> describing the business: contact, address, opening hours and social profiles. */
function businessJsonLd(): string
{
    global $site;
    $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
    $hours = [];
    foreach (bookingSettings()['hours'] as $day => $range) {
        if (!is_array($range) || count($range) !== 2 || !isset($days[(int) $day])) continue;
        $hours[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days[(int) $day], 'opens' => $range[0], 'closes' => $range[1]];
    }
    $data = [
        '@context' => 'https://schema.org',
        '@type' => 'AutomotiveBusiness',
        'name' => $site['name'],
        'legalName' => $site['company_name'] ?? null,
        'taxID' => $site['company_dic'] ?? null,
        'description' => $site['description'],
        'url' => siteUrl(),
        'image' => siteUrl('img/og_clean.jpg'),
        'logo' => siteUrl('auto_care_logo.png'),
        'telephone' => $site['phone'],
        'email' => $site['email'],
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => $site['street'], 'postalCode' => $site['zip'], 'addressLocality' => $site['city'], 'addressCountry' => 'SK'],
        'openingHoursSpecification' => $hours,
        'founder' => !empty($site['company_manager']) ? ['@type' => 'Person', 'name' => $site['company_manager']] : null,
        'sameAs' => array_values(array_filter([$site['facebook'] ?? '', $site['instagram'] ?? ''])),
    ];
    $data = array_filter($data, static fn ($value): bool => $value !== null && $value !== '');
    return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>';
}

<?php
// Kategórie padacích rámov a stojanov podľa typu produktu (všetko sa vyrába na mieru, na objednávku).
// Fotky patria do priečinka produkty/ramy/<kľúč kategórie>/ (webp, jpg, png), zobrazia sa automaticky.
// Všetko sa vyrába na mieru, preto sa konkrétne modely motoriek nezobrazujú.

return [
    'padacie-ramy' => [
        'title' => 'Padacie rámy',
        'text' => 'Chránia motorku, motor a plasty pri páde alebo pri státí. Vyrábame ich ručne presne na model.',
    ],
    'male-padacie-ramy' => [
        'title' => 'Malé padacie rámy',
        'text' => 'Menšie rámy, ktoré chránia motor a bočné časti a pritom zostávajú nenápadné.',
    ],
    'velke-padacie-ramy' => [
        'title' => 'Veľké padacie rámy',
        'text' => 'Väčšie rámy s vyššou ochranou, ktoré kryjú aj plasty a nádrž.',
    ],
    'narazove-tyce' => [
        'title' => 'Nárazové tyče',
        'text' => 'Pevné tyče, ktoré chránia motorku pri páde. Vyrábame ich presne na tvoj model.',
    ],
    'centralne-stojany' => [
        'title' => 'Centrálne stojany',
        'text' => 'Centrálny stojan na jednoduchú údržbu, mazanie reťaze alebo zimné uloženie motorky.',
        // Produktová časť stránky (nadpis, výhody, kompatibilita). Upraviteľná v administrácii pri kategórii.
        'detail' => [
            'heading' => 'Centrálny stojan',
            'name' => 'Honda XL 1000 Varadero',
            'brand' => 'Honda',
            'model' => 'XL 1000 Varadero',
            'series' => 'SD01 / SD02',
            'years' => 'podľa modelového radu',
            'lead' => 'Stabilné a spoľahlivé riešenie pre pohodlné odstavenie motocykla a jednoduchšiu údržbu. Vyrobené na mieru pre Honda XL 1000 Varadero.',
            'features' => ['Jednoduchšia údržba', 'Stabilné odstavenie', 'Odolná konštrukcia', 'Presné uchytenie'],
            'note' => 'Honda XL 1000 Varadero sa vyrába v dvoch modelových radoch SD01 a SD02, ktoré majú odlišné vyhotovenie základného uchytenia.',
            'notice' => 'Pri objednávke prosím uveďte modelový rad vašej motorky: SD01 alebo SD02.',
        ],
    ],
    'drziaky-na-nadrz' => [
        'title' => 'Držiaky na nádrž',
        'text' => 'Uzavreté a otvorené držiaky na nádrž.',
    ],
    'ine-riesenia' => [
        'title' => 'Ďalšie riešenia na mieru',
        'text' => 'Špeciálne riešenia podľa modelu motorky alebo špecifikácie zákazníka.',
    ],
];

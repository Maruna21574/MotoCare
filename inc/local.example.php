<?php
// Copy this file to inc/local.php (it is not in git) and adjust it for the server.
return [
    // Folder with customer data (bookings, orders, messages, admin password). Put it OUTSIDE the web root,
    // e.g. '/data/web/motocare-data'. Move the contents of the current data/ folder there first.
    // 'data_dir' => '/data/web/motocare-data',

    // Public address of the website, used for links in e-mails (otherwise the Host header of the request is used).
    // 'base_url' => 'https://www.motocarepro.eu',

    // Stripe payments (e-shop and bookings). Keys are in the Stripe dashboard > Developers > API keys;
    // the webhook secret is shown after adding the endpoint https://<domain>/stripe-webhook.php.
    // 'stripe' => ['secret_key' => 'sk_live_...', 'webhook_secret' => 'whsec_...'],
];

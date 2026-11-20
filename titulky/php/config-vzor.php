<?php
// Nastavenia Subgen. Tento súbor skopíruj ako config.php (vedľa index.php) a doplň hodnoty.
// config.php sa pri aktualizácii appky neprepisuje. Tajné kľúče nikomu neposielaj.
return [
    // Stripe → Developers → API keys. Stačí "Restricted key" s právom čítať Checkout Sessions a Subscriptions.
    'stripe_secret_key' => '',            // napr. rk_live_... alebo sk_live_...
    // Stripe → Payment Links → tvoj produkt 4,90 € ročne (odkaz https://buy.stripe.com/...)
    'payment_link' => '',
    // Voliteľné: Stripe → Developers → Webhooks → endpoint https://TVOJWEB/?auth=webhook
    // udalosti invoice.paid a customer.subscription.deleted; sem vlož "Signing secret" (whsec_...)
    'stripe_webhook_secret' => '',
    'price_label' => '4,90 € ročne',
    'mail_from' => '',                    // odosielateľ e-mailov na obnovu hesla, napr. no-reply@tvojweb.sk
    'site_name' => 'Subgen',
];

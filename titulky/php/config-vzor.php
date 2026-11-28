<?php
// Nastavenia Subgen. Tento súbor skopíruj ako config.php (vedľa index.php) a doplň hodnoty.
// config.php sa pri aktualizácii appky neprepisuje. Tajné kľúče nikomu neposielaj.
return [
    // Stripe → Developers → API keys. Stačí "Restricted key" s právom čítať Checkout Sessions a Subscriptions.
    'stripe_secret_key' => '',            // napr. rk_live_... alebo sk_live_...
    // Stripe → Payment Links → produkt Subgen 4,90 € ročne (už vyplnené)
    'payment_link' => 'https://buy.stripe.com/28E9AU0xs0BIerSgJzbZe01',
    // Voliteľné: Stripe → Developers → Webhooks → endpoint https://subgen.ideanet.sk/?auth=webhook
    // udalosti invoice.paid a customer.subscription.deleted; sem vlož "Signing secret" (whsec_...)
    'stripe_webhook_secret' => '',
    'price_label' => '4,90 € ročne',
    'mail_from' => '',                    // odosielateľ e-mailov na obnovu hesla, napr. no-reply@ideanet.sk (prázdne = no-reply@subgen.ideanet.sk)
    'site_name' => 'Subgen',
    // E-maily správcov: po prihlásení uvidia v menu Analytiku (návštevy a registrácie v čase)
    'admin_emails' => [],
    // E-maily s bezplatným prístupom (všetky funkcie, bez platby, natrvalo). Účet si založia cez
    // "Zabudnuté heslo?" v prihlásení: príde im odkaz na nastavenie hesla.
    'free_emails' => [],
    // Voliteľné: ak hosting neposiela e-maily, heslo pre free_emails a admin_emails si nastavíš cez
    // https://subgen.ideanet.sk/?setup=<kľúč>. Kľúč = dlhý náhodný text (aspoň 16 znakov). Potom ho vymaž.
    'setup_key' => '',
];

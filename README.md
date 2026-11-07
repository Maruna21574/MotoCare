# MotoCare Pro web

PHP web pre MotoCare Pro: prezentácia služieb, online rezervácia v krokoch, e-shop a rezervácie s platbou kartou (Stripe) a administrácia pre majiteľa. Bez databázy — dáta sú v JSON súboroch v `data/`.

Podstránky: `sluzby.php`, `sluzba.php`, `galeria.php`, `o-nas.php`, `kontakt.php`, `rezervacia.php`, `shop.php`, `produkt.php`, `kosik.php`, `objednavka.php`.

## Nasadenie na Websupport

1. Nahraj celý obsah priečinka na hosting do webrootu.
2. Nastav PHP 7.4+ (odporúčané PHP 8.x).
3. Priečinok `data/` musí mať právo zápisu pre PHP. Súbor `data/.htaccess` ho chráni pred priamym zobrazením (funguje na Apache, čo Websupport používa).
4. E-maily odchádzajú cez SMTP zo schránky `web@motocarepro.sk` (nastavenie `smtp` v `inc/local.php`, vzor v `inc/local.example.php`); upozornenia pre majiteľa chodia na `info@motocarepro.sk`, odpovede zákazníkov tiež.
5. Nastav heslo administrácie (pozri nižšie).

## Čisté adresy

Web používa adresy bez prípony, napr. `/kosik` a `/o-nas`. Zabezpečuje to `.htaccess` (Apache s `mod_rewrite` a `AllowOverride All`, čo Websupport podporuje). Staré adresy s `.php` sa presmerujú na nové (301). Na nginx by sa pravidlá museli prepísať, inak by odkazy v menu nefungovali. `api.php` a administrácia ostávajú s príponou.

## Produkty bez ceny

Produkt s cenou 0 (alebo prázdnou) sa zobrazí ako „Cena čoskoro“ a nedá sa objednať, kým v administrácii (Produkty) nenastavíš cenu. Nové produkty z `inc/catalog.php` sa pridajú príkazom `php admin/import-products.php` (existujúce produkty sa nemenia, dá sa spúšťať opakovane). Fotky sú v `produkty/<id produktu>.webp`. Na hostingu spusti príkaz po nahratí nových súborov, alebo produkty pridaj ručne v administrácii.

## Platby (Stripe)

Zákazník si vyberie spôsob platby. E-shop: kartou cez Stripe Checkout (hostovaná platobná stránka, údaje o karte web nevidí) alebo na dobierku. Rezervácia: kartou vopred alebo na mieste v štúdiu. Bez Stripe kľúčov je platba kartou vypnutá a ponúka sa len dobierka, resp. platba na mieste.

1. V Stripe dashboarde (Developers > API keys) skopíruj *Secret key* (`sk_test_...` na skúšku, `sk_live_...` ostro).
2. V Developers > Webhooks pridaj endpoint `https://<doména>/stripe-webhook.php` pre udalosti `checkout.session.completed` a `checkout.session.async_payment_succeeded` a skopíruj *Signing secret* (`whsec_...`).
3. Do `inc/local.php` (podľa `inc/local.example.php`) pridaj `'stripe' => ['secret_key' => 'sk_...', 'webhook_secret' => 'whsec_...']`.

Prechod späť na web po platbe platbu tiež overí, takže objednávka sa zapíše aj keď webhook meškal. Rezervácia platená kartou drží termín 30 minút, kým zákazník platí (stav „Čaká na platbu“), potom sa termín uvoľní. Nezaplatená objednávka sa v admine zobrazí až po zaplatení v „Na vybavenie“, ostatné nájdeš vo filtri „Všetky“. Vrátenie peňazí (zrušená rezervácia alebo objednávka) sa robí v Stripe dashboarde.

## Administrácia

Adresa: `/admin/` — prihlásenie heslom. Po 5 neúspešných pokusoch sa prihlásenie na 15 minút zablokuje.

- **Rezervácie** — zoznam podľa dní, potvrdenie, zrušenie, označenie ako hotové, úprava termínu, ručné pridanie rezervácie (napr. z telefonátu). Pri kolízii s inou rezerváciou alebo blokáciou systém upozorní.
- **Dostupnosť** — otváracie hodiny pre každý deň, interval časov, minimálny predstih a ako ďaleko dopredu sa dá rezervovať. Blokácie jedného času, celého dňa alebo rozsahu dní (dovolenka).
- **Objednávky** — stavy Nová / Potvrdená / Odoslaná / Vybavená / Zrušená.
- **Správy** — správy z kontaktného formulára.
- **Produkty** — pridanie, úprava, skrytie, vymazanie a poradie produktov vrátane fotky.
- **Služby** — ceny, trvanie, fotky a všetky texty služieb; pridanie a vymazanie služby.
- **Galéria** — nahrávanie, mazanie a poradie fotiek, výber fotiek pre pás na úvodnej stránke.
- **Rámy** — kategórie padacích rámov a stojanov (`/ramy`): pridanie, premenovanie, popis, skrytie, poradie a fotky kategórie (nahrávanie, poradie, titulná fotka, mazanie). Prvé zobrazenie berie fotky z priečinkov `produkty/ramy/<kategória>/`, po prvom uložení v admine sa spravujú len tu (dáta v `data/frames.json`). Fotky z priečinkov sa v admine len odstránia zo zoznamu, súbor na disku zostane.
- **Texty** — nadpisy, texty a fotky všetkých stránok (vrátane porovnaní pred/po v galérii).
- **E-maily** — náhľad všetkých e-mailov, ktoré web posiela.
- **Nastavenia** — údaje o firme (kontakt, adresa, sociálne siete, mapa), e-mail pre upozornenia a zmena hesla.

Heslo sa dá nastaviť alebo obnoviť aj z príkazového riadku (cez web je tento skript vypnutý):

```
php admin/set-password.php "nové heslo"
```

## E-maily

| Udalosť | Zákazník | Majiteľ |
|---|---|---|
| Nová rezervácia z webu | prijatie rezervácie | upozornenie s odkazom do adminu |
| Potvrdenie / zrušenie / zmena termínu v admine | áno (dá sa vypnúť pri úprave) | — |
| Nová objednávka | prijatie objednávky | upozornenie |
| Objednávka potvrdená / odoslaná / zrušená | áno (dá sa vypnúť) | — |
| Správa z kontaktného formulára | — | upozornenie |

Každý pokus o odoslanie sa zapíše do `data/mail.log` (OK / FAIL).

## Kde čo upravovať

- Všetok obsah webu (produkty, služby, galéria, texty, kontaktné údaje) sa mení v administrácii. Hodnoty v `inc/config.php` a `inc/texts.php` sú len predvolené — použijú sa, kým ich admin neprepíše.
- Nahrané obrázky sa ukladajú do `uploads/` (spúšťanie skriptov je tam zakázané).
- Otváracie hodiny a blokácie: administrácia → Dostupnosť. Hodiny sa automaticky zobrazujú vo footeri a na úvodnej stránke.
- Služba dlhšia ako otváracie hodiny dňa (napr. Výstavná starostlivosť) zaberie v rezervačnom systéme celý deň.

## Ochrana proti spamu

Kontakt, rezervácia aj objednávka majú skrytú pascu na roboty, podpísaný čas otvorenia formulára (odoslanie do 3 sekúnd alebo bez načítania stránky sa zahodí) a limit 6 odoslaní za hodinu z jednej IP adresy.

## Dáta v `data/`

`bookings.json`, `orders.json`, `contact-messages.json` (+ fotky v `contact-photos/`), `settings.json` (hodiny a pravidlá), `blocks.json` (blokácie), `admin.json` (hash hesla), `secret.json` (kľúč pre ochranu formulárov), obsah z adminu (`site.json`, `products.json`, `services.json`, `gallery.json`, `texts.json`) a `mail.log`. Súbory sa vytvoria samé pri prvom použití.

Priečinky `data/` a `uploads/` nie sú v gite (obsahujú osobné údaje a obsah od klienta). **Pri nahrávaní novej verzie na hosting ich neprepisuj** a pravidelne ich zálohuj.

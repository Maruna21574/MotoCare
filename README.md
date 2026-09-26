# MotoCare Pro web

PHP web pre MotoCare Pro: prezentácia služieb, online rezervácia v krokoch, e-shop s dobierkou a administrácia pre majiteľa. Bez databázy — dáta sú v JSON súboroch v `data/`.

Podstránky: `sluzby.php`, `sluzba.php`, `galeria.php`, `o-nas.php`, `kontakt.php`, `rezervacia.php`, `shop.php`, `produkt.php`, `kosik.php`, `objednavka.php`.

## Nasadenie na Websupport

1. Nahraj celý obsah priečinka na hosting do webrootu.
2. Nastav PHP 7.4+ (odporúčané PHP 8.x).
3. Priečinok `data/` musí mať právo zápisu pre PHP. Súbor `data/.htaccess` ho chráni pred priamym zobrazením (funguje na Apache, čo Websupport používa).
4. Vytvor schránku `info@motocarepro.eu` — z nej odchádzajú e-maily zákazníkom (PHP `mail()`).
5. Nastav heslo administrácie (pozri nižšie).

## Administrácia

Adresa: `/admin/` — prihlásenie heslom. Po 5 neúspešných pokusoch sa prihlásenie na 15 minút zablokuje.

- **Rezervácie** — zoznam podľa dní, potvrdenie, zrušenie, označenie ako hotové, úprava termínu, ručné pridanie rezervácie (napr. z telefonátu). Pri kolízii s inou rezerváciou alebo blokáciou systém upozorní.
- **Dostupnosť** — otváracie hodiny pre každý deň, interval časov, minimálny predstih a ako ďaleko dopredu sa dá rezervovať. Blokácie jedného času, celého dňa alebo rozsahu dní (dovolenka).
- **Objednávky** — stavy Nová / Potvrdená / Odoslaná / Vybavená / Zrušená.
- **Správy** — správy z kontaktného formulára.
- **Produkty** — pridanie, úprava, skrytie, vymazanie a poradie produktov vrátane fotky.
- **Služby** — ceny, trvanie, fotky a všetky texty služieb; pridanie a vymazanie služby.
- **Galéria** — nahrávanie, mazanie a poradie fotiek, výber fotiek pre pás na úvodnej stránke.
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

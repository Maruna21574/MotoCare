<?php
declare(strict_types=1);
// Website content: products, services, gallery, page texts and company details.
// Included from index.php after login; every POST action has already passed verifyCsrf().

/** Moves the item with $id one step up or down in an id-keyed list. */
function moveItem(array $items, string $id, string $direction): array
{
    $keys = array_keys($items);
    $index = array_search($id, $keys, true);
    $target = $direction === 'up' ? $index - 1 : $index + 1;
    if ($index === false || !isset($keys[$target])) return $items;
    [$keys[$index], $keys[$target]] = [$keys[$target], $keys[$index]];
    return array_combine($keys, array_map(static fn (string $key): array => $items[$key], $keys));
}

function uniqueSlug(string $name, array $items): string
{
    $base = slugify($name);
    if (ctype_digit($base)) $base = 'p-' . $base;
    $slug = $base;
    for ($i = 2; isset($items[$slug]); $i++) $slug = $base . '-' . $i;
    return $slug;
}

/** Image from an upload field, falling back to the current one. Flashes and redirects on a bad file. */
function imageFromForm(string $field, string $current, string $prefix, string $back): string
{
    $files = uploadedFiles($field);
    if ($files === []) return $current;
    try {
        return storeUploadedImage($files[0], $prefix);
    } catch (RuntimeException $exception) {
        flash($exception->getMessage(), 'error');
        redirect($back);
    }
}

function saveOrFail(string $file, array $data, string $back): void
{
    if (!saveJsonFile($file, $data)) { flash('Zmeny sa nepodarilo uložiť. Skontroluj práva na priečinok data.', 'error'); redirect($back); }
}

function imageSrc(string $path): string
{
    return preg_match('#^https?://#', $path) ? $path : '../' . $path;
}

function pairsToText(array $pairs): string
{
    return implode("\n", array_map(static fn (array $pair): string => $pair[0] . ' | ' . $pair[1], $pairs));
}

function textToPairs(string $text): array
{
    return array_map(static fn (string $line): array => array_map('trim', explode('|', $line, 2)) + [1 => ''], linesToList($text));
}

function moveButtons(string $action, string $id, bool $first, bool $last): string
{
    $html = '';
    foreach (['up' => ['↑', 'Posunúť vyššie', $first], 'down' => ['↓', 'Posunúť nižšie', $last]] as $direction => [$arrow, $title, $disabled]) {
        $html .= '<form method="post">' . csrfField() . '<input type="hidden" name="action" value="' . e($action) . '"><input type="hidden" name="id" value="' . e($id) . '"><input type="hidden" name="direction" value="' . $direction . '"><button class="btn btn--small btn--icon" type="submit" title="' . e($title) . '"' . ($disabled ? ' disabled' : '') . '>' . $arrow . '</button></form>';
    }
    return $html;
}

// ---------------------------------------------------------------- Actions

if ($action === 'product_save') {
    $id = post('id', 60);
    $items = $allProducts;
    if ($id !== '' && !isset($items[$id])) { flash('Produkt sa nenašiel.', 'error'); redirect('p=products'); }
    $back = 'p=product' . ($id !== '' ? '&id=' . rawurlencode($id) : '');
    $name = post('name', 120);
    $price = (int) round((float) str_replace(',', '.', post('price', 20)));
    if ($name === '' || $price <= 0) { flash('Zadaj názov produktu a cenu.', 'error'); redirect($back); }
    $current = $items[$id] ?? [];
    $image = imageFromForm('image', (string) ($current['image'] ?? ''), 'produkt-' . $name, $back);
    if ($image === '') { flash('Pridaj fotku produktu.', 'error'); redirect($back); }
    $product = array_replace($current, [
        'name' => $name,
        'brand' => post('brand', 80),
        'price' => $price,
        'tag' => post('tag', 40),
        'volume' => max(0, (int) post('volume', 10)),
        'form' => post('form', 60),
        'short' => post('short', 300),
        'description' => post('description', 800),
        'features' => linesToList(post('features', 2000)),
        'long' => textToParagraphs(post('long', 6000)),
        'howto' => linesToList(post('howto', 3000)),
        'image' => $image,
        'hidden' => empty($_POST['visible']),
    ]);
    if ($id === '') $id = uniqueSlug($name, $items);
    $oldImage = (string) ($current['image'] ?? '');
    $items[$id] = $product;
    saveOrFail($productsFile, $items, $back);
    if ($oldImage !== $image) deleteUploadIfUnused($oldImage);
    flash('Produkt „' . $name . '“ je uložený.');
    redirect('p=product&id=' . rawurlencode($id));
}

if ($action === 'product_delete') {
    $id = post('id', 60);
    $items = $allProducts;
    if (isset($items[$id])) {
        $image = (string) $items[$id]['image'];
        $name = $items[$id]['name'];
        unset($items[$id]);
        saveOrFail($productsFile, $items, 'p=products');
        deleteUploadIfUnused($image);
        flash('Produkt „' . $name . '“ je vymazaný.');
    }
    redirect('p=products');
}

if ($action === 'product_move') {
    saveOrFail($productsFile, moveItem($allProducts, post('id', 60), post('direction', 4)), 'p=products');
    redirect('p=products');
}

if ($action === 'service_save') {
    $id = post('id', 60);
    $items = $services;
    if ($id !== '' && !isset($items[$id])) { flash('Služba sa nenašla.', 'error'); redirect('p=services'); }
    $back = 'p=service' . ($id !== '' ? '&id=' . rawurlencode($id) : '');
    $name = post('name', 120);
    $price = (int) round((float) str_replace(',', '.', post('price', 20)));
    $minutes = (int) post('minutes', 10);
    if ($name === '' || $price <= 0) { flash('Zadaj názov služby a cenu.', 'error'); redirect($back); }
    if ($minutes < 15 || $minutes > 24 * 60) { flash('Trvanie musí byť od 15 minút do 24 hodín.', 'error'); redirect($back); }
    $current = $items[$id] ?? [];
    $image = imageFromForm('image', (string) ($current['image'] ?? ''), 'sluzba-' . $name, $back);
    if ($image === '') { flash('Pridaj fotku služby.', 'error'); redirect($back); }
    $service = array_replace($current, [
        'name' => $name,
        'label' => post('label', 80),
        'badge' => post('badge', 40),
        'price' => $price,
        'minutes' => $minutes,
        'short' => post('short', 300),
        'description' => post('description', 800),
        'features' => linesToList(post('features', 2000)),
        'details' => linesToList(post('details', 3000)),
        'intro' => textToParagraphs(post('intro', 8000)),
        'why' => textToPairs(post('why', 4000)),
        'ideal' => linesToList(post('ideal', 2000)),
        'image' => $image,
    ]);
    if ($id === '') $id = uniqueSlug($name, $items);
    $oldImage = (string) ($current['image'] ?? '');
    $items[$id] = $service;
    saveOrFail($servicesFile, $items, $back);
    if ($oldImage !== $image) deleteUploadIfUnused($oldImage);
    flash('Služba „' . $name . '“ je uložená.');
    redirect('p=service&id=' . rawurlencode($id));
}

if ($action === 'service_delete') {
    $id = post('id', 60);
    $items = $services;
    if (isset($items[$id])) {
        $upcoming = array_filter(readJsonFile($bookingsFile), static fn (array $booking): bool => ($booking['service'] ?? '') === $id && $booking['date'] >= date('Y-m-d') && in_array(bookingStatus($booking), ['pending', 'confirmed'], true));
        if ($upcoming !== []) { flash('Službu nemôžeš vymazať, má ' . count($upcoming) . ' nadchádzajúce rezervácie. Najprv ich presuň na inú službu alebo zruš.', 'error'); redirect('p=service&id=' . rawurlencode($id)); }
        if (count($items) === 1) { flash('Na webe musí zostať aspoň jedna služba.', 'error'); redirect('p=services'); }
        $image = (string) $items[$id]['image'];
        $name = $items[$id]['name'];
        unset($items[$id]);
        saveOrFail($servicesFile, $items, 'p=services');
        deleteUploadIfUnused($image);
        flash('Služba „' . $name . '“ je vymazaná.');
    }
    redirect('p=services');
}

if ($action === 'service_move') {
    saveOrFail($servicesFile, moveItem($services, post('id', 60), post('direction', 4)), 'p=services');
    redirect('p=services');
}

if ($action === 'gallery_upload') {
    $files = uploadedFiles('photos');
    if ($files === []) { flash('Vyber aspoň jednu fotku.', 'error'); redirect('p=gallery'); }
    $added = 0;
    $errors = [];
    foreach ($files as $file) {
        try {
            $gallery[] = ['src' => storeUploadedImage($file, 'galeria'), 'slider' => true];
            $added++;
        } catch (RuntimeException $exception) {
            $errors[] = $file['name'] . ': ' . $exception->getMessage();
        }
    }
    if ($added > 0) saveOrFail($galleryFile, array_values($gallery), 'p=gallery');
    if ($added > 0) flash('Pridané fotky: ' . $added . '.');
    if ($errors !== []) flash(implode("\n", $errors), 'error');
    redirect('p=gallery');
}

if (in_array($action, ['gallery_delete', 'gallery_move', 'gallery_slider'], true)) {
    $index = (int) post('id', 10);
    if (!isset($gallery[$index]) || $gallery[$index]['src'] !== post('src', 500)) { flash('Galéria sa medzitým zmenila, skús to znova.', 'error'); redirect('p=gallery'); }
    if ($action === 'gallery_delete') {
        $src = $gallery[$index]['src'];
        array_splice($gallery, $index, 1);
        saveOrFail($galleryFile, $gallery, 'p=gallery');
        deleteUploadIfUnused($src);
        flash('Fotka je odstránená z galérie.');
    } elseif ($action === 'gallery_move') {
        $target = post('direction', 4) === 'up' ? $index - 1 : $index + 1;
        if (isset($gallery[$target])) [$gallery[$index], $gallery[$target]] = [$gallery[$target], $gallery[$index]];
        saveOrFail($galleryFile, $gallery, 'p=gallery');
    } else {
        $gallery[$index]['slider'] = empty($gallery[$index]['slider']);
        saveOrFail($galleryFile, $gallery, 'p=gallery');
    }
    redirect('p=gallery#foto-' . $index);
}

if ($action === 'texts_save') {
    $pageKey = post('page', 30);
    $definition = textRegistry()[$pageKey] ?? null;
    if ($definition === null) redirect('p=texts');
    $back = 'p=texts&page=' . rawurlencode($pageKey);
    $overrides = readJsonFile($textsFile);
    $replaced = [];
    foreach ($definition['fields'] as $field => [$label, $type, $default]) {
        $key = $pageKey . '.' . $field;
        if ($type === 'image') {
            $current = textRaw($key);
            if (!empty($_POST['reset'][$field])) {
                unset($overrides[$key]);
            } elseif (($_FILES['image']['error'][$field] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $file = ['name' => $_FILES['image']['name'][$field], 'type' => $_FILES['image']['type'][$field], 'tmp_name' => $_FILES['image']['tmp_name'][$field], 'error' => $_FILES['image']['error'][$field], 'size' => $_FILES['image']['size'][$field]];
                try {
                    $overrides[$key] = storeUploadedImage($file, $pageKey . '-' . $field);
                } catch (RuntimeException $exception) {
                    flash($label . ': ' . $exception->getMessage(), 'error');
                    continue;
                }
            }
            if (($overrides[$key] ?? $default) !== $current) $replaced[] = $current;
            continue;
        }
        $value = str_replace("\r\n", "\n", trim(strip_tags((string) ($_POST['text'][$field] ?? ''))));
        $value = mb_substr($value, 0, 12000);
        if ($value === '' || $value === $default) unset($overrides[$key]);
        else $overrides[$key] = $value;
    }
    saveOrFail($textsFile, $overrides, $back);
    foreach ($replaced as $path) deleteUploadIfUnused($path);
    flash('Texty stránky „' . $definition['label'] . '“ sú uložené.');
    redirect($back);
}

if ($action === 'site_save') {
    $fields = ['name' => 80, 'phone' => 40, 'email' => 120, 'street' => 120, 'zip' => 20, 'city' => 80, 'country' => 60, 'instagram' => 300, 'facebook' => 300, 'tagline' => 200, 'description' => 300, 'map_embed' => 1500];
    $data = [];
    foreach ($fields as $field => $max) $data[$field] = post($field, $max);
    // The map may be pasted as the whole <iframe> code from Google Maps; keep just its address.
    if (preg_match('#<iframe[^>]+src="([^"]+)"#', (string) ($_POST['map_embed'] ?? ''), $match)) $data['map_embed'] = html_entity_decode($match[1]);
    $errors = [];
    if ($data['name'] === '') $errors[] = 'Zadaj názov firmy.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail nie je platný.';
    if ($data['phone'] === '') $errors[] = 'Zadaj telefón.';
    foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'map_embed' => 'Mapa'] as $field => $label) {
        if ($data[$field] !== '' && !preg_match('#^https://#', $data[$field])) $errors[] = $label . ': odkaz musí začínať https://';
    }
    if ($errors !== []) { flash(implode("\n", $errors), 'error'); redirect('p=settings'); }
    saveOrFail($siteFile, $data, 'p=settings');
    flash('Údaje o firme sú uložené.');
    redirect('p=settings');
}

// ---------------------------------------------------------------- Pages

if ($page === 'products') {
    renderHeader('Produkty', 'products');
    ?>
    <div class="page-head">
        <div><h1>Produkty</h1><p class="muted">Produkty v e-shope. Poradie tu je rovnaké ako na webe.</p></div>
        <a class="btn btn--primary" href="index.php?p=product">+ Nový produkt</a>
    </div>
    <?php if ($allProducts === []): ?><p class="card empty">Zatiaľ žiadne produkty.</p><?php endif; ?>
    <?php $keys = array_keys($allProducts); foreach ($allProducts as $id => $product): ?>
    <article class="item<?= !empty($product['hidden']) ? ' is-hidden' : '' ?>">
        <img class="item-thumb" src="<?= e(imageSrc($product['image'])) ?>" alt="">
        <div class="item-main">
            <p class="item-title"><a href="index.php?p=product&id=<?= e(rawurlencode($id)) ?>"><?= e($product['name']) ?></a><?php if (!empty($product['hidden'])): ?> <span class="badge badge--cancelled">Skrytý</span><?php endif; ?></p>
            <p class="muted small"><?= e(implode(' · ', array_filter([$product['brand'] ?? '', $product['tag'] ?? '', !empty($product['volume']) ? $product['volume'] . ' ml' : '']))) ?></p>
        </div>
        <strong class="item-price"><?= (int) $product['price'] ?> €</strong>
        <div class="item-actions">
            <?= moveButtons('product_move', $id, $id === $keys[0], $id === end($keys)) ?>
            <a class="btn btn--small" href="index.php?p=product&id=<?= e(rawurlencode($id)) ?>">Upraviť</a>
        </div>
    </article>
    <?php endforeach; ?>
    <?php
    renderFooter();
    exit;
}

if ($page === 'product') {
    $id = cleanText($_GET['id'] ?? '', 60);
    if ($id !== '' && !isset($allProducts[$id])) { flash('Produkt sa nenašiel.', 'error'); redirect('p=products'); }
    $product = array_replace(['name' => '', 'brand' => '', 'price' => '', 'tag' => '', 'volume' => '', 'form' => '', 'short' => '', 'description' => '', 'features' => [], 'long' => [], 'howto' => [], 'image' => '', 'hidden' => false], $allProducts[$id] ?? []);
    renderHeader($id === '' ? 'Nový produkt' : $product['name'], 'products');
    ?>
    <div class="page-head">
        <div><a class="back" href="index.php?p=products">← Produkty</a><h1><?= $id === '' ? 'Nový produkt' : e($product['name']) ?></h1></div>
        <?php if ($id !== ''): ?><a class="btn" href="../produkt.php?slug=<?= e(rawurlencode($id)) ?>" target="_blank">Zobraziť na webe ↗</a><?php endif; ?>
    </div>
    <form class="form" method="post" enctype="multipart/form-data">
        <?= csrfField() ?><input type="hidden" name="action" value="product_save"><input type="hidden" name="id" value="<?= e($id) ?>">
        <div class="columns columns--wide">
            <div class="card">
                <h2>Základ</h2>
                <label>Názov produktu<input type="text" name="name" value="<?= e($product['name']) ?>" maxlength="120" required></label>
                <div class="grid grid--3">
                    <label>Cena (€)<input type="number" name="price" value="<?= e($product['price']) ?>" min="1" step="1" required></label>
                    <label>Značka<input type="text" name="brand" value="<?= e($product['brand']) ?>" maxlength="80"></label>
                    <label>Štítok na fotke <small>napr. Novinka</small><input type="text" name="tag" value="<?= e($product['tag']) ?>" maxlength="40"></label>
                    <label>Objem (ml) <small>nepovinné</small><input type="number" name="volume" value="<?= e($product['volume']) ?>" min="0" step="1"></label>
                    <label class="span-2">Balenie <small>napr. Sprej</small><input type="text" name="form" value="<?= e($product['form']) ?>" maxlength="60"></label>
                </div>
                <label>Krátky popis <small>na karte produktu</small><textarea name="short" rows="2" maxlength="300"><?= e($product['short']) ?></textarea></label>
                <label>Úvodný popis <small>pod názvom v detaile</small><textarea name="description" rows="3" maxlength="800"><?= e($product['description']) ?></textarea></label>
                <label class="check"><input type="checkbox" name="visible" value="1" <?= empty($product['hidden']) ? 'checked' : '' ?>> Zobrazovať produkt na webe</label>
            </div>
            <div class="card">
                <h2>Fotka</h2>
                <?php if ($product['image'] !== ''): ?><img class="image-preview" src="<?= e(imageSrc($product['image'])) ?>" alt=""><?php endif; ?>
                <label><?= $product['image'] === '' ? 'Nahrať fotku' : 'Nahradiť fotku' ?> <small>JPG, PNG alebo WEBP, najlepšie na bielom pozadí</small><input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" <?= $product['image'] === '' ? 'required' : '' ?>></label>
            </div>
        </div>
        <div class="card">
            <h2>Detail produktu</h2>
            <label>Výhody <small>jedna na riadok</small><textarea name="features" rows="4"><?= e(implode("\n", $product['features'])) ?></textarea></label>
            <label>Na čo sa hodí <small>odseky oddeľ prázdnym riadkom</small><textarea name="long" rows="7"><?= e(implode("\n\n", $product['long'])) ?></textarea></label>
            <label>Ako používať <small>jeden krok na riadok</small><textarea name="howto" rows="4"><?= e(implode("\n", $product['howto'])) ?></textarea></label>
        </div>
        <div class="form-actions form-actions--sticky"><button class="btn btn--primary" type="submit">Uložiť produkt</button><a class="btn" href="index.php?p=products">Späť</a></div>
    </form>
    <?php if ($id !== ''): ?>
    <form class="danger-zone" method="post" data-confirm="Naozaj vymazať produkt „<?= e($product['name']) ?>“?">
        <?= csrfField() ?><input type="hidden" name="action" value="product_delete"><input type="hidden" name="id" value="<?= e($id) ?>">
        <span>Vymazanie produkt natrvalo odstráni z webu. Ak ho chceš len dočasne schovať, zruš „Zobrazovať produkt na webe“.</span>
        <button class="btn btn--danger" type="submit">Vymazať produkt</button>
    </form>
    <?php endif; ?>
    <?php
    renderFooter();
    exit;
}

if ($page === 'services') {
    renderHeader('Služby', 'services');
    ?>
    <div class="page-head">
        <div><h1>Služby</h1><p class="muted">Služby na webe a v rezervačnom formulári. Poradie tu je rovnaké ako na webe.</p></div>
        <a class="btn btn--primary" href="index.php?p=service">+ Nová služba</a>
    </div>
    <?php $keys = array_keys($services); foreach ($services as $id => $service): ?>
    <article class="item">
        <img class="item-thumb" src="<?= e(imageSrc($service['image'])) ?>" alt="">
        <div class="item-main">
            <p class="item-title"><a href="index.php?p=service&id=<?= e(rawurlencode($id)) ?>"><?= e($service['name']) ?></a><?php if (($service['badge'] ?? '') !== ''): ?> <span class="badge badge--new"><?= e($service['badge']) ?></span><?php endif; ?></p>
            <p class="muted small"><?= e($service['label']) ?> · <?= e(durationLabel((int) $service['minutes'])) ?></p>
        </div>
        <strong class="item-price">od <?= (int) $service['price'] ?> €</strong>
        <div class="item-actions">
            <?= moveButtons('service_move', $id, $id === $keys[0], $id === end($keys)) ?>
            <a class="btn btn--small" href="index.php?p=service&id=<?= e(rawurlencode($id)) ?>">Upraviť</a>
        </div>
    </article>
    <?php endforeach; ?>
    <?php
    renderFooter();
    exit;
}

if ($page === 'service') {
    $id = cleanText($_GET['id'] ?? '', 60);
    if ($id !== '' && !isset($services[$id])) { flash('Služba sa nenašla.', 'error'); redirect('p=services'); }
    $service = array_replace(['name' => '', 'label' => '', 'badge' => '', 'price' => '', 'minutes' => 120, 'short' => '', 'description' => '', 'features' => [], 'details' => [], 'intro' => [], 'why' => [], 'ideal' => [], 'image' => ''], $services[$id] ?? []);
    renderHeader($id === '' ? 'Nová služba' : $service['name'], 'services');
    ?>
    <div class="page-head">
        <div><a class="back" href="index.php?p=services">← Služby</a><h1><?= $id === '' ? 'Nová služba' : e($service['name']) ?></h1></div>
        <?php if ($id !== ''): ?><a class="btn" href="../sluzba.php?slug=<?= e(rawurlencode($id)) ?>" target="_blank">Zobraziť na webe ↗</a><?php endif; ?>
    </div>
    <form class="form" method="post" enctype="multipart/form-data">
        <?= csrfField() ?><input type="hidden" name="action" value="service_save"><input type="hidden" name="id" value="<?= e($id) ?>">
        <div class="columns columns--wide">
            <div class="card">
                <h2>Základ</h2>
                <label>Názov služby<input type="text" name="name" value="<?= e($service['name']) ?>" maxlength="120" required></label>
                <div class="grid grid--3">
                    <label>Cena od (€)<input type="number" name="price" value="<?= e($service['price']) ?>" min="1" step="1" required></label>
                    <label>Trvanie (minúty) <small>pre rezervácie</small><input type="number" name="minutes" value="<?= e($service['minutes']) ?>" min="15" max="1440" step="15" required></label>
                    <label>Odznak na fotke <small>nepovinné</small><input type="text" name="badge" value="<?= e($service['badge']) ?>" maxlength="40" placeholder="napr. Najžiadanejšie"></label>
                </div>
                <label>Podnadpis <small>napr. Perfektný základ</small><input type="text" name="label" value="<?= e($service['label']) ?>" maxlength="80"></label>
                <label>Krátky popis <small>v rezervácii a pri ďalších službách</small><textarea name="short" rows="2" maxlength="300"><?= e($service['short']) ?></textarea></label>
                <label>Popis <small>v zozname služieb a hore v detaile</small><textarea name="description" rows="3" maxlength="800"><?= e($service['description']) ?></textarea></label>
            </div>
            <div class="card">
                <h2>Fotka</h2>
                <?php if ($service['image'] !== ''): ?><img class="image-preview image-preview--cover" src="<?= e(imageSrc($service['image'])) ?>" alt=""><?php endif; ?>
                <label><?= $service['image'] === '' ? 'Nahrať fotku' : 'Nahradiť fotku' ?> <small>na šírku, aspoň 1600 px</small><input type="file" name="image" accept="image/jpeg,image/png,image/webp" <?= $service['image'] === '' ? 'required' : '' ?>></label>
            </div>
        </div>
        <div class="card">
            <h2>Obsah služby</h2>
            <div class="grid grid--2">
                <label>Čo je v cene <small>jedna položka na riadok</small><textarea name="features" rows="6"><?= e(implode("\n", $service['features'])) ?></textarea></label>
                <label>Pre koho je vhodná <small>jedna položka na riadok</small><textarea name="ideal" rows="6"><?= e(implode("\n", $service['ideal'])) ?></textarea></label>
            </div>
            <label>O službe <small>odseky oddeľ prázdnym riadkom, prvý odsek je zvýraznený</small><textarea name="intro" rows="9"><?= e(implode("\n\n", $service['intro'])) ?></textarea></label>
            <label>Čo ti služba prinesie <small>jeden bod na riadok v tvare: Nadpis | text</small><textarea name="why" rows="5"><?= e(pairsToText($service['why'])) ?></textarea></label>
            <label>Ako prebieha <small>jeden krok na riadok</small><textarea name="details" rows="5"><?= e(implode("\n", $service['details'])) ?></textarea></label>
        </div>
        <div class="form-actions form-actions--sticky"><button class="btn btn--primary" type="submit">Uložiť službu</button><a class="btn" href="index.php?p=services">Späť</a></div>
    </form>
    <?php if ($id !== ''): ?>
    <form class="danger-zone" method="post" data-confirm="Naozaj vymazať službu „<?= e($service['name']) ?>“?">
        <?= csrfField() ?><input type="hidden" name="action" value="service_delete"><input type="hidden" name="id" value="<?= e($id) ?>">
        <span>Službu s nadchádzajúcimi rezerváciami vymazať nejde. Staré rezervácie si názov služby ponechajú.</span>
        <button class="btn btn--danger" type="submit">Vymazať službu</button>
    </form>
    <?php endif; ?>
    <?php
    renderFooter();
    exit;
}

if ($page === 'gallery') {
    renderHeader('Galéria', 'gallery');
    $last = count($gallery) - 1;
    ?>
    <div class="page-head"><div><h1>Galéria</h1><p class="muted">Fotky na stránke Galéria. Fotky označené „Na úvode“ sa zobrazujú aj v pase fotiek na úvodnej stránke.</p></div></div>
    <form class="card upload-card" method="post" enctype="multipart/form-data">
        <?= csrfField() ?><input type="hidden" name="action" value="gallery_upload">
        <label>Pridať fotky <small>môžeš vybrať viac naraz · JPG, PNG alebo WEBP</small><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required></label>
        <button class="btn btn--primary" type="submit">Nahrať do galérie</button>
    </form>
    <div class="gallery-admin">
        <?php foreach ($gallery as $index => $item): $fields = csrfField() . '<input type="hidden" name="id" value="' . $index . '"><input type="hidden" name="src" value="' . e($item['src']) . '">'; ?>
        <figure class="gallery-tile<?= empty($item['slider']) ? '' : ' is-slider' ?>" id="foto-<?= $index ?>">
            <a href="<?= e(imageSrc($item['src'])) ?>" target="_blank"><img src="<?= e(imageSrc($item['src'])) ?>" alt="" loading="lazy"></a>
            <span class="gallery-tile-num"><?= $index + 1 ?></span>
            <figcaption>
                <form method="post"><?= $fields ?><input type="hidden" name="action" value="gallery_slider"><button class="btn btn--small<?= empty($item['slider']) ? '' : ' btn--on' ?>" type="submit" title="Zobrazovať na úvodnej stránke"><?= empty($item['slider']) ? 'Nie na úvode' : '✓ Na úvode' ?></button></form>
                <span class="gallery-tile-move">
                    <form method="post"><?= $fields ?><input type="hidden" name="action" value="gallery_move"><input type="hidden" name="direction" value="up"><button class="btn btn--small btn--icon" type="submit" title="Posunúť dopredu" <?= $index === 0 ? 'disabled' : '' ?>>←</button></form>
                    <form method="post"><?= $fields ?><input type="hidden" name="action" value="gallery_move"><input type="hidden" name="direction" value="down"><button class="btn btn--small btn--icon" type="submit" title="Posunúť dozadu" <?= $index === $last ? 'disabled' : '' ?>>→</button></form>
                    <form method="post" data-confirm="Odstrániť fotku z galérie?"><?= $fields ?><input type="hidden" name="action" value="gallery_delete"><button class="btn btn--small btn--ghost-danger btn--icon" type="submit" title="Odstrániť">×</button></form>
                </span>
            </figcaption>
        </figure>
        <?php endforeach; ?>
    </div>
    <?php
    renderFooter();
    exit;
}

if ($page === 'texts') {
    $registry = textRegistry();
    $pageKey = cleanText($_GET['page'] ?? 'home', 30);
    if (!isset($registry[$pageKey])) $pageKey = 'home';
    $definition = $registry[$pageKey];
    $overrides = readJsonFile($textsFile);
    $hints = [
        'title' => '*slovo* = oranžové zvýraznenie, nový riadok = zalomenie nadpisu',
        'paragraphs' => 'odseky oddeľ prázdnym riadkom',
        'rich' => 'odseky oddeľ prázdnym riadkom, podnadpis začni „## “',
        'list' => 'jedna položka na riadok',
        'pairs' => 'jedna položka na riadok v tvare: Nadpis | text',
    ];
    $rows = ['line' => 1, 'title' => 2, 'text' => 3, 'paragraphs' => 8, 'rich' => 14, 'list' => 6, 'pairs' => 6];
    renderHeader('Texty', 'texts');
    ?>
    <div class="page-head"><div><h1>Texty na webe</h1><p class="muted">Vyber stránku a uprav jej texty. Keď pole vymažeš, vráti sa pôvodný text. V textoch môžeš použiť {phone}, {email} a {address}.</p></div></div>
    <nav class="tabs"><?php foreach ($registry as $key => $item): ?><a class="<?= $key === $pageKey ? 'is-active' : '' ?>" href="index.php?p=texts&page=<?= e($key) ?>"><?= e($item['label']) ?></a><?php endforeach; ?></nav>
    <form class="card form" method="post" enctype="multipart/form-data">
        <?= csrfField() ?><input type="hidden" name="action" value="texts_save"><input type="hidden" name="page" value="<?= e($pageKey) ?>">
        <div class="card-head"><h2><?= e($definition['label']) ?></h2><a href="../<?= e($definition['url']) ?>" target="_blank">Otvoriť stránku ↗</a></div>
        <?php foreach ($definition['fields'] as $field => [$label, $type, $default]): $key = $pageKey . '.' . $field; $changed = isset($overrides[$key]); ?>
        <?php if ($type === 'image'): ?>
        <div class="text-image">
            <img src="<?= e(imageSrc(textRaw($key))) ?>" alt="">
            <div>
                <p class="text-label"><?= e($label) ?><?= $changed ? ' <span class="badge badge--new">Upravené</span>' : '' ?></p>
                <label><small>Nahradiť fotku</small><input type="file" name="image[<?= e($field) ?>]" accept="image/jpeg,image/png,image/webp"></label>
                <?php if ($changed): ?><label class="check"><input type="checkbox" name="reset[<?= e($field) ?>]" value="1"> Vrátiť pôvodnú fotku</label><?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <label><span class="text-label"><?= e($label) ?><?= $changed ? ' <span class="badge badge--new">Upravené</span>' : '' ?><?= isset($hints[$type]) ? ' <small>' . e($hints[$type]) . '</small>' : '' ?></span>
            <?php if ($type === 'line'): ?><input type="text" name="text[<?= e($field) ?>]" value="<?= e(textRaw($key)) ?>" placeholder="<?= e($default) ?>">
            <?php else: ?><textarea name="text[<?= e($field) ?>]" rows="<?= $rows[$type] ?>" placeholder="<?= e($default) ?>"><?= e(textRaw($key)) ?></textarea><?php endif; ?>
        </label>
        <?php endif; ?>
        <?php endforeach; ?>
        <div class="form-actions form-actions--sticky"><button class="btn btn--primary" type="submit">Uložiť texty</button></div>
    </form>
    <?php
    renderFooter();
    exit;
}

/** Company details form shown on the settings page. */
function renderSiteForm(): void
{
    global $site;
    ?>
    <form class="card form" method="post">
        <?= csrfField() ?><input type="hidden" name="action" value="site_save">
        <h2>Údaje o firme</h2>
        <p class="muted small" style="margin-bottom:14px">Zobrazujú sa v hlavičke, pätičke, na kontakte aj v e-mailoch.</p>
        <div class="grid grid--2">
            <label>Názov<input type="text" name="name" value="<?= e($site['name']) ?>" required></label>
            <label>Telefón<input type="text" name="phone" value="<?= e($site['phone']) ?>" required></label>
            <label class="span-2">E-mail<input type="email" name="email" value="<?= e($site['email']) ?>" required></label>
            <label class="span-2">Ulica a číslo<input type="text" name="street" value="<?= e($site['street']) ?>"></label>
            <label>PSČ<input type="text" name="zip" value="<?= e($site['zip']) ?>"></label>
            <label>Mesto<input type="text" name="city" value="<?= e($site['city']) ?>"></label>
            <label class="span-2">Krajina<input type="text" name="country" value="<?= e($site['country']) ?>"></label>
            <label class="span-2">Instagram (odkaz)<input type="url" name="instagram" value="<?= e($site['instagram']) ?>"></label>
            <label class="span-2">Facebook (odkaz)<input type="url" name="facebook" value="<?= e($site['facebook']) ?>"></label>
        </div>
        <label>Slogan v pätičke<input type="text" name="tagline" value="<?= e($site['tagline']) ?>"></label>
        <label>Popis webu pre Google <small>zobrazí sa vo výsledkoch vyhľadávania</small><textarea name="description" rows="2"><?= e($site['description']) ?></textarea></label>
        <label>Mapa na kontakte <small>Google Mapy → Zdieľať → Vložiť mapu → skopíruj HTML sem</small><textarea name="map_embed" rows="3"><?= e($site['map_embed']) ?></textarea></label>
        <div class="form-actions"><button class="btn btn--primary" type="submit">Uložiť údaje</button></div>
    </form>
    <?php
}

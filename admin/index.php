<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$page = cleanText($_GET['p'] ?? 'bookings', 20);
$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? cleanText($_POST['action'] ?? '', 40) : '';

// ---------------------------------------------------------------- Login

if (!isLoggedIn()) {
    $error = '';
    if ($action === 'login') {
        verifyCsrf();
        $lock = loginLockRemaining();
        if ($lock > 0) {
            $error = 'Priveľa neúspešných pokusov. Skús to znova o ' . (int) ceil($lock / 60) . ' min.';
        } elseif (adminPasswordHash() !== '' && password_verify((string) ($_POST['password'] ?? ''), adminPasswordHash())) {
            recordLoginAttempt(true);
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            $_SESSION['admin_seen'] = time();
            redirect();
        } else {
            recordLoginAttempt(false);
            $error = adminPasswordHash() === '' ? 'Heslo administrácie ešte nie je nastavené.' : 'Nesprávne heslo.';
        }
    }
    renderHeader('Prihlásenie');
    ?>
    <form class="login-card" method="post">
        <img src="../auto_care_logo.png" alt="MotoCare Pro">
        <h1>Administrácia</h1>
        <?php if ($error !== ''): ?><p class="flash flash--error"><?= e($error) ?></p><?php endif; ?>
        <?= csrfField() ?><input type="hidden" name="action" value="login">
        <label>Heslo<input type="password" name="password" autocomplete="current-password" required autofocus></label>
        <button class="btn btn--primary" type="submit">Prihlásiť sa</button>
    </form>
    <?php
    renderFooter();
    exit;
}

// ---------------------------------------------------------------- Actions

$formBooking = null;
$conflicts = [];

if ($action !== '') {
    verifyCsrf();

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        redirect();
    }

    if ($action === 'booking_status') {
        $id = post('id', 40);
        $status = post('status', 20);
        if (!isset(bookingStatuses()[$status])) redirect('p=bookings');
        $result = updateJsonFile($bookingsFile, static function (array &$bookings) use ($id, $status): ?array {
            foreach ($bookings as &$booking) {
                if (($booking['id'] ?? '') !== $id) continue;
                $old = bookingStatus($booking);
                $booking['status'] = $status;
                $booking['updated_at'] = date('c');
                return ['booking' => $booking, 'old' => $old];
            }
            return null;
        });
        if ($result === null) { flash('Rezervácia sa nenašla.', 'error'); redirect('p=bookings'); }
        $message = 'Rezervácia ' . $id . ': ' . bookingStatuses()[$status] . '.';
        if ($result['old'] !== $status && !empty($_POST['notify'])) {
            if ($result['booking']['email'] === '') $message .= ' Zákazník nemá e-mail, nič sa neposlalo.';
            else $message .= sendBookingMail($result['booking'], $status) ? ' Klientovi odišiel e-mail.' : ' E-mail sa nepodarilo odoslať.';
        }
        flash($message);
        $return = (string) ($_POST['return'] ?? '');
        redirect(preg_match('/^[A-Za-z0-9=&%_.\-]*$/', $return) && $return !== '' ? $return : 'p=bookings');
    }

    if ($action === 'booking_delete') {
        $id = post('id', 40);
        updateJsonFile($bookingsFile, static function (array &$bookings) use ($id): void {
            $bookings = array_values(array_filter($bookings, static fn (array $booking): bool => ($booking['id'] ?? '') !== $id));
        });
        flash('Rezervácia ' . $id . ' bola vymazaná.');
        redirect('p=bookings');
    }

    if ($action === 'booking_save') {
        $id = post('id', 40);
        $serviceId = post('service', 50);
        $form = [
            'id' => $id, 'service' => $serviceId, 'date' => post('date', 10), 'time' => post('time', 5),
            'duration' => (int) ($_POST['duration'] ?? 0), 'name' => post('name', 100), 'phone' => post('phone', 50),
            'email' => post('email', 120), 'bike' => post('bike', 120), 'note' => post('note', 800),
            'admin_note' => post('admin_note', 800), 'status' => post('status', 20),
            'pickup' => !empty($_POST['pickup']), 'pickup_address' => post('pickup_address', 240),
        ];
        if (!$form['pickup']) $form['pickup_address'] = '';
        $notify = !empty($_POST['notify']);
        $customerMessage = post('message', 800);
        $errors = [];
        if (!isset($services[$serviceId])) $errors[] = 'Vyber službu.';
        if (!validDate($form['date'])) $errors[] = 'Zadaj platný dátum.';
        if (!preg_match('/^\d{2}:\d{2}$/', $form['time'])) $errors[] = 'Zadaj čas začiatku.';
        if ($form['name'] === '') $errors[] = 'Zadaj meno zákazníka.';
        if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail nie je platný.';
        if ($form['pickup'] && $form['pickup_address'] === '') $errors[] = 'Zadaj adresu vyzdvihnutia.';
        if (!isset(bookingStatuses()[$form['status']])) $form['status'] = 'confirmed';
        if ($errors !== []) {
            flash(implode("\n", $errors), 'error');
            $formBooking = $form;
            $page = 'booking';
        } else {
            $service = $services[$serviceId];
            if ($form['duration'] < 15 || $form['duration'] > 24 * 60) $form['duration'] = occupiedMinutes($form['date'], (int) $service['minutes']) ?? (int) $service['minutes'];
            $form['end_time'] = formatTime(min(24 * 60 - 1, minutesFromTime($form['time']) + $form['duration']));
            $blocks = readJsonFile($blocksFile);
            $force = !empty($_POST['force']);
            $result = updateJsonFile($bookingsFile, static function (array &$bookings) use ($form, $service, $blocks, $force): array {
                $index = null;
                foreach ($bookings as $key => $booking) if ($form['id'] !== '' && ($booking['id'] ?? '') === $form['id']) $index = $key;
                if ($form['status'] !== 'cancelled' && !$force) {
                    $found = bookingConflicts($form['date'], $form['time'], $form['duration'], $bookings, $blocks, $form['id'] !== '' ? $form['id'] : null);
                    if ($found !== []) return ['conflicts' => $found];
                }
                $old = $index !== null ? $bookings[$index] : null;
                $record = array_merge($old ?? ['id' => newId('MC'), 'created_at' => date('c'), 'source' => 'admin'], [
                    'status' => $form['status'], 'service' => $form['service'], 'service_name' => $service['name'], 'price' => $old !== null && $old['service'] === $form['service'] ? $old['price'] : $service['price'],
                    'duration' => $form['duration'], 'date' => $form['date'], 'time' => $form['time'], 'end_time' => $form['end_time'],
                    'name' => $form['name'], 'phone' => $form['phone'], 'email' => $form['email'], 'bike' => $form['bike'], 'note' => $form['note'],
                    'pickup' => $form['pickup'], 'pickup_address' => $form['pickup_address'],
                    'admin_note' => $form['admin_note'], 'updated_at' => date('c'),
                ]);
                if ($index !== null) $bookings[$index] = $record;
                else $bookings[] = $record;
                return ['booking' => $record, 'old' => $old];
            });
            if (isset($result['conflicts'])) {
                $conflicts = $result['conflicts'];
                $formBooking = $form;
                $page = 'booking';
            } else {
                $booking = $result['booking'];
                $old = $result['old'];
                $type = null;
                if ($old === null) {
                    $type = ['confirmed' => 'confirmed', 'pending' => 'received', 'done' => 'done'][$booking['status']] ?? null;
                } elseif (bookingStatus($old) !== $booking['status']) {
                    $type = $booking['status'];
                } elseif ($booking['status'] !== 'cancelled' && ($old['date'] !== $booking['date'] || $old['time'] !== $booking['time'] || $old['service'] !== $booking['service'] || $customerMessage !== '')) {
                    $type = 'changed';
                }
                $message = 'Rezervácia ' . $booking['id'] . ' je uložená.';
                if ($notify && $type !== null && $booking['email'] !== '') {
                    $message .= sendBookingMail($booking, $type, $customerMessage) ? ' Klientovi odišiel e-mail.' : ' E-mail sa nepodarilo odoslať.';
                }
                flash($message);
                redirect('p=booking&id=' . rawurlencode($booking['id']));
            }
        }
    }

    if ($action === 'hours_save') {
        $hours = [];
        $errors = [];
        foreach (DAY_NAMES as $day => $label) {
            $from = post('from_' . $day, 5);
            $to = post('to_' . $day, 5);
            if (empty($_POST['open_' . $day])) { $hours[(string) $day] = null; continue; }
            if (!preg_match('/^\d{2}:\d{2}$/', $from) || !preg_match('/^\d{2}:\d{2}$/', $to) || $to <= $from) { $errors[] = $label . ': zadaj čas otvorenia a zatvorenia.'; continue; }
            $hours[(string) $day] = [$from, $to];
        }
        if ($errors !== []) {
            flash(implode("\n", $errors), 'error');
        } else {
            $values = [
                'hours' => $hours,
                'slot_step' => in_array((int) ($_POST['slot_step'] ?? 30), [15, 30, 60], true) ? (int) $_POST['slot_step'] : 30,
                'min_notice_hours' => max(0, min(168, (int) ($_POST['min_notice_hours'] ?? 12))),
                'max_days_ahead' => max(7, min(365, (int) ($_POST['max_days_ahead'] ?? 90))),
            ];
            updateJsonFile($settingsFile, static function (array &$settings) use ($values): void { $settings = array_replace($settings, $values); });
            flash('Otváracie hodiny a pravidlá rezervácií sú uložené.');
        }
        redirect('p=availability');
    }

    if ($action === 'block_add') {
        $from = post('from', 10);
        $to = post('to', 10);
        $whole = !empty($_POST['whole_day']);
        $start = post('start', 5);
        $end = post('end', 5);
        $label = post('label', 120);
        if (!validDate($from)) { flash('Zadaj dátum blokácie.', 'error'); redirect('p=availability'); }
        if (!validDate($to) || $to < $from) $to = $from;
        if (!$whole && (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end) || $end <= $start)) { flash('Zadaj čas od – do, alebo zaškrtni celý deň.', 'error'); redirect('p=availability'); }
        $block = ['id' => newId('BL'), 'from' => $from, 'to' => $to, 'start' => $whole ? null : $start, 'end' => $whole ? null : $end, 'label' => $label, 'created_at' => date('c')];
        updateJsonFile($blocksFile, static function (array &$blocks) use ($block): void { $blocks[] = $block; });
        $affected = array_filter(readJsonFile($bookingsFile), static function (array $booking) use ($block): bool {
            if (bookingStatus($booking) === 'cancelled' || $booking['date'] < $block['from'] || $booking['date'] > $block['to']) return false;
            if ($block['start'] === null) return true;
            return bookingOverlap(minutesFromTime($booking['time']), (int) $booking['duration'], minutesFromTime($block['start']), minutesFromTime($block['end']) - minutesFromTime($block['start']));
        });
        flash('Blokácia je pridaná. Tieto časy sa na webe už nedajú rezervovať.');
        if ($affected !== []) {
            $lines = array_map(static fn (array $b): string => '• ' . $b['id'] . ' – ' . formatDateSk($b['date'], false) . ' ' . $b['time'] . ', ' . $b['name'], $affected);
            flash("Pozor, v blokovanom čase už sú rezervácie. Ak ich treba zrušiť alebo presunúť, uprav ich:\n" . implode("\n", $lines), 'warning');
        }
        redirect('p=availability');
    }

    if ($action === 'block_delete') {
        $id = post('id', 40);
        updateJsonFile($blocksFile, static function (array &$blocks) use ($id): void {
            $blocks = array_values(array_filter($blocks, static fn (array $block): bool => ($block['id'] ?? '') !== $id));
        });
        flash('Blokácia je odstránená.');
        redirect('p=availability');
    }

    if ($action === 'order_status') {
        $id = post('id', 40);
        $status = post('status', 20);
        if (!isset(orderStatuses()[$status])) redirect('p=orders');
        $result = updateJsonFile($ordersFile, static function (array &$orders) use ($id, $status): ?array {
            foreach ($orders as &$order) {
                if (($order['id'] ?? '') !== $id) continue;
                $old = orderStatus($order);
                $order['status'] = $status;
                $order['updated_at'] = date('c');
                return ['order' => $order, 'old' => $old];
            }
            return null;
        });
        if ($result === null) { flash('Objednávka sa nenašla.', 'error'); redirect('p=orders'); }
        $message = 'Objednávka ' . $id . ': ' . orderStatuses()[$status] . '.';
        if ($result['old'] !== $status && !empty($_POST['notify'])) {
            $message .= sendOrderMail($result['order'], $status === 'new' ? 'received' : $status, post('message', 800)) ? ' Klientovi odišiel e-mail.' : ' E-mail sa nepodarilo odoslať.';
        }
        flash($message);
        redirect('p=orders');
    }

    if ($action === 'message_delete') {
        $id = post('id', 40);
        updateJsonFile($contactMessagesFile, static function (array &$messages) use ($id, $contactPhotosDir): void {
            foreach ($messages as $message) {
                if (($message['id'] ?? '') !== $id) continue;
                foreach ($message['photos'] ?? [] as $file) @unlink($contactPhotosDir . DIRECTORY_SEPARATOR . basename($file));
            }
            $messages = array_values(array_filter($messages, static fn (array $message): bool => ($message['id'] ?? '') !== $id));
        });
        flash('Správa je vymazaná.');
        redirect('p=messages');
    }

    if ($action === 'settings_save') {
        $email = post('notify_email', 120);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('E-mail pre upozornenia nie je platný.', 'error'); redirect('p=settings'); }
        updateJsonFile($settingsFile, static function (array &$settings) use ($email): void { $settings['notify_email'] = $email; });
        flash('Nastavenia sú uložené.');
        redirect('p=settings');
    }

    if ($action === 'password_change') {
        $current = (string) ($_POST['current'] ?? '');
        $new = (string) ($_POST['new'] ?? '');
        if (!password_verify($current, adminPasswordHash())) flash('Súčasné heslo nie je správne.', 'error');
        elseif (mb_strlen($new) < 10) flash('Nové heslo musí mať aspoň 10 znakov.', 'error');
        elseif ($new !== (string) ($_POST['confirm'] ?? '')) flash('Nové heslá sa nezhodujú.', 'error');
        else { setAdminPassword($new); flash('Heslo je zmenené.'); }
        redirect('p=settings');
    }
}

require __DIR__ . '/cms.php';

// ---------------------------------------------------------------- Pages

$today = date('Y-m-d');
$statuses = bookingStatuses();

if ($page === 'booking') {
    $id = cleanText($_GET['id'] ?? ($formBooking['id'] ?? ''), 40);
    $booking = $formBooking;
    if ($booking === null && $id !== '') {
        foreach (readJsonFile($bookingsFile) as $item) if (($item['id'] ?? '') === $id) $booking = $item;
        if ($booking === null) { flash('Rezervácia sa nenašla.', 'error'); redirect('p=bookings'); }
    }
    $isNew = $booking === null || ($booking['id'] ?? '') === '';
    $booking = array_replace(['id' => '', 'service' => array_key_first($services), 'date' => cleanText($_GET['date'] ?? $today, 10), 'time' => '09:00', 'duration' => 0, 'name' => '', 'phone' => '', 'email' => '', 'bike' => '', 'note' => '', 'admin_note' => '', 'status' => 'confirmed', 'pickup' => false, 'pickup_address' => ''], $booking ?? []);
    renderHeader($isNew ? 'Nová rezervácia' : 'Rezervácia ' . $booking['id'], 'bookings');
    ?>
    <div class="page-head">
        <div><a class="back" href="index.php?p=bookings">← Rezervácie</a><h1><?= $isNew ? 'Nová rezervácia' : 'Rezervácia ' . e($booking['id']) ?></h1>
        <?php if (!$isNew && !empty($booking['created_at'])): ?><p class="muted">Vytvorená <?= e(date('j. n. Y H:i', strtotime($booking['created_at']))) ?> · <?= ($booking['source'] ?? 'web') === 'admin' ? 'zadaná v administrácii' : 'z webu' ?></p><?php endif; ?></div>
        <?php if (!$isNew): ?><?= statusBadge(bookingStatus($booking), $statuses) ?><?php endif; ?>
    </div>

    <?php if ($conflicts !== []): ?>
    <div class="flash flash--warning"><strong>Termín koliduje:</strong><br><?= implode('<br>', array_map('e', $conflicts)) ?><br>Uprav čas, alebo zaškrtni „Uložiť aj napriek kolízii“.</div>
    <?php endif; ?>

    <form class="card form" method="post" action="index.php?p=booking<?= $isNew ? '' : '&id=' . e(rawurlencode($booking['id'])) ?>">
        <?= csrfField() ?><input type="hidden" name="action" value="booking_save"><input type="hidden" name="id" value="<?= e($booking['id']) ?>">
        <h2>Termín</h2>
        <div class="grid grid--4">
            <label class="span-2">Služba<select name="service" data-service-select><?php foreach ($services as $serviceId => $service): ?><option value="<?= e($serviceId) ?>" data-minutes="<?= (int) $service['minutes'] ?>" <?= $booking['service'] === $serviceId ? 'selected' : '' ?>><?= e($service['name']) ?> · <?= e(durationLabel((int) $service['minutes'])) ?> · od <?= (int) $service['price'] ?> €</option><?php endforeach; ?></select></label>
            <label>Dátum<input type="date" name="date" value="<?= e($booking['date']) ?>" required></label>
            <label>Začiatok<input type="time" name="time" value="<?= e($booking['time']) ?>" step="900" required></label>
            <label>Dĺžka (min)<input type="number" name="duration" min="15" max="1440" step="15" value="<?= (int) $booking['duration'] ?: '' ?>" placeholder="podľa služby" data-duration></label>
            <label>Stav<select name="status"><?php foreach ($statuses as $status => $label): ?><option value="<?= e($status) ?>" <?= $booking['status'] === $status ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        </div>
        <h2>Zákazník</h2>
        <div class="grid grid--4">
            <label>Meno<input type="text" name="name" value="<?= e($booking['name']) ?>" required></label>
            <label>Telefón<input type="tel" name="phone" value="<?= e($booking['phone']) ?>"></label>
            <label>E-mail<input type="email" name="email" value="<?= e($booking['email']) ?>"></label>
            <label>Motorka<input type="text" name="bike" value="<?= e($booking['bike']) ?>"></label>
            <label class="check span-2"><input type="checkbox" name="pickup" value="1" <?= !empty($booking['pickup']) ? 'checked' : '' ?> data-pickup> Vyzdvihnutie motorky na adrese</label>
            <label class="span-2" data-pickup-address>Adresa vyzdvihnutia<input type="text" name="pickup_address" value="<?= e($booking['pickup_address']) ?>"></label>
            <label class="span-2">Poznámka zákazníka<textarea name="note" rows="3"><?= e($booking['note']) ?></textarea></label>
            <label class="span-2">Interná poznámka <small>(zákazník ju nevidí)</small><textarea name="admin_note" rows="3"><?= e($booking['admin_note']) ?></textarea></label>
        </div>
        <h2>Upozornenie zákazníka</h2>
        <label class="check"><input type="checkbox" name="notify" value="1" checked> Poslať zákazníkovi e-mail o potvrdení, zrušení alebo zmene termínu</label>
        <label>Správa pre zákazníka <small>(nepovinné, pridá sa do e-mailu)</small><textarea name="message" rows="2" placeholder="napr. Motorku prosím privez o 15 minút skôr."></textarea></label>
        <?php if ($conflicts !== []): ?><label class="check check--warning"><input type="checkbox" name="force" value="1"> Uložiť aj napriek kolízii</label><?php endif; ?>
        <div class="form-actions"><button class="btn btn--primary" type="submit"><?= $isNew ? 'Vytvoriť rezerváciu' : 'Uložiť zmeny' ?></button><a class="btn" href="index.php?p=bookings">Zrušiť</a></div>
    </form>

    <?php if (!$isNew): ?>
    <form class="danger-zone" method="post" data-confirm="Naozaj natrvalo vymazať rezerváciu <?= e($booking['id']) ?>? Zákazníkovi nepríde žiadny e-mail. Ak chceš termín zrušiť, radšej zmeň stav na Zrušená.">
        <?= csrfField() ?><input type="hidden" name="action" value="booking_delete"><input type="hidden" name="id" value="<?= e($booking['id']) ?>">
        <span>Spam alebo omyl? Rezerváciu môžeš natrvalo vymazať.</span><button class="btn btn--danger" type="submit">Vymazať</button>
    </form>
    <?php endif; ?>
    <?php
    renderFooter();
    exit;
}

if ($page === 'availability') {
    $settings = bookingSettings(true);
    $blocks = readJsonFile($blocksFile);
    $showPast = !empty($_GET['past']);
    $blocks = array_values(array_filter($blocks, static fn (array $block): bool => $showPast || ($block['to'] ?? $block['from']) >= $today));
    usort($blocks, static fn (array $a, array $b): int => [$a['from'], $a['start'] ?? ''] <=> [$b['from'], $b['start'] ?? '']);
    renderHeader('Dostupnosť', 'availability');
    ?>
    <div class="page-head"><div><h1>Dostupnosť</h1><p class="muted">Kedy sa dá rezervovať online. Zmeny sa na webe prejavia okamžite.</p></div></div>

    <div class="columns">
        <form class="card form" method="post">
            <?= csrfField() ?><input type="hidden" name="action" value="hours_save">
            <h2>Otváracie hodiny</h2>
            <div class="hours">
                <?php foreach (DAY_NAMES as $day => $label): $range = $settings['hours'][(string) $day] ?? null; ?>
                <div class="hours-row" data-hours-row>
                    <label class="check"><input type="checkbox" name="open_<?= $day ?>" value="1" <?= is_array($range) ? 'checked' : '' ?>> <?= e($label) ?></label>
                    <input type="time" name="from_<?= $day ?>" value="<?= e($range[0] ?? '08:00') ?>" step="900" aria-label="<?= e($label) ?> od">
                    <span>–</span>
                    <input type="time" name="to_<?= $day ?>" value="<?= e($range[1] ?? '18:00') ?>" step="900" aria-label="<?= e($label) ?> do">
                </div>
                <?php endforeach; ?>
            </div>
            <h2>Pravidlá rezervácií</h2>
            <div class="grid grid--3">
                <label>Časy po<select name="slot_step"><?php foreach ([15, 30, 60] as $step): ?><option value="<?= $step ?>" <?= (int) $settings['slot_step'] === $step ? 'selected' : '' ?>><?= $step ?> min</option><?php endforeach; ?></select></label>
                <label>Najskôr o (hodín)<input type="number" name="min_notice_hours" min="0" max="168" value="<?= (int) $settings['min_notice_hours'] ?>"></label>
                <label>Najneskôr o (dní)<input type="number" name="max_days_ahead" min="7" max="365" value="<?= (int) $settings['max_days_ahead'] ?>"></label>
            </div>
            <p class="muted small">„Najskôr o“ – koľko hodín vopred musí zákazník rezervovať. „Najneskôr o“ – ako ďaleko dopredu je kalendár otvorený.</p>
            <div class="form-actions"><button class="btn btn--primary" type="submit">Uložiť hodiny</button></div>
        </form>

        <form class="card form" method="post" data-block-form>
            <?= csrfField() ?><input type="hidden" name="action" value="block_add">
            <h2>Zablokovať termín</h2>
            <p class="muted small">Dovolenka, sviatok, súkromná akcia… Zablokovaný čas sa na webe nedá rezervovať.</p>
            <div class="grid grid--2">
                <label>Od dátumu<input type="date" name="from" min="<?= e($today) ?>" value="<?= e($today) ?>" required></label>
                <label>Do dátumu <small>(nepovinné)</small><input type="date" name="to" min="<?= e($today) ?>"></label>
            </div>
            <label class="check"><input type="checkbox" name="whole_day" value="1" checked data-whole-day> Celý deň</label>
            <div class="grid grid--2" data-block-times hidden>
                <label>Čas od<input type="time" name="start" value="12:00" step="900"></label>
                <label>Čas do<input type="time" name="end" value="14:00" step="900"></label>
            </div>
            <label>Popis <small>(vidíš ho len ty)</small><input type="text" name="label" placeholder="napr. Dovolenka"></label>
            <div class="form-actions"><button class="btn btn--primary" type="submit">Pridať blokáciu</button></div>
        </form>
    </div>

    <section class="card">
        <div class="card-head"><h2><?= $showPast ? 'Všetky blokácie' : 'Aktuálne blokácie' ?></h2><a href="index.php?p=availability<?= $showPast ? '' : '&past=1' ?>"><?= $showPast ? 'Skryť staré' : 'Zobraziť aj staré' ?></a></div>
        <?php if ($blocks === []): ?><p class="empty">Žiadne blokácie.</p><?php else: ?>
        <table class="table">
            <thead><tr><th>Dátum</th><th>Čas</th><th>Popis</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($blocks as $block): ?>
                <tr>
                    <td><?= e(formatDateSk($block['from'])) ?><?= $block['to'] !== $block['from'] ? ' – ' . e(formatDateSk($block['to'])) : '' ?></td>
                    <td><?= empty($block['start']) ? 'Celý deň' : e($block['start'] . ' – ' . $block['end']) ?></td>
                    <td><?= e($block['label'] ?? '') ?></td>
                    <td class="actions"><form method="post" data-confirm="Odstrániť túto blokáciu?"><?= csrfField() ?><input type="hidden" name="action" value="block_delete"><input type="hidden" name="id" value="<?= e($block['id']) ?>"><button class="btn btn--small" type="submit">Odstrániť</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>
    <?php
    renderFooter();
    exit;
}

if ($page === 'orders') {
    $orderStatusLabels = orderStatuses();
    $filter = cleanText($_GET['f'] ?? 'open', 20);
    $orders = array_reverse(readJsonFile($ordersFile));
    $orders = array_values(array_filter($orders, static function (array $order) use ($filter): bool {
        $status = orderStatus($order);
        if ($filter === 'open') return in_array($status, ['new', 'confirmed'], true);
        return $filter === 'all' || $status === $filter;
    }));
    renderHeader('Objednávky', 'orders');
    ?>
    <div class="page-head"><div><h1>Objednávky</h1><p class="muted">Objednávky z e-shopu na dobierku.</p></div></div>
    <nav class="tabs">
        <?php foreach (['open' => 'Na vybavenie', 'new' => 'Nové', 'shipped' => 'Odoslané', 'done' => 'Vybavené', 'cancelled' => 'Zrušené', 'all' => 'Všetky'] as $key => $label): ?>
        <a class="<?= $filter === $key ? 'is-active' : '' ?>" href="index.php?p=orders&f=<?= e($key) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php if ($orders === []): ?><p class="card empty">Žiadne objednávky.</p><?php endif; ?>
    <?php foreach ($orders as $order): $customer = $order['customer']; ?>
    <article class="card order">
        <div class="card-head">
            <div><h2><?= e($order['id']) ?> · <?= (int) $order['total'] ?> €</h2><p class="muted small"><?= e(date('j. n. Y H:i', strtotime($order['created_at']))) ?> · dobierka</p></div>
            <?= statusBadge(orderStatus($order), $orderStatusLabels) ?>
        </div>
        <div class="order-grid">
            <div>
                <p><strong><?= e($customer['name']) ?></strong><br><a href="tel:<?= e(str_replace(' ', '', $customer['phone'])) ?>"><?= e($customer['phone']) ?></a> · <a href="mailto:<?= e($customer['email']) ?>"><?= e($customer['email']) ?></a><br><?= e($customer['address']) ?></p>
                <?php if (($customer['ico'] ?? '') !== '' || ($customer['dic'] ?? '') !== ''): ?><p class="muted small">IČO <?= e($customer['ico'] ?: '—') ?> · DIČ <?= e($customer['dic'] ?: '—') ?></p><?php endif; ?>
                <?php if (($customer['note'] ?? '') !== ''): ?><p class="note"><?= nl2br(e($customer['note'])) ?></p><?php endif; ?>
            </div>
            <ul class="order-items"><?php foreach ($order['items'] as $item): ?><li><span><?= (int) $item['quantity'] ?>× <?= e($item['name']) ?></span><b><?= (int) $item['price'] * (int) $item['quantity'] ?> €</b></li><?php endforeach; ?></ul>
        </div>
        <form class="inline-form" method="post">
            <?= csrfField() ?><input type="hidden" name="action" value="order_status"><input type="hidden" name="id" value="<?= e($order['id']) ?>">
            <select name="status"><?php foreach ($orderStatusLabels as $status => $label): ?><option value="<?= e($status) ?>" <?= orderStatus($order) === $status ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
            <input type="text" name="message" placeholder="Správa pre zákazníka (nepovinné)">
            <label class="check"><input type="checkbox" name="notify" value="1" checked> Poslať e-mail</label>
            <button class="btn btn--primary" type="submit">Uložiť stav</button>
        </form>
    </article>
    <?php endforeach; ?>
    <?php
    renderFooter();
    exit;
}

if ($page === 'photo') {
    $file = basename((string) ($_GET['f'] ?? ''));
    $path = $contactPhotosDir . DIRECTORY_SEPARATOR . $file;
    $types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'heic' => 'image/heic'];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!preg_match('/^SPR-[A-F0-9]+-\d+\.[a-z]+$/', $file) || !isset($types[$ext]) || !is_file($path)) { http_response_code(404); exit; }
    header('Content-Type: ' . $types[$ext]);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline; filename="' . $file . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=86400');
    readfile($path);
    exit;
}

if ($page === 'messages') {
    $messages = array_reverse(readJsonFile($contactMessagesFile));
    updateJsonFile($contactMessagesFile, static function (array &$items): void { foreach ($items as &$item) $item['read'] = true; });
    renderHeader('Správy', 'messages');
    ?>
    <div class="page-head"><div><h1>Správy</h1><p class="muted">Správy z kontaktného formulára. Odpovedaj priamo e-mailom.</p></div></div>
    <?php if ($messages === []): ?><p class="card empty">Zatiaľ žiadne správy.</p><?php endif; ?>
    <?php foreach ($messages as $message): ?>
    <article class="card message<?= empty($message['read']) ? ' is-unread' : '' ?>">
        <div class="card-head">
            <div><h2><?= e($message['name']) ?><?php if (empty($message['read'])): ?> <span class="badge badge--pending">Nová</span><?php endif; ?></h2><p class="muted small"><?= e(date('j. n. Y H:i', strtotime($message['created_at']))) ?> · <a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a><?= ($message['phone'] ?? '') !== '' ? ' · <a href="tel:' . e(str_replace(' ', '', $message['phone'])) . '">' . e($message['phone']) . '</a>' : '' ?></p></div>
            <form method="post" data-confirm="Vymazať správu?"><?= csrfField() ?><input type="hidden" name="action" value="message_delete"><input type="hidden" name="id" value="<?= e($message['id']) ?>"><button class="btn btn--small" type="submit">Vymazať</button></form>
        </div>
        <?php if (($message['topic'] ?? '') !== ''): ?><p class="muted small">Téma: <strong><?= e($message['topic']) ?></strong></p><?php endif; ?>
        <p class="message-text"><?= nl2br(e($message['message'])) ?></p>
        <?php if (!empty($message['photos'])): ?><div class="message-photos"><?php foreach ($message['photos'] as $file): $url = 'index.php?p=photo&f=' . rawurlencode($file); ?><a href="<?= e($url) ?>" target="_blank" rel="noopener"><?php if (str_ends_with($file, '.heic')): ?><span>HEIC fotka</span><?php else: ?><img src="<?= e($url) ?>" alt="Fotka motorky" loading="lazy"><?php endif; ?></a><?php endforeach; ?></div><?php endif; ?>
        <a class="btn btn--small" href="mailto:<?= e($message['email']) ?>?subject=<?= e(rawurlencode('Re: správa pre MotoCare Pro')) ?>">Odpovedať</a>
    </article>
    <?php endforeach; ?>
    <?php
    renderFooter();
    exit;
}

if ($page === 'settings') {
    $settings = bookingSettings(true);
    renderHeader('Nastavenia', 'settings');
    ?>
    <div class="page-head"><div><h1>Nastavenia</h1></div></div>
    <?php renderSiteForm(); ?>
    <div class="columns">
        <form class="card form" method="post">
            <?= csrfField() ?><input type="hidden" name="action" value="settings_save">
            <h2>Upozornenia</h2>
            <label>E-mail pre nové rezervácie, objednávky a správy<input type="email" name="notify_email" value="<?= e($settings['notify_email']) ?>" placeholder="<?= e($site['email']) ?>"></label>
            <p class="muted small">Ak necháš prázdne, upozornenia chodia na <?= e($site['email']) ?>. Z tejto adresy odchádzajú aj e-maily zákazníkom.</p>
            <div class="form-actions"><button class="btn btn--primary" type="submit">Uložiť</button></div>
        </form>
        <form class="card form" method="post">
            <?= csrfField() ?><input type="hidden" name="action" value="password_change">
            <h2>Zmena hesla</h2>
            <label>Súčasné heslo<input type="password" name="current" autocomplete="current-password" required></label>
            <label>Nové heslo <small>(aspoň 10 znakov)</small><input type="password" name="new" autocomplete="new-password" minlength="10" required></label>
            <label>Nové heslo znova<input type="password" name="confirm" autocomplete="new-password" minlength="10" required></label>
            <div class="form-actions"><button class="btn btn--primary" type="submit">Zmeniť heslo</button></div>
        </form>
    </div>
    <?php
    renderFooter();
    exit;
}

if ($page === 'emails') {
    $sampleBooking = [
        'id' => 'MC-1A2B3C4D', 'service_name' => reset($services)['name'], 'price' => reset($services)['price'], 'date' => date('Y-m-d', strtotime('+3 days')),
        'time' => '10:00', 'end_time' => '12:00', 'name' => 'Ján Vzorový', 'phone' => '+421 900 123 456', 'email' => 'jan@example.com',
        'bike' => 'BMW R1200 GS', 'note' => 'Prosím aj kontrolu reťaze.', 'pickup' => true, 'pickup_address' => 'Hlavná 1, 036 01 Martin',
    ];
    $sampleOrder = [
        'id' => 'OBJ-5E6F7A8B', 'total' => 63, 'items' => [['name' => reset($products)['name'], 'quantity' => 1, 'price' => 39], ['name' => 'SONAX Odstraňovač živice', 'quantity' => 2, 'price' => 12]],
        'customer' => ['name' => 'Ján Vzorový', 'phone' => '+421 900 123 456', 'email' => 'jan@example.com', 'address' => 'Hlavná 1, 036 01 Martin', 'ico' => '', 'dic' => '', 'note' => ''],
    ];
    $sampleMessage = ['name' => 'Ján Vzorový', 'email' => 'jan@example.com', 'phone' => '+421 900 123 456', 'message' => 'Dobrý deň, koľko by stála ochrana na Hondu Africa Twin?'];
    $groups = ['Rezervácie – zákazník' => [], 'Objednávky – zákazník' => [], 'Upozornenia pre teba' => [], 'Kontaktný formulár – zákazník' => []];
    foreach (bookingMailTypes() as $type => $label) $groups['Rezervácie – zákazník'][$label] = bookingMail($sampleBooking, $type);
    foreach (orderMailTypes() as $type => $label) $groups['Objednávky – zákazník'][$label] = orderMail($sampleOrder, $type);
    $groups['Upozornenia pre teba'] = ['Nová rezervácia' => bookingOwnerMail($sampleBooking), 'Nová objednávka' => orderOwnerMail($sampleOrder), 'Nová správa z webu' => contactOwnerMail($sampleMessage)];
    $groups['Kontaktný formulár – zákazník'] = ['Správa prijatá' => contactCustomerMail($sampleMessage)];
    renderHeader('E-maily', 'emails');
    ?>
    <div class="page-head"><div><h1>E-maily</h1><p class="muted">Náhľad všetkých e-mailov, ktoré web posiela. Údaje sú ukážkové. E-maily odchádzajú z <?= e($site['email']) ?>, upozornenia chodia na <?= e(notifyEmail()) ?>.</p></div></div>
    <?php foreach ($groups as $group => $mails): ?>
    <h2 class="group-title"><?= e($group) ?></h2>
    <?php foreach ($mails as $label => $mail): ?>
    <details class="card mail-preview">
        <summary><span><?= e($label) ?></span><small><?= e($mail['subject']) ?></small></summary>
        <iframe title="<?= e($label) ?>" srcdoc="<?= e($mail['html']) ?>" loading="lazy"></iframe>
    </details>
    <?php endforeach; ?>
    <?php endforeach; ?>
    <?php
    renderFooter();
    exit;
}

// Default page: bookings list.
$filters = ['upcoming' => 'Nadchádzajúce', 'pending' => 'Na potvrdenie', 'today' => 'Dnes', 'past' => 'Minulé', 'cancelled' => 'Zrušené', 'all' => 'Všetky'];
$filter = cleanText($_GET['f'] ?? 'upcoming', 20);
if (!isset($filters[$filter])) $filter = 'upcoming';
$query = cleanText($_GET['q'] ?? '', 80);
$bookings = array_values(array_filter(readJsonFile($bookingsFile), static function (array $booking) use ($filter, $today, $query): bool {
    $status = bookingStatus($booking);
    $match = [
        'upcoming' => $booking['date'] >= $today && in_array($status, ['pending', 'confirmed'], true),
        'pending' => $status === 'pending',
        'today' => $booking['date'] === $today && $status !== 'cancelled',
        'past' => $booking['date'] < $today,
        'cancelled' => $status === 'cancelled',
        'all' => true,
    ][$filter];
    if (!$match || $query === '') return $match;
    $haystack = implode(' ', [$booking['id'], $booking['name'], $booking['phone'], $booking['email'], $booking['bike'] ?? '']);
    return mb_stripos($haystack, $query) !== false;
}));
$descending = in_array($filter, ['past', 'cancelled', 'all'], true);
usort($bookings, static fn (array $a, array $b): int => $descending ? [$b['date'], $b['time']] <=> [$a['date'], $a['time']] : [$a['date'], $a['time']] <=> [$b['date'], $b['time']]);
$grouped = [];
foreach ($bookings as $booking) $grouped[$booking['date']][] = $booking;
$returnQuery = 'p=bookings&f=' . $filter . ($query !== '' ? '&q=' . rawurlencode($query) : '');

renderHeader('Rezervácie', 'bookings');
?>
<div class="page-head">
    <div><h1>Rezervácie</h1><p class="muted">Potvrdzuj, presúvaj a ruš termíny. Zákazník dostane e-mail pri potvrdení aj zrušení.</p></div>
    <a class="btn btn--primary" href="index.php?p=booking">+ Nová rezervácia</a>
</div>
<div class="toolbar">
    <nav class="tabs"><?php foreach ($filters as $key => $label): ?><a class="<?= $filter === $key ? 'is-active' : '' ?>" href="index.php?p=bookings&f=<?= e($key) ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
    <form class="search" method="get"><input type="hidden" name="p" value="bookings"><input type="hidden" name="f" value="<?= e($filter) ?>"><input type="search" name="q" value="<?= e($query) ?>" placeholder="Hľadať meno, telefón, kód…"></form>
</div>

<?php if ($grouped === []): ?><p class="card empty">Žiadne rezervácie<?= $query !== '' ? ' pre „' . e($query) . '“' : '' ?>.</p><?php endif; ?>
<?php foreach ($grouped as $date => $items): ?>
<section class="day">
    <h2 class="day-title"><?= e(ucfirst(formatDateSk($date))) ?><?= $date === $today ? ' <span class="badge badge--today">Dnes</span>' : '' ?></h2>
    <?php foreach ($items as $booking): $status = bookingStatus($booking); ?>
    <article class="booking booking--<?= e($status) ?>">
        <div class="booking-time"><b><?= e($booking['time']) ?></b><span><?= e($booking['end_time'] ?? '') ?></span></div>
        <div class="booking-main">
            <p class="booking-service"><?= e($booking['service_name']) ?> <span class="muted">· <?= e($booking['id']) ?></span></p>
            <p class="booking-customer"><strong><?= e($booking['name']) ?></strong> · <a href="tel:<?= e(str_replace(' ', '', $booking['phone'])) ?>"><?= e($booking['phone']) ?></a><?= $booking['email'] !== '' ? ' · <a href="mailto:' . e($booking['email']) . '">' . e($booking['email']) . '</a>' : '' ?></p>
            <?php if (($booking['bike'] ?? '') !== ''): ?><p class="muted small">Motorka: <?= e($booking['bike']) ?></p><?php endif; ?>
            <?php if (!empty($booking['pickup'])): ?><p class="pickup"><span>Vyzdvihnutie</span><?= e($booking['pickup_address']) ?></p><?php endif; ?>
            <?php if (($booking['note'] ?? '') !== ''): ?><p class="note"><?= nl2br(e($booking['note'])) ?></p><?php endif; ?>
        </div>
        <div class="booking-side">
            <?= statusBadge($status, $statuses) ?>
            <div class="booking-actions">
                <?php if ($status === 'pending'): ?>
                <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="booking_status"><input type="hidden" name="id" value="<?= e($booking['id']) ?>"><input type="hidden" name="status" value="confirmed"><input type="hidden" name="notify" value="1"><input type="hidden" name="return" value="<?= e($returnQuery) ?>"><button class="btn btn--small btn--primary" type="submit">Potvrdiť</button></form>
                <?php endif; ?>
                <?php if ($status === 'confirmed' && $booking['date'] <= $today): ?>
                <form method="post"><?= csrfField() ?><input type="hidden" name="action" value="booking_status"><input type="hidden" name="id" value="<?= e($booking['id']) ?>"><input type="hidden" name="status" value="done"><input type="hidden" name="notify" value="1"><input type="hidden" name="return" value="<?= e($returnQuery) ?>"><button class="btn btn--small" type="submit">Hotovo</button></form>
                <?php endif; ?>
                <?php if (in_array($status, ['pending', 'confirmed'], true)): ?>
                <form method="post" data-confirm="Zrušiť rezerváciu <?= e($booking['id']) ?>? Zákazníkovi príde e-mail o zrušení."><?= csrfField() ?><input type="hidden" name="action" value="booking_status"><input type="hidden" name="id" value="<?= e($booking['id']) ?>"><input type="hidden" name="status" value="cancelled"><input type="hidden" name="notify" value="1"><input type="hidden" name="return" value="<?= e($returnQuery) ?>"><button class="btn btn--small btn--ghost-danger" type="submit">Zrušiť</button></form>
                <?php endif; ?>
                <a class="btn btn--small" href="index.php?p=booking&id=<?= e(rawurlencode($booking['id'])) ?>">Upraviť</a>
            </div>
        </div>
    </article>
    <?php endforeach; ?>
</section>
<?php endforeach; ?>
<?php
renderFooter();

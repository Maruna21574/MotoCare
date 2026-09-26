<?php
declare(strict_types=1);

// Booking domain: opening hours, blocked periods, availability and statuses.
// Opening hours and blocks are edited in the admin and stored in data/settings.json and data/blocks.json.

const DAY_NAMES = [1 => 'Pondelok', 2 => 'Utorok', 3 => 'Streda', 4 => 'Štvrtok', 5 => 'Piatok', 6 => 'Sobota', 7 => 'Nedeľa'];
const DAY_SHORT = [1 => 'Po', 2 => 'Ut', 3 => 'St', 4 => 'Št', 5 => 'Pi', 6 => 'So', 7 => 'Ne'];

function defaultBookingSettings(): array
{
    return [
        'hours' => [
            '1' => null,
            '2' => ['08:00', '18:00'],
            '3' => ['08:00', '18:00'],
            '4' => ['08:00', '18:00'],
            '5' => ['08:00', '18:00'],
            '6' => ['08:00', '18:00'],
            '7' => null,
        ],
        'slot_step' => 30,
        'min_notice_hours' => 12,
        'max_days_ahead' => 90,
        'notify_email' => '',
    ];
}

function bookingSettings(bool $fresh = false): array
{
    static $settings = null;
    global $settingsFile;
    if ($settings === null || $fresh) $settings = array_replace(defaultBookingSettings(), readJsonFile($settingsFile));
    return $settings;
}

function notifyEmail(): string
{
    global $site;
    $email = (string) (bookingSettings()['notify_email'] ?? '');
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : $site['email'];
}

function bookingStatuses(): array
{
    return [
        'pending' => 'Čaká na potvrdenie',
        'confirmed' => 'Potvrdená',
        'done' => 'Dokončená',
        'cancelled' => 'Zrušená',
    ];
}

function orderStatuses(): array
{
    return [
        'new' => 'Nová',
        'confirmed' => 'Potvrdená',
        'shipped' => 'Odoslaná',
        'done' => 'Vybavená',
        'cancelled' => 'Zrušená',
    ];
}

function bookingStatus(array $booking): string
{
    $status = (string) ($booking['status'] ?? 'pending');
    return isset(bookingStatuses()[$status]) ? $status : 'pending';
}

function orderStatus(array $order): string
{
    $status = (string) ($order['status'] ?? 'new');
    return isset(orderStatuses()[$status]) ? $status : 'new';
}

/** Opening window of a date as [openMinutes, closeMinutes], or null when closed. */
function dayHours(string $date): ?array
{
    $hours = bookingSettings()['hours'][(string) date('N', strtotime($date))] ?? null;
    if (!is_array($hours) || count($hours) !== 2) return null;
    $open = minutesFromTime($hours[0]);
    $close = minutesFromTime($hours[1]);
    return $close > $open ? [$open, $close] : null;
}

/** Blocks affecting a date as [start, end, label] in minutes; whole-day blocks cover the full day. */
function blocksForDate(string $date, array $blocks): array
{
    $result = [];
    foreach ($blocks as $block) {
        $from = (string) ($block['from'] ?? '');
        $to = (string) ($block['to'] ?? $from);
        if ($date < $from || $date > $to) continue;
        $whole = empty($block['start']) || empty($block['end']);
        $result[] = [
            'start' => $whole ? 0 : minutesFromTime($block['start']),
            'end' => $whole ? 24 * 60 : minutesFromTime($block['end']),
            'whole_day' => $whole,
            'label' => (string) ($block['label'] ?? ''),
        ];
    }
    return $result;
}

function isWholeDayBlocked(string $date, array $blocks): bool
{
    foreach (blocksForDate($date, $blocks) as $block) if ($block['whole_day']) return true;
    return false;
}

/**
 * How long a service occupies the studio on a given date. Services longer than the
 * opening window take the whole day (the bike stays in the studio).
 */
function occupiedMinutes(string $date, int $serviceMinutes): ?int
{
    $hours = dayHours($date);
    if ($hours === null) return null;
    return min($serviceMinutes, $hours[1] - $hours[0]);
}

function isBookableDate(string $date): bool
{
    $today = date('Y-m-d');
    $last = date('Y-m-d', strtotime('+' . (int) bookingSettings()['max_days_ahead'] . ' days'));
    return validDate($date) && $date >= $today && $date <= $last;
}

/**
 * All start times of a day with their availability for a service.
 * Cancelled bookings and $excludeId (the booking being edited) do not block time.
 */
function availableSlots(string $date, int $serviceMinutes, array $bookings, array $blocks, ?string $excludeId = null): array
{
    $hours = dayHours($date);
    if ($hours === null || !isBookableDate($date) || isWholeDayBlocked($date, $blocks)) return [];
    [$open, $close] = $hours;
    $duration = occupiedMinutes($date, $serviceMinutes);
    $step = max(15, (int) bookingSettings()['slot_step']);
    $earliest = time() + (int) bookingSettings()['min_notice_hours'] * 3600;
    $dayBlocks = blocksForDate($date, $blocks);
    $slots = [];
    for ($start = $open; $start + $duration <= $close; $start += $step) {
        $available = strtotime($date . ' ' . formatTime($start)) >= $earliest;
        if ($available) foreach ($dayBlocks as $block) {
            if (bookingOverlap($start, $duration, $block['start'], $block['end'] - $block['start'])) { $available = false; break; }
        }
        if ($available) foreach ($bookings as $booking) {
            if (($booking['date'] ?? '') !== $date || bookingStatus($booking) === 'cancelled' || ($booking['id'] ?? '') === $excludeId) continue;
            if (bookingOverlap($start, $duration, minutesFromTime((string) $booking['time']), (int) ($booking['duration'] ?? 0))) { $available = false; break; }
        }
        $slots[] = ['time' => formatTime($start), 'end' => formatTime($start + $duration), 'available' => $available];
    }
    return $slots;
}

/** Per-day summary for the booking calendar: closed, past, full or available with a count. */
function monthAvailability(string $month, int $serviceMinutes, array $bookings, array $blocks): array
{
    $days = [];
    $first = new DateTime($month . '-01');
    $count = (int) $first->format('t');
    for ($day = 1; $day <= $count; $day++) {
        $date = $first->format('Y-m-') . sprintf('%02d', $day);
        if (!isBookableDate($date)) { $days[$date] = ['state' => 'past', 'free' => 0]; continue; }
        $hours = dayHours($date);
        if ($hours === null || isWholeDayBlocked($date, $blocks)) { $days[$date] = ['state' => 'closed', 'free' => 0]; continue; }
        // A day whose last possible start is inside the minimum notice period is "past", not "full".
        $lastStart = strtotime($date . ' ' . formatTime($hours[1] - occupiedMinutes($date, $serviceMinutes)));
        if ($lastStart < time() + (int) bookingSettings()['min_notice_hours'] * 3600) { $days[$date] = ['state' => 'past', 'free' => 0]; continue; }
        $free = count(array_filter(availableSlots($date, $serviceMinutes, $bookings, $blocks), static fn (array $slot): bool => $slot['available']));
        $days[$date] = ['state' => $free > 0 ? 'available' : 'full', 'free' => $free];
    }
    return $days;
}

/** Human-readable reasons why a booking cannot take the given time; used by the admin to warn before saving. */
function bookingConflicts(string $date, string $time, int $duration, array $bookings, array $blocks, ?string $excludeId = null): array
{
    $conflicts = [];
    $start = minutesFromTime($time);
    $hours = dayHours($date);
    if ($hours === null) $conflicts[] = 'V tento deň je štúdio podľa otváracích hodín zatvorené.';
    elseif ($start < $hours[0] || $start + $duration > $hours[1]) $conflicts[] = 'Termín presahuje otváracie hodiny (' . formatTime($hours[0]) . ' – ' . formatTime($hours[1]) . ').';
    foreach (blocksForDate($date, $blocks) as $block) {
        if (bookingOverlap($start, $duration, $block['start'], $block['end'] - $block['start'])) $conflicts[] = 'Prekrýva sa s blokáciou' . ($block['label'] !== '' ? ' „' . $block['label'] . '“' : '') . '.';
    }
    foreach ($bookings as $booking) {
        if (($booking['date'] ?? '') !== $date || bookingStatus($booking) === 'cancelled' || ($booking['id'] ?? '') === $excludeId) continue;
        if (bookingOverlap($start, $duration, minutesFromTime((string) $booking['time']), (int) ($booking['duration'] ?? 0))) {
            $conflicts[] = 'Prekrýva sa s rezerváciou ' . $booking['id'] . ' (' . $booking['time'] . ' – ' . ($booking['end_time'] ?? '') . ', ' . ($booking['name'] ?? '') . ').';
        }
    }
    return $conflicts;
}

/**
 * Opening hours grouped into consecutive days with the same hours, starting the week
 * where the hours change, e.g. "Utorok – Sobota: 8:00 – 18:00", "Nedeľa – Pondelok: Zatvorené".
 */
function openingHoursGroups(): array
{
    $hours = bookingSettings()['hours'];
    $value = static function (int $day) use ($hours): string {
        $range = $hours[(string) $day] ?? null;
        return is_array($range) ? preg_replace('/^0(\d)/', '$1', $range[0]) . ' – ' . preg_replace('/^0(\d)/', '$1', $range[1]) : '';
    };
    $start = 1;
    for ($day = 1; $day <= 7; $day++) {
        if ($value($day) !== $value($day === 1 ? 7 : $day - 1)) { $start = $day; break; }
    }
    $groups = [];
    for ($i = 0; $i < 7; $i++) {
        $day = (($start - 1 + $i) % 7) + 1;
        $current = $value($day);
        $last = count($groups) - 1;
        if ($last >= 0 && $groups[$last]['value'] === $current) { $groups[$last]['to'] = $day; continue; }
        $groups[] = ['from' => $day, 'to' => $day, 'value' => $current];
    }
    return array_map(static fn (array $group): array => [
        'label' => DAY_NAMES[$group['from']] . ($group['to'] !== $group['from'] ? ' – ' . DAY_NAMES[$group['to']] : ''),
        'short' => DAY_SHORT[$group['from']] . ($group['to'] !== $group['from'] ? ' – ' . DAY_SHORT[$group['to']] : ''),
        'value' => $group['value'] !== '' ? $group['value'] : 'Zatvorené',
        'open' => $group['value'] !== '',
    ], $groups);
}

function formatDateSk(string $date, bool $withWeekday = true): string
{
    $time = strtotime($date);
    $text = date('j. n. Y', $time);
    return $withWeekday ? mb_strtolower(DAY_NAMES[(int) date('N', $time)]) . ' ' . $text : $text;
}

function durationLabel(int $minutes): string
{
    if ($minutes % 60 !== 0) return sprintf('%d h %02d min', intdiv($minutes, 60), $minutes % 60);
    $hours = intdiv($minutes, 60);
    return $hours . ' ' . ($hours === 1 ? 'hodina' : ($hours <= 4 ? 'hodiny' : 'hodín'));
}

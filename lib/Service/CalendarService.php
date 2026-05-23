<?php

declare(strict_types=1);

namespace OCA\AdminPage\Service;

use OCP\IDBConnection;
use Sabre\VObject\Reader;

/**
 * Org-wide upcoming calendar events.
 *
 * Resolves every organization member's calendars, finds VEVENT objects whose
 * series overlaps the upcoming window, and expands recurrences (via Sabre\VObject)
 * to the next concrete occurrence per event.
 */
class CalendarService {

    private IDBConnection $db;

    /** How far ahead (days) to look for upcoming events. */
    private const WINDOW_DAYS = 90;

    /** Hard cap on the number of events returned. */
    private const MAX_EVENTS = 300;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    /**
     * @param int $orgId
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingEvents(int $orgId): array {
        $uids = $this->resolveMemberUids($orgId);
        if (empty($uids)) {
            return [];
        }

        $calendars = $this->resolveCalendars($uids);
        if (empty($calendars)) {
            return [];
        }

        $now       = new \DateTimeImmutable('now');
        $windowEnd = $now->add(new \DateInterval('P' . self::WINDOW_DAYS . 'D'));

        $objects = $this->fetchCandidateObjects(
            array_keys($calendars),
            $now->getTimestamp(),
            $windowEnd->getTimestamp()
        );

        $displayNames = $this->resolveDisplayNames($uids);

        $events = [];
        foreach ($objects as $row) {
            $event = $this->expandNextOccurrence($row, $calendars, $displayNames, $now, $windowEnd);
            if ($event !== null) {
                $events[] = $event;
            }
        }

        usort($events, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        return array_slice($events, 0, self::MAX_EVENTS);
    }

    /**
     * Org member UIDs: every organization_members row plus the org admin_uid.
     *
     * @return string[]
     */
    private function resolveMemberUids(int $orgId): array {
        $uids = [];

        $stmt = $this->db->prepare("SELECT user_uid FROM *PREFIX*organization_members WHERE organization_id = ?");
        $stmt->bindValue(1, $orgId, \PDO::PARAM_INT);
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            if (!empty($row['user_uid'])) {
                $uids[$row['user_uid']] = true;
            }
        }

        $stmt = $this->db->prepare("SELECT admin_uid FROM *PREFIX*organizations WHERE id = ? LIMIT 1");
        $stmt->bindValue(1, $orgId, \PDO::PARAM_INT);
        $stmt->execute();
        $admin = $stmt->fetch();
        if ($admin && !empty($admin['admin_uid'])) {
            $uids[$admin['admin_uid']] = true;
        }

        return array_keys($uids);
    }

    /**
     * Member UIDs → their calendars.
     *
     * @param string[] $uids
     * @return array<int, array{displayname: string, color: ?string, owner: string}>  calendarid => meta
     */
    private function resolveCalendars(array $uids): array {
        $principals = array_map(static function ($uid) {
            return 'principals/users/' . $uid;
        }, $uids);

        $placeholders = implode(',', array_fill(0, count($principals), '?'));
        $sql = "
            SELECT id, principaluri, displayname, calendarcolor
            FROM *PREFIX*calendars
            WHERE principaluri IN ({$placeholders})
              AND deleted_at IS NULL
        ";
        $stmt = $this->db->prepare($sql);
        $idx = 1;
        foreach ($principals as $p) {
            $stmt->bindValue($idx++, $p, \PDO::PARAM_STR);
        }
        $stmt->execute();

        $calendars = [];
        foreach ($stmt->fetchAll() as $row) {
            $owner = str_replace('principals/users/', '', (string)$row['principaluri']);
            $calendars[(int)$row['id']] = [
                'displayname' => (string)($row['displayname'] ?? ''),
                'color'       => $row['calendarcolor'] !== null ? (string)$row['calendarcolor'] : null,
                'owner'       => $owner,
            ];
        }
        return $calendars;
    }

    /**
     * Candidate VEVENT objects whose series overlaps [now, windowEnd].
     *
     * @param int[] $calendarIds
     * @return array<int, array<string, mixed>>
     */
    private function fetchCandidateObjects(array $calendarIds, int $nowTs, int $windowEndTs): array {
        if (empty($calendarIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($calendarIds), '?'));
        $sql = "
            SELECT id, uid, calendarid, calendardata, firstoccurence, lastoccurence
            FROM *PREFIX*calendarobjects
            WHERE calendarid IN ({$placeholders})
              AND componenttype = 'VEVENT'
              AND deleted_at IS NULL
              AND lastoccurence >= ?
              AND firstoccurence <= ?
            ORDER BY firstoccurence ASC
        ";
        $stmt = $this->db->prepare($sql);
        $idx = 1;
        foreach ($calendarIds as $cid) {
            $stmt->bindValue($idx++, $cid, \PDO::PARAM_INT);
        }
        $stmt->bindValue($idx++, $nowTs, \PDO::PARAM_INT);
        $stmt->bindValue($idx++, $windowEndTs, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * UID → display name (fallback to uid).
     *
     * @param string[] $uids
     * @return array<string, string>
     */
    private function resolveDisplayNames(array $uids): array {
        $placeholders = implode(',', array_fill(0, count($uids), '?'));
        $sql = "SELECT uid, displayname FROM *PREFIX*users WHERE uid IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $idx = 1;
        foreach ($uids as $uid) {
            $stmt->bindValue($idx++, $uid, \PDO::PARAM_STR);
        }
        $stmt->execute();

        $names = [];
        foreach ($stmt->fetchAll() as $row) {
            $names[(string)$row['uid']] = (string)($row['displayname'] ?? $row['uid']);
        }
        return $names;
    }

    /**
     * Parse one calendar object, expand recurrences into the window, return the next occurrence.
     *
     * @param array<string, mixed> $row
     * @param array<int, array{displayname: string, color: ?string, owner: string}> $calendars
     * @param array<string, string> $displayNames
     * @return array<string, mixed>|null
     */
    private function expandNextOccurrence(
        array $row,
        array $calendars,
        array $displayNames,
        \DateTimeImmutable $now,
        \DateTimeImmutable $windowEnd
    ): ?array {
        $calId = (int)$row['calendarid'];
        $cal = $calendars[$calId] ?? null;
        if ($cal === null) {
            return null;
        }

        try {
            $vobject = Reader::read($row['calendardata']);
            // expand() is non-mutating in sabre/vobject 4.x: it returns a NEW VCalendar
            // containing the concrete occurrences within [now, windowEnd]. Non-recurring
            // events are simply filtered by range.
            $expanded = $vobject->expand($now, $windowEnd);
        } catch (\Throwable $e) {
            // Malformed blob — skip this object rather than break the whole list.
            return null;
        }

        if ($expanded === null) {
            return null;
        }

        $nowTs = $now->getTimestamp();
        $best  = null; // earliest occurrence with start >= now

        // select('VEVENT') returns every VEVENT component (iterating $expanded->VEVENT
        // would walk the first component's properties instead of sibling occurrences).
        foreach ($expanded->select('VEVENT') as $vevent) {
            if (!isset($vevent->DTSTART)) {
                continue;
            }
            try {
                $startTs = $vevent->DTSTART->getDateTime()->getTimestamp();
            } catch (\Throwable $e) {
                continue;
            }
            if ($startTs < $nowTs) {
                continue;
            }
            if ($best !== null && $startTs >= $best['start']) {
                continue;
            }

            $endTs = null;
            if (isset($vevent->DTEND)) {
                try {
                    $endTs = $vevent->DTEND->getDateTime()->getTimestamp();
                } catch (\Throwable $e) {
                    $endTs = null;
                }
            }

            $title = isset($vevent->SUMMARY) ? trim((string)$vevent->SUMMARY) : '';
            if ($title === '') {
                $title = 'Busy';
            }
            $location = isset($vevent->LOCATION) ? trim((string)$vevent->LOCATION) : '';

            $best = [
                'id'            => (int)$row['id'],
                'uid'           => (string)$row['uid'],
                'title'         => $title,
                'start'         => $startTs,
                'end'           => $endTs,
                'allDay'        => !$vevent->DTSTART->hasTime(),
                'member'        => $cal['owner'],
                'memberName'    => $displayNames[$cal['owner']] ?? $cal['owner'],
                'calendar'      => $cal['displayname'],
                'calendarColor' => $cal['color'],
                'location'      => $location !== '' ? $location : null,
            ];
        }

        return $best;
    }
}

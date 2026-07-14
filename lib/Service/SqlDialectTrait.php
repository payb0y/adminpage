<?php

declare(strict_types=1);

namespace OCA\AdminPage\Service;

use OCP\IDBConnection;

/**
 * Emits dialect-correct SQL fragments so the raw-SQL services run on both
 * MariaDB/MySQL (dev) and PostgreSQL (prod). The platform is detected once via
 * IDBConnection::getDatabaseProvider(); the using class must expose $this->db.
 *
 * Only fragments that genuinely differ between the two engines live here.
 * Constructs that are already portable are used inline without a helper:
 *   - NOW(), GREATEST(), COALESCE()
 *   - CAST(x AS DATE), CURRENT_DATE
 *   - reserved words used *qualified* (e.g. `s.order`) — valid in both because
 *     the post-dot position accepts any keyword as a column label.
 */
trait SqlDialectTrait {

    private ?bool $sqlIsPostgres = null;

    private function isPostgres(): bool {
        if ($this->sqlIsPostgres === null) {
            $this->sqlIsPostgres =
                $this->db->getDatabaseProvider() === IDBConnection::PLATFORM_POSTGRES;
        }
        return $this->sqlIsPostgres;
    }

    /**
     * Cast to a plain integer. MySQL's CAST has no INTEGER target (only
     * SIGNED/UNSIGNED); Postgres has no UNSIGNED type. Used to join the VARCHAR
     * `custom_projects.board_id` to the INT `deck_*.board_id`/`deck_boards.id`.
     */
    private function castInt(string $expr): string {
        // NULLIF guards empty-string board_ids: Postgres errors on
        // CAST('' AS INTEGER) (invalid input syntax), and MySQL would coerce
        // '' to 0. Empty -> NULL -> the join simply doesn't match (correct).
        // Assumes $expr is a text column that is either empty or numeric.
        return $this->isPostgres()
            ? "CAST(NULLIF($expr, '') AS INTEGER)"
            : "CAST(NULLIF($expr, '') AS UNSIGNED)";
    }

    /**
     * Cast to text. Postgres `CHAR` means `CHAR(1)` (silently truncates!);
     * MySQL's CAST has no TEXT target. Used for the reverse int->varchar join.
     */
    private function castText(string $expr): string {
        return $this->isPostgres()
            ? "CAST($expr AS TEXT)"
            : "CAST($expr AS CHAR)";
    }

    /** A datetime column/expression as a unix-epoch integer. */
    private function toEpoch(string $expr): string {
        return $this->isPostgres()
            ? "EXTRACT(EPOCH FROM $expr)::bigint"
            : "UNIX_TIMESTAMP($expr)";
    }

    /** Current time as a unix-epoch integer. */
    private function nowEpoch(): string {
        return $this->isPostgres()
            ? "EXTRACT(EPOCH FROM NOW())::bigint"
            : "UNIX_TIMESTAMP()";
    }

    /** Add a whole number of days to a date expression. */
    private function dateAddDays(string $dateExpr, int $days): string {
        return $this->isPostgres()
            ? "($dateExpr + $days)"
            : "DATE_ADD($dateExpr, INTERVAL $days DAY)";
    }

    /** A unix-epoch integer column/expression as a datetime. */
    private function fromEpoch(string $expr): string {
        return $this->isPostgres()
            ? "TO_TIMESTAMP($expr)"
            : "FROM_UNIXTIME($expr)";
    }

    /**
     * Whole-day difference ($a - $b) between two date/datetime expressions,
     * matching MySQL DATEDIFF: a calendar-day count that ignores the time part.
     * Postgres date subtraction yields an integer number of days, but only when
     * both operands are DATE (timestamp subtraction yields an interval), hence
     * the explicit casts.
     */
    private function datediffDays(string $a, string $b): string {
        return $this->isPostgres()
            ? "(CAST($a AS DATE) - CAST($b AS DATE))"
            : "DATEDIFF($a, $b)";
    }
}

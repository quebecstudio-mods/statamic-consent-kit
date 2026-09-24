<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Query\Filters;

use Illuminate\Database\Query\Builder;
use Statamic\Query\Scopes\Filter;

/** When the decision was made, by the server clock. */
class DecidedAt extends Filter
{
    /** @var bool Untyped in the parent, so it cannot be typed here. */
    protected $pinned = true;

    public static function title(): string
    {
        return __('Decided');
    }

    /** @param string $key */
    public function visibleTo($key): bool
    {
        return $key === 'consent-decisions';
    }

    /** @return array<string, mixed> */
    public function fieldItems(): array
    {
        return [
            'from' => [
                'display' => __('From'),
                'type' => 'date',
                'time_enabled' => false,
                'width' => 50,
            ],
            'to' => [
                'display' => __('To'),
                'type' => 'date',
                'time_enabled' => false,
                'width' => 50,
            ],
        ];
    }

    /** @param array<string, mixed> $values */
    public function apply($query, $values): void
    {
        /** @var Builder $query */
        if ($from = $this->date($values['from'] ?? null)) {
            $query->where('d.decidedAt', '>=', $from . ' 00:00:00');
        }

        if ($to = $this->date($values['to'] ?? null)) {
            $query->where('d.decidedAt', '<=', $to . ' 23:59:59');
        }
    }

    /** @param array<string, mixed> $values */
    public function badge($values): string
    {
        $from = $this->date($values['from'] ?? null);
        $to = $this->date($values['to'] ?? null);

        return match (true) {
            $from !== null && $to !== null => __('Decided') . ": $from – $to",
            $from !== null => __('Decided') . ' ≥ ' . $from,
            $to !== null => __('Decided') . ' ≤ ' . $to,
            default => __('Decided'),
        };
    }

    /** The date fieldtype hands back a string or a `{date: …}` array. */
    private function date(mixed $value): ?string
    {
        $value = is_array($value) ? ($value['date'] ?? null) : $value;

        if (!is_string($value) || $value === '') {
            return null;
        }

        return substr($value, 0, 10);
    }
}

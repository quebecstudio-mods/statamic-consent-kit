<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Query\Filters;

use Illuminate\Database\Query\Builder;
use QuebecStudioMods\ConsentKit\Core\Decision;
use Statamic\Query\Scopes\Filter;

/** What the visitor granted, beyond what the site requires. */
class Outcome extends Filter
{
    /** @var bool Untyped in the parent, so it cannot be typed here. */
    protected $pinned = true;

    public static function title(): string
    {
        return __('Granted');
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
            'outcome' => [
                'type' => 'radio',
                'options' => [
                    Decision::OUTCOME_ALL => __('Everything'),
                    Decision::OUTCOME_SOME => __('Some categories'),
                    Decision::OUTCOME_NONE => __('Required only'),
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $values */
    public function apply($query, $values): void
    {
        /** @var Builder $query */
        if (in_array($values['outcome'] ?? null, Decision::OUTCOMES, true)) {
            $query->where('d.outcome', $values['outcome']);
        }
    }

    /** @param array<string, mixed> $values */
    public function badge($values): string
    {
        $labels = [
            Decision::OUTCOME_ALL => __('Everything'),
            Decision::OUTCOME_SOME => __('Some categories'),
            Decision::OUTCOME_NONE => __('Required only'),
        ];

        return __('Granted') . ': ' . ($labels[$values['outcome'] ?? ''] ?? '');
    }
}

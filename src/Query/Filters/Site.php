<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Query\Filters;

use Illuminate\Database\Query\Builder;
use Statamic\Facades\Site as Sites;
use Statamic\Query\Scopes\Filter;
use Statamic\Sites\Site as StatamicSite;

/**
 * Which site a decision was given on. Hidden on a single-site install, where
 * it would only ever hold one answer.
 */
class Site extends Filter
{
    /** @var bool Untyped in the parent, so it cannot be typed here. */
    protected $pinned = true;

    public static function title(): string
    {
        return __('Site');
    }

    /** @param string $key */
    public function visibleTo($key): bool
    {
        return $key === 'consent-decisions' && Sites::hasMultiple();
    }

    /** @return array<string, mixed> */
    public function fieldItems(): array
    {
        $options = Sites::all()
            ->mapWithKeys(fn (StatamicSite $site): array => [$site->handle() => $site->name()])
            ->all();

        return [
            'site' => [
                'type' => count($options) > 5 ? 'select' : 'radio',
                'options' => $options,
            ],
        ];
    }

    /** @param array<string, mixed> $values */
    public function apply($query, $values): void
    {
        /** @var Builder $query */
        if (is_string($values['site'] ?? null) && $values['site'] !== '') {
            $query->where('d.siteHandle', $values['site']);
        }
    }

    /** @param array<string, mixed> $values */
    public function badge($values): string
    {
        return __('Site') . ': ' . ($values['site'] ?? '');
    }
}

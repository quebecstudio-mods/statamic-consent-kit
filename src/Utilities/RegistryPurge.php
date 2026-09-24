<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Utilities;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use QuebecStudioMods\ConsentKit\Statamic\Registry\Decisions;
use QuebecStudioMods\ConsentKit\Statamic\ServiceProvider;
use Statamic\Facades\Utility;

/**
 * Deleting records by hand, beside the retention that removes outlived ones on
 * its own. A utility is run and forgotten, which is why the register itself
 * lives in the navigation instead: it is reread months later.
 */
class RegistryPurge
{
    private const DOCS = 'https://github.com/quebecstudio-mods/statamic-consent-kit/blob/6.x/DOCUMENTATION.md';

    private const PERIODS = [36, 24, 12, 6];

    public static function register(string $icon): void
    {
        Utility::register('consent-purge')
            ->title(__('Consent purge'))
            ->icon($icon)
            ->description(__('Delete consent records older than a given age.'))
            ->docsUrl(self::DOCS)
            ->inertia('ConsentPurge', fn (): array => self::data())
            ->routes(function (Router $router): void {
                $router->post('/', [self::class, 'purge'])->name('purge');
            });
    }

    public function purge(Request $request): RedirectResponse
    {
        abort_unless(Gate::allows('purge consent register'), 403);

        $months = max(0, (int)$request->input('months', 0));
        $deleted = app(Decisions::class)->purgeOlderThan($months);

        return back()->with('success', __(':count records deleted.', ['count' => $deleted]));
    }
    /** @return array<string, mixed> */
    private static function data(): array
    {
        $decisions = app(Decisions::class);
        $total = $decisions->total();

        $periods = array_map(
            static fn (int $months): array => ['value' => (string)$months, 'label' => __(':n months', ['n' => $months])],
            self::PERIODS,
        );

        $periods[] = ['value' => '0', 'label' => __('All records')];

        return [
            'total' => $total,
            'oldest' => $decisions->oldest(),
            'canPurge' => Gate::allows('purge consent register'),
            'urls' => ['purge' => cp_route('utilities.consent-purge.purge')],
            'icon' => ServiceProvider::icon(),
            'labels' => [
                'title' => __('Consent purge'),

                'counted' => $total === 0
                    ? __('No decision recorded yet.')
                    : trans_choice(':count decision recorded, the oldest on :date.|:count decisions recorded, the oldest on :date.', $total, [
                        'count' => $total,
                        'date' => $decisions->oldest(),
                    ]),
                'deleteOlderThan' => __('Delete records older than'),
                'retention' => __('Retention removes outlived records on its own. This is for a deletion that cannot wait.'),
                'warning' => __('Purging frees nobody: consent lives in the visitor’s cookie and keeps applying. What goes is the proof of it, and a site that still acts on a consent it can no longer show has the worst of both.'),
                'notAllowed' => __('You do not have permission to purge the register.'),
                'purge' => __('Purge'),
                'confirm' => __('Deleting records cannot be undone. Continue?'),
                'cancel' => __('Cancel'),
                'periods' => $periods,
            ],
        ];
    }
}

<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use QuebecStudioMods\ConsentKit\Core\Decision;
use QuebecStudioMods\ConsentKit\Statamic\Registry\Decisions;
use QuebecStudioMods\ConsentKit\Statamic\ServiceProvider;
use Statamic\CP\Column;
use Statamic\Facades\Scope;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reading the register. Never gated on the edition: what was collected stays
 * readable, exportable and purgeable whatever the licence says afterwards.
 */
class RegistryController extends CpController
{
    use QueriesFilters;

    private const PER_PAGE = 50;

    public function __construct(private readonly Decisions $decisions)
    {
        parent::__construct(request());
    }

    public function index(): InertiaResponse
    {
        $this->authorize('view consent register');

        return Inertia::render('ConsentRegistry', [
            'columns' => $this->columns(),
            'filters' => Scope::filters('consent-decisions'),
            'suspended' => $this->decisions->isSuspended(),
            'collecting' => $this->decisions->isCollecting(),
            'canExport' => Gate::allows('export consent register'),
            'urls' => [
                'data' => cp_route('cookie-consent-kit.registry.data'),
                'export' => cp_route('cookie-consent-kit.registry.export'),
            ],
            'icon' => ServiceProvider::icon(),

            'labels' => $this->labels(),
        ]);
    }
    public function show(string $id): InertiaResponse
    {
        $this->authorize('view consent register');

        $decision = $this->decisions->find($id);

        if ($decision === null) {
            throw new NotFoundHttpException();
        }

        $payload = json_decode((string)($decision['payload'] ?? ''), true);

        return Inertia::render('ConsentDecision', [
            'decision' => $decision,

            'screen' => is_array($payload) ? $payload : null,
            'urls' => ['index' => cp_route('cookie-consent-kit.registry.index')],
            'icon' => ServiceProvider::icon(),
            'labels' => $this->decisionLabels($id),
        ]);
    }

    /** Streamed in batches: an export must not depend on the register fitting in memory. */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export consent register');

        $format = $request->query('format') === 'json' ? 'json' : 'csv';
        $filters = $this->filters($request);

        return response()->streamDownload(
            fn () => $format === 'json' ? $this->streamJson($filters) : $this->streamCsv($filters),
            'consent-register-' . date('Y-m-d') . '.' . $format,
            ['Content-Type' => $format === 'json' ? 'application/json' : 'text/csv'],
        );
    }

    /** @param array<string, mixed> $filters */
    private function streamCsv(array $filters): void
    {
        $handle = fopen('php://output', 'wb');

        if ($handle === false) {
            return;
        }

        fputcsv($handle, ['id', 'decidedAt', 'site', 'language', 'action', 'origin', 'granted', 'categories', 'consentVersion', 'screen']);

        foreach ($this->batches($filters) as $row) {
            fputcsv($handle, [
                $row['id'], $row['decidedAt'], $row['siteHandle'], $row['language'],
                $row['action'], $row['origin'], $row['outcome'],
                json_encode($row['categories'], JSON_THROW_ON_ERROR),
                $row['consentVersion'], $row['hash'] ?? '',
            ]);
        }

        fclose($handle);
    }

    /**
     * Screens are dereferenced, so a fingerprint can be recomputed from the
     * export alone.
     *
     * @param  array<string, mixed>  $filters
     */
    private function streamJson(array $filters): void
    {
        echo '{"exportedAt":' . json_encode(date('c'), JSON_THROW_ON_ERROR)
            . ',"fingerprint":{"algorithm":"sha256","over":"the canonical JSON of the screen, keys sorted"}'
            . ',"decisions":[';

        $first = true;
        $screens = [];

        foreach ($this->batches($filters) as $row) {
            echo $first ? '' : ',';
            $first = false;

            if (($hash = (string)($row['hash'] ?? '')) !== '') {
                $screens[$hash] = true;
            }

            echo json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }

        echo '],"screens":' . json_encode($this->decisions->screens(array_keys($screens)), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . '}';
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return iterable<array<string, mixed>>
     */
    private function batches(array $filters): iterable
    {
        $page = 1;

        do {
            $batch = $this->decisions->page($filters, $page, 500);

            yield from $batch['rows'];

            $page++;
        } while ($batch['rows'] !== [] && ($page - 1) * 500 < $batch['total']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decisionLabels(string $id): array
    {
        return [
            'title' => __('Decision :id', ['id' => $id]),
            'back' => __('Back to the register'),
            'recorded' => __('What was recorded'),
            'onScreen' => __('What was on screen'),
            'decided' => __('Decided'),
            'site' => __('Site'),
            'answer' => __('Answer'),
            'categoriesAnswered' => __('Categories answered'),
            'consentVersion' => __('Consent version'),
            'policy' => __('Policy'),
            'signedInAs' => __('Signed in as'),
            'address' => __('Address'),
            'browser' => __('Browser'),
            'accepted' => __('Accepted'),
            'refused' => __('Refused'),
            'category' => __('Category'),
            'label' => __('Label'),
            'description' => __('Description'),
            'cookies' => __('Cookies'),
            'alwaysOn' => __('Always on'),
            'everyString' => __('Every string that was displayed'),
            'notStored' => __('The presentation was not stored.'),
            'fingerprint' => __('Fingerprint of this screen. Decisions sharing it were shown exactly the same wording, and it lets anyone recompute the text below to check that nothing moved.'),
            'actions' => [
                'accept-all' => __('Accepted everything'),
                'refuse-all' => __('Refused everything'),
                'save' => __('Chose category by category'),
            ],
            'origins' => [
                'banner' => __('from the banner'),
                'dialog' => __('from the preferences panel'),
                'gpc' => __('from the browser’s Global Privacy Control signal'),
            ],
        ];
    }

    /**
     * The rows the listing asks for. The shape is the one every core listing
     * returns: `data`, plus the `meta` the component draws itself from.
     */
    /** @return array<string, mixed> */
    public function data(FilteredRequest $request): array
    {
        $this->authorize('view consent register');

        $query = $this->decisions->query();

        $badges = $this->queryFilters($query, $request->filters ?? [], []);

        $page = max(1, (int) $request->input('page', 1));
        $perPage = (int) $request->input('perPage', self::PER_PAGE);
        /** @var int $total */
        $total = (clone $query)->count();

        $rows = $this->decisions->rows(
            $query,
            $page,
            $perPage,
            (string) $request->input('sort', 'decidedAt'),
            (string) $request->input('order', 'desc'),
        );

        return [
            'data' => array_map(fn (array $row): array => $this->row($row), $rows),
            'links' => [],
            'meta' => [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / max(1, $perPage))),
                'per_page' => $perPage,
                'total' => $total,

                'from' => $rows === [] ? null : ($page - 1) * $perPage + 1,
                'to' => $rows === [] ? null : ($page - 1) * $perPage + count($rows),
                'columns' => $this->columns(),
                'activeFilterBadges' => $badges,
                'sortColumn' => $request->input('sort', 'decidedAt'),
                'sortDirection' => $request->input('order', 'desc'),
            ],
        ];
    }

    /**
     * One row, its wording resolved server side.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function row(array $row): array
    {
        $labels = $this->labels();

        return [
            'id' => $row['id'],
            'decidedAt' => $row['decidedAt'],
            'site' => $row['siteHandle'],
            'answer' => $labels['actions'][$row['action']] ?? $row['action'],
            'origin' => $labels['origins'][$row['origin']] ?? $row['origin'],
            'granted' => $labels['granted_'][$row['outcome']] ?? $row['outcome'],

            'screen' => substr((string) ($row['hash'] ?? ''), 0, 12),
            'url' => cp_route('cookie-consent-kit.registry.show', $row['id']),
        ];
    }

    /**
     * Fixed columns. Origin travels under Answer; Site appears where there is
     * more than one.
     *
     * @return array<int, array<string, mixed>>
     */
    private function columns(): array
    {
        $columns = [Column::make('decidedAt')->label(__('Decided'))];

        if (Site::hasMultiple()) {
            $columns[] = Column::make('site')->label(__('Site'));
        }

        $columns[] = Column::make('answer')->label(__('Answer'));
        $columns[] = Column::make('granted')->label(__('Granted'));
        $columns[] = Column::make('screen')->label(__('Screen'))->sortable(false);

        return array_map(static fn (Column $column): array => $column->toArray(), $columns);
    }

    /**
     * Every string the page shows, so the component holds none.
     *
     * @return array<string, mixed>
     */
    private function labels(): array
    {
        $granted = [
            'all' => __('Everything'),
            'some' => __('Some categories'),
            'none' => __('Required only'),
        ];

        return [
            'title' => __('Consent register'),
            'export' => __('Export'),
            'site' => __('Site'),
            'allSites' => __('All sites'),
            'granted' => __('Granted'),
            'from' => __('From'),
            'to' => __('To'),
            'clearFilters' => __('Clear filters'),
            'decided' => __('Decided'),
            'answer' => __('Answer'),
            'screen' => __('Screen'),
            'empty' => __('No decision recorded yet.'),
            'suspended' => __('This install asks for a register in its configuration. Without the Pro edition it stays dormant, and nothing is written. Records already kept remain readable, exportable and purgeable.'),
            'notCollecting' => __('Collection is off. What is listed here was recorded while it was on.'),
            'granted_' => $granted,
            'outcomes' => array_merge(
                [['value' => '', 'label' => __('Any')]],
                array_map(
                    static fn (string $outcome): array => ['value' => $outcome, 'label' => $granted[$outcome]],
                    Decision::OUTCOMES,
                ),
            ),
            'actions' => [
                'accept-all' => __('Accepted everything'),
                'refuse-all' => __('Refused everything'),
                'save' => __('Chose category by category'),
            ],
            'origins' => [
                'banner' => __('from the banner'),
                'dialog' => __('from the preferences panel'),
                'gpc' => __('from the browser’s Global Privacy Control signal'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return array_filter([
            'site' => $request->query('site'),
            'outcome' => $request->query('outcome'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ], static fn (mixed $value): bool => is_string($value) && $value !== '');
    }
}

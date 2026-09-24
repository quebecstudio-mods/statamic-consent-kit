<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Registry;

use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use QuebecStudioMods\ConsentKit\Core\Decision;
use QuebecStudioMods\ConsentKit\Core\Presentation;
use QuebecStudioMods\ConsentKit\Core\Registry;
use QuebecStudioMods\ConsentKit\Register\Register;
use QuebecStudioMods\ConsentKit\Statamic\Edition;
use QuebecStudioMods\ConsentKit\Statamic\Settings\SettingsStore;
use Statamic\Facades\Site;
use Statamic\Facades\User;

/**
 * The consent register: what was answered, and the wording it was answered on.
 *
 * Everything but the answer comes from the server, the fingerprint included:
 * a decision attests what the site was showing, not what a browser claimed.
 */
class Decisions
{
    private const CACHE_ANY = 'cookie-consent-kit:registry:any';

    public function __construct(
        private readonly Register $repository,
        private readonly SettingsStore $settings,
    ) {
    }

    /** What the site asked for, whatever the licence says. */
    public function isEnabled(): bool
    {
        return (bool)($this->settings->all()['registry'] ?? false);
    }

    /** Whether anything is actually written. */
    public function isCollecting(): bool
    {
        return $this->isEnabled() && Edition::isPro();
    }

    /** Asked for, but not licensed. */
    public function isSuspended(): bool
    {
        return $this->isEnabled() && !Edition::isPro();
    }

    public function canEnable(): bool
    {
        return Edition::isPro();
    }

    /** Enabled, or not empty: a downgrade never puts a proof out of reach. */
    public function isVisible(): bool
    {
        return $this->isEnabled() || $this->hasRecords();
    }

    public function hasRecords(): bool
    {
        return (bool)Cache::remember(self::CACHE_ANY, 300, fn () => $this->repository->hasRecords());
    }

    /**
     * Records one reported decision. Returns null when anything about it does
     * not match what this site offers.
     *
     * @param  array<string, mixed>  $categories
     */
    public function record(string $siteHandle, string $action, string $origin, array $categories): ?string
    {
        $site = Site::get($siteHandle);

        if (!$this->isCollecting() || $site === null) {
            return null;
        }

        if (!in_array($action, Decision::ACTIONS, true) || !in_array($origin, Decision::ORIGINS, true)) {
            return null;
        }

        $config = $this->configFor($siteHandle);
        $offered = $config['categories'] ?? [];
        $answer = Decision::reconcile($categories, $offered);

        if ($answer === null) {
            return null;
        }

        $settings = $this->settings->all();
        $context = (bool)($settings['registryRequestContext'] ?? false);

        $id = $this->repository->store([
            'presentationId' => $this->presentationId($config),
            'siteHandle' => $siteHandle,
            'userId' => ($settings['registryUser'] ?? true) ? $this->currentUserId() : null,
            'language' => (string)($config['language'] ?? ''),
            'decidedAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'action' => $action,
            'origin' => $origin,
            'categories' => $answer,
            'outcome' => Decision::outcome($answer, $offered),
            'consentVersion' => (int)($config['version'] ?? 1),
            'policyUrl' => $config['policyUrl'] ?? null,
            'ip' => $context ? request()->ip() : null,
            'userAgent' => $context ? substr((string)request()->userAgent(), 0, 512) : null,
        ]);

        Cache::put(self::CACHE_ANY, true, 300);

        return $id;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    public function page(array $filters = [], int $page = 1, int $perPage = 50, string $sort = 'decidedAt', string $dir = 'desc'): array
    {
        return $this->repository->page($filters, $page, $perPage, $sort, $dir);
    }

    /** The query the panel listing filters, sorts and pages. */
    public function query(): Builder
    {
        return $this->repository->query();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rows(Builder $query, int $page, int $perPage, string $sort, string $dir): array
    {
        return $this->repository->rows($query, $page, $perPage, $sort, $dir);
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        return $this->repository->find($id);
    }

    /**
     * The stored screens for these fingerprints, so an export carries the
     * wording its decisions were made on.
     *
     * @param  array<int, string>  $hashes
     * @return array<string, mixed>
     */
    public function screens(array $hashes): array
    {
        return $this->repository->screens($hashes);
    }

    public function total(): int
    {
        return $this->repository->total();
    }

    public function oldest(): ?string
    {
        return $this->repository->oldest();
    }

    /** Deletes records older than the given months; zero deletes every one. */
    public function purgeOlderThan(int $months): int
    {
        $before = $months > 0
            ? (new DateTimeImmutable())->modify("-$months months")->format('Y-m-d H:i:s')
            : null;

        $deleted = $this->repository->purge($before);

        Cache::forget(self::CACHE_ANY);

        return $deleted;
    }

    /** Retention: the cookie's own life plus the grace months. Zero keeps everything. */
    public function purge(): int
    {
        $settings = $this->settings->all();

        $before = Registry::cutoff(
            (int)($settings['cookieMaxAge'] ?? 0),
            (int)($settings['registryGrace'] ?? 0),
        );

        return $before === null ? 0 : $this->repository->purge($before);
    }

    /** The one identity the server can assert rather than be told. */
    private function currentUserId(): ?string
    {
        $id = User::current()?->getAuthIdentifier();

        return $id === null ? null : (string)$id;
    }

    /**
     * The screen as it is now, stored once per distinct fingerprint.
     *
     * @param  array<string, mixed>  $config
     */
    private function presentationId(array $config): string
    {
        $payload = Presentation::payload($config);

        return $this->repository->presentationId([
            'hash' => Presentation::hash($payload),
            'language' => (string)($payload['language'] ?? ''),
            'payload' => Presentation::canonical($payload),
        ]);
    }

    /** @return array<string, mixed> */
    private function configFor(string $siteHandle): array
    {
        return app(Resolved::class)->configFor($siteHandle);
    }
}

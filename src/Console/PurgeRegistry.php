<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Console;

use Illuminate\Console\Command;
use QuebecStudioMods\ConsentKit\Statamic\Registry\Decisions;

/**
 * Applies the register's retention: deletes the records that have outlived the
 * consent they attest, then the screens nothing cites any more.
 *
 * Scheduled daily by the addon. Craft CMS hangs the same work on Craft's own
 * housekeeping; Statamic has none, so this runs with the site's scheduler.
 */
class PurgeRegistry extends Command
{
    /** @var string */
    protected $signature = 'cookie-consent:purge-registry';

    /** @var string */
    protected $description = 'Delete consent records that have outlived their retention.';

    public function handle(Decisions $decisions): int
    {
        if (!$decisions->isEnabled()) {
            $this->components->info('The consent register is off; nothing to purge.');

            return self::SUCCESS;
        }

        $deleted = $decisions->purge();

        $this->components->info($deleted === 0
            ? 'No consent record has outlived its retention.'
            : "Deleted $deleted consent record(s).");

        return self::SUCCESS;
    }
}

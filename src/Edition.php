<?php

namespace QuebecStudioMods\ConsentKit\Statamic;

use QuebecStudioMods\ConsentKit\Statamic\Settings\SettingsStore;
use Statamic\Facades\Addon;

/**
 * Standard collects and honours consent; Pro archives the proof.
 *
 * The edition is what the site declared in `config/statamic/editions.php`,
 * which the Outpost then validates. The gate falls on collecting, never on
 * reading: a register already kept stays readable whatever the licence says.
 */
class Edition
{
    public const STANDARD = 'standard';

    public const PRO = 'pro';

    public static function isPro(): bool
    {
        $addon = Addon::get(SettingsStore::PACKAGE);

        return $addon !== null && $addon->edition() === self::PRO;
    }
}

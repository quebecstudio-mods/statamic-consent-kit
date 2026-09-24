<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Settings;

use QuebecStudioMods\ConsentKit\Core\Settings\Catalogue;

/**
 * The fields of the addon settings screen, which Statamic renders as a publish
 * form and saves to `resources/addons/statamic-consent-kit.yaml`.
 *
 * The wording is the Craft CMS plugin's, so the same setting reads the same
 * across the suite.
 */
class SettingsBlueprint
{
    private const DOCS = 'https://github.com/quebecstudio-mods/statamic-consent-kit/blob/6.x/DOCUMENTATION.md';

    private const REPOSITORY = 'https://github.com/quebecstudio-mods/statamic-consent-kit';

    /** Handle to label and tag, in the order the inventory renders them. */
    private const ELEMENTS = [
        'wrapper' => ['Wrapper', 'div'],
        'section' => ['Category', 'section'],
        'heading' => ['Category title', 'h2>`–`<h6'],
        'description' => ['Category description', 'p'],
        'table' => ['Table', 'table'],
        'thead' => ['Table header', 'thead'],
        'tbody' => ['Table body', 'tbody'],
        'tr' => ['Row', 'tr'],
        'th' => ['Header cell', 'th'],
        'td' => ['Cell', 'td'],
    ];

    /** @var array<string, mixed> */
    private static array $values = [];

    /** @var array<string, bool> */
    private static array $fixed = [];

    private static bool $pro = false;

    private static ?string $registerUrl = null;

    /**
     * @param  array<string, mixed>  $values  the settings in force, shown as defaults
     * @param  array<string, bool>  $fixed  keys the site pinned in its config file
     * @param  bool  $pro  whether the licence opens the register
     * @param  string|null  $registerUrl  linked when there is a register to read
     * @return array<string, mixed>
     */
    public static function contents(array $values = [], array $fixed = [], bool $pro = false, ?string $registerUrl = null): array
    {
        self::$values = $values;
        self::$fixed = $fixed;
        self::$pro = $pro;
        self::$registerUrl = $registerUrl;

        return [
            'tabs' => [
                'main' => [
                    'display' => __('General'),
                    'sections' => [self::section('general'), self::section('cookie'), self::section('policy')],
                ],
                'behaviour' => [
                    'display' => __('Behaviour'),
                    'sections' => [self::section('behaviour'), self::section('measurement'), self::section('video')],
                ],
                'appearance' => [
                    'display' => __('Appearance'),
                    'sections' => [self::section('appearance'), self::inventoryTable()],
                ],
                'inventory' => [
                    'display' => __('Cookie inventory'),
                    'sections' => [self::inventory()],
                ],
                'registry' => [
                    'display' => __('Register'),
                    'sections' => [self::registry()],
                ],
                'help' => [
                    'display' => __('Help'),
                    'sections' => [self::help()],
                ],
            ],
        ];
    }

    /**
     * The control panel has nowhere to put these: the addons screen only
     * carries the marketplace, updates and settings links, and the publish
     * form takes no action of its own. Last, so it never stands between
     * someone and the setting they came to change.
     *
     * @return array<string, mixed>
     */
    private static function help(): array
    {
        $links = [
            '[' . __('Documentation') . '](' . self::DOCS . ') — ' . __('Settings, tags, cookie inventory and the consent register.'),
            '[' . __('Repository') . '](' . self::REPOSITORY . ') — ' . __('Releases, changelog and issues.'),
        ];

        if (self::$registerUrl !== null) {
            $links[] = '[' . __('Consent register') . '](' . self::$registerUrl . ') — ' . __('The decisions recorded so far.');
        }

        return [
            'fields' => self::fields([
                'help' => [
                    'display' => __('Cookie Consent Kit'),
                    'type' => 'info',
                    'state' => 'tip',
                    'content' => '- ' . implode("\n- ", $links),
                ],
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private static function inventoryTable(): array
    {
        $classes = [];

        foreach (self::ELEMENTS as $handle => [$label, $tag]) {
            $classes[$handle] = [
                'display' => __($label) . " `<$tag>`",
                'type' => 'text',
                'width' => '50',
            ];
        }

        return self::custom(
            __('Inventory table'),
            __('The cookie table can be shown in a page of the site — the privacy policy, most of the time. It has no style of its own: it takes on the style of the page around it.'),
            [
                'inventoryFramework' => [
                    'display' => __('CSS framework'),
                    'instructions' => __('The shipped sets match one version of each framework. Custom writes your own.'),
                    'type' => 'select',
                    'options' => [
                        '' => __('None'),
                        'bootstrap' => 'Bootstrap',
                        'bulma' => 'Bulma',
                        'tailwind' => 'Tailwind',
                        'custom' => __('Custom'),
                    ],
                    'width' => '50',
                ],
                'inventoryClasses' => [
                    'display' => __('Custom'),
                    'type' => 'group',
                    'if' => ['inventoryFramework' => 'custom'],
                    'fields' => self::fields($classes),
                ],
            ]
        );
    }

    /** @return array<string, mixed> */
    private static function inventory(): array
    {
        return self::custom(
            __('Cookie inventory'),
            __('This inventory is a compliance record: it must reflect what the site actually sets. A category with no declared cookie is not shown in the banner. Leave it empty to use the shipped categories.'),
            [
                'categories' => [
                    'display' => __('Category'),
                    'instructions' => __('A category with no declared cookie stays hidden, so adding one costs nothing until it is used. Its handle is permanent: the consent cookie stores it, and renaming it would strand every consent already given.'),
                    'type' => 'grid',
                    'mode' => 'stacked',
                    'add_row' => __('Add a category'),
                    'fields' => self::fields([
                        'handle' => [
                            'display' => __('Handle'),
                            'type' => 'text',
                            'validate' => 'required',
                            'width' => '50',
                        ],
                        'required' => [
                            'display' => __('Always on'),
                            'instructions' => __('The visitor cannot refuse it.'),
                            'type' => 'toggle',
                            'width' => '50',
                        ],
                        'label' => [
                            'display' => __('Label'),
                            'type' => 'text',
                            'width' => '50',
                        ],
                        'description' => [
                            'display' => __('Description'),
                            'type' => 'textarea',
                        ],
                        'cookies' => [
                            'display' => __('Cookies'),
                            'add_row' => __('Add a cookie'),
                            'type' => 'grid',
                            'fields' => self::fields([
                                'name' => ['display' => __('Name'), 'type' => 'text', 'validate' => 'required'],
                                'provider' => ['display' => __('Set by'), 'type' => 'text'],
                                'purpose' => ['display' => __('Purpose'), 'type' => 'text'],
                                'duration' => ['display' => __('Retention'), 'type' => 'text'],
                            ]),
                        ],
                    ]),
                ],
            ]
        );
    }

    /**
     * A section this addon declares itself: the cookie inventory, whose
     * repeatable field has no shape the catalogue could hold.
     *
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private static function custom(string $display, ?string $instructions, array $fields): array
    {
        $current = [];

        foreach ($fields as $handle => $field) {
            $current[$handle] = self::withCurrentValue($field, (string)$handle);
        }

        return array_filter([
            'display' => $display,
            'instructions' => $instructions,
            'fields' => self::fields($current),
        ], static fn (mixed $value) => $value !== null);
    }

    /**
     * A section of the catalogue, rendered as a blueprint section.
     *
     * @return array<string, mixed>
     */
    private static function section(string $name, ?string $instructions = null): array
    {
        $definition = Catalogue::SECTIONS[$name];
        $fields = [];

        foreach ($definition['fields'] as $handle => $field) {
            $fields[$handle] = self::withCurrentValue(self::control($field), (string)$handle);
        }

        return array_filter([
            'display' => __($definition['label']),
            'instructions' => $instructions ?? (isset($definition['help']) ? (string)__($definition['help']) : null),
            'fields' => self::fields($fields),
        ], static fn (mixed $value) => $value !== null);
    }

    /**
     * One catalogue field as Statamic describes a field. The help names the
     * template call as `:tag`, which every host spells its own way.
     *
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private static function control(array $field): array
    {
        $control = [
            'display' => __($field['label']),
            'type' => $field['kind'],
        ];

        if (isset($field['help'])) {
            $control['instructions'] = __($field['help'], ['tag' => '`{{ consent:banner }}`']);
        }

        if (isset($field['options'])) {
            $control['options'] = array_map(static fn (string $label): string => (string)__($label), $field['options']);
        }

        foreach (['if', 'width', 'validate'] as $key) {
            if (isset($field[$key])) {
                $control[$key] = $field[$key];
            }
        }

        return $control;
    }

    /**
     * The register section, plus what only this addon knows: whether the
     * licence opens it.
     *
     * @return array<string, mixed>
     */
    private static function registry(): array
    {
        $section = self::section('registry', self::$pro
            ? null
            : (string)__('Keeping a server-side register is part of the Pro edition. Standard collects and honours consent; Pro archives the proof.'));

        if (self::$pro) {
            return $section;
        }

        foreach ($section['fields'] as $index => $field) {
            if ($field['handle'] === 'registry') {
                $section['fields'][$index]['field']['visibility'] = 'read_only';
            }
        }

        return $section;
    }

    /**
     * Shows the setting in force as the field's default, so an untouched
     * screen reads true rather than empty, and locks what the config file
     * pinned — saving it would be ignored.
     *
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    private static function withCurrentValue(array $field, string $handle): array
    {
        if (array_key_exists($handle, self::$values)) {
            $field['default'] = self::$values[$handle];
        }

        if (self::$fixed[$handle] ?? false) {
            $field['visibility'] = 'read_only';
            $field['instructions'] = trim(($field['instructions'] ?? '') . ' ' . __('Set in the config file, which takes precedence.'));
        }

        return $field;
    }

    /**
     * A blueprint takes its fields as a list of handle and config, not as a map.
     *
     * @param  array<string, mixed>  $fields
     * @return array<int, array<string, mixed>>
     */
    private static function fields(array $fields): array
    {
        $list = [];

        foreach ($fields as $handle => $field) {
            $list[] = ['handle' => $handle, 'field' => $field];
        }

        return $list;
    }
}

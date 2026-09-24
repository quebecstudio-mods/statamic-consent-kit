<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Settings;

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
                    'sections' => [self::general(), self::cookie(), self::policy()],
                ],
                'behaviour' => [
                    'display' => __('Behaviour'),
                    'sections' => [self::behaviour(), self::measurement(), self::video()],
                ],
                'appearance' => [
                    'display' => __('Appearance'),
                    'sections' => [self::appearance(), self::inventoryTable()],
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
    private static function general(): array
    {
        return self::section(__('General'), null, [
            'autoInject' => [
                'display' => __('Automatic injection'),
                'instructions' => __('Places the banner at the end of `<body>`, without touching any template. Turn this off only if the site needs to position it itself, with `{{ consent:banner }}`. The `<head>` bootstrap always stays automatic.'),
                'type' => 'toggle',
            ],
            'defaultLanguage' => [
                'display' => __('Fallback language'),
                'instructions' => __('Used when the current locale has no wording. The addon ships English and French, plus any the site adds in lang/vendor/cookie-consent.'),
                'type' => 'text',
                'width' => '50',
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private static function cookie(): array
    {
        return self::section(__('Consent cookie'), null, [
            'cookieName' => [
                'display' => __('Cookie name'),
                'instructions' => __('Name of the cookie that remembers the visitor’s choice. Renaming it invalidates existing consents — bump the policy version at the same time.'),
                'type' => 'text',
                'width' => '50',
            ],
            'cookieMaxAge' => [
                'display' => __('Lifetime'),
                'instructions' => __('In seconds. 15,552,000 is 180 days.'),
                'type' => 'integer',
                'validate' => 'min:0',
                'width' => '50',
            ],
            'version' => [
                'display' => __('Policy version'),
                'instructions' => __('Bump this when a cookie appears in a non-necessary category, a category is added, or a purpose changes. Visitors will then be asked again.'),
                'type' => 'integer',
                'validate' => 'min:1',
                'width' => '50',
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private static function policy(): array
    {
        return self::section(__('Privacy policy'), null, [
            'policyUrl' => [
                'display' => __('Privacy policy URL'),
                'instructions' => __('Shown in the banner. Leave it empty for no link.'),
                'type' => 'text',
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private static function behaviour(): array
    {
        return self::section(__('Behaviour'), null, [
            'gpcHidesBanner' => [
                'display' => __('Skip the banner on a Global Privacy Control refusal'),
                'instructions' => __('Some browsers send a signal meaning “I refuse optional cookies”. That refusal is always honoured: optional categories start off, and nothing is set before consent. This setting only decides whether the banner still asks. With the banner skipped, a visitor changes their mind from the reopen tab — so keep that tab on, or provide your own entry point.'),
                'type' => 'toggle',
            ],
            'reopenButton' => [
                'display' => __('Reopen tab'),
                'instructions' => __('Small tab shown once the visitor has decided, so the banner can be reopened. Required for compliance — withdrawal must be as easy as consent. Turn it off only if the site provides its own entry point calling window.qsmConsentKit.open().'),
                'type' => 'toggle',
            ],
            'reopenPosition' => [
                'display' => __('Reopen tab position'),
                'instructions' => __('Bottom edge of the screen, on this side. “Auto” follows the display mode: on the right for a bottom right corner, on the left otherwise.'),
                'type' => 'select',
                'options' => [
                    'auto' => __('Auto'),
                    'left' => __('Left'),
                    'right' => __('Right'),
                ],
                'if' => ['reopenButton' => true],
                'width' => '50',
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private static function measurement(): array
    {
        return self::section(
            __('Measurement'),
            __('Which category a visitor has to accept before Google Consent Mode and Matomo are granted. A site that measures nothing leaves both empty.'),
            [
                'analyticsCategory' => [
                    'display' => __('Analytics category'),
                    'instructions' => __('Drives Matomo and Google analytics_storage.'),
                    'type' => 'text',
                    'width' => '50',
                ],
                'marketingCategory' => [
                    'display' => __('Marketing category'),
                    'instructions' => __('Drives Google ad_storage, ad_user_data and ad_personalization.'),
                    'type' => 'text',
                    'width' => '50',
                ],
            ]
        );
    }

    /** @return array<string, mixed> */
    private static function video(): array
    {
        return self::section(__('Video'), null, [
            'videoFacade' => [
                'display' => __('YouTube facade'),
                'instructions' => __('YouTube videos load only when the visitor clicks, so nothing reaches Google beforehand — the click is the consent, for that video alone. Only YouTube is covered: videos hosted elsewhere are untouched by this setting. Turning this off embeds YouTube directly, which lets Google set cookies as soon as the page is displayed, without any consent.'),
                'type' => 'toggle',
            ],
            'videoThumbnails' => [
                'display' => __('YouTube thumbnails'),
                'instructions' => __('Show the real thumbnail on the facade. The server fetches it from YouTube once, caches it, and serves it from this domain — the visitor never contacts Google before clicking. Turning this off falls back to a plain gradient.'),
                'type' => 'toggle',
                'if' => ['videoFacade' => true],
            ],
            'videoConsentCategory' => [
                'display' => __('Category that lifts the facade'),
                'instructions' => __('A visitor who accepted this category gets the video loaded outright, without clicking. Left empty, the facade always applies — which is the safer answer: consent for a category is broader than consent for one video, and Law 25 asks for specific consent.'),
                'type' => 'text',
                'if' => ['videoFacade' => true],
                'width' => '50',
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private static function appearance(): array
    {
        return self::section(__('Appearance'), null, [
            'colorScheme' => [
                'display' => __('Colour scheme'),
                'instructions' => __('“Auto” follows the visitor’s system preference. The dark scheme uses the palette set through --qsm-ck-dark-*.'),
                'type' => 'select',
                'options' => [
                    'auto' => __('Auto (recommended)'),
                    'light' => __('Light'),
                    'dark' => __('Dark'),
                ],
                'width' => '50',
            ],
            'displayMode' => [
                'display' => __('Display mode'),
                'instructions' => __('Full width along the bottom, or a box: floating in the middle, or in a bottom corner. On a narrow screen every mode is full width.'),
                'type' => 'select',
                'options' => [
                    'full' => __('Full width'),
                    'floating' => __('Floating box'),
                    'corner-left' => __('Bottom left corner'),
                    'corner-right' => __('Bottom right corner'),
                ],
                'width' => '50',
            ],
            'backdropStyle' => [
                'display' => __('Panel backdrop'),
                'instructions' => __('Effect applied behind the “Manage” panel. Blur signals the modality without hiding the page.'),
                'type' => 'select',
                'options' => [
                    'blur' => __('Blur (recommended)'),
                    'dim' => __('Dim'),
                    'none' => __('None'),
                ],
                'width' => '50',
            ],
        ]);
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

        return self::section(
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
        return self::section(
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

    /** @return array<string, mixed> */
    private static function registry(): array
    {
        $pro = self::$pro
            ? null
            : (string)__('Keeping a server-side register is part of the Pro edition. Standard collects and honours consent; Pro archives the proof.');

        return self::section(__('Consent register'), $pro, [
            'registry' => [
                'display' => __('Record decisions'),
                'instructions' => __('Each decision is written down as the browser makes it: the server clock, the site, the categories answered, and a fingerprint of the wording that was on screen. The cookie’s own timestamp lives on the visitor’s device and proves nothing. Off by default — a register is something a site announces in its privacy policy.'),
                'type' => 'toggle',

                'visibility' => self::$pro ? 'visible' : 'read_only',
            ],
            'registryGrace' => [
                'display' => __('Keep records for'),
                'instructions' => __('Months kept beyond the life of the consent cookie itself, so a proof outlives what it attests. Zero keeps every record until it is purged by hand.'),
                'type' => 'integer',
                'validate' => 'min:0',
                'if' => ['registry' => true],
                'width' => '50',
            ],
            'registryUser' => [
                'display' => __('Record the signed-in user'),
                'instructions' => __('When a decision comes from someone signed in, their account is recorded with it. This is the one identity the server can assert rather than be told. Deleting an account clears the link and leaves the decision.'),
                'type' => 'toggle',
                'if' => ['registry' => true],
            ],
            'registryRequestContext' => [
                'display' => __('Record where the decision came from'),
                'instructions' => __('The visitor’s address and browser, stored as they are, so a record answers where a decision came from — which a hash cannot. It also makes the register personal data, to be declared and to be answered for. Off by default.'),
                'type' => 'toggle',
                'if' => ['registry' => true],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private static function section(string $display, ?string $instructions, array $fields): array
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

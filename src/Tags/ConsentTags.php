<?php

namespace QuebecStudioMods\ConsentKit\Statamic\Tags;

use QuebecStudioMods\ConsentKit\Core\Defaults;
use QuebecStudioMods\ConsentKit\Laravel\CookieConsent;
use Statamic\Tags\Tags;

/**
 * The Antlers surface of the addon. Each tag hands its parameters to
 * `CookieConsent`, which renders the core's Blade templates.
 */
class ConsentTags extends Tags
{
    /** @var string Untyped in the parent, so it cannot be typed here. */
    protected static $handle = 'consent';

    /** Empty when the banner is already on the page. */
    public function banner(): string
    {
        return $this->consent()->banner()->toHtml();
    }

    /** The declared cookies as tables. */
    public function cookieTable(): string
    {
        return $this->consent()->cookieTable([
            'category' => $this->params->explode('category', []),
            'classes' => $this->classOverrides(),
            'heading' => $this->params->bool('heading', true),
            'headingLevel' => $this->params->int('heading_level', 3),
        ])->toHtml();
    }

    /** A YouTube video that loads on click. */
    public function videoFacade(): string
    {
        return $this->consent()->videoFacade(
            $this->params->get('id') ?? $this->params->get('youtube_id'),
            $this->params->get('title'),
            $this->params->get('poster'),
        )->toHtml();
    }

    /**
     * One parameter per element, `class_table="…"`: an Antlers parameter is a
     * string, and the core expects classes keyed by element.
     *
     * @return array<string, string>
     */
    private function classOverrides(): array
    {
        $classes = [];

        foreach (Defaults::inventoryElements() as $element) {
            $value = trim((string)$this->params->get('class_' . $element, ''));

            if ($value !== '') {
                $classes[$element] = $value;
            }
        }

        return $classes;
    }

    /** Scoped to the request: it remembers whether the banner was rendered. */
    private function consent(): CookieConsent
    {
        return app(CookieConsent::class);
    }
}

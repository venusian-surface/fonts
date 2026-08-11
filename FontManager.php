<?php

namespace ScrapyardIO\Tubes\Fonts;

use ReflectionClass;
use ReflectionException;
use ScrapyardIO\Tubes\Contracts\Fonts\FontException;
use ScrapyardIO\Tubes\Contracts\Fonts\FontFactory;
use ScrapyardIO\Tubes\Contracts\Fonts\GFXFont;

/**
 * Font registry — companions {@see extend()} / {@see addFont()} like Window/Framebuffer.
 *
 * Built-in: `classic` → {@see ClassicFont}.
 */
class FontManager implements FontFactory
{
    /**
     * @var array<string, class-string<GFXFont>>
     */
    protected array $fonts = [];

    /**
     * @var array<string, GFXFont>
     */
    protected array $instances = [];

    protected string $defaultFont = 'classic';

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(array $config = [])
    {
        $this->defaultFont = $this->resolveConfiguredDefault('font', $this->defaultFont);

        $this->extend('classic', ClassicFont::class);

        if ($config !== []) {
            $this->registerFromConfig($config);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function registerFromConfig(array $config): static
    {
        if (isset($config['default']) && is_string($config['default']) && $config['default'] !== '') {
            $this->defaultFont = strtolower($config['default']);
        }

        foreach ($config as $name => $entry) {
            if ($name === 'default' || ! is_string($name) || $name === '' || ! is_array($entry)) {
                continue;
            }

            if (array_key_exists('enabled', $entry) && ! ($entry['enabled'] ?? false)) {
                continue;
            }

            $class = $entry['class'] ?? null;

            if (is_string($class) && $class !== '' && class_exists($class)) {
                $this->extend($name, $class);
            }
        }

        return $this;
    }

    /**
     * @param  class-string<GFXFont>  $class
     */
    public function extend(string $name, string $class): static
    {
        $key = $this->normalize($name);

        if (! $this->validateClass($class)) {
            throw new FontException("Font [{$class}] must be a concrete subclass of ".GFXFont::class.'.');
        }

        $this->fonts[$key] = $class;
        unset($this->instances[$key]);

        return $this;
    }

    /**
     * @param  class-string<GFXFont>  $class
     */
    public function addFont(string $name, string $class): static
    {
        return $this->extend($name, $class);
    }

    /**
     * Alias of {@see font()} for MagicAlias symmetry with Window/Framebuffer.
     */
    public function driver(?string $name = null): GFXFont
    {
        return $this->font($name);
    }

    public function font(?string $name = null): GFXFont
    {
        $key = $this->normalize($name ?? $this->defaultFont);

        if (! isset($this->fonts[$key])) {
            throw new FontException("Font [{$key}] not registered.");
        }

        if (! isset($this->instances[$key])) {
            $class = $this->fonts[$key];
            $this->instances[$key] = new $class;
        }

        return $this->instances[$key];
    }

    public function defaultFont(): string
    {
        return $this->defaultFont;
    }

    /**
     * Alias of {@see defaultFont()} for MagicAlias symmetry.
     */
    public function defaultDriver(): string
    {
        return $this->defaultFont();
    }

    public function hasFont(string $name): bool
    {
        return isset($this->fonts[$this->normalize($name)]);
    }

    /**
     * @return array<string, class-string<GFXFont>>
     */
    public function listFonts(): array
    {
        return $this->fonts;
    }

    protected function resolveConfiguredDefault(string $alias, string $fallback): string
    {
        if (function_exists('config')) {
            $fromTubes = config("tubes.defaults.{$alias}");
            if (is_string($fromTubes) && $fromTubes !== '') {
                return strtolower($fromTubes);
            }
        }

        return strtolower($fallback);
    }

    protected function normalize(string $name): string
    {
        return strtolower(trim($name));
    }

    protected function validateClass(string $class_name): bool
    {
        try {
            $reflection = new ReflectionClass($class_name);
        } catch (ReflectionException) {
            return false;
        }

        return $reflection->isInstantiable()
            && $reflection->isSubclassOf(GFXFont::class);
    }
}

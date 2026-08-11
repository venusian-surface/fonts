<?php

namespace ScrapyardIO\Tubes\Fonts\Console;

use Fabricate\Console\GeneratorCommand;
use ScrapyardIO\Tubes\Fonts\Support\AdafruitGfxHeader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:font')]
class FontMakeCommand extends GeneratorCommand
{
    protected string $name = 'make:font';

    protected string $description = 'Create a new GFX font class (empty scaffold or from an Adafruit GFXfont .h)';

    /**
     * @var string
     */
    protected $type = 'Font';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/font.stub');
    }

    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->scrapyard_io->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * @param  string  $rootNamespace
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Fonts';
    }

    /**
     * @return array<int, array{0: string, 1: string|null, 2: int, 3?: string}>
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the font already exists'],
            ['from', null, InputOption::VALUE_REQUIRED, 'Path to an Adafruit GFXfont C header (.h) to import'],
        ];
    }

    public function handle(): ?bool
    {
        $from = $this->option('from');

        if (is_string($from) && $from !== '') {
            return $this->handleFromHeader($from);
        }

        return parent::handle();
    }

    protected function handleFromHeader(string $path): ?bool
    {
        if ($this->isReservedName($this->getNameInput())) {
            $this->components->error('The name "'.$this->getNameInput().'" is reserved by PHP.');

            return false;
        }

        $name = $this->qualifyClass($this->getNameInput());
        $target = $this->getPath($name);

        if ((! $this->hasOption('force') || ! $this->option('force')) && $this->alreadyExists($this->getNameInput())) {
            $this->components->error($this->type.' already exists.');

            return false;
        }

        $parsed = AdafruitGfxHeader::parseFile($path);
        $class = class_basename($name);
        $namespace = $this->getNamespace($name);
        $source = AdafruitGfxHeader::renderClassSource($namespace, $class, $parsed);

        $this->makeDirectory($target);
        $this->files->put($target, $this->sortImports($source));

        if (windows_os()) {
            $target = str_replace('/', '\\', $target);
        }

        $this->components->info(sprintf('%s [%s] created successfully from %s.', $this->type, $target, $path));

        return null;
    }
}

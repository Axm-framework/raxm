<?php

namespace Axm\Raxm\Commands;

use Console\BaseCommand;
use Console\CLI;
use Console\GeneratorTrait;

class MakeRaxm extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The Command's Group
      */
    protected string $group = 'Raxm';

    /**
     * The Command's Name
      */
    protected string $name = 'make:raxm';

    /**
     * The Command's Description
      */
    protected string $description = 'Create a Raxm component';

    /**
     * The Command's Usage
      */
    protected string $usage = 'make:raxm [name]';

    /**
     * The Command's Arguments
      */
    protected array $arguments = [];

    /**
     * The Command's Options
      */
    protected array $options = [];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {
        if (empty($params[1])) {
            CLI::error('You must add the component name.');
            system('php axm help make:raxm --no-header');
            CLI::newLine(3);
            exit;
        }

        try {
            $options = [
                'alias' => 'raxm',
                'class' => 'Axm\Raxm\Raxm',
                'description' => "Raxm is a livewire-based library for providing a development experience similar to that of single-page applications (SPA), but without the need to write complex JavaScript code. It allows you to create interactive user interfaces by updating user interface components in response to user actions.",
                'paths' => config('paths.providersPath') . DIRECTORY_SEPARATOR . 'providers.php'
            ];

            $this->call('add:provider', $options);

            $this->createTemplate('component', null, 'App', 'Raxm', 'raxm.component.tpl.php', $params);
            $this->createTemplate('view', null, 'resources', 'views/raxm', 'raxm.view.tpl.php', $params, false);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Create a template for a specific component type and execute it if necessary.
     */
    private function createTemplate(string $templateType, string|null $className, string $namespace, string $directory, string $templateFile, array $params, bool $phpOutputOnly = true)
    {
        $this->hasClassName = !empty($className);
        $this->className = $className;
        $this->component = 'Raxm';
        $this->namespace = $namespace;
        $this->directory = $directory;
        $this->template = __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $templateFile;
        $this->phpOutputOnly = $phpOutputOnly;

        if ($templateType === 'providers') {
            $dir = config('paths.providersPath') . DIRECTORY_SEPARATOR . "$className.php";
            if (is_file($dir)) {
                return;
            }
        }

        $this->execute($params);
    }

    /**
     * Prepare a class name by parsing the template and replacing placeholders.
     */
    protected function prepare(string $class): string
    {
        return $this->parseTemplate(
            $class,
            [
                '{view}' => 'raxm.' . strtolower($this->classBasename($class))
            ]
        );
    }

    /**
     * Get the base name of a class, stripping its namespace.
     */
    public function classBasename(string $class): string
    {
        $parts = explode('\\', $class);
        return end($parts);
    }

    /**
     * Add a new service to the configuration file if it doesn't already exist.
     */
    function addService(string $serviceName, string $serviceClass, string $configFile)
    {
        // Check if the service already exists in the file
        if ($this->serviceExists($serviceName, $configFile)) {
            return;
        }

        // Read the current content of the file
        $currentConfig = file_get_contents($configFile);

        // Define the new service
        $newService = "\n  '{$serviceName}' => {$serviceClass}::class,\n];";

        // Replace the last closing bracket with the new service and the original closing bracket
        $modifiedConfig = preg_replace('/\];/', $newService, $currentConfig, 1);

        // Write the modified configuration back to the file
        file_put_contents($configFile, $modifiedConfig);

        echo "Service '{$serviceName}' added successfully.\n";
    }

    /**
     * Check if a service already exists in the configuration file.
     */
    function serviceExists(string $serviceName, string  $configFile): bool
    {
        // Read the current content of the file
        $currentConfig = file_get_contents($configFile);
        return str_contains($currentConfig, "'{$serviceName}'");
    }
}

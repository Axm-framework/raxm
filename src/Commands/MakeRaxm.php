<?php

namespace Axm\Raxm\Commands;

use Axm\Console\BaseCommand;
use Axm\Console\GeneratorTrait;

class MakeRaxm extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The Command's Group
     * @var string
     */
    protected $group = 'Axm';

    /**
     * The Command's Name
     * @var string
     */
    protected $name = 'make:raxm';

    /**
     * The Command's Description
     * @var string
     */
    protected $description = '';

    /**
     * The Command's Usage
     * @var string
     */
    protected $usage = 'make:raxm [name] [options]';

    /**
     * The Command's Arguments
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     * @param array $params
     */
    public function run(array $params)
    {
        try {

            $this->createTemplate('services', 'Raxm', 'Services', '', 'raxm.services.tpl.php', $params);
            $this->createTemplate('component', null, 'App', 'Raxm', 'raxm.component.tpl.php', $params);
            $this->createTemplate('view', null, 'App', 'Views/raxm', 'raxm.view.tpl.php', $params, false);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Create a template for a specific component type and execute it if necessary.
     *
     * @param string $templateType The type of template to create (e.g., 'services', 'component', 'view').
     * @param string|null $className The class name for the template (null for 'component' and 'view' types).
     * @param string $namespace The namespace for the template.
     * @param string $directory The directory for the template.
     * @param string $templateFile The name of the template file.
     * @param array $params Additional parameters for the template rendering.
     * @param bool $phpOutputOnly Whether to output PHP code only (default is true).
     */
    private function createTemplate($templateType, $className, $namespace, $directory, $templateFile, $params, $phpOutputOnly = true)
    {
        $this->hasClassName = !empty($className);
        $this->className = $className;
        $this->component = 'Raxm';
        $this->namespace = $namespace;
        $this->directory = $directory;
        $this->template = __DIR__ . '/templates/' . $templateFile;
        $this->phpOutputOnly = $phpOutputOnly;

        if ($templateType === 'services') {
            $dir = config('paths.servicesPath') . "/$className.php";
            if (is_file($dir)) {
                return;
            }
        }

        $this->execute($params);
    }

    /**
     * Prepare a class name by parsing the template and replacing placeholders.
     *
     * @param string $class The fully qualified class name to prepare.
     * @return string The prepared class name.
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
     *
     * @param string $class The fully qualified class name.
     * @return string The base name of the class without its namespace.
     */
    public function classBasename($class)
    {
        $parts = explode('\\', $class);
        $className = end($parts);
        return $className;
    }
}

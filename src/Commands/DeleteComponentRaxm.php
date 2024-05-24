<?php

namespace Axm\Raxm\Commands;

use Console\CLI;
use Axm\Raxm\Raxm;
use Console\BaseCommand;
use Console\GeneratorTrait;

class DeleteComponentRaxm extends BaseCommand
{
    use GeneratorTrait;

    /**
     * The Command's Group
     */
    protected string $group = 'Raxm';

    /**
     * The Command's Name
     */
    protected string $name = 'delete:raxm';

    /**
     * The Command's Description
     */
    protected string $description = '';

    /**
     * The Command's Usage
     */
    protected string $usage = 'delete:raxm [name] [options]';

    /**
     * The Command's Arguments
     */
    protected array $arguments = [];

    /**
     * The Command's Options
     */
    protected array $options = [
        '--force' => 'Force overwrite existing file.',
    ];

    /**
     * Actually execute a command.
     */
    public function run(array $params)
    {

        $force = array_key_exists('force', $params) || CLI::getOption('force');
        $componentName = ucfirst($params[1]);

        Raxm::registerConfig();

        $filePaths = [
            'view' => config('raxm.view_path') . $componentName . '.php',
            'component' => config('raxm.component_path') . $componentName . '.php'
        ];

        $existingFiles = [];
        $missingFiles = [];
        foreach ($filePaths as $filePath) {
            if (is_file($filePath)) {
                $existingFiles[] = $filePath;
            } else {
                $missingFiles[] = $filePath;
            }
        }

        if (!empty($existingFiles)) {
            if (!$force && $this->confirmFileDeletion($existingFiles) === 'n') {
                return;
            }

            foreach ($existingFiles as $filePath) {
                if (unlink($filePath)) {
                    CLI::success("File deleted: $filePath 🤙 ");
                }
            }
        }

        if (!empty($missingFiles)) {
            foreach ($missingFiles as $file) {
                CLI::error("File doesn't exist: $file ❌ \n");
            }
        }
    }

    /**
     * Confirm whether to delete existing files.
     */
    private function confirmFileDeletion(array $existingFiles): string
    {
        $message = "The following files exist and will be deleted:\n\n";
        $message .= CLI::color(implode("\n", $existingFiles), 'green');
        $message .= "\n\nAre you sure you want to delete the existing files? (y/n)";

        return CLI::prompt($message, ['n', 'y'], 'required');
    }
}

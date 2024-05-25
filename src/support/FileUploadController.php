<?php

namespace Axm\Raxm\Support;

use DateTime;
use DateInterval;
use Axm\Raxm\Component;
use Validation\Validator;
use Axm\Raxm\Support\FileHandler;
use Axm\Raxm\Support\InteractsWithProperties;

/**
 * FileUploadController handles file uploads and validation.
 */
class FileUploadController extends Component
{
    use InteractsWithProperties;

    /**
     * Handle incoming file uploads.
     */
    public function handle(): mixed
    {
        // Get the current date and time
        $currentDateTime = new DateTime();

        // add 1 minute
        $time = $currentDateTime->add(new DateInterval('PT1M'));

        // Check if the request has a valid signature within 5 minutes.
        if (!app()->request->hasValidSignature($time)) {
            return app()->response->abort(401);
        }

        // Create a FileHandler instance to manage uploaded files.
        $files = new FileHandler($_FILES['files']);

        // Determine the target disk for storing files.
        $disk = config('raxm.temporary_file_upload.disk');

        // Validate and store the uploaded files.
        $filePaths = $this->validateAndStore($files, $disk);

        // Return a JSON response with the file paths.
        return app()->response->toJson(['paths' => $filePaths]);
    }

    /**
     * Validate and store uploaded files.
     */
    public function validateAndStore(object $files, ?string $disk): array
    {
        // Define validation rules for the uploaded files.
        $rules = $this->rules();

        // Validate the uploaded files against the defined rules.
        $validator = Validator::make($rules, ['files' => $files->tmpName()]);
        if ($validator->fails()) {
            app()->request->deleteCookie('message_errorFile_upload');
            app()->request->setCookie('message_errorFile_upload', $validator->getFirstError(), 600);

            throw new \Exception('The failed to upload. Error: ' . $validator->getFirstError());
        }

        // Get allowed MIME types for file preview.
        $mimes = config('raxm.temporary_file_upload.preview_mimes');

        // Configure FileHandler with allowed extensions and upload directory.
        $files->setAllowedExtensions($mimes);
        $files->setUploadDir($disk ?? STORAGE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);

        $validatedFiles = [];

        // Handle multiple files if present.
        if ($files->ifMultiple()) {
            foreach ($files->get()['name'] as $file) {
                $validatedFiles[] = $this->storeFile($file);
            }
        } else {
            // Handle a single file.
            $validatedFiles[] = $this->storeFile($files);
        }

        // Save the temporary file name for reference.
        $validatedFiles[] = $files->tmpName();

        return $validatedFiles;
    }

    /**
     * Store a validated file.
     */
    protected function storeFile(mixed $files): bool
    {
        if ($files->isValid()) {
            if ($files->move()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Define validation rules for uploaded files.
     */
    protected function rules(): mixed
    {
        $rules = config('raxm.temporary_file_upload.rules');

        if (is_null($rules)) {
            return ['files' => 'required|file|max:54288'];
        }

        if (is_array($rules)) return $rules;

        return explode('|', $rules);
    }
}

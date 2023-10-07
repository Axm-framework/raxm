<?php

namespace Axm\Raxm\Support;

use Axm\Raxm\Component;
use Axm\Raxm\Support\FileHandler;
use Axm\Validation\Validator;
use Axm\Raxm\Support\InteractsWithProperties;
use DateTime;
use DateInterval;

/**
 * FileUploadController handles file uploads and validation.
 */
class FileUploadController extends Component
{
    use InteractsWithProperties;

    /**
     * Handle incoming file uploads.
     * @return mixed The response containing file paths or an error response.
     */
    public function handle()
    {
        // Get the current date and time
        $currentDateTime = new DateTime();

        // add 1 minute
        $time = $currentDateTime->add(new DateInterval('PT1M'));

        // La variable $currentDateTime ahora contiene la fecha y hora actual + 1 minuto

        // Check if the request has a valid signature within 5 minutes.
        if (!app()->request->hasValidSignature($time)) {
            return app()->response->abort(401);
        }

        // Load configuration settings from raxm.php.
        config()->load(dirname(__FILE__, 3) . '/config/raxm.php');

        // Create a FileHandler instance to manage uploaded files.
        $files = new FileHandler($_FILES['files']);

        // Determine the target disk for storing files.
        $disk = config('temporary_file_upload.disk');

        // Validate and store the uploaded files.
        $filePaths = $this->validateAndStore($files, $disk);

        // Return a JSON response with the file paths.
        return app()->response->toJson(['paths' => $filePaths]);
    }

    /**
     * Validate and store uploaded files.
     *
     * @param mixed $files The uploaded files to validate and store.
     * @param string|null $disk The target disk for storing files.
     * @return array An array of validated file paths.
     */
    public function validateAndStore($files, $disk)
    {
        // Define validation rules for the uploaded files.
        $rules = $this->rules();

        // Validate the uploaded files against the defined rules.
        $validator = Validator::make($rules, ['files' => $files->tmpName()]);
        if ($validator->fails()) {
            app()->request->deleteCookie('message_errorFile_upload');
            app()->request->setCookie('message_errorFile_upload', $validator->getFirstError(), 600);
            throw new \Exception("The failed to upload.");
        }

        // Get allowed MIME types for file preview.
        $mimes = config('temporary_file_upload.preview_mimes');

        // Configure FileHandler with allowed extensions and upload directory.
        $files->setAllowedExtensions($mimes);
        $files->setUploadDir($disk ?? STORAGE_PATH . '/app/uploads/');

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
     *
     * @param mixed $files The validated file to store.
     * @return bool True if the file is successfully stored, otherwise false.
     */
    protected function storeFile($files)
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
     * @return mixed The validation rules for uploaded files.
     */
    protected function rules()
    {
        $rules = config('temporary_file_upload.rules');

        if (is_null($rules)) {
            return ['files' => 'required|file|max:12'];
        }

        if (is_array($rules)) return $rules;

        return explode('|', $rules);
    }
}

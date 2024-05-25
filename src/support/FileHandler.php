<?php

namespace Axm\Raxm\Support;

/**
 * Class FileHandler
 *  A class for handling uploaded files, providing validation and moving functionalities.
 * 
 * @author  Juan Cristobal <juancristobalgd1@gmail.com>
 * @package Axm\HTTP
 */
class FileHandler
{
    /**
     * The uploaded file data.
     */
    protected array $file;

    /**
     * The allowed file extensions.
     */
    protected array $allowedExtensions = [];

    /**
     * The maximum allowed file size in bytes.
     */
    protected int $maxFileSize = 5242880; // 5 MB

    /**
     * The directory where files will be uploaded.
     */
    protected string $uploadDir = 'uploads/';

    /**
     * Errors occurred during validation.
     */
    protected array $errors = [];

    /**
     *  File destination.
     */
    protected string $destination;

    /**
     * Create a new FileHandler instance.
     */
    public function __construct(array $file)
    {
        $this->file = $file;
    }

    /**
     * Set the allowed file extensions.
     */
    public function setAllowedExtensions(array $extensions)
    {
        $this->allowedExtensions = $extensions;
    }

    /**
     * Set the upload directory.
     */
    public function setUploadDir(string $dir)
    {
        $this->uploadDir = rtrim($dir, '/') . '/';
    }

    /**
     * Set the maximum allowed file size.
     */
    public function setMaxFileSize(int $size)
    {
        $this->maxFileSize = $size;
    }

    /**
     * Get file.
     */
    public function get(): array
    {
        return $this->file;
    }

    /**
     * Get directory file.
     */
    public function getDir(): string
    {
        return $this->uploadDir;
    }

    /**
     * Check if the uploaded file is valid.
     */
    public function isValid(): bool
    {
        if (!$this->file || $this->errors() !== UPLOAD_ERR_OK) {
            $this->addError("File upload error.");
            return false;
        }

        $extension = strtolower(pathinfo($this->name(), PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->addError("Invalid file extension.");
            return false;
        }

        if ($this->size() > $this->maxFileSize) {
            $this->addError("File size exceeds the limit.");
            return false;
        }

        return true;
    }

    /**
     * Move the uploaded file to the configured directory.
     */
    public function move(): bool
    {
        if (!$this->isValid())
            return false;

        $this->destination = str_replace('\\', '/', $this->getDir() . $this->generateUniqueFileName());
        return move_uploaded_file($this->tmpName(), $this->destination);
    }

    /**
     * Generate a unique file name based on timestamp and unique ID.
     */
    public function generateUniqueFileName(): string
    {
        $name = $this->name();
        $meta = '-meta' . str_replace('/', '_', base64_encode($name)) . '-';
        $filename = time() . '_' . $meta . uniqid() . '.' . pathinfo($name, PATHINFO_EXTENSION);
        return $filename;
    }

    /**
     * Extract Original FileName
     */
    public static function extractOriginalFileName(string $generatedFileName): string
    {
        // Remove the timestamp and unique ID suffix from the generated filename
        $originalNameWithExtension = substr($generatedFileName, strpos($generatedFileName, '-') + 1);
        $originalName = substr($originalNameWithExtension, strpos($originalNameWithExtension, '-') + 1, strrpos($originalNameWithExtension, '.') - strlen($originalNameWithExtension));
        $originalName = base64_decode(str_replace('_', '/', substr($originalName, 0, strpos($originalName, '-'))));

        return $originalName;
    }

    /**
     * Get the original file name.
     */
    public function name(): ?string
    {
        if ($this->ifMultiple()) {
            return data_get($this->getAllFilesMultiples(), 'name');
        }

        return $this->file['name'][0];
    }

    /**
     * Get the file size.
     */
    public function size(): int
    {
        if ($this->ifMultiple()) {
            return (int) data_get($this->getAllFilesMultiples(), 'size');
        }

        return (int) $this->file['size'][0];
    }

    /**
     * Get the temporal name.
     */
    public function tmpName(): string
    {
        if ($this->ifMultiple()) {
            return data_get($this->getAllFilesMultiples(), 'tmp_name');
        }

        return $this->file['tmp_name'][0];
    }

    /**
     * Get the MIME type of the file.
     */
    public function mime(): string
    {
        if ($this->ifMultiple()) {
            return data_get($this->getAllFilesMultiples(), 'type');
        }

        return $this->file['type'][0];
    }

    /**
     * Get the errors file.
     */
    public function errors(): string
    {
        if ($this->ifMultiple()) {
            return data_get($this->getAllFilesMultiples(), 'error');
        }

        return $this->file['error'][0];
    }

    /**
     * Get destination file.
     */
    public function destination(): string
    {
        return $this->destination;
    }

    /**
     * Get the errors occurred during validation.
     */
    public function getErrorsMessages(): array
    {
        return $this->errors;
    }

    /**
     * Add an error message to the error array.
     */
    protected function addError(string $message)
    {
        $this->errors[] = $message;
    }

    /**
     * Checks if the file input contains multiple files.
     */
    public function isMultiple(): bool
    {
        return count($this->file['name']) > 1;
    }

    /**
     * Get All FilesMultiples
     */
    private function getAllFilesMultiples(): array
    {
        $files = [];
        foreach ($this->file['name'] as $file) {
            $files[] = $file;
        }

        return $files;
    }

    /**
     * Delete file
     */
    public function delete(): bool
    {
        return @unlink($this->file['name']);
    }

    /**
     * __invoke
     */
    function __invoke(): self
    {
        return $this;
    }
}

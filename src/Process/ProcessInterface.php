<?php

declare(strict_types=1);

namespace Churn\Process;

use Churn\File\File;

interface ProcessInterface
{

    /**
     * Start the process.
     */
    public function start(): void;

    /**
     * Determines if the process was successful.
     */
    public function isSuccessful(): bool;

    /**
     * Gets the file name of the file the process
     * is being executed on.
     */
    public function getFilename(): string;

    /**
     * Gets the file the process is being executed on.
     */
    public function getFile(): File;
}

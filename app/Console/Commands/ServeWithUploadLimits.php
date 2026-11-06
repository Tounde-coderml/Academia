<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Foundation\Console\ServeCommand;

#[Signature('serve:uploads {--host= : The host address to serve from} {--port= : The port to serve on} {--tries=10 : The max number of ports to attempt} {--no-reload : Do not reload the development server on .env file changes}')]
#[Description('Serve Academia with support for 20 MB uploads')]
class ServeWithUploadLimits extends ServeCommand
{
    protected function serverCommand(): array
    {
        $command = parent::serverCommand();

        return [
            $command[0],
            '-d', 'upload_max_filesize=20M',
            '-d', 'post_max_size=25M',
            ...array_slice($command, 1),
        ];
    }
}

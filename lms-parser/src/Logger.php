<?php

declare(strict_types=1);

namespace Carono\LmsParser;

final class Logger
{
    public function __construct(private bool $verbose = true)
    {
    }

    public function info(string $msg): void
    {
        $this->write("[".date('H:i:s')."] $msg", "\033[0m");
    }

    public function ok(string $msg): void
    {
        $this->write("[".date('H:i:s')."] ✓ $msg", "\033[32m");
    }

    public function warn(string $msg): void
    {
        $this->write("[".date('H:i:s')."] ! $msg", "\033[33m");
    }

    public function err(string $msg): void
    {
        $this->write("[".date('H:i:s')."] ✗ $msg", "\033[31m");
    }

    public function debug(string $msg): void
    {
        if ($this->verbose) {
            $this->write("[".date('H:i:s')."]   $msg", "\033[90m");
        }
    }

    private function write(string $msg, string $color): void
    {
        $reset = "\033[0m";
        fwrite(STDOUT, $color.$msg.$reset.PHP_EOL);
    }
}

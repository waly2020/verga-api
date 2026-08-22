<?php

namespace App\Mail\Concerns;

trait QueuesAfterCommit
{
    protected function configureQueuesAfterCommit(): void
    {
        $this->afterCommit();
    }
}

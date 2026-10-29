<?php

namespace App\Jobs;

use App\Models\ClientProject;
use App\Services\Monitoring\HealthProbe;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProbeProjectHealth implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public int $uniqueFor = 240;

    public function __construct(public int $projectId) {}

    public function uniqueId(): string
    {
        return (string) $this->projectId;
    }

    public function handle(HealthProbe $probe): void
    {
        if ($project = ClientProject::find($this->projectId)) {
            $probe->probe($project);
        }
    }
}

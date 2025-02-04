<?php

declare(strict_types=1);

namespace App\Nova\Metrics;

use App\Team;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

class TotalTeamMembers extends Value
{
    /**
     * Calculate the value of the metric.
     *
     * @param \Illuminate\Http\Request  $request
     *
     * @return \Laravel\Nova\Metrics\ValueResult
     */
    public function calculate(Request $request): ValueResult
    {
        $count = Team::where('id', $request->resourceId)->get()->first()->members()->count();

        return $this->result($count);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey(): string
    {
        return 'total-team-members';
    }
}

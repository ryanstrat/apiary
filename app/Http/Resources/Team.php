<?php

declare(strict_types=1);

// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter,SlevomatCodingStandard.Functions.UnusedParameter,SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint,SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint

namespace App\Http\Resources;

use App\Http\Resources\User as UserResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Attendance as AttendanceResource;

class Team extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request  $request
     *
     * @return array<string,mixed>
     */
    public function toArray($request): array
    {
        return [
            // Attributes
            'id' => $this->id,
            'name' => $this->name,
            'self_serviceable' => $this->self_serviceable,
            'visible' => $this->visible,
            'attendable' => $this->attendable,
            'slug' => $this->slug,
            'description' => $this->description,
            'mailing_list_name' => $this->mailing_list_name,
            'slack_channel_id' => $this->slack_channel_id,
            'slack_channel_name' => $this->slack_channel_name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,

            // Relationships
            'members' => UserResource::collection($this->whenLoaded('members')),
            'attendance' => AttendanceResource::collection($this->whenLoaded('attendance')),
        ];
    }
}

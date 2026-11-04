<?php

namespace App\Services;

use App\Meeting;

class ConsultationQueueService
{
    public function waiting($unit)
    {
        return Meeting::where('work_unit', $unit)->whereIn('status', ['scheduled', 'started'])
            ->whereIn('verification_status', ['verified', 'immediate'])->where('start_time', '<=', now())
            ->orderByRaw("CASE WHEN status = 'started' THEN 0 ELSE 1 END")
            ->orderByRaw('CASE WHEN called_at IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('start_time')->orderBy('id')->get();
    }

    public function state(Meeting $meeting)
    {
        $queue = $this->waiting($meeting->work_unit);
        $index = $queue->search(function ($item) use ($meeting) { return (int) $item->id === (int) $meeting->id; });
        return [
            'number' => $meeting->queueLabel(),
            'position' => $index === false ? null : $index + 1,
            'ahead' => $index === false ? 0 : $index,
            'ready' => $index === 0 && in_array($meeting->status, ['scheduled', 'started'], true),
            'status' => $meeting->statusLabel(),
            'entries' => $queue->map(function ($item) use ($meeting) {
                return ['number' => $item->queueLabel(), 'date' => $item->localStart()->format('d/m/Y'),
                    'time' => $item->localStart()->format('H:i'), 'status' => $item->statusLabel(),
                    'is_you' => (int) $item->id === (int) $meeting->id];
            })->values()->all(),
        ];
    }
}

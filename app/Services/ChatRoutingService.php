<?php

namespace App\Services;

use App\ChatRoom;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatRoutingService
{
    public function openFor(User $user, $serviceId = null)
    {
        abort_if($user->isAdmin() || $user->isOperator(), 403);

        if (!in_array($user->work_unit, \App\WorkUnit::knownNames(), true)) {
            throw ValidationException::withMessages([
                'work_unit' => 'Pilih satuan kerja terlebih dahulu agar chat diterima PTSP yang tepat.',
            ]);
        }

        return DB::transaction(function () use ($user, $serviceId) {
            // Serialize room creation for the same registrant, including concurrent requests.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $rooms = ChatRoom::where('user_id', $user->id)
                ->where('work_unit', $user->work_unit)->where('status', 'open');

            if ($serviceId !== null) {
                $rooms->where('service_id', $serviceId);
            }

            $room = $rooms->orderBy('updated_at', 'desc')->orderBy('id', 'desc')->first();

            if ($room) {
                $assigned = $room->admin;
                if (!$assigned || !$assigned->is_active || $assigned->work_unit !== $room->work_unit
                    || (!$assigned->isAdmin() && !$assigned->isOperator())) {
                    $room->update(['admin_id' => optional($this->findStaff($user->work_unit))->id]);
                    $room->unsetRelation('admin');
                }

                return $room;
            }

            return ChatRoom::create([
                'user_id' => $user->id,
                'work_unit' => $user->work_unit,
                'admin_id' => optional($this->findStaff($user->work_unit))->id,
                'service_id' => $serviceId,
                'status' => 'open',
            ]);
        });
    }

    private function findStaff($workUnit)
    {
        return User::where('is_active', true)->whereIn('role', ['operator', 'admin'])
            ->where('work_unit', $workUnit)
            ->orderByDesc('is_online')
            ->orderByRaw("CASE WHEN role = 'operator' THEN 0 ELSE 1 END")
            ->orderBy('id')->first();
    }
}

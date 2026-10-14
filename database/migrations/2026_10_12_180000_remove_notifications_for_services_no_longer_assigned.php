<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * An employee who was reassigned away from a service can no longer open it,
     * so drop their old notifications that still point at it.
     */
    public function up(): void
    {
        $employeeIds = DB::table('users')->where('role', UserRole::Employee->value)->pluck('id');

        DB::table('app_notifications')
            ->whereIn('user_id', $employeeIds)
            ->whereNotNull('link_text')
            ->orderBy('id')
            ->each(function ($notification) {
                $assignedTo = DB::table('tickets')->where('number', $notification->link_text)->value('assigned_to');

                // Only service notifications (numbers starting with the service prefix); leave enquiry ones alone.
                $isService = DB::table('tickets')->where('number', $notification->link_text)->exists();

                if ($isService && (int) $assignedTo !== (int) $notification->user_id) {
                    DB::table('app_notifications')->where('id', $notification->id)->delete();
                }
            });
    }

    public function down(): void
    {
        // Deleted notifications cannot be restored.
    }
};

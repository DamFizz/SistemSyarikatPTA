<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\LeaveRequest;
use App\Models\Overtime;
use App\Models\StoredFile;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the attachment of a leave / overtime request, helpdesk ticket or announcement
 * to the people allowed to see that record.
 */
class AttachmentController extends Controller
{
    public const TYPES = [
        'announcement' => Announcement::class,
        'leave' => LeaveRequest::class,
        'overtime' => Overtime::class,
        'ticket' => Ticket::class,
    ];

    public function show(Request $request, string $type, int $id): Response
    {
        /** @var Model $record */
        $record = (self::TYPES[$type] ?? abort(404))::findOrFail($id);

        abort_unless($this->canView($request->user(), $type, $record), 403);

        return StoredFile::responseFor($record->attachment)
            ?? abort(404, 'This attachment is no longer available.');
    }

    /**
     * Route parameters for a record's attachment link.
     */
    public static function routeFor(Model $record): array
    {
        return [array_search($record::class, self::TYPES, true), $record->getKey()];
    }

    private function canView(User $user, string $type, Model $record): bool
    {
        if ($user->hasRole(User::ROLE_SUPER_ADMIN, User::ROLE_HR_ADMIN)) {
            return true;
        }

        return match ($type) {
            'announcement' => Announcement::visibleTo($user)->whereKey($record->getKey())->exists(),
            'ticket' => $user->hasRole(User::ROLE_TECHNICIAN) || $record->employee_id === $user->employee?->id,
            default => $record->employee_id === $user->employee?->id
                || ($user->hasRole(User::ROLE_MANAGER) && $record->employee->isApprovableBy($user->employee)),
        };
    }
}

<?php

namespace App\Enums;

enum TicketStatus: string
{
    case DocumentsPending = 'Documents Pending';
    case PartiallyReceived = 'Partially Received';
    case DocumentsReceived = 'Documents Received';
    case UnderVerification = 'Under Verification';
    case AdditionalDocumentsRequired = 'Additional Documents Required';
    case WorkInProgress = 'Work In Progress';
    case SubmittedToDepartment = 'Submitted to Department';
    case OnHold = 'On Hold';
    case TaskCompleted = 'Task Completed';

    public function badgeVariant(): string
    {
        return match ($this) {
            self::DocumentsPending => 'secondary',
            self::PartiallyReceived => 'dark',
            self::DocumentsReceived => 'info',
            self::UnderVerification => 'primary',
            self::AdditionalDocumentsRequired => 'warning',
            self::WorkInProgress => 'warning',
            self::SubmittedToDepartment => 'info',
            self::OnHold => 'danger',
            self::TaskCompleted => 'success',
        };
    }

    public function isClosed(): bool
    {
        return $this === self::TaskCompleted;
    }

    /**
     * The statuses staff can manually move a ticket to. Every other status is
     * set automatically from document upload/verification progress. Payment
     * is tracked entirely separately — see Ticket::paymentStatus().
     *
     * @return array<int, self>
     */
    public static function manualOptions(): array
    {
        return [self::SubmittedToDepartment, self::OnHold, self::TaskCompleted];
    }
}

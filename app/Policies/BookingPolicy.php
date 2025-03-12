<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine if the user can view a particular booking.
     */
    public function view(User $user, Booking $booking)
    {
        // Only the user who created it (or an admin) can view
        return $booking->created_by === $user->id
            || $user->hasRole('CEO')
            || $user->hasRole('CFO')
            || $user->hasRole('Accountant')
            || $user->hasRole('System Maintenance');
    }

    /**
     * Determine if the user can update a particular booking.
     */
    public function update(User $user, Booking $booking)
    {
        // Only the user who created it (or an admin) can update
        return $booking->created_by === $user->id
            || $user->hasRole('CEO')
            || $user->hasRole('System Maintenance');
    }

    // Add other methods as needed (delete, etc.)
}

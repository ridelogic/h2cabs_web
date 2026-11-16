<?php

namespace App\Http\Controllers\Admin;

use App\Booking\BookingSummary;
use App\Content\DestinationCatalog;
use App\Enums\BookingType;
use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Booking requests submitted on the website. Only admins can see them
 * (routes are behind the auth + admin middleware). A request becomes
 * "confirmed" only when an admin sets that status here.
 */
class BookingRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::enum(BookingType::class)],
            'status' => ['nullable', Rule::in(array_keys(BookingRequest::STATUSES))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $bookings = BookingRequest::query()
            ->search($filters['q'] ?? null)
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('booking_type', $type))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            // Date filters apply to the travel date.
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('travel_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('travel_date', '<=', $to))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'types' => BookingType::cases(),
            'statuses' => BookingRequest::STATUSES,
            'newCount' => BookingRequest::query()->where('status', BookingRequest::STATUS_NEW)->count(),
        ]);
    }

    public function show(BookingRequest $booking, DestinationCatalog $destinations): View
    {
        return view('admin.bookings.show', [
            'booking' => $booking,
            'summary' => BookingSummary::make($booking, $destinations),
            'statuses' => BookingRequest::STATUSES,
        ]);
    }

    public function update(Request $request, BookingRequest $booking): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(BookingRequest::STATUSES))],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $booking->forceFill([
            'status' => $data['status'],
            'admin_notes' => filled($data['admin_notes'] ?? null) ? trim($data['admin_notes']) : null,
            'status_changed_at' => $booking->status !== $data['status'] ? now() : $booking->status_changed_at,
        ])->save();

        return redirect()->route('admin.bookings.show', $booking)->with('status', "Booking {$booking->reference} updated: {$booking->statusLabel()}.");
    }
}

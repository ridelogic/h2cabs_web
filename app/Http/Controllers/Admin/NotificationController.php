<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingRequest;
use Illuminate\Contracts\View\View;

/**
 * The top-bar notifications list: the latest new (unhandled) booking
 * requests. Loaded into the bell menu by resources/js/admin.js when it opens.
 */
class NotificationController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.partials.notifications', [
            'notifications' => BookingRequest::query()
                ->where('status', BookingRequest::STATUS_NEW)
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }
}

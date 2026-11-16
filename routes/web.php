<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CustomPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PlaceSearchController;
use App\Http\Controllers\SitemapController;
use App\Models\Page;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::controller(PageController::class)->group(function () {
    Route::get('/about', 'about')->name('about');
    Route::get('/services', 'services')->name('services');
    Route::get('/destinations', 'destinations')->name('destinations');
    Route::get('/contact', 'contact')->name('contact');
    Route::get('/photo-credits', 'photoCredits')->name('photo-credits');
});

Route::controller(BookingController::class)->group(function () {
    Route::get('/booking', 'create')->name('booking.create');
    Route::post('/booking', 'store')->middleware('throttle:booking-submit')->name('booking.store');
    Route::post('/booking/estimate', 'estimate')->middleware('throttle:booking-estimate')->name('booking.estimate');
    Route::get('/booking/confirmation', 'confirmation')->name('booking.confirmation');
    Route::post('/booking/whatsapp-opened', 'whatsappOpened')->middleware('throttle:booking-whatsapp')->name('booking.whatsapp-opened');
});

Route::get('/places/search', PlaceSearchController::class)
    ->middleware('throttle:places-search')
    ->name('places.search');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

/*
|--------------------------------------------------------------------------
| Admin dashboard
|--------------------------------------------------------------------------
| Session login for users with is_admin = true. Create an admin with
| `php artisan h2cabs:create-admin you@example.com`.
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'create'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'store'])->middleware('throttle:admin-login')->name('login.store');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'destroy'])->name('logout');
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::get('/search', Admin\SearchController::class)->name('search');
        Route::get('/notifications', Admin\NotificationController::class)->name('notifications');

        Route::get('/bookings', [Admin\BookingRequestController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [Admin\BookingRequestController::class, 'show'])->name('bookings.show');
        Route::patch('/bookings/{booking}', [Admin\BookingRequestController::class, 'update'])->name('bookings.update');

        Route::resource('members', Admin\MemberController::class)->except('show');
        Route::patch('/members/{member}/toggle', [Admin\MemberController::class, 'toggle'])->name('members.toggle');
        Route::patch('/members/{member}/move', [Admin\MemberController::class, 'move'])->name('members.move');

        Route::resource('vehicles', Admin\VehicleController::class);
        Route::patch('/vehicles/{vehicle}/toggle', [Admin\VehicleController::class, 'toggle'])->name('vehicles.toggle');

        Route::get('/fares', [Admin\FareRuleController::class, 'index'])->name('fares.index');
        Route::get('/fares/create', [Admin\FareRuleController::class, 'create'])->name('fares.create');
        Route::post('/fares', [Admin\FareRuleController::class, 'store'])->name('fares.store');
        Route::get('/fares/{fare}/edit', [Admin\FareRuleController::class, 'edit'])->name('fares.edit');
        Route::put('/fares/{fare}', [Admin\FareRuleController::class, 'update'])->name('fares.update');
        Route::patch('/fares/{fare}/toggle', [Admin\FareRuleController::class, 'toggle'])->name('fares.toggle');

        Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');

        Route::get('/seo', [Admin\SeoController::class, 'index'])->name('seo.index');
        Route::get('/seo/{page}/edit', [Admin\SeoController::class, 'edit'])->name('seo.edit');
        Route::put('/seo/{page}', [Admin\SeoController::class, 'update'])->name('seo.update');

        Route::resource('pages', Admin\PageController::class)->except('show');
        Route::patch('/pages/{page}/toggle', [Admin\PageController::class, 'toggle'])->name('pages.toggle');
        Route::get('/pages/{page}/preview', [Admin\PageController::class, 'preview'])->name('pages.preview');

        // Rental packages belong to a vehicle's Rental fare.
        Route::scopeBindings()->group(function () {
            Route::post('/fares/{fare}/packages', [Admin\RentalPackageController::class, 'store'])->name('fares.packages.store');
            Route::put('/fares/{fare}/packages/{package}', [Admin\RentalPackageController::class, 'update'])->name('fares.packages.update');
            Route::delete('/fares/{fare}/packages/{package}', [Admin\RentalPackageController::class, 'destroy'])->name('fares.packages.destroy');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Custom website pages
|--------------------------------------------------------------------------
| Published pages from Admin → Website Pages at /{slug}. Keep this LAST:
| every route above takes priority, and those paths are also blocked as
| slugs when a page is saved (App\Models\Page::reservedSlugs()).
*/
Route::get('/{slug}', CustomPageController::class)
    ->where('slug', Page::SLUG_PATTERN)
    ->name('pages.show');

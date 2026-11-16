<?php

namespace App\Http\Controllers;

use App\Content\AssociationContent;
use App\Content\DestinationCatalog;
use App\Content\HomePageContent;
use App\Content\ServiceCatalog;
use App\Models\Member;
use App\Models\Vehicle;
use App\Support\SeoMeta;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    public function about(AssociationContent $content): View
    {
        return view('pages.about', [
            'seo' => SeoMeta::page('about'),
            'content' => $content,
            'memberGroups' => $this->publishedMembers()->groupBy('member_group')
                ->sortBy(fn ($members, string $group) => array_search($group, array_keys(Member::GROUPS), true)),
        ]);
    }

    public function services(ServiceCatalog $services, HomePageContent $home): View
    {
        return view('pages.services', [
            'seo' => SeoMeta::page('services'),
            'primary' => $services->primary(),
            'uses' => $services->uses(),
            'routes' => $services->outstationRoutes(),
            'vehicles' => $this->vehicles(),
            'steps' => $home->bookingSteps(),
        ]);
    }

    /**
     * Every active vehicle with its fares, for the vehicle cards (the admin
     * Active toggle controls visibility; vehicles without a bookable fare
     * show "Fare available on request"). Returns an empty collection if the
     * tables are not migrated yet.
     *
     * @return Collection<int, Vehicle>
     */
    private function vehicles(): Collection
    {
        try {
            return Vehicle::query()->active()->with('fareRules')->get();
        } catch (QueryException $e) {
            Log::warning('Vehicles could not be loaded; run `php artisan migrate`.', ['error' => $e->getMessage()]);

            return collect();
        }
    }

    public function destinations(DestinationCatalog $destinations): View
    {
        return view('pages.destinations', [
            'seo' => SeoMeta::page('destinations'),
            'destinations' => $destinations->all(),
            'categories' => DestinationCatalog::CATEGORIES,
            'itineraries' => $destinations->itineraries(),
            'catalog' => $destinations,
        ]);
    }

    public function contact(): View
    {
        return view('pages.contact', [
            'seo' => SeoMeta::page('contact'),
        ]);
    }

    public function photoCredits(): View
    {
        return view('pages.photo-credits', [
            'seo' => SeoMeta::page('photo-credits'),
            'photos' => config('photos'),
        ]);
    }

    /**
     * Published member profiles. If the members table has not been migrated
     * yet, the page still renders with its "coming soon" placeholder.
     *
     * @return Collection<int, Member>
     */
    private function publishedMembers(): Collection
    {
        try {
            return Member::query()->published()->get();
        } catch (QueryException $e) {
            Log::warning('Members could not be loaded; run `php artisan migrate`.', ['error' => $e->getMessage()]);

            return collect();
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VehicleRequest;
use App\Models\BookingRequest;
use App\Models\Vehicle;
use App\Support\ImageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class VehicleController extends Controller
{
    public function __construct(private readonly ImageStore $images) {}

    public function index(): View
    {
        return view('admin.vehicles.index', [
            'vehicles' => Vehicle::query()->with('fareRules.packages')->orderBy('sort_order')->orderBy('name')->get(),
            'bookingTypes' => BookingType::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.vehicles.form', ['vehicle' => new Vehicle(['is_active' => true, 'category' => 'sedan'])]);
    }

    public function store(VehicleRequest $request): RedirectResponse
    {
        $vehicle = new Vehicle($this->attributes($request));
        $vehicle->sort_order ??= (int) Vehicle::max('sort_order') + 10;

        if ($request->hasFile('image')) {
            $vehicle->image_path = $this->images->store($request->file('image'), 'vehicles');
        }

        $vehicle->save();

        return redirect()->route('admin.fares.index')->with('status', "{$vehicle->name} was added. Configure its fares below.");
    }

    /** Details, image and per-type booking availability (with the reason when not bookable). */
    public function show(Vehicle $vehicle): View
    {
        $vehicle->load('fareRules.packages');

        return view('admin.vehicles.show', [
            'vehicle' => $vehicle,
            'bookingTypes' => BookingType::cases(),
            'bookingCount' => BookingRequest::query()->where('vehicle_id', $vehicle->id)->count(),
        ]);
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('admin.vehicles.form', ['vehicle' => $vehicle]);
    }

    public function toggle(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update(['is_active' => ! $vehicle->is_active]);

        return back()->with('status', $vehicle->is_active
            ? "{$vehicle->name} is active and appears on the booking form for each trip type with an approved fare."
            : "{$vehicle->name} is inactive and hidden from customers.");
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $oldImage = $vehicle->image_path;
        $vehicle->fill($this->attributes($request));

        if ($request->hasFile('image')) {
            $vehicle->image_path = $this->images->store($request->file('image'), 'vehicles');
        } elseif ($request->boolean('remove_image')) {
            $vehicle->image_path = null;
        }

        $vehicle->save();

        if ($oldImage !== $vehicle->image_path) {
            $this->images->deleteIfUnreferenced($oldImage);
        }

        return redirect()->route('admin.vehicles.edit', $vehicle)->with('status', 'Changes saved.');
    }

    /** Deleting a vehicle also deletes its fare rules; past booking requests keep the vehicle name. */
    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $image = $vehicle->image_path;
        $vehicle->delete();
        $this->images->deleteIfUnreferenced($image);

        return redirect()->route('admin.vehicles.index')->with('status', "{$vehicle->name} and its fares were deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(VehicleRequest $request): array
    {
        $attributes = $request->safe()->except(['image', 'remove_image']);

        if (($attributes['sort_order'] ?? null) === null) {
            unset($attributes['sort_order']);
        }

        return $attributes;
    }
}

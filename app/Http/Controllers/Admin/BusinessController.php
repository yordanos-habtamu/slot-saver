<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ComputeBusinessAnalytics;
use App\Actions\Admin\RegisterBusiness;
use App\Enums\BusinessStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBusinessRequest;
use App\Models\Business;
use App\Models\BusinessType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    /**
     * List every business registered on the platform.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Business::class);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $typeId = (int) $request->query('business_type_id', 0);

        return Inertia::render('admin/businesses/index', [
            'businessTypes' => $this->typeOptions(),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'business_type_id' => $typeId > 0 ? $typeId : null,
            ],
            'businesses' => Business::query()
                ->with(['businessType:id,name', 'owner:id,name,email'])
                ->withCount('services')
                ->when($search !== '', fn (Builder $query): Builder => $query->where(function (Builder $query) use ($search): void {
                    $like = '%'.$search.'%';
                    $query->where('name', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhereHas('owner', fn (Builder $owner): Builder => $owner
                            ->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like));
                }))
                ->when($status !== '', fn (Builder $query): Builder => $query
                    ->when(in_array($status, array_column(BusinessStatus::cases(), 'value'), true),
                        fn (Builder $query): Builder => $query->where('status', $status),
                        fn (Builder $query): Builder => $query->whereRaw('1 = 0')))
                ->when($typeId > 0, fn (Builder $query): Builder => $query->where('business_type_id', $typeId))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * Show one business with its platform analytics.
     */
    public function show(Request $request, Business $business, ComputeBusinessAnalytics $analytics): Response
    {
        Gate::authorize('view', $business);

        return Inertia::render('admin/businesses/show', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                'email' => $business->email,
                'phone' => $business->phone,
                'website' => $business->website,
                'city' => $business->city,
                'state' => $business->state,
                'country' => $business->country,
                'timezone' => $business->timezone,
                'status' => $business->status->value,
                'created_at' => $business->created_at->toDateString(),
                'business_type' => $business->businessType->only('id', 'name'),
                'owner' => $business->owner?->only('id', 'name', 'email'),
            ],
            'analytics' => $analytics->handle($business),
            'locations' => $business->locations()
                ->withCount('bookings')
                ->withCount('employees')
                ->orderBy('name')
                ->get()
                ->map(fn ($location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'city' => $location->city,
                    'address' => $location->address_line1,
                    'is_active' => (bool) $location->is_active,
                    'bookings_count' => (int) $location->bookings_count,
                    'employees_count' => (int) $location->employees_count,
                ])
                ->all(),
        ]);
    }

    /**
     * Register a business together with its owner account and default services.
     */
    public function store(StoreBusinessRequest $request, RegisterBusiness $registerBusiness): RedirectResponse
    {
        Gate::authorize('create', Business::class);

        $business = $registerBusiness->handle($request->payload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Business registered with its owner account.'),
        ]);

        return to_route('admin.businesses.index');
    }

    /**
     * The active business types a new business may be registered under.
     *
     * @return array<int, array{id: int, name: string, description: string|null, serviceCount: int}>
     */
    protected function typeOptions(): array
    {
        return BusinessType::query()
            ->active()
            ->withCount('serviceTemplates')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'description'])
            ->map(fn (BusinessType $type): array => [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
                'serviceCount' => $type->service_templates_count,
            ])
            ->all();
    }
}

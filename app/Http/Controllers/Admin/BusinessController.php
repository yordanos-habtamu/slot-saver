<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\RegisterBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBusinessRequest;
use App\Models\Business;
use App\Models\BusinessType;
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

        return Inertia::render('admin/businesses/index', [
            'businessTypes' => $this->typeOptions(),
            'businesses' => Business::query()
                ->with(['businessType:id,name', 'owner:id,name,email'])
                ->withCount('services')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * Show the registration form for a new business.
     */
    public function create(): Response
    {
        Gate::authorize('create', Business::class);

        return Inertia::render('admin/businesses/create', [
            'businessTypes' => $this->typeOptions(),
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

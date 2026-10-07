<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Admin\CreateUserAccount;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\AssignLocationEmployeeRequest;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class LocationEmployeeController extends Controller
{
    /**
     * Assign a specialist to a location. Either attach an existing
     * employee account by user id, or provision a fresh one by email.
     */
    public function store(AssignLocationEmployeeRequest $request, Location $location, CreateUserAccount $createUserAccount): JsonResponse
    {
        Gate::authorize('manage', $location);

        if ($request->filled('user_id')) {
            $user = User::query()->findOrFail($request->integer('user_id'));

            if ($user->role !== UserRole::Employee) {
                return response()->json([
                    'message' => 'Only employee accounts can be assigned to a location.',
                ], 422);
            }
        } else {
            $user = User::query()->where('email', $request->input('email'))->first();

            if ($user === null) {
                $user = $createUserAccount->handle([
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                    'password' => $request->input('password'),
                    'timezone' => $location->timezone,
                ], UserRole::Employee, true);
            } elseif ($user->role !== UserRole::Employee) {
                return response()->json([
                    'message' => 'This email belongs to an account that is not an employee.',
                ], 422);
            }
        }

        if ($location->employees()->whereKey($user->id)->exists()) {
            return response()->json([
                'message' => 'That employee is already assigned to this location.',
            ], 422);
        }

        $location->employees()->attach($user->id, ['is_active' => true]);

        $location->load('employees:id,name,email,role,avatar_path');

        return response()->json([
            'message' => 'Employee assigned.',
            'employees' => $this->employeeCollection($location),
        ], 201);
    }

    /**
     * Remove a specialist from a location (their past bookings stay intact).
     */
    public function destroy(Location $location, User $user): JsonResponse
    {
        Gate::authorize('manage', $location);

        $detached = $location->employees()->detach($user->id);

        if ($detached === 0) {
            abort(404, 'That employee is not assigned to this location.');
        }

        $location->load('employees:id,name,email,role,avatar_path');

        return response()->json([
            'message' => 'Employee removed.',
            'employees' => $this->employeeCollection($location),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function employeeCollection(Location $location): array
    {
        return $location->employees
            ->values()
            ->map(fn (User $employee): array => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => $employee->role->value,
                'avatar_path' => $employee->avatar_path,
                'is_active' => (bool) ($employee->pivot->is_active ?? true),
            ])
            ->all();
    }
}

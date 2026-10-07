<?php

namespace Tests\Feature\Owner;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Location;
use App\Models\LocationClosure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Business $business;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->owner()->create();
        $this->business = Business::factory()->create(['owner_user_id' => $this->owner->id]);
        $this->location = Location::factory()->create(['business_id' => $this->business->id]);
    }

    /**
     * A full working week, 09:00–18:00, with per-day replacements applied.
     *
     * @return array<int, array<string, mixed>>
     */
    private function weekPayload(array $replacements = []): array
    {
        return collect(range(1, 7))->map(function (int $day) use ($replacements): array {
            return $replacements[$day] ?? [
                'day_of_week' => $day,
                'opens_at' => '09:00',
                'closes_at' => '18:00',
                'is_closed' => false,
            ];
        })->all();
    }

    public function test_owner_can_list_locations_with_schedule_and_staff(): void
    {
        $employee = User::factory()->employee()->create();
        $this->location->employees()->attach($employee->id, ['is_active' => true]);
        $this->location->openingHours()->create([
            'day_of_week' => 2,
            'opens_at' => '10:00:00',
            'closes_at' => '19:00:00',
            'is_closed' => false,
        ]);
        $this->location->closures()->create([
            'starts_on' => now()->addDays(5),
            'ends_on' => null,
            'reason' => 'Staff training',
        ]);

        $response = $this->actingAs($this->owner)->getJson(route('owner.locations.index'));

        $response->assertOk()
            ->assertJsonCount(1, 'locations')
            ->assertJsonPath('locations.0.id', $this->location->id)
            ->assertJsonPath('locations.0.opening_hours.0.day_of_week', 2)
            ->assertJsonPath('locations.0.closures.0.reason', 'Staff training')
            ->assertJsonPath('locations.0.employees.0.id', $employee->id);
    }

    public function test_guest_cannot_access_owner_location_endpoints(): void
    {
        $this->getJson(route('owner.locations.index'))->assertUnauthorized();
        $this->postJson(route('owner.locations.store'), [])->assertUnauthorized();
        $this->getJson(route('owner.locations.show', $this->location))->assertUnauthorized();
    }

    public function test_client_role_cannot_access_owner_location_endpoints(): void
    {
        $client = User::factory()->client()->create();

        $this->actingAs($client)->getJson(route('owner.locations.index'))->assertForbidden();
        $this->actingAs($client)->putJson(route('owner.locations.hours.update', $this->location), [])->assertForbidden();
    }

    public function test_owner_can_create_a_location(): void
    {
        $payload = [
            'name' => 'Riverside Studio',
            'address_line1' => 'Avenida da Liberdade 100',
            'city' => 'Lisbon',
            'country' => 'Portugal',
            'timezone' => 'Europe/Lisbon',
            'max_capacity' => 4,
        ];

        $response = $this->actingAs($this->owner)->postJson(route('owner.locations.store'), $payload);

        $response->assertCreated()
            ->assertJsonPath('location.name', 'Riverside Studio')
            ->assertJsonPath('location.max_capacity', 4);

        $this->assertDatabaseHas('locations', [
            'business_id' => $this->business->id,
            'name' => 'Riverside Studio',
            'is_active' => true,
        ]);
    }

    public function test_create_location_requires_core_address_fields(): void
    {
        $this->actingAs($this->owner)
            ->postJson(route('owner.locations.store'), ['name' => 'No address'])
            ->assertJsonValidationErrors(['address_line1', 'city', 'country', 'timezone']);
    }

    public function test_owner_can_update_a_location(): void
    {
        $response = $this->actingAs($this->owner)->putJson(route('owner.locations.update', $this->location), [
            'name' => 'Renamed Branch',
            'max_capacity' => 9,
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('location.name', 'Renamed Branch')
            ->assertJsonPath('location.max_capacity', 9)
            ->assertJsonPath('location.is_active', false);

        $this->assertDatabaseHas('locations', [
            'id' => $this->location->id,
            'name' => 'Renamed Branch',
            'is_active' => false,
        ]);
    }

    public function test_owner_cannot_manage_locations_of_another_business(): void
    {
        $otherOwner = User::factory()->owner()->create();
        $otherBusiness = Business::factory()->create(['owner_user_id' => $otherOwner->id]);
        $foreignLocation = Location::factory()->create(['business_id' => $otherBusiness->id]);

        $actor = $this->actingAs($this->owner);
        $foreignEmployee = User::factory()->employee()->create();

        $actor->getJson(route('owner.locations.show', $foreignLocation))->assertForbidden();
        $actor->putJson(route('owner.locations.update', $foreignLocation), ['name' => 'Hijacked'])->assertForbidden();
        $actor->deleteJson(route('owner.locations.destroy', $foreignLocation))->assertForbidden();
        $actor->putJson(route('owner.locations.hours.update', $foreignLocation), ['hours' => $this->weekPayload()])->assertForbidden();
        $actor->postJson(route('owner.locations.closures.store', $foreignLocation), [
            'starts_on' => now()->addDays(5)->toDateString(),
        ])->assertForbidden();
        $actor->postJson(route('owner.locations.employees.store', $foreignLocation), [
            'user_id' => $foreignEmployee->id,
        ])->assertForbidden();

        $this->assertDatabaseHas('locations', ['id' => $foreignLocation->id, 'name' => $foreignLocation->name]);
    }

    public function test_admin_can_manage_any_location(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson(route('owner.locations.update', $this->location), ['name' => 'Admin Rename'])
            ->assertOk()
            ->assertJsonPath('location.name', 'Admin Rename');
    }

    public function test_deleting_a_location_with_bookings_is_rejected(): void
    {
        Booking::factory()->create([
            'business_id' => $this->business->id,
            'location_id' => $this->location->id,
        ]);

        $this->actingAs($this->owner)
            ->deleteJson(route('owner.locations.destroy', $this->location))
            ->assertStatus(422);

        $this->assertDatabaseHas('locations', ['id' => $this->location->id]);
    }

    public function test_deleting_a_location_without_bookings_succeeds(): void
    {
        $this->actingAs($this->owner)
            ->deleteJson(route('owner.locations.destroy', $this->location))
            ->assertOk();

        $this->assertDatabaseMissing('locations', ['id' => $this->location->id]);
    }

    public function test_weekly_hours_are_replaced_not_merged(): void
    {
        $this->actingAs($this->owner)
            ->putJson(route('owner.locations.hours.update', $this->location), [
                'hours' => $this->weekPayload([
                    1 => ['day_of_week' => 1, 'opens_at' => '11:00', 'closes_at' => '15:00', 'is_closed' => false],
                    7 => ['day_of_week' => 7, 'is_closed' => true],
                ]),
            ])
            ->assertOk()
            ->assertJsonPath('opening_hours', collect(range(1, 7))->map(fn (int $day): array => [
                'day_of_week' => $day,
                'opens_at' => $day === 1 ? '11:00' : '09:00',
                'closes_at' => $day === 1 ? '15:00' : '18:00',
                'is_closed' => $day === 7,
            ])->all());

        $this->assertSame(7, $this->location->openingHours()->count());

        // Replacing again keeps exactly seven rows
        $this->actingAs($this->owner)
            ->putJson(route('owner.locations.hours.update', $this->location), [
                'hours' => $this->weekPayload(),
            ])
            ->assertOk();

        $this->assertSame(7, $this->location->openingHours()->count());

        // The second full-week payload overwrote the first one entirely
        $this->assertSame(
            '09:00',
            substr((string) $this->location->openingHours()->where('day_of_week', 1)->first()->opens_at, 0, 5)
        );
        $this->assertFalse(
            $this->location->openingHours()->where('day_of_week', 7)->first()->is_closed
        );
    }

    public function test_hours_validation_requires_a_full_valid_week(): void
    {
        // Only six days
        $this->actingAs($this->owner)
            ->putJson(route('owner.locations.hours.update', $this->location), [
                'hours' => array_slice($this->weekPayload(), 0, 6),
            ])
            ->assertJsonValidationErrors(['hours']);

        // Duplicate day
        $duplicate = $this->weekPayload();
        $duplicate[6]['day_of_week'] = 2;

        $this->actingAs($this->owner)
            ->putJson(route('owner.locations.hours.update', $this->location), ['hours' => $duplicate])
            ->assertJsonValidationErrors(['hours.6.day_of_week']);

        // Closing before opening
        $badWindow = $this->weekPayload([
            3 => ['day_of_week' => 3, 'opens_at' => '18:00', 'closes_at' => '09:00', 'is_closed' => false],
        ]);

        $this->actingAs($this->owner)
            ->putJson(route('owner.locations.hours.update', $this->location), ['hours' => $badWindow])
            ->assertJsonValidationErrors(['hours.2.closes_at']);

        // Open day without times
        $missingTimes = $this->weekPayload([
            4 => ['day_of_week' => 4, 'is_closed' => false],
        ]);

        $this->actingAs($this->owner)
            ->putJson(route('owner.locations.hours.update', $this->location), ['hours' => $missingTimes])
            ->assertJsonValidationErrors(['hours.3.opens_at']);

        $this->assertSame(0, $this->location->openingHours()->count());
    }

    public function test_closure_lifecycle(): void
    {
        $starts = now()->addDays(10)->toDateString();

        $response = $this->actingAs($this->owner)->postJson(
            route('owner.locations.closures.store', $this->location),
            ['starts_on' => $starts, 'ends_on' => now()->addDays(12)->toDateString(), 'reason' => 'Renovation']
        );

        $response->assertCreated()
            ->assertJsonPath('closure.starts_on', $starts)
            ->assertJsonPath('closure.reason', 'Renovation');

        $closureId = $response->json('closure.id');
        $this->assertDatabaseHas('location_closures', ['id' => $closureId, 'reason' => 'Renovation']);

        $this->actingAs($this->owner)
            ->deleteJson(route('owner.locations.closures.destroy', [$this->location, $closureId]))
            ->assertOk();

        $this->assertDatabaseMissing('location_closures', ['id' => $closureId]);
    }

    public function test_closure_cannot_be_deleted_from_a_foreign_location(): void
    {
        $otherLocation = Location::factory()->create(['business_id' => $this->business->id]);
        $closure = LocationClosure::create([
            'location_id' => $otherLocation->id,
            'starts_on' => now()->addDays(3),
            'reason' => 'Elsewhere',
        ]);

        $this->actingAs($this->owner)
            ->deleteJson(route('owner.locations.closures.destroy', [$this->location, $closure->id]))
            ->assertNotFound();

        $this->assertDatabaseHas('location_closures', ['id' => $closure->id]);
    }

    public function test_closure_dates_must_be_chronological(): void
    {
        $this->actingAs($this->owner)
            ->postJson(route('owner.locations.closures.store', $this->location), [
                'starts_on' => now()->addDays(5)->toDateString(),
                'ends_on' => now()->addDays(2)->toDateString(),
            ])
            ->assertJsonValidationErrors(['ends_on']);
    }

    public function test_existing_employee_can_be_assigned_and_detached(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($this->owner)
            ->postJson(route('owner.locations.employees.store', $this->location), ['user_id' => $employee->id])
            ->assertCreated()
            ->assertJsonPath('employees.0.id', $employee->id);

        $this->assertDatabaseHas('location_user', [
            'location_id' => $this->location->id,
            'user_id' => $employee->id,
            'is_active' => true,
        ]);

        // Duplicate assignment is rejected
        $this->actingAs($this->owner)
            ->postJson(route('owner.locations.employees.store', $this->location), ['user_id' => $employee->id])
            ->assertStatus(422);

        // Clients cannot be assigned
        $client = User::factory()->client()->create();

        $this->actingAs($this->owner)
            ->postJson(route('owner.locations.employees.store', $this->location), ['user_id' => $client->id])
            ->assertStatus(422);

        // Detach
        $this->actingAs($this->owner)
            ->deleteJson(route('owner.locations.employees.destroy', [$this->location, $employee->id]))
            ->assertOk()
            ->assertJsonPath('employees', []);

        $this->assertDatabaseMissing('location_user', [
            'location_id' => $this->location->id,
            'user_id' => $employee->id,
        ]);

        $this->actingAs($this->owner)
            ->deleteJson(route('owner.locations.employees.destroy', [$this->location, $employee->id]))
            ->assertNotFound();
    }

    public function test_owner_can_provision_a_new_employee_account(): void
    {
        $response = $this->actingAs($this->owner)->postJson(
            route('owner.locations.employees.store', $this->location),
            ['email' => 'marta@chairside.test', 'name' => 'Marta Nunes', 'password' => 'secret-password']
        );

        $response->assertCreated()
            ->assertJsonPath('employees.0.email', 'marta@chairside.test')
            ->assertJsonPath('employees.0.role', 'employee');

        $this->assertDatabaseHas('users', [
            'email' => 'marta@chairside.test',
            'role' => UserRole::Employee->value,
        ]);

        $newUser = User::query()->where('email', 'marta@chairside.test')->firstOrFail();
        $this->assertNotNull($newUser->email_verified_at);
        $this->assertDatabaseHas('location_user', [
            'location_id' => $this->location->id,
            'user_id' => $newUser->id,
        ]);
    }

    public function test_existing_client_email_cannot_be_provisioned_as_employee(): void
    {
        $client = User::factory()->client()->create(['email' => 'repeat-client@example.com']);

        $this->actingAs($this->owner)
            ->postJson(route('owner.locations.employees.store', $this->location), [
                'email' => $client->email,
                'name' => 'Repeat Client',
                'password' => 'secret-password',
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('location_user', [
            'location_id' => $this->location->id,
            'user_id' => $client->id,
        ]);
    }

    public function test_assignment_requires_either_an_existing_user_or_full_account_details(): void
    {
        $this->actingAs($this->owner)
            ->postJson(route('owner.locations.employees.store', $this->location), [])
            ->assertJsonValidationErrors(['user_id', 'email']);

        $this->actingAs($this->owner)
            ->postJson(route('owner.locations.employees.store', $this->location), [
                'email' => 'no-name@chairside.test',
            ])
            ->assertJsonValidationErrors(['name', 'password']);
    }
}

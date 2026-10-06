<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\BusinessStatus;
use App\Enums\HistoryAction;
use App\Models\Booking;
use App\Models\BookingHistory;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\BusinessTypeService;
use App\Models\Location;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @phpstan-type Contact array{name: string, email: string}
 * @phpstan-type LocationBlueprint array{name: string, address: string, max_capacity: int, surcharge?: float}
 * @phpstan-type BusinessBlueprint array{
 *     type: string,
 *     name: string,
 *     email: string,
 *     city: string,
 *     country: string,
 *     timezone: string,
 *     owner: Contact,
 *     employees: list<Contact>,
 *     locations: list<LocationBlueprint>
 * }
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the platform admin, a handful of businesses and their bookings.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@slotsaver.test',
        ]);

        $clients = User::factory()->count(6)->client()->create();

        foreach ($this->businessBlueprints() as $blueprint) {
            $this->seedBusiness($blueprint, $clients);
        }
    }

    /**
     * @return list<BusinessBlueprint>
     */
    protected function businessBlueprints(): array
    {
        return [
            [
                'type' => 'hair-salon',
                'name' => 'Aurora Hair Studio',
                'email' => 'hello@aurorahair.test',
                'city' => 'Lisbon',
                'country' => 'Portugal',
                'timezone' => 'Europe/Lisbon',
                'owner' => ['name' => 'Nadia Ferreira', 'email' => 'owner@aurorahair.test'],
                'employees' => [
                    ['name' => 'Tiago Lopes', 'email' => 'tiago@aurorahair.test'],
                    ['name' => 'Marta Silva', 'email' => 'marta@aurorahair.test'],
                    ['name' => 'Rui Costa', 'email' => 'rui@aurorahair.test'],
                ],
                'locations' => [
                    ['name' => 'Príncipe Real', 'max_capacity' => 4, 'address' => 'Rua da Escola Politécnica 12'],
                    ['name' => 'Cais do Sodré', 'max_capacity' => 3, 'address' => 'Av. 24 de Julho 8'],
                ],
            ],
            [
                'type' => 'dental-clinic',
                'name' => 'Bright Smile Dental',
                'email' => 'reception@brightsmile.test',
                'city' => 'Porto',
                'country' => 'Portugal',
                'timezone' => 'Europe/Lisbon',
                'owner' => ['name' => 'Ana Beatriz Reis', 'email' => 'owner@brightsmile.test'],
                'employees' => [
                    ['name' => 'Dr. Hugo Martins', 'email' => 'hugo@brightsmile.test'],
                    ['name' => 'Dr. Inês Rocha', 'email' => 'ines@brightsmile.test'],
                ],
                'locations' => [
                    ['name' => 'Clinic Centre', 'max_capacity' => 2, 'address' => 'Rua de Santa Catarina 210'],
                ],
            ],
            [
                'type' => 'fitness-studio',
                'name' => 'Pulse Athletic Club',
                'email' => 'info@pulseathletic.test',
                'city' => 'Braga',
                'country' => 'Portugal',
                'timezone' => 'Europe/Lisbon',
                'owner' => ['name' => 'Rui Azevedo', 'email' => 'owner@pulseathletic.test'],
                'employees' => [
                    ['name' => 'Sofia Nunes', 'email' => 'sofia@pulseathletic.test'],
                    ['name' => 'Diogo Faria', 'email' => 'diogo@pulseathletic.test'],
                ],
                'locations' => [
                    ['name' => 'Main Floor', 'max_capacity' => 12, 'address' => 'Avenida Central 45'],
                    ['name' => 'Studio B', 'max_capacity' => 6, 'address' => 'Rua dos Axes 3', 'surcharge' => 5.00],
                ],
            ],
        ];
    }

    /**
     * @param  BusinessBlueprint  $blueprint
     * @param  Collection<int, User>  $clients
     */
    protected function seedBusiness(array $blueprint, Collection $clients): void
    {
        $type = BusinessType::where('slug', $blueprint['type'])->firstOrFail();

        $owner = User::factory()->owner()->create([
            'name' => $blueprint['owner']['name'],
            'email' => $blueprint['owner']['email'],
            'timezone' => $blueprint['timezone'],
        ]);

        $business = Business::factory()->create([
            'business_type_id' => $type->id,
            'owner_user_id' => $owner->id,
            'name' => $blueprint['name'],
            'email' => $blueprint['email'],
            'about' => $blueprint['name'].' — '.$type->description,
            'city' => $blueprint['city'],
            'country' => $blueprint['country'],
            'timezone' => $blueprint['timezone'],
            'status' => BusinessStatus::Active,
            'cancellation_notice' => 'Free cancellation up to 24 hours before your appointment.',
        ]);

        $locations = $this->seedLocations($business, $blueprint);
        $employees = $this->seedEmployees($blueprint);
        $services = $this->seedServices($business, $type);

        $locations->each(function (Location $location) use ($employees): void {
            $location->employees()->attach(
                $employees->mapWithKeys(fn (User $employee): array => [$employee->id => ['is_active' => true]])->all()
            );
        });

        foreach ($locations as $index => $location) {
            $surcharge = (float) ($blueprint['locations'][$index]['surcharge'] ?? 0);

            foreach ($services as $service) {
                $service->locations()->attach($location->id, array_filter([
                    'price_override' => $surcharge > 0 ? (float) $service->price + $surcharge : null,
                    'is_active' => true,
                ]));
            }
        }

        foreach ($services as $service) {
            $service->employees()->attach(
                $employees->random(min(2, $employees->count()))
                    ->mapWithKeys(fn (User $employee): array => [$employee->id => []])
                    ->all()
            );
        }

        $this->seedBookings($business, $locations, $services, $employees, $clients);
    }

    /**
     * @param  BusinessBlueprint  $blueprint
     * @return Collection<int, Location>
     */
    protected function seedLocations(Business $business, array $blueprint): Collection
    {
        return collect($blueprint['locations'])->map(
            fn (array $location): Location => Location::factory()->create([
                'business_id' => $business->id,
                'name' => $location['name'],
                'address_line1' => $location['address'],
                'city' => $blueprint['city'],
                'country' => $blueprint['country'],
                'timezone' => $blueprint['timezone'],
                'max_capacity' => $location['max_capacity'],
            ])
        );
    }

    /**
     * @param  BusinessBlueprint  $blueprint
     * @return Collection<int, User>
     */
    protected function seedEmployees(array $blueprint): Collection
    {
        return collect($blueprint['employees'])->map(
            fn (array $employee): User => User::factory()->employee()->create([
                'name' => $employee['name'],
                'email' => $employee['email'],
                'timezone' => $blueprint['timezone'],
            ])
        );
    }

    /**
     * Copy the business type's service templates onto the business.
     *
     * @return Collection<int, Service>
     */
    protected function seedServices(Business $business, BusinessType $type): Collection
    {
        return $type->serviceTemplates->map(
            fn (BusinessTypeService $template): Service => Service::factory()->create([
                'business_id' => $business->id,
                'name' => $template->name,
                'description' => $template->description,
                'price' => $template->price,
                'duration_minutes' => $template->duration_minutes,
                'booking_fee' => $template->booking_fee,
                'cancellation_fee' => $template->cancellation_fee,
                'free_cancellation_hours' => $template->free_cancellation_hours,
                'max_per_client_per_day' => $template->max_per_client_per_day,
                'is_recommended' => $template->is_recommended,
                'sort_order' => $template->sort_order,
            ])
        );
    }

    /**
     * @param  Collection<int, Location>  $locations
     * @param  Collection<int, Service>  $services
     * @param  Collection<int, User>  $employees
     * @param  Collection<int, User>  $clients
     */
    protected function seedBookings(
        Business $business,
        Collection $locations,
        Collection $services,
        Collection $employees,
        Collection $clients,
    ): void {
        $statuses = [
            BookingStatus::Completed,
            BookingStatus::Completed,
            BookingStatus::Confirmed,
            BookingStatus::Pending,
            BookingStatus::Cancelled,
            BookingStatus::NoShow,
        ];

        foreach ($statuses as $index => $status) {
            $service = $services[$index % $services->count()];
            $location = $locations[$index % $locations->count()];
            $client = $clients[$index % $clients->count()];
            $employee = $employees[$index % $employees->count()];

            $start = Carbon::now()
                ->subDays(($index % 3) + 1)
                ->setTime(10 + ($index % 6), $index % 2 === 0 ? 0 : 30);

            $booking = Booking::factory()->create([
                'client_user_id' => $client->id,
                'business_id' => $business->id,
                'location_id' => $location->id,
                'service_id' => $service->id,
                'employee_user_id' => $employee->id,
                'status' => $status,
                'start_at' => $start,
                'end_at' => $start->copy()->addMinutes($service->duration_minutes),
                'duration_minutes' => $service->duration_minutes,
                'service_price' => $service->price,
                'booking_fee' => $service->booking_fee,
                'total_amount' => (float) $service->price + (float) $service->booking_fee,
            ]);

            BookingHistory::factory()->create([
                'booking_id' => $booking->id,
                'action' => HistoryAction::Booked,
                'amount' => $booking->total_amount,
            ]);

            if ($status === BookingStatus::Cancelled) {
                $cancelledAt = $start->copy()->subHours(3);

                $booking->forceFill([
                    'cancelled_at' => $cancelledAt,
                    'cancelled_by_user_id' => $client->id,
                    'cancellation_fee' => $booking->cancellationFeeDue($cancelledAt),
                    'cancellation_reason' => 'Something came up at short notice.',
                ])->save();

                BookingHistory::factory()->create([
                    'booking_id' => $booking->id,
                    'action' => HistoryAction::Cancelled,
                    'amount' => $booking->cancellation_fee,
                    'note' => 'Cancelled by the client from their booking history.',
                ]);
            }

            if ($status === BookingStatus::Completed) {
                $booking->forceFill(['completed_at' => $booking->end_at])->save();

                Review::factory()->create([
                    'booking_id' => $booking->id,
                    'rating' => fake()->numberBetween(3, 5),
                    'title' => $service->name,
                    'body' => 'Great service, the team was punctual and friendly.',
                ]);
            }

            if ($status === BookingStatus::NoShow) {
                $booking->forceFill(['completed_at' => $booking->end_at])->save();
            }

            if (in_array($status, [BookingStatus::Confirmed, BookingStatus::Completed, BookingStatus::NoShow], true)) {
                $service->increment('times_booked');
            }
        }

        $services->each(function (Service $service): void {
            $service->recalculateRatings();
        });

        $reviews = Review::query()
            ->where('business_id', $business->id)
            ->where('is_published', true);

        $business->update([
            'rating_average' => (clone $reviews)->avg('rating'),
            'rating_count' => (clone $reviews)->count(),
        ]);
    }
}

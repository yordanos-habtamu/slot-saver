<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\BusinessStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Location;
use App\Models\Reminder;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use App\Models\WaitlistEntry;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Random\Randomizer;

class DemoBusinessSeeder extends Seeder
{
    /**
     * Run the 26-week realistic longitudinal dataset seeder.
     */
    public function run(): void
    {
        mt_srand(42);

        $businessType = BusinessType::firstOrCreate(
            ['slug' => 'barbershop'],
            ['name' => 'Barbershop', 'description' => 'Precision grooming and styling']
        );

        $owner = User::firstOrCreate(
            ['email' => 'mateo@crownblade.test'],
            [
                'name' => 'Mateo Silva',
                'role' => UserRole::Owner,
                'email_verified_at' => now(),
                'timezone' => 'Europe/Lisbon',
                'password' => bcrypt('password'),
            ]
        );

        $business = Business::firstOrCreate(
            ['slug' => 'crown-and-blade'],
            [
                'business_type_id' => $businessType->id,
                'owner_user_id' => $owner->id,
                'name' => 'Crown & Blade Barbershop',
                'email' => 'hello@crownblade.test',
                'phone' => '+351 21 890 1234',
                'about' => 'Modern craft barbershop in Chiado, Lisbon. Specializing in hot towel shaves and skin fades.',
                'address_line1' => 'Rua do Alecrim 34',
                'city' => 'Lisbon',
                'country' => 'Portugal',
                'timezone' => 'Europe/Lisbon',
                'status' => BusinessStatus::Active,
                'cancellation_notice' => 'Free cancellation up to 24 hours prior to appointment.',
            ]
        );

        $location = Location::firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Chiado Flagship'],
            [
                'address_line1' => 'Rua do Alecrim 34',
                'city' => 'Lisbon',
                'country' => 'Portugal',
                'timezone' => 'Europe/Lisbon',
                'max_capacity' => 6,
            ]
        );

        // Seed 3 Barbers
        $barbers = collect([
            ['name' => 'André Rocha', 'email' => 'andre@crownblade.test'],
            ['name' => 'Diogo Santos', 'email' => 'diogo@crownblade.test'],
            ['name' => 'Tiago Mendes', 'email' => 'tiago@crownblade.test'],
        ])->map(function ($b) use ($location) {
            $user = User::firstOrCreate(
                ['email' => $b['email']],
                [
                    'name' => $b['name'],
                    'role' => UserRole::Employee,
                    'email_verified_at' => now(),
                    'timezone' => 'Europe/Lisbon',
                    'password' => bcrypt('password'),
                ]
            );
            $location->employees()->syncWithoutDetaching([$user->id => ['is_active' => true]]);

            return $user;
        });

        // Seed 4 Core Services
        $serviceData = [
            ['name' => 'Signature Haircut', 'price' => 35.00, 'duration_minutes' => 45],
            ['name' => 'Beard Sculpt & Hot Towel', 'price' => 25.00, 'duration_minutes' => 30],
            ['name' => 'Royal Treatment (Hair + Beard)', 'price' => 55.00, 'duration_minutes' => 60],
            ['name' => 'Quick Buzz & Cleanup', 'price' => 20.00, 'duration_minutes' => 25],
        ];

        $services = collect($serviceData)->map(function ($s) use ($business, $location, $barbers) {
            $service = Service::firstOrCreate(
                ['business_id' => $business->id, 'name' => $s['name']],
                [
                    'price' => $s['price'],
                    'duration_minutes' => $s['duration_minutes'],
                    'booking_fee' => 0.00,
                    'is_active' => true,
                    'is_recommended' => $s['price'] > 30,
                ]
            );
            $service->locations()->syncWithoutDetaching([$location->id => ['is_active' => true]]);
            $service->employees()->syncWithoutDetaching($barbers->pluck('id')->all());

            return $service;
        });

        // Seed Client Pool (40 clients)
        $clients = collect(range(1, 40))->map(function ($i) {
            $client = User::firstOrCreate(
                ['email' => "client{$i}@example.com"],
                [
                    'name' => "Client {$i} Silva",
                    'phone' => '+351912'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                    'role' => UserRole::Client,
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                ]
            );

            return $client;
        });

        // Generate 26 Weeks of Longitudinal Appointments
        // Start date: 26 weeks ago
        $startDate = Carbon::now()->subWeeks(26)->startOfWeek();
        $random = new Randomizer;

        $bookingInsertBatch = [];
        $reminderInsertBatch = [];
        $waitlistInsertBatch = [];

        // Track sequential slot times per barber per day to avoid exclusion constraint conflicts
        for ($week = 1; $week <= 26; $week++) {
            $weekStart = $startDate->copy()->addWeeks($week - 1);

            // Determine era behavior
            // Weeks 1-4: Baseline (22% no-show, 0 deposits, 0 refills)
            // Weeks 5-8: Phase 1 Reminders (12% no-show)
            // Weeks 9-26: Full SlotSaver (4.5% no-show, deposits, refills)
            $isBaselineEra = $week <= 4;
            $isIntroEra = $week > 4 && $week <= 8;
            $isSlotSaverEra = $week > 8;

            // Generate ~25-30 appointments per week (Tuesday through Saturday)
            foreach ([1, 2, 3, 4, 5] as $dayOffset) {
                $dayDate = $weekStart->copy()->addDays($dayOffset);

                foreach ($barbers as $barberIndex => $barber) {
                    // 2 appointments per barber per day = ~30 bookings/week
                    foreach ([0, 1] as $slotIndex) {
                        $service = $services->random();
                        $client = $clients->random();
                        $duration = $service->duration_minutes;

                        $hour = 10 + ($slotIndex * 3) + $barberIndex;
                        $apptStart = $dayDate->copy()->setHour($hour)->setMinute(0)->setSecond(0);
                        $apptEnd = $apptStart->copy()->addMinutes($duration);

                        // Determine status based on era probabilities
                        $randPct = mt_rand(1, 100);
                        $status = BookingStatus::Completed;
                        $depositStatus = 'none';
                        $depositAmount = 0.00;
                        $riskScore = 0.15;

                        if ($isBaselineEra) {
                            if ($randPct <= 24) {
                                $status = BookingStatus::NoShow;
                            } elseif ($randPct <= 32) {
                                $status = BookingStatus::Cancelled;
                            }
                        } elseif ($isIntroEra) {
                            if ($randPct <= 12) {
                                $status = BookingStatus::NoShow;
                            } elseif ($randPct <= 24) {
                                $status = BookingStatus::Cancelled;
                            }
                        } else {
                            // Full SlotSaver era
                            $riskScore = round(mt_rand(10, 85) / 100, 2);
                            $requiresDeposit = $riskScore >= 0.65;
                            if ($requiresDeposit) {
                                $depositAmount = 15.00;
                                $depositStatus = 'paid';
                            }

                            if ($randPct <= 4) {
                                $status = BookingStatus::NoShow;
                                if ($requiresDeposit) {
                                    $depositStatus = 'forfeited';
                                }
                            } elseif ($randPct <= 16) {
                                $status = BookingStatus::Cancelled;
                            }
                        }

                        $refCode = 'CB-'.strtoupper(substr(md5("{$week}-{$dayOffset}-{$barber->id}-{$slotIndex}"), 0, 8));

                        $booking = Booking::create([
                            'reference_code' => $refCode,
                            'business_id' => $business->id,
                            'location_id' => $location->id,
                            'service_id' => $service->id,
                            'employee_user_id' => $barber->id,
                            'client_user_id' => $client->id,
                            'status' => $status,
                            'start_at' => $apptStart,
                            'end_at' => $apptEnd,
                            'duration_minutes' => $duration,
                            'buffer_minutes' => 10,
                            'party_size' => 1,
                            'service_price' => $service->price,
                            'booking_fee' => 0.00,
                            'total_amount' => $service->price,
                            'currency' => 'EUR',
                            'deposit_amount' => $depositAmount,
                            'deposit_status' => $depositStatus,
                            'risk_score' => $riskScore,
                            'created_at' => $apptStart->copy()->subDays(4),
                            'updated_at' => $apptStart,
                        ]);

                        // Seed reminders if beyond baseline era
                        if (! $isBaselineEra) {
                            Reminder::create([
                                'booking_id' => $booking->id,
                                'channel' => 'whatsapp',
                                'type' => '24h',
                                'scheduled_for' => $apptStart->copy()->subHours(24),
                                'delivery_status' => 'confirmed',
                                'sent_at' => $apptStart->copy()->subHours(24),
                                'created_at' => $apptStart->copy()->subDays(4),
                            ]);

                            if ($isSlotSaverEra) {
                                Reminder::create([
                                    'booking_id' => $booking->id,
                                    'channel' => 'whatsapp',
                                    'type' => '48h',
                                    'scheduled_for' => $apptStart->copy()->subHours(48),
                                    'delivery_status' => 'delivered',
                                    'sent_at' => $apptStart->copy()->subHours(48),
                                    'created_at' => $apptStart->copy()->subDays(4),
                                ]);
                            }
                        }

                        // Seed waitlist auto-fill refills for cancelled bookings in SlotSaver era
                        if ($isSlotSaverEra && $status === BookingStatus::Cancelled) {
                            // 85% refill success rate
                            if (mt_rand(1, 100) <= 85) {
                                $waitlistedClient = $clients->where('id', '!=', $client->id)->random();
                                WaitlistEntry::create([
                                    'business_id' => $business->id,
                                    'client_user_id' => $waitlistedClient->id,
                                    'service_id' => $service->id,
                                    'preferred_date' => $dayDate,
                                    'preferred_time_from' => '10:00',
                                    'preferred_time_to' => '18:00',
                                    'status' => 'claimed',
                                    'claim_token' => 'wl_'.str()->random(24),
                                    'freed_booking_id' => $booking->id,
                                    'offer_expires_at' => $apptStart->copy()->subHours(12),
                                    'created_at' => $apptStart->copy()->subDays(2),
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // Reviews: roughly one in three completed appointments gets a rating
        $reviewCatalogue = [
            ['title' => 'Best haircut in Lisbon', 'body' => 'Consistent every single visit. The consultation is thorough and the fade is flawless.'],
            ['title' => 'Great service', 'body' => 'Friendly team, on time, and the hot towel finish is excellent value.'],
            ['title' => 'Perfect as always', 'body' => 'Been coming for months and never disappointed. Booking flow with reminders is smooth.'],
            ['title' => 'Solid cut', 'body' => 'Good cut, though the wait was a few minutes past my slot. Would book again.'],
            ['title' => 'Loved it', 'body' => 'They remembered my preferences from the notes. Felt genuinely looked after.'],
            ['title' => 'Not my style', 'body' => 'The cut came out shorter than I asked. Staff was apologetic and offered a redo.'],
        ];

        $completedBookings = Booking::query()
            ->where('business_id', $business->id)
            ->where('status', BookingStatus::Completed->value)
            ->orderBy('start_at')
            ->get(['id', 'service_id', 'business_id', 'client_user_id']);

        foreach ($completedBookings as $completed) {
            if (mt_rand(1, 100) > 33) {
                continue;
            }

            $roll = mt_rand(1, 100);
            $rating = $roll <= 60 ? 5 : ($roll <= 85 ? 4 : ($roll <= 95 ? 3 : 2));
            $entry = $reviewCatalogue[array_rand($reviewCatalogue)];

            Review::firstOrCreate(
                ['booking_id' => $completed->id],
                [
                    'service_id' => $completed->service_id,
                    'business_id' => $completed->business_id,
                    'client_user_id' => $completed->client_user_id,
                    'rating' => $rating,
                    'title' => $entry['title'],
                    'body' => $entry['body'],
                    'would_recommend' => $rating >= 4,
                    'is_published' => true,
                ]
            );
        }

        // Refresh denormalized rating aggregates
        $business->recalculateRatings();

        foreach ($services as $service) {
            $service->recalculateRatings();
        }

        foreach ($barbers as $barber) {
            $barber->recalculateRatingAggregate();
        }
    }
}

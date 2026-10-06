<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    /**
     * Seed the admin-managed business types and their default service lists.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Hair Salon',
                'slug' => 'hair-salon',
                'description' => 'Cuts, colour, treatments and styling.',
                'icon' => 'scissors',
                'theme' => 'rose',
                'services' => [
                    ['Signature Cut & Style', 45.00, 45, 3.00, 12.00, 24, 1, true],
                    ['Full Colour', 120.00, 120, 5.00, 25.00, 48, 1, true],
                    ['Balayage', 180.00, 180, 5.00, 35.00, 72, 1, false],
                    ['Blow Dry', 25.00, 30, 2.00, 8.00, 12, 2, false],
                    ['Keratin Treatment', 210.00, 90, 8.00, 40.00, 72, 1, false],
                ],
            ],
            [
                'name' => 'Barbershop',
                'slug' => 'barbershop',
                'description' => 'Fades, beard work and shaves.',
                'icon' => 'scissors',
                'theme' => 'default',
                'services' => [
                    ['Skin Fade', 30.00, 30, 2.00, 10.00, 12, 2, true],
                    ['Beard Sculpt', 20.00, 20, 2.00, 7.00, 12, null, true],
                    ['Cut & Beard', 45.00, 50, 3.00, 15.00, 24, 1, false],
                    ['Hot Towel Shave', 35.00, 40, 3.00, 12.00, 24, 1, false],
                    ['Kids Cut', 20.00, 25, 1.50, 8.00, 12, null, false],
                ],
            ],
            [
                'name' => 'Nail Spa',
                'slug' => 'nail-spa',
                'description' => 'Manicures, pedicures and nail art.',
                'icon' => 'sparkles',
                'theme' => 'rose',
                'services' => [
                    ['Classic Manicure', 28.00, 40, 2.00, 9.00, 24, 2, true],
                    ['Gel Manicure', 45.00, 55, 3.00, 15.00, 24, 2, true],
                    ['Spa Pedicure', 50.00, 60, 3.00, 18.00, 24, 1, true],
                    ['Nail Art', 20.00, 30, 2.00, 8.00, 12, null, false],
                    ['Extensions', 90.00, 120, 5.00, 25.00, 72, 1, false],
                ],
            ],
            [
                'name' => 'Dental Clinic',
                'slug' => 'dental-clinic',
                'description' => 'Check-ups, hygiene and restorative care.',
                'icon' => 'stethoscope',
                'theme' => 'sky',
                'services' => [
                    ['Routine Check-up', 60.00, 30, 5.00, 20.00, 24, 1, true],
                    ['Scale & Polish', 110.00, 45, 5.00, 25.00, 48, 1, true],
                    ['Whitening', 260.00, 60, 10.00, 45.00, 72, 1, false],
                    ['Filling', 150.00, 50, 5.00, 30.00, 72, 1, false],
                    ['Emergency Slot', 95.00, 30, 15.00, 0.00, 0, 1, false],
                ],
            ],
            [
                'name' => 'Fitness Studio',
                'slug' => 'fitness-studio',
                'description' => 'Classes, personal training and assessments.',
                'icon' => 'dumbbell',
                'theme' => 'emerald',
                'services' => [
                    ['Personal Training (60m)', 55.00, 60, 4.00, 15.00, 12, 1, true],
                    ['Group Class', 18.00, 50, 2.00, 8.00, 6, 2, true],
                    ['Fitness Assessment', 40.00, 45, 4.00, 15.00, 24, 1, false],
                    ['Mobility Session', 25.00, 40, 2.00, 8.00, 12, 2, false],
                    ['Monthly Membership', 220.00, 60, 0.00, 0.00, 0, 1, false],
                ],
            ],
            [
                'name' => 'Tutoring Centre',
                'slug' => 'tutoring-centre',
                'description' => 'One-to-one and small group academic support.',
                'icon' => 'graduation-cap',
                'theme' => 'amber',
                'services' => [
                    ['Maths One-to-One', 40.00, 60, 3.00, 12.00, 24, 1, true],
                    ['Science One-to-One', 40.00, 60, 3.00, 12.00, 24, 1, true],
                    ['Exam Prep Workshop', 65.00, 90, 5.00, 20.00, 48, 1, true],
                    ['Small Group (4)', 22.00, 60, 2.00, 8.00, 24, 2, false],
                    ['Homework Support', 30.00, 45, 2.00, 10.00, 24, 2, false],
                ],
            ],
        ];

        foreach ($types as $sortOrder => $type) {
            $businessType = BusinessType::updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'icon' => $type['icon'],
                    'theme' => $type['theme'],
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ],
            );

            $businessType->serviceTemplates()->delete();

            foreach ($type['services'] as $serviceSortOrder => $service) {
                $businessType->serviceTemplates()->create([
                    'name' => $service[0],
                    'description' => $service[0].' as offered by a '.$type['name'].'.',
                    'price' => $service[1],
                    'duration_minutes' => $service[2],
                    'booking_fee' => $service[3],
                    'cancellation_fee' => $service[4],
                    'free_cancellation_hours' => $service[5] === 0 ? null : $service[5],
                    'max_per_client_per_day' => $service[6],
                    'is_recommended' => $service[7],
                    'sort_order' => $serviceSortOrder,
                ]);
            }
        }

        BusinessType::whereNotIn('slug', array_column($types, 'slug'))->delete();
    }
}

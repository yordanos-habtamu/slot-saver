<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportRiskFeatures extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'risk:export-features {--output= : Custom path for the exported CSV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export completed and no-show booking features for risk model training';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $outputPath = $this->option('output') ?: storage_path('app/risk/features.csv');
        $directory = dirname($outputPath);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $bookings = Booking::query()
            ->whereIn('status', [BookingStatus::Completed->value, BookingStatus::NoShow->value])
            ->with(['client'])
            ->get();

        $file = fopen($outputPath, 'w');

        if ($file === false) {
            $this->error("Unable to open {$outputPath} for writing.");

            return self::FAILURE;
        }

        // CSV Header
        fputcsv($file, [
            'booking_id',
            'lead_time_hours',
            'prior_no_shows',
            'prior_completed',
            'no_show_ratio',
            'day_of_week',
            'hour_of_day',
            'service_duration_min',
            'service_price',
            'deposit_paid',
            'no_show',
        ]);

        $count = 0;

        foreach ($bookings as $booking) {
            $created = $booking->created_at ?? $booking->start_at->copy()->subHours(24);
            $leadTimeHours = max(0.5, round($created->diffInMinutes($booking->start_at, false) / 60, 2));

            $priorNoShows = Booking::query()
                ->where('client_user_id', $booking->client_user_id)
                ->where('status', BookingStatus::NoShow->value)
                ->where('start_at', '<', $booking->start_at)
                ->count();

            $priorCompleted = Booking::query()
                ->where('client_user_id', $booking->client_user_id)
                ->where('status', BookingStatus::Completed->value)
                ->where('start_at', '<', $booking->start_at)
                ->count();

            $totalVisits = $priorNoShows + $priorCompleted;
            $ratio = $totalVisits > 0 ? round($priorNoShows / $totalVisits, 4) : 0.0;

            $isNoShow = $booking->status === BookingStatus::NoShow ? 1 : 0;
            $depositPaid = $booking->deposit_status === 'paid' || (float) $booking->deposit_amount > 0 ? 1 : 0;

            fputcsv($file, [
                $booking->id,
                $leadTimeHours,
                $priorNoShows,
                $priorCompleted,
                $ratio,
                $booking->start_at->dayOfWeekIso - 1,
                (int) $booking->start_at->format('G'),
                $booking->duration_minutes,
                (float) $booking->service_price,
                $depositPaid,
                $isNoShow,
            ]);

            $count++;
        }

        fclose($file);

        $this->info("Successfully exported {$count} booking feature record(s) to: {$outputPath}");

        return self::SUCCESS;
    }
}

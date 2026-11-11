<?php

namespace App\Support;

use App\Models\Examination;
use Carbon\CarbonImmutable;

/**
 * Formats examination documents for the proctor dashboard and tables so the
 * API shape stays identical on every page.
 */
final class ExaminationPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Examination $examination, ?int $enrolledStudents = null): array
    {
        $date = self::date($examination);

        return [
            'id' => (string) $examination->getKey(),
            'title' => $examination->title,
            'subject' => $examination->subject,
            'section' => $examination->section,
            'scheduled_date' => $examination->scheduled_date,
            'start_time' => $examination->start_time,
            'end_time' => $examination->end_time,
            'date_label' => $date?->format('M j, Y') ?? '—',
            'time_range' => self::timeRange($examination->start_time, $examination->end_time),
            'schedule' => trim(($date?->format('M j, Y') ?? '—').' · '.self::timeRange($examination->start_time, $examination->end_time)),
            'status' => $examination->status,
            'status_label' => $examination->statusLabel(),
            'access_code' => $examination->access_code,
            'started_at' => $examination->started_at?->toIso8601String(),
            'ended_at' => $examination->ended_at?->toIso8601String(),
            'enrolled_students' => $enrolledStudents,
        ];
    }

    public static function date(Examination $examination): ?CarbonImmutable
    {
        if (blank($examination->scheduled_date)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $examination->scheduled_date);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function timeRange(?string $start, ?string $end): string
    {
        if (blank($start) && blank($end)) {
            return 'To be announced';
        }

        if (blank($end)) {
            return self::time($start);
        }

        return self::time($start).' – '.self::time($end);
    }

    public static function time(?string $value): string
    {
        if (blank($value)) {
            return '—';
        }

        try {
            return CarbonImmutable::createFromFormat('!H:i', $value)->format('g:i A');
        } catch (\Throwable) {
            return $value;
        }
    }
}

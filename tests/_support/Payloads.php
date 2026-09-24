<?php

namespace clickrain\stratus\tests\_support;

/**
 * Builds payloads shaped like the ones the Stratus API returns, so sync tests
 * exercise the same code path as a real import.
 */
final class Payloads
{
    public static function listing(array $overrides = []): array
    {
        return $overrides + [
            'uuid' => 'listing-0000-0000-0000-000000000001',
            'name' => 'Click Rain',
            'type' => 'location',
            'address' => '405 E 8th St',
            'address2' => 'Suite 300',
            'city' => 'Sioux Falls',
            'state' => 'SD',
            'zip' => '57103',
            'phone' => '605-274-1200',
            'timezone' => 'America/Chicago',
            'hours' => null,
            'holiday_hours' => null,
            'reviewables' => null,
            'deleted_at' => null,
        ];
    }

    public static function review(array $overrides = []): array
    {
        return $overrides + [
            'uuid' => 'review-0000-0000-0000-000000000001',
            'parent_uuid' => 'listing-0000-0000-0000-000000000001',
            'platform' => 'google',
            'rating' => 5,
            'recommendation' => null,
            'author' => 'A. Reviewer',
            'platform_published_date' => '2026-01-15 12:00:00',
            'content' => 'Genuinely helpful people.',
            'reviewable_type' => 'location',
            'reviewable_name' => 'Click Rain',
            'deleted_at' => null,
        ];
    }
}

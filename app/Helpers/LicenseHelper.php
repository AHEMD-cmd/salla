<?php

namespace App\Helpers;

class LicenseHelper
{
    // public static function generateKey(string $domainName): string
    // {
    //     $segments = [];

    //     // Generate first 4 random segments
    //     for ($i = 0; $i < 4; $i++) {
    //         $segments[] = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    //     }

    //     // Create checksum using domain name + segments
    //     $data = $domainName . '-' . implode('-', $segments);
    //     $checksum = strtoupper(substr(hash('sha256', $data), 0, 4));

    //     return implode('-', [...$segments, $checksum]);
    // }

    public static function generateKey(string $domainName, string $type): string
    {
        $segments = [];

        for ($i = 0; $i < 4; $i++) {
            $segments[] = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        }

        // Encode type into a 4-char segment (مثلاً "IV00" أو "CL00")
        $typeSegment = $type === 'inversor' ? 'IV00' : 'CL00';
        $segments[] = $typeSegment;

        // checksum includes domain and type segment
        $data = $domainName . '-' . implode('-', $segments);
        $checksum = strtoupper(substr(hash('sha256', $data), 0, 4));

        $segments[] = $checksum;

        return implode('-', $segments); // 6 segments
    }



    // public static function validateKey(string $key, string $domainName): bool
    // {
    //     if (!preg_match('/^([A-Z0-9]{4}-){4}[A-Z0-9]{4}$/', $key)) {
    //         return false;
    //     }

    //     $parts = explode('-', $key);
    //     if (count($parts) !== 5) return false;

    //     $dataSegments = array_slice($parts, 0, 4);
    //     $checksum = $parts[4];

    //     // Recalculate checksum with domain name
    //     $data = $domainName . '-' . implode('-', $dataSegments);
    //     $expectedChecksum = strtoupper(substr(hash('sha256', $data), 0, 4));

    //     return $checksum === $expectedChecksum;
    // }

    public static function validateKey(string $key, string $domainName): ?string
    {
        // الشكل المتوقع: XXXX-XXXX-XXXX-XXXX-TYPE-CHECK
        if (!preg_match('/^([A-Z0-9]{4}-){5}[A-Z0-9]{4}$/', $key)) {
            return null;
        }

        $parts = explode('-', $key);
        if (count($parts) !== 6) return null;

        $typeSegment = $parts[4];
        $checksum = $parts[5];

        $data = $domainName . '-' . implode('-', array_slice($parts, 0, 5));
        $expectedChecksum = strtoupper(substr(hash('sha256', $data), 0, 4));

        if ($checksum !== $expectedChecksum) {
            return null;
        }

        // Extract type from segment
        return match ($typeSegment) {
            'IV00' => 'inversor',
            'CL00' => 'client',
            default => null,
        };
    }
}

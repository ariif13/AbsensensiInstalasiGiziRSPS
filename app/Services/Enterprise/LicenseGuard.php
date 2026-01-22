<?php

namespace App\Services\Enterprise;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;


final class LicenseGuard {
    private const PUB = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlJQklqQU5CZ2txaGtpRzl3MEJBUUVGQUFPQ0FROEFNSUlCQ2dLQ0FRRUEyVVBHUXpEbWxtbTh2M0NZRzc3UwpSQUQrOHk3R28rR0xOZ2tBc1B4d0JyMmNsd01McEgzNm9nRFBEUkhGR2JGay8zeGFGdTBpTlRGYVpYNnd3dTJWCnlYRGhjNHovVzZvY2szamZRR0xYMDREWlNlS3hzMVpEaTZlZ0xYN3ZWQVVMdDUzM0tlM2ZkTnZWRjFYTU1mOUQKSGgzSE9Hc2J3cXRVSWtyWXB0a01zNU5BYkREZUZueVM0cjRnWm9aVDJQakNaaFZWanZQZFo2OXFVOWxJMTdVTwpzUzdocHZYd1hDYUhhditRYzc4OW9Rb2s0b2E2UUhBRUFQQllTUHJ2R3RrdG9lRTRGVGRlV2c0QW1DLzBDT1hECitJdlgwZGk1SVMwemY3STFIMzg3MkgvWGF0UW1QL2tkb21ZeEVxblNRVWFXbW9nWFpDN0o0dG55akRnQW8zQzUKSndJREFRQUIKLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg==';

    public static function check() {
        if (!self::hasValidLicense()) abort(403, 'Invalid Enterprise License Key');
        return true;
    }

    public static function hasValidLicense() {
        $k = Setting::getValue('enterprise_license_key');
        if (!$k) return false;
        if (Cache::has('enterprise_license_valid')) return true;
        if (self::v($k)) {
            Cache::put('enterprise_license_valid', true, now()->addHours(24));
            return true;
        }
        return false;
    }

    private static function v($s) {
        try {
            $p = explode('.', $s);
            if (count($p) !== 2) return false;
            $d = base64_decode($p[0]);
            $g = base64_decode($p[1]);
            if (openssl_verify($d, $g, base64_decode(self::PUB), OPENSSL_ALGO_SHA256) !== 1) return false;
            $j = json_decode($d, true);
            if (($j['author']??'') !== 'RiprLutuk(https://riprlutuk.github.io)') return false;
            if (isset($j['expires_at']) && \Carbon\Carbon::parse($j['expires_at'])->isPast()) return false;
            return ($j['client']??'') === Setting::getValue('app.company_name');
        } catch (\Exception $e) { return false; }
    }

    public static function getLicenseInfo() {
        $k = Setting::getValue('enterprise_license_key');
        if (!$k) return null;
        if (!self::v($k)) return null; 
        try {
            $p = explode('.', $k);
            $d = base64_decode($p[0]);
            return json_decode($d, true);
        } catch (\Exception $e) { return null; }
    }
}

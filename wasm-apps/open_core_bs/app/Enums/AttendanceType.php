<?php

namespace App\Enums;

enum AttendanceType: string
{
    case OPEN = 'open';
    case GEOFENCE = 'geofence';
    case QR_CODE = 'qr_code';
    case STATIC_QR = 'static_qr';
    case DYNAMIC_QR = 'dynamic_qr';
    case IP_ADDRESS = 'ip_address';
    case SITE = 'site';
    case FACE_RECOGNITION = 'face_recognition';
    case FINGERPRINT = 'fingerprint';
    case NFC = 'nfc';
    case RFID = 'rfid';
    case MANUAL = 'manual';

    /**
     * Get the human-readable label for the attendance type.
     */
    public function label(): string
    {
        return match ($this) {
            self::OPEN => __('Open Attendance'),
            self::GEOFENCE => __('Geofence'),
            self::QR_CODE => __('QR Code'),
            self::STATIC_QR => __('Static QR'),
            self::DYNAMIC_QR => __('Dynamic QR'),
            self::IP_ADDRESS => __('IP Address'),
            self::SITE => __('Site-based'),
            self::FACE_RECOGNITION => __('Face Recognition'),
            self::FINGERPRINT => __('Fingerprint'),
            self::NFC => __('NFC'),
            self::RFID => __('RFID'),
            self::MANUAL => __('Manual'),
        };
    }

    /**
     * Check if this attendance type requires a dependency.
     */
    public function requiresDependency(): bool
    {
        return match ($this) {
            self::GEOFENCE,
            self::IP_ADDRESS,
            self::STATIC_QR,
            self::QR_CODE,
            self::SITE,
            self::DYNAMIC_QR => true,
            default => false,
        };
    }

    /**
     * Get the dependency field name for this attendance type.
     */
    public function getDependencyField(): ?string
    {
        return match ($this) {
            self::GEOFENCE => 'geofence_group_id',
            self::IP_ADDRESS => 'ip_address_group_id',
            self::STATIC_QR, self::QR_CODE => 'qr_group_id',
            self::SITE => 'site_id',
            self::DYNAMIC_QR => 'dynamic_qr_device_id',
            default => null,
        };
    }
}

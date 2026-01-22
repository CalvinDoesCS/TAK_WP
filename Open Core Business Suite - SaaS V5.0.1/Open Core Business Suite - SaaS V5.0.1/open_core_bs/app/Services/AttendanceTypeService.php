<?php

namespace App\Services;

use App\Models\User;
use App\Services\AddonService\AddonService;
use Exception;
use Illuminate\Support\Facades\DB;

class AttendanceTypeService
{
    protected AddonService $addonService;

    public function __construct(AddonService $addonService)
    {
        $this->addonService = $addonService;
    }

    /**
     * Attendance type mapping for internal values.
     */
    protected const TYPE_MAP = [
        'geofence' => 'geofence',
        'ipAddress' => 'ip_address',
        'staticqr' => 'qr_code',
        'site' => 'site',
        'dynamicqr' => 'dynamic_qr',
        'face' => 'face_recognition',
        'open' => 'open',
    ];

    /**
     * Validate if the attendance type has all required dependencies.
     *
     * @param  string  $type  Attendance type (geofence, ipAddress, staticqr, site, dynamicqr, face, open)
     * @param  array  $data  Data containing required fields
     * @return bool True if valid, throws exception if invalid
     *
     * @throws Exception
     */
    public function validateAttendanceType(string $type, array $data): bool
    {
        switch ($type) {
            case 'geofence':
                if (! $this->addonService->isAddonEnabled('GeofenceSystem')) {
                    throw new Exception(__('Geofence attendance module is not enabled'));
                }

                if (! isset($data['geofenceGroupId']) || empty($data['geofenceGroupId'])) {
                    throw new Exception(__('Geofence group is required for geofence attendance type'));
                }

                $geofenceGroupClass = \Modules\GeofenceSystem\App\Models\GeofenceGroup::class;
                $geofenceGroup = $geofenceGroupClass::find($data['geofenceGroupId']);
                if (! $geofenceGroup) {
                    throw new Exception(__('Selected geofence group does not exist'));
                }
                break;

            case 'ipAddress':
                if (! $this->addonService->isAddonEnabled('IpAddressAttendance')) {
                    throw new Exception(__('IP Address attendance module is not enabled'));
                }

                if (! isset($data['ipGroupId']) || empty($data['ipGroupId'])) {
                    throw new Exception(__('IP address group is required for IP address attendance type'));
                }

                $ipGroupClass = \Modules\IpAddressAttendance\App\Models\IpAddressGroup::class;
                $ipGroup = $ipGroupClass::find($data['ipGroupId']);
                if (! $ipGroup) {
                    throw new Exception(__('Selected IP address group does not exist'));
                }
                break;

            case 'staticqr':
                if (! $this->addonService->isAddonEnabled('QrAttendance')) {
                    throw new Exception(__('QR Attendance module is not enabled'));
                }

                if (! isset($data['qrGroupId']) || empty($data['qrGroupId'])) {
                    throw new Exception(__('QR group is required for static QR attendance type'));
                }

                $qrGroupClass = \Modules\QRAttendance\App\Models\QrGroup::class;
                $qrGroup = $qrGroupClass::find($data['qrGroupId']);
                if (! $qrGroup) {
                    throw new Exception(__('Selected QR group does not exist'));
                }
                break;

            case 'site':
                if (! $this->addonService->isAddonEnabled('SiteAttendance')) {
                    throw new Exception(__('Site Attendance module is not enabled'));
                }

                if (! isset($data['siteId']) || empty($data['siteId'])) {
                    throw new Exception(__('Site is required for site attendance type'));
                }

                $siteClass = \Modules\SiteAttendance\App\Models\Site::class;
                $site = $siteClass::find($data['siteId']);
                if (! $site) {
                    throw new Exception(__('Selected site does not exist'));
                }
                break;

            case 'dynamicqr':
                if (! $this->addonService->isAddonEnabled('DynamicQrAttendance')) {
                    throw new Exception(__('Dynamic QR Attendance module is not enabled'));
                }

                if (! isset($data['dynamicQrId']) || empty($data['dynamicQrId'])) {
                    throw new Exception(__('Dynamic QR device is required for dynamic QR attendance type'));
                }

                $deviceClass = \Modules\DynamicQrAttendance\App\Models\DynamicQrDevice::class;
                $device = $deviceClass::find($data['dynamicQrId']);
                if (! $device) {
                    throw new Exception(__('Selected dynamic QR device does not exist'));
                }

                // Check if device is already assigned to another user
                if ($device->status === 'in_use' && $device->user_id !== null) {
                    // Allow if reassigning to same user
                    if (! isset($data['userId']) || $device->user_id !== $data['userId']) {
                        throw new Exception(__('Selected dynamic QR device is already in use by another employee'));
                    }
                }
                break;

            case 'face':
                if (! $this->addonService->isAddonEnabled('FaceAttendance')) {
                    throw new Exception(__('Face Attendance module is not enabled'));
                }
                // Face recognition doesn't require additional dependencies
                // Face data will be captured separately through the face enrollment process
                break;

            case 'open':
                // Open attendance type doesn't require any dependencies
                break;

            default:
                throw new Exception(__('Invalid attendance type'));
        }

        return true;
    }

    /**
     * Assign attendance type to user and update related records.
     *
     * @param  User  $user  The user to assign attendance type to
     * @param  string  $type  Attendance type
     * @param  array  $data  Data containing required fields
     *
     * @throws Exception
     */
    public function assignAttendanceType(User $user, string $type, array $data): void
    {
        DB::beginTransaction();

        try {
            // Validate first
            $this->validateAttendanceType($type, array_merge($data, ['userId' => $user->id]));

            // Clear previous attendance type associations
            $this->clearPreviousAttendanceType($user);

            // Assign new attendance type
            switch ($type) {
                case 'geofence':
                    if ($this->addonService->isAddonEnabled('GeofenceSystem')) {
                        $user->attendance_type = self::TYPE_MAP['geofence'];
                        $user->geofence_group_id = $data['geofenceGroupId'];
                    }
                    break;

                case 'ipAddress':
                    if ($this->addonService->isAddonEnabled('IpAddressAttendance')) {
                        $user->attendance_type = self::TYPE_MAP['ipAddress'];
                        $user->ip_address_group_id = $data['ipGroupId'];
                    }
                    break;

                case 'staticqr':
                    if ($this->addonService->isAddonEnabled('QrAttendance')) {
                        $user->attendance_type = self::TYPE_MAP['staticqr'];
                        $user->qr_group_id = $data['qrGroupId'];
                    }
                    break;

                case 'site':
                    if ($this->addonService->isAddonEnabled('SiteAttendance')) {
                        $user->attendance_type = self::TYPE_MAP['site'];
                        $user->site_id = $data['siteId'];
                    }
                    break;

                case 'dynamicqr':
                    if ($this->addonService->isAddonEnabled('DynamicQrAttendance')) {
                        $user->attendance_type = self::TYPE_MAP['dynamicqr'];
                        $user->dynamic_qr_device_id = $data['dynamicQrId'];
                        $this->updateDeviceStatus($user, $type, $data);
                    }
                    break;

                case 'face':
                    if ($this->addonService->isAddonEnabled('FaceAttendance')) {
                        $user->attendance_type = self::TYPE_MAP['face'];
                    }
                    break;

                case 'open':
                default:
                    $user->attendance_type = self::TYPE_MAP['open'];
                    break;
            }

            $user->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get required fields for a specific attendance type.
     *
     * @param  string  $type  Attendance type
     * @return array List of required field names
     */
    public function getRequiredFieldsForType(string $type): array
    {
        return match ($type) {
            'geofence' => ['geofenceGroupId'],
            'ipAddress' => ['ipGroupId'],
            'staticqr' => ['qrGroupId'],
            'site' => ['siteId'],
            'dynamicqr' => ['dynamicQrId'],
            'face', 'open' => [],
            default => [],
        };
    }

    /**
     * Update device status when assigning dynamic QR attendance type.
     *
     * @param  User  $user  The user
     * @param  string  $type  Attendance type
     * @param  array  $data  Data containing device information
     */
    public function updateDeviceStatus(User $user, string $type, array $data): void
    {
        if ($type === 'dynamicqr' && isset($data['dynamicQrId']) && $this->addonService->isAddonEnabled('DynamicQrAttendance')) {
            $deviceClass = \Modules\DynamicQrAttendance\App\Models\DynamicQrDevice::class;
            $deviceClass::where('id', $data['dynamicQrId'])
                ->update([
                    'user_id' => $user->id,
                    'status' => 'in_use',
                ]);
        }
    }

    /**
     * Clear previous attendance type associations.
     *
     * @param  User  $user  The user
     */
    protected function clearPreviousAttendanceType(User $user): void
    {
        // Release dynamic QR device if previously assigned
        if ($user->attendance_type === 'dynamic_qr' && $user->dynamic_qr_device_id && $this->addonService->isAddonEnabled('DynamicQrAttendance')) {
            $deviceClass = \Modules\DynamicQrAttendance\App\Models\DynamicQrDevice::class;
            $deviceClass::where('id', $user->dynamic_qr_device_id)
                ->update([
                    'user_id' => null,
                    'status' => 'available',
                ]);
        }

        // Clear all attendance type related fields - only if the respective modules are enabled
        if ($this->addonService->isAddonEnabled('GeofenceSystem')) {
            $user->geofence_group_id = null;
        }
        if ($this->addonService->isAddonEnabled('IpAddressAttendance')) {
            $user->ip_address_group_id = null;
        }
        if ($this->addonService->isAddonEnabled('QrAttendance')) {
            $user->qr_group_id = null;
        }
        if ($this->addonService->isAddonEnabled('SiteAttendance')) {
            $user->site_id = null;
        }
        if ($this->addonService->isAddonEnabled('DynamicQrAttendance')) {
            $user->dynamic_qr_device_id = null;
        }
    }

    /**
     * Get the internal attendance type value from external type.
     *
     * @param  string  $type  External attendance type
     * @return string Internal attendance type value
     */
    public function getInternalType(string $type): string
    {
        return self::TYPE_MAP[$type] ?? self::TYPE_MAP['open'];
    }

    /**
     * Get all available attendance types.
     *
     * @return array List of attendance types with labels
     */
    public function getAvailableTypes(): array
    {
        return [
            'open' => __('Open (No restriction)'),
            'geofence' => __('Geofence'),
            'ipAddress' => __('IP Address'),
            'staticqr' => __('Static QR Code'),
            'dynamicqr' => __('Dynamic QR Code'),
            'site' => __('Site'),
            'face' => __('Face Recognition'),
        ];
    }
}

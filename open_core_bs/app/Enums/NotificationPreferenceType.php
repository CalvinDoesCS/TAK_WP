<?php

namespace App\Enums;

enum NotificationPreferenceType: string
{
    // Leave & Approval Notifications
    case LEAVE_REQUEST = 'leave_request';
    case LEAVE_APPROVED = 'leave_approved';
    case LEAVE_REJECTED = 'leave_rejected';

    // Expense Notifications
    case EXPENSE_REQUEST = 'expense_request';
    case EXPENSE_APPROVED = 'expense_approved';
    case EXPENSE_REJECTED = 'expense_rejected';

    // Loan Notifications
    case LOAN_REQUEST = 'loan_request';
    case LOAN_APPROVED = 'loan_approved';
    case LOAN_REJECTED = 'loan_rejected';

    // Document Notifications
    case DOCUMENT_REQUEST = 'document_request';

    // Chat Notifications
    case CHAT_MESSAGE = 'chat_message';
    case CHAT_REACTION = 'chat_reaction';

    // Call Notifications
    case INCOMING_CALL = 'incoming_call';
    case MISSED_CALL = 'missed_call';
    case CALL_ENDED = 'call_ended';

    // Alert Notifications
    case GPS_ALERT = 'gps_alert';
    case ATTENDANCE_ALERT = 'attendance_alert';
    case LOW_BATTERY_ALERT = 'low_battery_alert';

    // Payment Collection Notifications
    case PAYMENT_COLLECTION_CREATED = 'payment_collection_created';
    case PAYMENT_VERIFIED = 'payment_verified';
    case PAYMENT_APPROVED = 'payment_approved';
    case PAYMENT_REJECTED = 'payment_rejected';

    // Sales Target Notifications
    case SALES_TARGET_ASSIGNED = 'sales_target_assigned';
    case SALES_TARGET_MILESTONE = 'sales_target_milestone';
    case SALES_TARGET_EXPIRING = 'sales_target_expiring';
    case SALES_TARGET_EXPIRED = 'sales_target_expired';

    // Employee Lifecycle Notifications
    case EMPLOYEE_ONBOARDING = 'employee_onboarding';
    case EMPLOYEE_TERMINATED = 'employee_terminated';
    case PROBATION_CONFIRMED = 'probation_confirmed';
    case PROBATION_FAILED = 'probation_failed';

    /**
     * Get all preference types.
     */
    public static function all(): array
    {
        return [
            // Leave
            self::LEAVE_REQUEST,
            self::LEAVE_APPROVED,
            self::LEAVE_REJECTED,

            // Expense
            self::EXPENSE_REQUEST,
            self::EXPENSE_APPROVED,
            self::EXPENSE_REJECTED,

            // Loan
            self::LOAN_REQUEST,
            self::LOAN_APPROVED,
            self::LOAN_REJECTED,

            // Document
            self::DOCUMENT_REQUEST,

            // Chat
            self::CHAT_MESSAGE,
            self::CHAT_REACTION,

            // Call
            self::INCOMING_CALL,
            self::MISSED_CALL,
            self::CALL_ENDED,

            // Alerts
            self::GPS_ALERT,
            self::ATTENDANCE_ALERT,
            self::LOW_BATTERY_ALERT,

            // Payment Collection
            self::PAYMENT_COLLECTION_CREATED,
            self::PAYMENT_VERIFIED,
            self::PAYMENT_APPROVED,
            self::PAYMENT_REJECTED,

            // Sales Target
            self::SALES_TARGET_ASSIGNED,
            self::SALES_TARGET_MILESTONE,
            self::SALES_TARGET_EXPIRING,
            self::SALES_TARGET_EXPIRED,

            // Employee Lifecycle
            self::EMPLOYEE_ONBOARDING,
            self::EMPLOYEE_TERMINATED,
            self::PROBATION_CONFIRMED,
            self::PROBATION_FAILED,
        ];
    }
}

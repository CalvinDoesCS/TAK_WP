<?php

if (class_exists('ModuleConstants')) {
    return;
}

class ModuleConstants
{
    const BREAK = 'BreakSystem';

    const DOCUMENT = 'DocumentManagement';

    const DATA_IMPORT_EXPORT = 'DataImportExport';

    const DYNAMIC_FORMS = 'FormBuilder';

    const GEOFENCE = 'GeofenceSystem';

    const IP_ADDRESS_ATTENDANCE = 'IpAddressAttendance';

    const LOAN_MANAGEMENT = 'LoanManagement';

    const MANAGER_APP = 'ManagerApp';

    const NOTICE_BOARD = 'NoticeBoard';

    const OFFLINE_TRACKING = 'OfflineTracking';

    const PAYMENT_COLLECTION = 'PaymentCollection';

    const PRODUCT_ORDER = 'ProductOrder';

    const QR_ATTENDANCE = 'QRAttendance';

    const SITE_ATTENDANCE = 'SiteAttendance';

    const FACE_ATTENDANCE = 'FaceAttendance';

    const AI_CHATBOT = 'AiChat';

    const NOTES = 'Notes';

    const ASSETS = 'Assets';

    const DYNAMIC_QR_ATTENDANCE = 'DynamicQrAttendance';

    const FIELD_TASK = 'FieldTask';

    const UID_LOGIN = 'UidLogin';

    const DIGITAL_ID_CARD = 'DigitalIdCard';

    const PAYROLL = 'Payroll';

    const SALES_TARGET = 'SalesTarget';

    const APPROVALS = 'Approvals';

    const DISCIPLINARY_ACTIONS = 'DisciplinaryActions';

    const HR_POLICIES = 'HRPolicies';

    // Build in Modules
    const LEAVE_MANAGEMENT = 'LeaveManagement';

    const EXPENSE_MANAGEMENT = 'ExpenseManagement';

    const CLIENT_VISIT = 'ClientVisit';

    const CHAT_SYSTEM = 'ChatSystem';

    const RECRUITMENT = 'Recruitment';

    const CALENDAR = 'Calendar';

    const ACCOUNTING = 'AccountingCore';

    const SOS = 'SoS';

    const GOOGLE_RECAPTCHA = 'GoogleReCAPTCHA';

    const SYSTEM_BACKUP = 'SystemBackup';

    const LMS = 'LMS';

    const FACE_ATTENDANCE_DEVICE = 'FaceAttendanceDevice';

    const FIELD_MANAGER = 'FieldManager';

    const AGORA_CALL = 'AgoraCall';

    const LOCATION_MANAGEMENT = 'LocationManagement';

    const OC_CONNECT = 'OCConnect';

    const STANDARD_MODULES = [
        self::LEAVE_MANAGEMENT,
        self::EXPENSE_MANAGEMENT,
        self::CLIENT_VISIT,
        self::CHAT_SYSTEM,
        self::SOS,
    ];

    const ATTENDANCE_TYPES = [
        self::IP_ADDRESS_ATTENDANCE,
        self::QR_ATTENDANCE,
        self::DYNAMIC_QR_ATTENDANCE,
        self::GEOFENCE,
        self::FACE_ATTENDANCE,
        self::SITE_ATTENDANCE,
    ];

    const ALL_MODULES_EXCEPT_ATTENDANCE = [
        self::BREAK,
        self::DOCUMENT,
        self::DATA_IMPORT_EXPORT,
        self::DYNAMIC_FORMS,
        self::LOAN_MANAGEMENT,
        self::MANAGER_APP,
        self::NOTICE_BOARD,
        self::OFFLINE_TRACKING,
        self::PAYMENT_COLLECTION,
        self::PRODUCT_ORDER,
        self::FIELD_TASK,
        self::UID_LOGIN,
        self::DIGITAL_ID_CARD,
        self::APPROVALS,
        self::DISCIPLINARY_ACTIONS,
        self::HR_POLICIES,
        self::AI_CHATBOT,
        self::NOTES,
        self::ASSETS,
        self::PAYROLL,
        self::RECRUITMENT,
        self::CALENDAR,
        self::ACCOUNTING,

    ];

    const All_MODULES = [
        self::BREAK,
        self::DOCUMENT,
        self::DATA_IMPORT_EXPORT,
        self::DYNAMIC_FORMS,
        self::GEOFENCE,
        self::IP_ADDRESS_ATTENDANCE,
        self::LOAN_MANAGEMENT,
        self::MANAGER_APP,
        self::NOTICE_BOARD,
        self::OFFLINE_TRACKING,
        self::PAYMENT_COLLECTION,
        self::PRODUCT_ORDER,
        self::QR_ATTENDANCE,
        self::SITE_ATTENDANCE,
        self::FIELD_TASK,
        self::UID_LOGIN,
        self::DIGITAL_ID_CARD,
        self::LEAVE_MANAGEMENT,
        self::EXPENSE_MANAGEMENT,
        self::CLIENT_VISIT,
        self::CHAT_SYSTEM,
        self::FACE_ATTENDANCE,
        self::APPROVALS,
        self::DISCIPLINARY_ACTIONS,
        self::HR_POLICIES,
        self::AI_CHATBOT,
        self::NOTES,
        self::ASSETS,
        self::PAYROLL,
        self::RECRUITMENT,
        self::CALENDAR,
        self::ACCOUNTING,
    ];
}

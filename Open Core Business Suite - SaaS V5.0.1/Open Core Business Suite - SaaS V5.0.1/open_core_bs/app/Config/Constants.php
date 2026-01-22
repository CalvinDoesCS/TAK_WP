<?php

namespace App\Config;

class Constants
{
    public const DateTimeFormat = 'd-m-Y h:i A';

    // Constants::DateTimeFormat
    public const DateFormat = 'd-m-Y';

    public const TimeFormat = 'h:i A';

    public const DateTimeHumanFormat = 'F j, Y, g:i a';

    public const DateTimeHumanFormatShort = 'M d, Y H:i';

    public const BaseFolderVisitImages = 'uploads/visitimages/';

    public const BaseFolderTaskUpdateFiles = 'uploads/taskupdatefiles/';

    public const BaseFolderLeaveRequestDocument = 'uploads/leaverequestdocuments/';

    public const BaseFolderExpenseProofs = 'uploads/expenseProofs/';

    public const BaseFolderUserDocumentRequest = 'uploads/userDocumentRequests/';

    public const BaseFolderEmployeeProfile = 'uploads/employeeProfilePictures';

    public const BaseFolderEmployeeProfileWithSlash = 'uploads/employeeProfilePictures/';

    public const BaseFolderEmployeeDocument = 'uploads/employeeDocuments/';

    public const BaseFolderChatFiles = 'uploads/chatFiles/';

    public const BaseFolderClientImages = 'uploads/clientImages/';

    /**
     * Default marketplace URL for addons
     * Used as fallback when module.json doesn't have a purchaseUrl
     */
    public const ALL_ADDONS_PURCHASE_LINK = 'https://czappstudio.com/open-core-bs-addons/';

    /**
     * @deprecated This array is deprecated as of 2025-11-06
     * Addon information is now read from each module's module.json file
     * This provides better maintainability and allows modules to manage their own metadata
     *
     * The new system uses:
     * - AddonController with DataTable for management UI
     * - ModuleDependencyService for dependency resolution
     * - module.json files for all addon metadata (name, description, purchaseUrl, etc.)
     *
     * For backward compatibility, this constant is kept but should not be used for new code.
     * To get addon information, use the Module facade or AddonService instead.
     */
    public const All_ADDONS_ARRAY = [];

    public const BuiltInRoles = ['super_admin', 'admin', 'hr', 'field_employee', 'office_employee', 'manager'];
}

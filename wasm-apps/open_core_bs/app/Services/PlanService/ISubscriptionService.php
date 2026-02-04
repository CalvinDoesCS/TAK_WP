<?php

namespace App\Services\PlanService;

use App\Models\Settings;
use DateTime;
use Modules\MultiTenancyCore\App\Models\Payment as Order;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\Subscription;

interface ISubscriptionService
{
    public function generatePlanExpiryDate(Plan $plan): DateTime;

    public function getPlanTotalAmount(Plan $plan, int $usersCount): float;

    public function getAddUserTotalAmount(int $usersCount): float;

    public function getCurrentPlan(): Plan;

    public function activatePlan(Order $order): void;

    public function renewPlan(Order $order): void;

    public function upgradePlan(Order $order): void;

    public function addUsersToSubscription(Order $order): void;

    public function getRenewalAmount(): float;

    public function getSubscription(): Subscription;

    public function getDifferencePriceForUpgrade(int $newPlanId): float;

    public function processTenantSettingsForAccessRoutesByPlan(): Settings;

    public function refreshPlanAccessForTenants($planId);
}

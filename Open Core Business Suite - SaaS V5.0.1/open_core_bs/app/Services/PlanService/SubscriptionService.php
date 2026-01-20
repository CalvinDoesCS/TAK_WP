<?php

namespace App\Services\PlanService;

use App\Enums\OrderStatus;
use App\Enums\PlanDurationType;
use App\Enums\SubscriptionStatus;
use App\Models\Settings;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Log;
use Modules\MultiTenancyCore\App\Models\Payment as Order;
use Modules\MultiTenancyCore\App\Models\Plan;
use Modules\MultiTenancyCore\App\Models\Subscription;
use Modules\MultiTenancyCore\App\Models\Tenant;

class SubscriptionService implements ISubscriptionService
{
    public function getPlanTotalAmount(Plan $plan, int $usersCount): float
    {
        return $plan->base_price + ($plan->per_user_price * $usersCount);
    }

    public function activatePlan(Order $order): void
    {
        if ($order->status != OrderStatus::COMPLETED) {
            return;
        }

        $plan = Plan::find($order->plan_id);
        $user = auth()->user();

        $user->plan_id = $plan->id;
        $user->plan_expired_date = $this->generatePlanExpiryDate($plan);
        $user->save();

        $subscription = new Subscription;
        $subscription->user_id = $user->id;
        $subscription->plan_id = $plan->id;
        $subscription->users_count = $plan->included_users + $order->additional_users;
        $subscription->additional_users = $order->additional_users;
        $subscription->per_user_price = $plan->per_user_price;
        $subscription->total_price = $order->total_amount;
        $subscription->start_date = now();
        $subscription->end_date = $user->plan_expired_date;
        $subscription->status = SubscriptionStatus::ACTIVE;
        $subscription->tenant_id = $user->tenant_id;

        $subscription->save();
    }

    public function generatePlanExpiryDate(Plan $plan): DateTime
    {
        return match ($plan->duration_type) {
            PlanDurationType::MONTHS => now()->addMonths($plan->duration),
            PlanDurationType::YEARS => now()->addYears($plan->duration),
            default => now()->addDays($plan->duration),
        };
    }

    public function renewPlan(Order $order): void
    {
        if ($order->status != OrderStatus::COMPLETED) {
            return;
        }

        $plan = Plan::find($order->plan_id);

        $subscription = Subscription::where('user_id', $order->user_id)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->first();

        if ($subscription->end_date < now()) {
            $newExpiryDate = $this->generatePlanExpiryDate($plan);
        } else {
            $newExpiryDate = $this->generatePlanExpiryDateByDate($plan, Carbon::parse($subscription->end_date));
        }

        $user = $order->user;
        $user->plan_id = $plan->id;
        $user->plan_expired_date = $newExpiryDate;
        $user->save();

        $subscription->end_date = $user->plan_expired_date;
        $subscription->status = SubscriptionStatus::ACTIVE;

        $subscription->save();
    }

    public function generatePlanExpiryDateByDate(Plan $plan, DateTime $startDate): DateTime
    {
        return match ($plan->duration_type) {
            PlanDurationType::MONTHS => $startDate->addMonths($plan->duration),
            PlanDurationType::YEARS => $startDate->addYears($plan->duration),
            default => $startDate->addDays($plan->duration),
        };
    }

    public function addUsersToSubscription(Order $order): void
    {
        if ($order->status != OrderStatus::COMPLETED) {
            return;
        }

        $subscription = Subscription::where('user_id', $order->user_id)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->first();

        $subscription->users_count += $order->additional_users;
        $subscription->additional_users += $order->additional_users;
        $subscription->total_price += $order->total_amount;
        $subscription->save();
    }

    public function getAddUserTotalAmount(int $usersCount): float
    {
        $subscription = Subscription::where('user_id', auth()->id())
            ->where('status', SubscriptionStatus::ACTIVE)
            ->first();

        $plan = $subscription->plan;

        $perUserPricePerDay = $this->getPerUserPricePerDayByPlanType($plan);

        $differenceDays = now()->diffInDays($subscription->end_date);

        return round(($perUserPricePerDay * $differenceDays) * $usersCount, 2);

    }

    private function getPerUserPricePerDayByPlanType(Plan $plan): float
    {
        return match ($plan->duration_type) {
            PlanDurationType::MONTHS => $plan->per_user_price / 30,
            PlanDurationType::YEARS => $plan->per_user_price / 365,
            default => $plan->per_user_price,
        };
    }

    public function upgradePlan(Order $order): void
    {
        if ($order->status != OrderStatus::COMPLETED) {
            return;
        }

        $plan = Plan::find($order->plan_id);
        $user = $order->user;

        $user->plan_id = $plan->id;
        $user->plan_expired_date = $this->generatePlanExpiryDate($plan);
        $user->save();

        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', SubscriptionStatus::ACTIVE)
            ->first();

        $subscription->plan_id = $plan->id;
        $subscription->total_price = $order->total_amount;
        $subscription->end_date = $user->plan_expired_date;

        $subscription->save();
    }

    public function getRenewalAmount(): float
    {
        $plan = $this->getCurrentPlan();

        return $plan->base_price + ($plan->per_user_price * $this->getSubscription()->users_count);
    }

    public function getCurrentPlan(): Plan
    {
        $user = auth()->user();

        return Plan::find($user->plan_id);
    }

    public function getSubscription(): Subscription
    {
        return Subscription::where('user_id', auth()->id())
            ->where('status', SubscriptionStatus::ACTIVE)
            ->with('plan')
            ->first();
    }

    public function getDifferencePriceForUpgrade(int $newPlanId): float
    {
        $newPlan = Plan::find($newPlanId);

        $subscription = $this->getSubscription();

        $pricePerDay = $this->getPerUserPricePerDayByPlanType($subscription->plan);

        $daysLeft = -(Carbon::parse($subscription->end_date)->diffInDays(now()));

        $balanceAmountInCurrentPlan = $pricePerDay * $daysLeft;

        $newPlanPrice = $newPlan->base_price + ($newPlan->per_user_price * $subscription->additional_users);

        return round($newPlanPrice - $balanceAmountInCurrentPlan, 2);
    }

    public function refreshPlanAccessForTenants($planId): void
    {
        $plan = Plan::find($planId);
        $tenantIds = User::where('plan_id', $planId)->pluck('tenant_id')->unique();

        $tenants = Tenant::whereIn('id', $tenantIds)->get();

        Log::info('Syncing Plan Access for Tenants: '.json_encode($tenants));

        foreach ($tenants as $tenant) {
            // Initialize tenant context if tenancy helper exists (multi-tenant mode)
            if (function_exists('tenancy')) {
                tenancy()->initialize($tenant);
            }

            // Process accessible routes based on plan
            $this->processTenantSettingsForAccessRoutesByPlan($tenant);

            Log::info('Plan Access Synced for Tenant: '.$tenant->name);
        }
    }

    public function processTenantSettingsForAccessRoutesByPlan(?Tenant $tenant = null): Settings
    {
        Log::info('Processing Tenant Settings for Access Routes by Plan');

        $settings = Settings::first();

        // Get accessible modules from tenant's plan (includes core modules)
        $accessModules = [];

        if ($tenant && $tenant->subscription && $tenant->subscription->plan) {
            $accessModules = $tenant->subscription->plan->getAllAccessibleModules();
        } else {
            // No subscription - allow all enabled modules
            $accessModules = getAllEnabledModules();
        }

        Log::info('Accessible Modules: '.json_encode($accessModules));

        // Read menu structure
        $tenantVerticalMenuJson = file_get_contents(base_path('resources/menu/verticalMenu.json'));
        $tenantVerticalMenuData = json_decode($tenantVerticalMenuJson);
        $allModulesFromMenu = $tenantVerticalMenuData->menu;

        $availableRoutes = [];

        foreach ($allModulesFromMenu as $key => $module) {

            if (isset($module->menuHeader)) {
                continue;
            }

            if (isset($module->addon) && in_array($module->addon, $accessModules)) {
                if (is_array($module->slug)) {
                    foreach ($module->slug as $slug) {
                        $availableRoutes[] = $slug;
                    }
                } else {
                    $availableRoutes[] = $module->slug;
                }
            } elseif (isset($module->submenu)) {
                foreach ($module->submenu as $subMenu) {
                    if (isset($subMenu->addon) && in_array($subMenu->addon, $accessModules)) {
                        if (is_array($subMenu->slug)) {
                            foreach ($subMenu->slug as $slug) {
                                $availableRoutes[] = $slug;
                            }
                        } else {
                            $availableRoutes[] = $subMenu->slug;
                        }
                    }
                }
            } else {
                // Routes without addon specification are always available
                if (is_array($module->slug)) {
                    foreach ($module->slug as $slug) {
                        $availableRoutes[] = $slug;
                    }
                } else {
                    $availableRoutes[] = $module->slug;
                }
            }
        }

        $settings->accessible_module_routes = $availableRoutes;

        Log::info('Generated Accessible Routes: '.json_encode($availableRoutes));

        $settings->save();

        return $settings;
    }
}

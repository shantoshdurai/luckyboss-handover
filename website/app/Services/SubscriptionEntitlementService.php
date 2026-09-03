<?php
namespace App\Services;
use App\Models\Company;
class SubscriptionEntitlementService { public function allows(Company $company, string $feature): bool { $subscription = $company->subscriptions()->where('status','active')->whereDate('expires_at','>=',today())->latest('expires_at')->first(); return (bool) data_get($subscription?->entitlements, $feature, false); } }
<?php

namespace App\Http\Controllers\Web\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreSubscriptionPlanRequest;
use App\Http\Requests\Owner\UpdateSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    public function index(): View
    {
        return view('owner.subscription-plans.index', [
            'plans' => SubscriptionPlan::query()->latest('id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('owner.subscription-plans.create', [
            'plan' => new SubscriptionPlan(),
        ]);
    }

    public function store(StoreSubscriptionPlanRequest $request): RedirectResponse
    {
        $plan = SubscriptionPlan::query()->create($request->safe()->except('feature_limits_json'));

        return redirect()
            ->route('owner.subscription-plans.show', $plan)
            ->with('status', 'Subscription plan created successfully.');
    }

    public function show(SubscriptionPlan $subscriptionPlan): View
    {
        return view('owner.subscription-plans.show', [
            'plan' => $subscriptionPlan,
        ]);
    }

    public function edit(SubscriptionPlan $subscriptionPlan): View
    {
        return view('owner.subscription-plans.edit', [
            'plan' => $subscriptionPlan,
        ]);
    }

    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $subscriptionPlan->fill($request->safe()->except('feature_limits_json'))->save();

        return redirect()
            ->route('owner.subscription-plans.show', $subscriptionPlan)
            ->with('status', 'Subscription plan updated successfully.');
    }

    public function destroy(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $subscriptionPlan->delete();

        return redirect()
            ->route('owner.subscription-plans.index')
            ->with('status', 'Subscription plan deleted successfully.');
    }
}

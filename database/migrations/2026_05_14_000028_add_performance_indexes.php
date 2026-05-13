<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->index(['user_id', 'role_id'], 'role_user_user_id_role_id_index');
        });

        Schema::table('permission_role', function (Blueprint $table) {
            $table->index(['role_id', 'permission_id'], 'permission_role_role_id_permission_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'users_tenant_id_status_index');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->index(['tenant_id', 'apartment_id', 'id'], 'wallet_transactions_tenant_apartment_id_index');
        });

        Schema::table('debit_transactions', function (Blueprint $table) {
            $table->index(['tenant_id', 'apartment_id', 'id'], 'debit_transactions_tenant_apartment_id_index');
        });

        Schema::table('expense_splits', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'apartment_id', 'is_confirmed', 'is_paid', 'is_reversed', 'id'],
                'expense_splits_apartment_state_index',
            );
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'status', 'financial_period_id'],
                'journal_entries_tenant_status_period_index',
            );
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index(
                ['notifiable_type', 'type', 'notifiable_id'],
                'notifications_notifiable_type_type_id_index',
            );
        });

        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->index(['status', 'renews_at'], 'tenant_subscriptions_status_renews_at_index');
            $table->index(['status', 'ends_at'], 'tenant_subscriptions_status_ends_at_index');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->index(
                ['status', 'subscription_status', 'trial_ends_at', 'reminder_sent_at'],
                'tenants_trial_reminder_lookup_index',
            );
            $table->index(
                ['status', 'subscription_status', 'subscription_ends_at', 'reminder_sent_at'],
                'tenants_subscription_reminder_lookup_index',
            );
            $table->index(
                ['status', 'subscription_status', 'grace_ends_at'],
                'tenants_grace_lookup_index',
            );
            $table->index(
                ['status', 'subscription_status', 'suspended_at'],
                'tenants_suspension_lookup_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex('tenants_suspension_lookup_index');
            $table->dropIndex('tenants_grace_lookup_index');
            $table->dropIndex('tenants_subscription_reminder_lookup_index');
            $table->dropIndex('tenants_trial_reminder_lookup_index');
        });

        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->dropIndex('tenant_subscriptions_status_ends_at_index');
            $table->dropIndex('tenant_subscriptions_status_renews_at_index');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_type_type_id_index');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex('journal_entries_tenant_status_period_index');
        });

        Schema::table('expense_splits', function (Blueprint $table) {
            $table->dropIndex('expense_splits_apartment_state_index');
        });

        Schema::table('debit_transactions', function (Blueprint $table) {
            $table->dropIndex('debit_transactions_tenant_apartment_id_index');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex('wallet_transactions_tenant_apartment_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_tenant_id_status_index');
        });

        Schema::table('permission_role', function (Blueprint $table) {
            $table->dropIndex('permission_role_role_id_permission_id_index');
        });

        Schema::table('role_user', function (Blueprint $table) {
            $table->dropIndex('role_user_user_id_role_id_index');
        });
    }
};

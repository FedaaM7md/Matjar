<?php

namespace App\Support;

/**
 * Central catalogue of permission slugs used by the in-house RBAC layer.
 * Permissions are seeded into the database and attached to roles.
 */
final class Permissions
{
    public const DASHBOARD_VIEW = 'dashboard.view';

    public const PRODUCTS_VIEW = 'products.view';

    public const PRODUCTS_MANAGE = 'products.manage';

    public const INVENTORY_VIEW = 'inventory.view';

    public const INVENTORY_MANAGE = 'inventory.manage';

    public const ORDERS_VIEW = 'orders.view';

    public const ORDERS_MANAGE = 'orders.manage';

    public const CUSTOMERS_VIEW = 'customers.view';

    public const COUPONS_VIEW = 'coupons.view';

    public const COUPONS_MANAGE = 'coupons.manage';

    public const SHIPPING_VIEW = 'shipping.view';

    public const SHIPPING_MANAGE = 'shipping.manage';

    public const SETTINGS_VIEW = 'settings.view';

    public const SETTINGS_MANAGE = 'settings.manage';

    public const REPORTS_VIEW = 'reports.view';

    /** @return array<string, array{group: string, ar: string, en: string}> */
    public static function all(): array
    {
        return [
            self::DASHBOARD_VIEW => ['group' => 'dashboard', 'ar' => 'عرض لوحة التحكم', 'en' => 'View dashboard'],
            self::PRODUCTS_VIEW => ['group' => 'catalog', 'ar' => 'عرض المنتجات', 'en' => 'View products'],
            self::PRODUCTS_MANAGE => ['group' => 'catalog', 'ar' => 'إدارة المنتجات والمتغيرات', 'en' => 'Manage products and variants'],
            self::INVENTORY_VIEW => ['group' => 'inventory', 'ar' => 'عرض المخزون', 'en' => 'View inventory'],
            self::INVENTORY_MANAGE => ['group' => 'inventory', 'ar' => 'تعديل المخزون', 'en' => 'Manage inventory'],
            self::ORDERS_VIEW => ['group' => 'orders', 'ar' => 'عرض الطلبات', 'en' => 'View orders'],
            self::ORDERS_MANAGE => ['group' => 'orders', 'ar' => 'إدارة الطلبات', 'en' => 'Manage orders'],
            self::CUSTOMERS_VIEW => ['group' => 'customers', 'ar' => 'عرض العملاء', 'en' => 'View customers'],
            self::COUPONS_VIEW => ['group' => 'marketing', 'ar' => 'عرض الكوبونات', 'en' => 'View coupons'],
            self::COUPONS_MANAGE => ['group' => 'marketing', 'ar' => 'إدارة الكوبونات', 'en' => 'Manage coupons'],
            self::SHIPPING_VIEW => ['group' => 'shipping', 'ar' => 'عرض الشحن', 'en' => 'View shipping'],
            self::SHIPPING_MANAGE => ['group' => 'shipping', 'ar' => 'إدارة الشحن', 'en' => 'Manage shipping'],
            self::SETTINGS_VIEW => ['group' => 'settings', 'ar' => 'عرض الإعدادات', 'en' => 'View settings'],
            self::SETTINGS_MANAGE => ['group' => 'settings', 'ar' => 'تعديل الإعدادات', 'en' => 'Manage settings'],
            self::REPORTS_VIEW => ['group' => 'reports', 'ar' => 'عرض التقارير', 'en' => 'View reports'],
        ];
    }

    /** @return list<string> */
    public static function staffDefaults(): array
    {
        return [
            self::DASHBOARD_VIEW,
            self::PRODUCTS_VIEW,
            self::PRODUCTS_MANAGE,
            self::INVENTORY_VIEW,
            self::INVENTORY_MANAGE,
            self::ORDERS_VIEW,
            self::ORDERS_MANAGE,
            self::CUSTOMERS_VIEW,
            self::REPORTS_VIEW,
        ];
    }
}

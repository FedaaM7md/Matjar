// Matjar — © 2026 Fedaa Mohamed. All rights reserved. Proprietary.
import '@fontsource-variable/rubik';
import '@fontsource-variable/noto-sans-arabic';

// CSP-safe Alpine build: no `new Function()`/eval. Components read their
// configuration from data-* attributes so no expression needs evaluation.
import Alpine from '@alpinejs/csp';

window.Alpine = Alpine;

Alpine.data('qtyStepper', () => ({
    qty: 1,
    max: 20,
    init() {
        this.qty = Number(this.$el.dataset.initial) || 1;
        this.max = Number(this.$el.dataset.max) || 20;
    },
    increment() {
        if (this.qty < this.max) this.qty++;
    },
    decrement() {
        if (this.qty > 1) this.qty--;
    },
}));

Alpine.data('dismissible', () => ({
    visible: true,
    init() {
        const delay = Number(this.$el.dataset.delay) || 6000;
        setTimeout(() => { this.visible = false; }, delay);
    },
    dismiss() {
        this.visible = false;
    },
}));

// Replaces inline `onsubmit="return confirm(...)"` / `onclick` handlers so the
// site works under a strict Content-Security-Policy (no `unsafe-inline`).
Alpine.data('confirmForm', () => ({
    message: '',
    init() {
        this.message = this.$el.dataset.message || '';
    },
    onConfirm(event) {
        if (! window.confirm(this.message)) {
            event.preventDefault();
        }
    },
}));

Alpine.data('printPage', () => ({
    print() {
        window.print();
    },
}));

// Admin shell: sidebar open/desktop state without inline x-init scripts.
Alpine.data('adminShell', () => ({
    sidebarOpen: false,
    desktop: false,
    init() {
        const mq = window.matchMedia('(min-width: 1024px)');
        this.desktop = mq.matches;
        const onChange = (event) => { this.desktop = event.matches; };
        if (mq.addEventListener) {
            mq.addEventListener('change', onChange);
        } else if (mq.addListener) {
            mq.addListener(onChange);
        }
    },
}));

Alpine.data('productBuy', () => ({
    variants: [],
    selectedId: null,
    qty: 1,
    init() {
        try {
            this.variants = JSON.parse(this.$el.dataset.variants || '[]');
        } catch (e) {
            this.variants = [];
        }
        const first = this.variants.find((variant) => variant.available > 0) ?? this.variants[0];
        this.selectedId = first ? first.id : null;
    },
    get selected() {
        return this.variants.find((variant) => variant.id === this.selectedId) ?? null;
    },
    get max() {
        const available = this.selected?.available ?? 1;
        return Math.max(1, Math.min(available, 20));
    },
    select(id) {
        this.selectedId = id;
        this.qty = 1;
    },
    increment() {
        if (this.qty < this.max) this.qty++;
    },
    decrement() {
        if (this.qty > 1) this.qty--;
    },
}));

Alpine.data('checkoutForm', () => ({
    zones: [],
    subtotal: 0,
    discount: 0,
    currency: 'EGP',
    governorateId: null,
    paymentMethod: null,
    init() {
        let config = {};
        try {
            config = JSON.parse(this.$el.dataset.config || '{}');
        } catch (e) {
            config = {};
        }
        this.zones = config.zones ?? [];
        this.subtotal = Number(config.subtotal) || 0;
        this.discount = Number(config.discount) || 0;
        this.currency = config.currency ?? 'EGP';
        this.governorateId = Number(config.governorateId) || null;
        this.paymentMethod = config.paymentMethod ?? null;
    },
    get zone() {
        return this.zones.find((zone) => zone.id === Number(this.governorateId)) ?? null;
    },
    get afterDiscount() {
        return Math.max(0, this.subtotal - this.discount);
    },
    get freeShipping() {
        return !!this.zone && this.zone.free_threshold_cents !== null && this.afterDiscount >= this.zone.free_threshold_cents;
    },
    get shippingCents() {
        if (!this.zone) return null;
        return this.freeShipping ? 0 : Number(this.zone.rate_cents);
    },
    get remainingForFree() {
        if (!this.zone || this.freeShipping || this.zone.free_threshold_cents === null) return 0;
        return Math.max(0, this.zone.free_threshold_cents - this.afterDiscount);
    },
    get total() {
        return this.afterDiscount + (this.shippingCents ?? 0);
    },
    format(cents) {
        return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format((Number(cents) || 0) / 100) + ' ' + this.currency;
    },
}));

Alpine.start();

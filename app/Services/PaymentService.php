<?php

namespace App\Services;

use App\Models\Payment;

class PaymentService
{
    public const OUTCOME_SUCCESS = 'success';

    public const OUTCOME_FAIL = 'fail';

    public const OUTCOME_CANCEL = 'cancel';

    /** @return list<string> */
    public function outcomes(): array
    {
        return [self::OUTCOME_SUCCESS, self::OUTCOME_FAIL, self::OUTCOME_CANCEL];
    }

    public function signature(Payment $payment, string $outcome, int $expiresAt): string
    {
        return hash_hmac(
            'sha256',
            implode('|', [$payment->reference, $outcome, $payment->amount_cents, $expiresAt]),
            (string) config('app.key'),
        );
    }

    /** @return array{expires: int, signature: string, amount_cents: int} */
    public function callbackFields(Payment $payment, string $outcome): array
    {
        $expiresAt = now()->addMinutes((int) config('matjar.payment.callback_ttl_minutes', 120))->timestamp;

        return [
            'expires' => $expiresAt,
            'signature' => $this->signature($payment, $outcome, $expiresAt),
            'amount_cents' => $payment->amount_cents,
        ];
    }

    public function verify(Payment $payment, string $outcome, int $expiresAt, int $amountCents, string $signature): bool
    {
        if (! in_array($outcome, $this->outcomes(), true)) {
            return false;
        }

        if ($expiresAt < now()->timestamp) {
            return false;
        }

        if ($amountCents !== $payment->amount_cents) {
            return false;
        }

        $expected = $this->signature($payment, $outcome, $expiresAt);

        return hash_equals($expected, $signature);
    }
}

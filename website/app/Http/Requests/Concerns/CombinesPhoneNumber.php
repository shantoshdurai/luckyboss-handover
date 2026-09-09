<?php

namespace App\Http\Requests\Concerns;

/**
 * Stitches the sign-up form's dialling-code select and national-number input
 * back into the single `phone` field the rules, the controllers and the
 * `users.phone` column already expect.
 *
 * Splitting the control was the point: one free-text box with a placeholder
 * produced "91+6383515761". Splitting the *storage* was never wanted — `phone`
 * is unique across the table and is what an employer rings, so it stays one
 * canonical E.164-ish string, "+91" followed by the local digits.
 *
 * Runs before validation, so `required` and `unique` see the merged value and
 * a form posted by a client that never sent the new fields (the mobile apps,
 * an old cached page) is left exactly as it was.
 */
trait CombinesPhoneNumber
{
    protected function combinePhone(): void
    {
        if (! $this->has('phone_national')) {
            return;
        }

        $national = preg_replace('/\D/', '', (string) $this->input('phone_national'));

        if ($national === '') {
            // Leave `phone` absent rather than merging an empty string, so the
            // failure is "please enter a phone number" and not a unique-key
            // collision with the last person who submitted a blank one.
            return;
        }

        $dial = preg_replace('/\D/', '', (string) $this->input('dial_code', '91'));

        // A trunk prefix is how the number is dialled inside the country, not
        // part of it — "091234" and "91234" are the same subscriber, and only
        // one of them should ever reach the unique index.
        $national = ltrim($national, '0') ?: $national;

        $this->merge(['phone' => '+' . $dial . $national]);
    }
}

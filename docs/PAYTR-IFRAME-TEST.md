# PayTR iFrame Sandbox test guide

This integration uses PayTR's hosted iFrame API. The application sends a server-to-server token request; card data is entered only in the PayTR-hosted frame.

## Configure

1. Open Admin → Settings.
2. Enter the PayTR merchant ID, merchant key and merchant salt. Secrets are encrypted before storage.
3. Select Sandbox while testing, save settings, then enable PayTR.
4. In the PayTR merchant panel, set the notification/callback URL to:
   `https://YOUR-DOMAIN/payments/paytr/callback`
   Use the actual installed domain and subdirectory prefix if the app is installed in a subdirectory.
5. Ensure the hosting account permits outbound HTTPS requests to `www.paytr.com` and inbound HTTPS POST requests to the callback route.

## Test

1. Create a pending villa booking using a customer account.
2. Open its checkout page and choose PayTR.
3. Complete a test transaction using the test card details provided in the PayTR merchant panel / official test environment.
4. Wait for PayTR's server callback. The callback is independent of the customer's browser return.
5. Confirm that a valid successful callback marks exactly one payment as paid and confirms the still-pending booking. A failed callback leaves the booking pending.
6. Repeat a callback delivery; it must be idempotent and must not create a second payment.
7. Alter the callback hash, order reference or amount in a local test and verify that it cannot confirm a booking.

## Production

Production is not enabled by default. Before switching the PayTR environment to Production, verify the merchant account's live credentials and callback URL with PayTR. The admin must explicitly enable PayTR. Automated tests do not make a live request or charge a card.

The integration disables installments (`no_installment=1`) so the callback's collected amount can be compared exactly with the booking total. Refunds, automated payouts and partial captures are not part of this phase.

Official references:
- [PayTR iFrame API – Step 1](https://dev.paytr.com/iframe-api/iframe-api-1-adim)
- [PayTR iFrame API – Step 2](https://dev.paytr.com/iframe-api/iframe-api-2-adim)

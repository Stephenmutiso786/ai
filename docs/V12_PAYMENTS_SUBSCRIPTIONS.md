# V12 — Real Payments & Weekly Subscription Lifecycle

## Implemented providers
- Safaricom Daraja STK Push (M-Pesa)
- Stripe Checkout for international cards

## Admin configuration
Configure all provider credentials in **Super Admin → Settings → API Keys & Integrations**. Secrets are encrypted in the database and are never committed into source control.

Required M-Pesa settings: consumer key, consumer secret, shortcode, passkey, API base URL, and USD→KES billing rate. The public callback URL is derived from `APP_URL`, so production `APP_URL` must be HTTPS and publicly reachable.

Required Stripe settings: secret key and webhook signing secret. Configure Stripe to POST `checkout.session.completed` to `/payments/stripe/webhook`.

## Lifecycle
1. Client selects a paid plan.
2. A pending payment transaction is created with a unique merchant reference.
3. Provider checkout/STK Push is initiated.
4. Provider confirmation activates exactly one weekly subscription period.
5. Payment activation is idempotent: repeated callbacks do not create repeated subscription periods.
6. Cancellation/expiry is enforced by the scheduled `subscriptions:expire` command.

## Production checklist
- Use real Safaricom production credentials only after Daraja approval.
- Configure HTTPS before enabling M-Pesa callbacks.
- Configure and test Stripe webhook signing.
- Set the correct billing conversion rate in Admin Settings.
- Test failed, cancelled and repeated callbacks.
- Do not enable unrestricted live trading merely because payment works.

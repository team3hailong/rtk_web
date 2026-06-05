---
name: auth-otp-feature-workflow
description: Workflow command scaffold for auth-otp-feature-workflow in rtk_web.
allowed_tools: ["Bash", "Read", "Write", "Grep", "Glob"]
---

# /auth-otp-feature-workflow

Use this workflow when working on **auth-otp-feature-workflow** in `rtk_web`.

## Goal

Implements or updates OTP authentication and password reset features.

## Common Files

- `db/migrations/*otp*.sql`
- `private/action/auth/*otp*.php`
- `private/utils/email_helper.php`
- `private/utils/otp_helper.php`
- `private/utils/sms_helper.php`
- `docs/doc_for_user/otp_authentication.md`

## Suggested Sequence

1. Understand the current state and failure mode before editing.
2. Make the smallest coherent change that satisfies the workflow goal.
3. Run the most relevant verification for touched files.
4. Summarize what changed and what still needs review.

## Typical Commit Signals

- Create or update migration in db/migrations/ (e.g., *_add_otp_verification.sql)
- Update or add PHP logic in private/action/auth/ (process_*, resend-*, verify-*)
- Update helper utilities in private/utils/ (email_helper.php, otp_helper.php, sms_helper.php)
- Update or add documentation in docs/doc_for_user/otp_authentication.md
- Update frontend pages in public/pages/auth/ (forgot_password.php, new_password.php, reset-password-otp.php, verify-email-otp.php)

## Notes

- Treat this as a scaffold, not a hard-coded script.
- Update the command if the workflow evolves materially.
---
trigger: always_on
---

# AI Agent Instructions (Laravel 12 + Vue 3 + Inertia)

You are working in a monorepo for a small business appointment + SMS reminder system.
Primary goals: correctness, clarity, minimal code, consistent conventions, and small reviewable commits.

## Tech & Architecture
- Backend: Laravel 12 (PHP 8.3+), MySQL/Postgres.
- Frontend: Vue 3 + Inertia.js (server-driven pages with SPA feel).
- Realtime is NOT required for MVP. Prefer queues + scheduler.
- Use Domain-ish structure but keep it pragmatic:
  - app/Models, app/Http, app/Actions, app/Services, app/Notifications (or app/Sms), app/Jobs.
- No “clever” meta-programming. Favor explicit code.

## Product Requirements (core)
Entities:
- Client: full_name, phone_e164, email (optional), public_uid (uuid/ulid), sms_opt_out (bool).
- Appointment: client_id, starts_at (timezone-aware), duration_minutes, note (optional), send_reminder (bool), reminder_sent_at (nullable).
- Business settings: default_sms_note (string), timezone, reminder_hours (default 24).

Features:
1) CRUD clients
2) Calendar-like schedule view for mother (create/edit/cancel appointments quickly)
3) SMS reminders 24h before appointment (only if send_reminder = true and client not opted out)
4) Public client page: /c/{public_uid} showing upcoming appointments (read-only)
5) Opt-out: “Disable SMS reminders” on the public page (sets sms_opt_out = true)

## Non-Functional Requirements
- Keep UI minimal and fast (mother is the primary user).
- All times must be stored in UTC; displayed in configured business timezone.
- Input validation is mandatory (phone normalization to E.164).
- Security: public page must not leak other clients; only show appointments for that uid.
- Do not send SMS in local/dev by default; use a “log” driver.

## Coding Standards
Laravel:
- Use FormRequest for validation.
- Use Policies/Gates only if needed; MVP can be single-user (auth still recommended).
- Use Jobs for sending SMS.
- Use queues (database/redis) + scheduler (cron) to dispatch reminder jobs.
- Prefer `php artisan make:*` scaffolding, but delete unused boilerplate.

Vue/Inertia:
- Keep pages under `resources/js/Pages`.
- Prefer small components; avoid global state.
- Server is source of truth; avoid duplicating business logic in JS.
- Use simple UI patterns: searchable client select, quick appointment form, weekly/day views.

Testing:
- Add/adjust tests when logic is non-trivial:
  - Feature tests for reminder eligibility
  - Unit tests for phone normalization + reminder time calculation
- Always run: `php artisan test` (or at least relevant subset).

## SMS Provider Abstraction
Implement:
- interface SmsProvider { public function send(string $toE164, string $message): SmsResult; }
- SmsResult: success(bool), provider_message_id(?string), error(?string)
Drivers:
- LogSmsProvider (default for local/testing)
- SmsApiProvider (production candidate)
Configuration:
- config/sms.php: driver, from, api_token, etc.
- .env: SMS_DRIVER=log|smsapi, SMS_FROM=...

Never hardcode provider calls in controllers/jobs; always go through SmsProvider.

## Reminder Scheduling
Rule:
- Reminder should be sent at (appointment.starts_at - reminder_hours).
Implementation approach:
1) A scheduler command runs every 5 minutes:
   - Finds appointments needing reminder in the next window (e.g., now..now+5min),
     where send_reminder=1, reminder_sent_at is null, client.sms_opt_out=0, starts_at in future.
   - Dispatch SendAppointmentReminderJob(appointment_id).
2) Job composes SMS with:
   - Greeting + appointment datetime in business timezone
   - Public link: APP_URL/c/{client.public_uid}
   - Footer note from settings
   - Opt-out hint: “To stop reminders: open the link and disable SMS.”
3) On success: set reminder_sent_at=now()

Idempotency:
- Job must be safe to retry. Use unique job middleware or a DB-level guard:
  - Update reminder_sent_at with atomic condition (where null).

## Git Workflow & Commits (MANDATORY)
- Default branch is `main`.
- Make small commits per feature/fix.
- Use Conventional Commits:
  - feat: ..., fix: ..., refactor: ..., test: ..., chore: ...
- After implementing a task:
  1) run tests
  2) ensure formatting/lint is acceptable
  3) write a short commit message and commit ALL related changes

If a task includes multiple logical steps, commit after each step that leaves the repo green.

## How to Work on Any Task
For every assigned task, follow this checklist:
1) Restate goal in 1-2 lines.
2) List files to touch.
3) Implement smallest working slice.
4) Add/adjust tests if needed.
5) Run tests.
6) Commit with Conventional Commit message.
7) Summarize what changed + how to verify manually.

## “Do Not Do”
- Do not introduce heavy calendaring libs for MVP unless asked.
- Do not overbuild RBAC/roles.
- Do not add real-time websockets for reminders.
- Do not put secrets in repo.
- Do not skip validation or timezone handling.

## Manual QA Scenarios (must pass)
- Create client → create appointment tomorrow → reminder is scheduled and sent once.
- Client has sms_opt_out=true → no reminder sent.
- Appointment updated time → reminder recalculated (old reminder not sent, new one sent).
- Public page shows only that client’s appointments.
- Opt-out from public page works and blocks future reminders.
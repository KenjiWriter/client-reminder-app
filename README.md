# Client Reminder App

A small business appointment + SMS reminder system.

## Features

### 📊 Dashboard & Analytics
- **Key Performance Indicators (KPIs)**:
  - Total Clients, Appointments, and Canceled visits
  - Period-based metrics (7/30/90 days, MTD): New Clients, SMS Sent, Rescheduled, Canceled
- **Interactive Charts**:
  - Appointments per day (Area chart)
  - SMS Sent per day (Line chart)
  - New Clients per day (Bar chart)
  - Reschedules per day (Line chart)
- **Filters**: Date range filtering (7d, 30d, 90d, Month-to-Date, Custom Range)

### 📅 Appointment Management
- **Calendar View**: Visual overview of all scheduled visits
- **Status Tracking**: Confirmed, Pending Approval, Canceled, Completed
- **SMS Reminders**: Automated 24h reminders via queue/scheduler process

### 👥 Client Management
- **Profile**: Detailed client information and history
- **Public Page**: Unique public link for each client to check their appointments
- **Self-Service**: Clients can request rescheduling or cancellation via their public page
- **Reschedule Workflow**: Admin approval system for client-requested changes

### 💬 Communication
- **SMS Logs**: Full history of sent messages with delivery status
- **Templates**: Configurable SMS footer and content (via translations)

## Setup

1. **Clone the repository**
2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```
3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. **Database**
   Configure your database credentials in `.env`.
   ```bash
   php artisan migrate
   ```
5. **Generate Routes**
   Required for frontend to access Laravel routes and typed route helpers (Ziggy + Wayfinder).
   ```bash
   php artisan ziggy:generate
   php artisan wayfinder:generate
   ```
6. **Build Frontend**
   ```bash
   npm run build
   ```

## Configuration

### Queue & Scheduler

This application uses Laravel Queues and the Scheduler to send SMS reminders.

1. **Queue Configuration**
   Ensure your `.env` is set to use the database driver (or redis):
   ```
   QUEUE_CONNECTION=database
   ```
   
   **Local Development:**
   ```bash
   php artisan queue:work
   ```

   **Production (Supervisor):**
   In production, use Supervisor to keep the queue worker running.
   
   Install Supervisor:
   ```bash
   sudo apt-get install supervisor
   ```

   Create configuration file `/etc/supervisor/conf.d/client-reminder-worker.conf`.
   **Important:** Replace `/path/to/project` with your actual path (e.g., `/var/www/client-reminder-app`).

   ```ini
   [program:client-reminder-worker]
   process_name=%(program_name)s_%(process_num)02d
   # Replace /path/to/project with your actual root directory
   command=php /path/to/project/artisan queue:work database --sleep=3 --tries=3
   autostart=true
   autorestart=true
   user=www-data
   numprocs=1
   redirect_stderr=true
   # Replace /path/to/project with your actual root directory
   stdout_logfile=/path/to/project/storage/logs/worker.log
   stopwaitsecs=3600
   ```
   
   After creating the file:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start client-reminder-worker:*
   ```

2. **Scheduler**
   The scheduler handles checking for upcoming appointments. Add the following Cron entry to your server:
   ```bash
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```
   
   The scheduler runs the `reminders:send` command every minute.
   
   Locally, you can run:
   ```bash
   php artisan schedule:work
   ```


## SMS Configuration

This application uses an SMS provider abstraction to send appointment reminders.

### Environment Variables

Add the following to your `.env` file:

```env
SMS_DRIVER=log
SMS_FROM="Your Business Name"
SMS_REMINDER_HOURS=24
SMS_FOOTER_NOTE="Thank you for choosing our service!"
```

### SMS Drivers

**`log` (Default for Development)**
- Logs SMS messages to `storage/logs/laravel.log`
- No external API required
- Ideal for local testing

**`smsapi` (Production)**
- Integrates with SMS.API (https://www.smsapi.pl/)
- Requires `SMSAPI_TOKEN` in `.env`
- To enable: Set `SMS_DRIVER=smsapi`

### Triggering Reminders Manually

**Manual Individual Reminder**
Send a reminder for a specific appointment ID. This respects guards (opt-out, already sent) unless `--force` is used. Use `--sync` to send immediately without the queue.
```bash
php artisan reminders:send {appointment_id} [--force] [--sync]
```

**Manual Bulk Check**
Run the same check the scheduler does (appointments due in ~24h):
```bash
php artisan reminders:send-bulk
```

Both commands respect the logic defined in the `AppointmentReminderSender` service.

### How It Works

1. The scheduler runs every 5 minutes
2. It finds appointments due for reminders (24 hours before by default)
3. Jobs are dispatched to the queue
4. The queue worker sends SMS via the configured provider
5. `reminder_sent_at` is marked to prevent duplicates (idempotent)


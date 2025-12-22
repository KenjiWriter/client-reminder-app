# Client Reminder App

A small business appointment + SMS reminder system.

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
5. **Build Frontend**
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
   
   Run the queue worker:
   ```bash
   php artisan queue:work
   ```

2. **Scheduler**
   The scheduler handles checking for upcoming appointments. Add the following Cron entry to your server:
   ```bash
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```
   
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

### Testing Reminders

To manually trigger the reminder command:
```bash
php artisan reminders:send
```

The scheduler automatically runs this command every 5 minutes when `php artisan schedule:work` is running.

### How It Works

1. The scheduler runs every 5 minutes
2. It finds appointments due for reminders (24 hours before by default)
3. Jobs are dispatched to the queue
4. The queue worker sends SMS via the configured provider
5. `reminder_sent_at` is marked to prevent duplicates (idempotent)


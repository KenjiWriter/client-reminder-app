<?php

return [
    'nav' => [
        'dashboard' => 'Dashboard',
        'calendar' => 'Calendar',
        'clients' => 'Clients',
        'messages' => 'Messages',
        'settings' => 'Settings',
    ],
    
    'common' => [
        'today' => 'Today',
        'newAppointment' => 'New Appointment',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'actions' => 'Actions',
        'search' => 'Search',
        'close' => 'Close',
        'updateAppointment' => 'Update Appointment',
        'minutes' => 'minutes',
        'hours' => 'hours',
    ],
    
    'calendar' => [
        'day' => 'Day',
        'week' => 'Week',
        'month' => 'Month',
        'noAppointments' => 'No appointments',
        'editAppointment' => 'Edit Appointment',
        'addAppointment' => 'Add Appointment',
        'updateDetails' => 'Update appointment details',
        'sendReminder' => 'Send SMS reminder',
    ],
    
    'clients' => [
        'title' => 'Clients',
        'newClient' => 'New Client',
        'editClient' => 'Edit Client',
        'addClient' => 'Add Client',
        'fullName' => 'Full Name',
        'phone' => 'Phone',
        'email' => 'Email',
        'optedOut' => 'Opted out from SMS',
    ],
    
    'settings' => [
        'title' => 'Settings',
        'timezone' => 'Timezone',
        'reminderHours' => 'Hours before reminder',
        'smsFooterNote' => 'SMS Footer Note',
        'businessSettings' => 'Business Settings',
    ],
    
    'appointments' => [
        'client' => 'Client',
        'date' => 'Date',
        'time' => 'Time',
        'duration' => 'Duration (minutes)',
        'note' => 'Note (optional)',
        'sendReminder' => 'Send reminder',
    ],
    
    'dashboard' => [
        'title' => 'Dashboard',
        'filters' => [
            'range' => 'Range',
            '7d' => 'Last 7 Days',
            '30d' => 'Last 30 Days',
            '90d' => 'Last 90 Days',
            'mtd' => 'Month to Date',
            'custom' => 'Custom Range',
        ],
        'stats' => [
            'totalClients' => 'Total Clients',
            'totalAppointments' => 'Total Appointments',
            'newClients' => 'New Clients',
            'smsSent' => 'SMS Sent',
            'reschedules' => 'Reschedules',
            'inPeriod' => 'in period',
        ],
        'charts' => [
            'appointmentsPerDay' => 'Appointments per day',
            'smsSentPerDay' => 'SMS sent per day',
            'newClientsPerDay' => 'New clients per day',
            'reschedulesPerDay' => 'Reschedules per day',
        ],
    ],
    
    'public' => [
        'title' => 'Appointments for {name}',
        'hello' => 'Hello, {name}!',
        'viewUpcoming' => 'View your upcoming appointments',
        'smsReminders' => 'SMS Reminders',
        'remindersDisabled' => 'SMS reminders are currently disabled',
        'remindersEnabled' => 'You will receive SMS reminders 24 hours before your appointments',
        'enableReminders' => 'Enable SMS Reminders',
        'upcomingAppointments' => 'Upcoming Appointments',
        'noAppointments' => 'No upcoming appointments',
        'footerContact' => 'If you need to reschedule or cancel, please contact us directly.',
    ],
];

<?php

return [
    'title' => 'Settings',
    'description' => 'Manage your application and account settings.',
    'nav' => [
        'account' => 'Account',
        'sms' => 'SMS Configuration',
        'appearance' => 'Appearance',
        'logout' => 'Log Out',
    ],
    'account' => [
        'title' => 'Profile Information',
        'description' => 'Update your account\'s profile information and email address.',
        'name' => 'Name',
        'email' => 'Email',
        'save' => 'Save',
        'saved' => 'Saved.',
        'password_title' => 'Update Password',
        'password_description' => 'Ensure your account is using a long, random password to stay secure.',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm Password',
        'change_password' => 'Change Password',
    ],
    'sms' => [
        'title' => 'SMS Configuration',
        'description' => 'SMSAPI integration settings and sending schedule.',
        'token_label' => 'SMSAPI Token',
        'token_desc' => 'You can generate the token in the SMSAPI panel.',
        'sender_label' => 'Sender Name ("From")',
        'sender_desc' => 'The sender name must be approved in SMSAPI.',
        'time_label' => 'Reminder Send Time',
        'time_desc' => 'The system will attempt to send reminders for the next day at this time.',
        'save' => 'Save Configuration',
        'saved' => 'Saved.',
    ],
    'appearance' => [
        'title' => 'Appearance',
        'description' => 'Customize the application appearance to your preferences.',
        'light' => 'Light',
        'dark' => 'Dark',
    ],
];

<?php
/**
 * Application Constants
 * Centralized definition of constants used throughout the application
 */

// Valid job status values
const VALID_JOB_STATUSES = [
    'New',
    'Awaiting approval',
    'Create Email',
    'Not interested',
    'Email sent',
    'Email Opened',
    'Responded to Email'
];

// Webhook timeout in seconds
const WEBHOOK_TIMEOUT = 10;

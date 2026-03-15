<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withoutVite();
        
        // Ensure tests don't try to connect to a real IMAP server if ImapService is instantiated
        config(['imap.accounts.default.protocol' => 'imap']);
        config(['imap.accounts.default.host' => 'localhost']);
    }
}

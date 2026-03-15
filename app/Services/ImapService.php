<?php

declare(strict_types=1);

namespace App\Services;

use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Illuminate\Support\Facades\Log;

class ImapService
{
    private ClientManager $clientManager;

    public function __construct(ClientManager $clientManager)
    {
        $this->clientManager = $clientManager;
    }

    /**
     * Retrieve dynamic IMAP configuration from DB.
     */
    private function getImapConfig(): ?array
    {
        $settings = \App\Models\Setting::first();
        if (!$settings || empty($settings->imap_host) || empty($settings->imap_username) || empty($settings->imap_password) || empty($settings->imap_port)) {
            return null;
        }

        try {
            $password = \Illuminate\Support\Facades\Crypt::decryptString($settings->imap_password);
        } catch (\Throwable $e) {
            return null;
        }

        return [
            'host'          => $settings->imap_host,
            'port'          => $settings->imap_port,
            'encryption'    => 'ssl',
            'validate_cert' => true,
            'username'      => $settings->imap_username,
            'password'      => $password,
            'protocol'      => 'imap',
        ];
    }

    /**
     * Open and connect IMAP client, returning the specified folder.
     * Throws Exception on configuration failure.
     */
    private function getFolderInstance(string $folderName = 'INBOX')
    {
        $config = $this->getImapConfig();
        if (!$config) {
            throw new \Exception(__('email.errors.not_configured'));
        }

        $client = $this->clientManager->make($config);
        $client->connect();

        $folder = $client->getFolder($folderName);

        if (!$folder) {
            $availableFolders = $client->getFolders()->map(fn($f) => $f->name)->toArray();
            \Illuminate\Support\Facades\Log::error("Available IMAP folders: " . implode(', ', $availableFolders));
            throw new \Exception(__('email.errors.folder_not_found', ['folder' => $folderName]));
        }

        return $folder;
    }

    /**
     * Fetch paginated emails from the specified folder.
     *
     * Returns an array with keys:
     *   - emails: array of simplified email DTOs
     *   - current_page: int
     *   - last_page: int
     *   - total: int
     *   - per_page: int
     *   - error: string|null
     */
    public function getFolderMessages(string $folderName = 'INBOX', int $page = 1, int $perPage = 15): array
    {
        try {
            $folder = $this->getFolderInstance($folderName);

            // Use native webklex pagination (memory-efficient, no full fetch)
            $paginator = $folder->query()
                ->all()
                ->setFetchOrder('desc')
                ->paginate($perPage, $page);

            $emails = [];
            foreach ($paginator as $message) {
                // Ensure fromAddress is a string by checking the collection and extracting ->mail
                $fromAddress = '';
                $fromName    = '';
                $fromCollection = $message->getFrom();
                if ($fromCollection && $fromCollection->count() > 0) {
                    $firstFrom   = $fromCollection->first();
                    $fromAddress = (string) ($firstFrom->mail ?? '');
                    $fromName    = (string) ($firstFrom->personal ?? $fromAddress);
                }

                // Safely format date
                $dateString = '';
                $dateObj = $message->getDate();
                if ($dateObj) {
                    try {
                        // $dateObj might be a Carbon instance or an Attribute collection.
                        // Assuming it's typically returning a Carbon-like object or collection of them
                        if (method_exists($dateObj, 'first') && $dateObj->first()) {
                            $dateString = clone $dateObj->first();
                            $dateString = $dateString->format('Y-m-d H:i');
                        } elseif (method_exists($dateObj, 'format')) {
                            $dateString = $dateObj->format('Y-m-d H:i');
                        } else {
                            $dateString = (string) $dateObj;
                        }
                    } catch (\Throwable $e) {
                         $dateString = (string) $dateObj;
                    }
                }

                // Extract plain text snippet
                $snippet = '';
                try {
                    $textBody = $message->getTextBody();
                    $snippet = \Illuminate\Support\Str::limit(strip_tags((string) $textBody), 80);
                } catch (\Throwable) {}

                $emails[] = [
                    'uid'        => (string) $message->getUid(),
                    'subject'    => (string) ($message->getSubject() ?? __('email.no_subject')),
                    'snippet'    => $snippet,
                    'from'       => $fromName ?: $fromAddress,
                    'from_email' => $fromAddress,
                    'date'       => $dateString,
                    'is_seen'    => $message->getFlags()->contains('Seen'),
                ];
            }

            // Enforce strict descending sort by date
            $emails = collect($emails)->sortByDesc(function ($email) {
                try {
                    return \Carbon\Carbon::parse($email['date'])->timestamp;
                } catch (\Throwable $e) {
                    return 0; // fallback if date is unparseable
                }
            })->values()->all();

            return [
                'emails'       => $emails,
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
                'per_page'     => $perPage,
                'error'        => null,
            ];
        } catch (\Throwable $e) {
            Log::error('IMAP Connection Failed: ' . $e->getMessage());

            return [
                'emails'       => [],
                'current_page' => 1,
                'last_page'    => 1,
                'total'        => 0,
                'per_page'     => $perPage,
                'error'        => $e->getMessage(),
            ];
        }
    }

    /**
     * Fetch a single message by UID, mark it as seen.
     *
     * Returns an array with keys:
     *   uid, subject, from, from_email, date, message_id, body_html, body_text, error
     */
    public function getMessage(string $uid, string $folderName = 'INBOX'): array
    {
        try {
            $folder = $this->getFolderInstance($folderName);

            $message = $folder->query()->getMessageByUid($uid);

            if ($message === null) {
                return ['error' => __('email.errors.message_not_found')];
            }

            // Mark as read
            $message->setFlag('Seen');

            $fromAddress = '';
            $fromName    = '';
            $fromCollection = $message->getFrom();
            if ($fromCollection && $fromCollection->count() > 0) {
                $firstFrom   = $fromCollection->first();
                $fromAddress = (string) ($firstFrom->mail ?? '');
                $fromName    = (string) ($firstFrom->personal ?? $fromAddress);
            }

            // Extract Message-ID header for threading
            $rawMessageId = '';
            try {
                $rawMessageId = (string) $message->getMessageId();
            } catch (\Throwable) {
                // Non-critical – threading will degrade gracefully
            }
            
            // Safely format date
            $dateString = '';
            $dateObj = $message->getDate();
            if ($dateObj) {
                try {
                    if (method_exists($dateObj, 'first') && $dateObj->first()) {
                        $dateString = clone $dateObj->first();
                        $dateString = $dateString->format('Y-m-d H:i');
                    } elseif (method_exists($dateObj, 'format')) {
                        $dateString = $dateObj->format('Y-m-d H:i');
                    } else {
                        $dateString = (string) $dateObj;
                    }
                } catch (\Throwable $e) {
                     $dateString = (string) $dateObj;
                }
            }

            $bodyHtml = '';
            $bodyText = '';

            try {
                $htmlPart = $message->getHTMLBody();
                $bodyHtml = $htmlPart ? (string) $htmlPart : '';
            } catch (\Throwable) {}

            try {
                $textPart = $message->getTextBody();
                $bodyText = $textPart ? (string) $textPart : '';
            } catch (\Throwable) {}

            return [
                'uid'        => (string) $message->getUid(),
                'subject'    => (string) ($message->getSubject() ?? __('email.no_subject')),
                'from'       => $fromName ?: $fromAddress,
                'from_email' => $fromAddress,
                'date'       => $dateString,
                'message_id' => $rawMessageId,
                'body_html'  => $bodyHtml,
                'body_text'  => $bodyText,
                'error'      => null,
            ];
        } catch (\Throwable $e) {
            Log::error('IMAP Connection Failed: ' . $e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Count unseen messages in INBOX. Returns 0 on any failure.
     */
    public function getUnreadCount(): int
    {
        try {
            $folder = $this->getFolderInstance('INBOX');

            return (int) $folder->query()
                ->unseen()
                ->setFetchFlags(true)
                ->setFetchBody(false)
                ->count();
        } catch (\Throwable $e) {
            Log::warning('IMAP getUnreadCount failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Delete an email across IMAP and expunge the folder so it disappears immediately.
     */
    public function deleteMessage(string $uid, string $folderName = 'INBOX'): bool
    {
        try {
            $folder = $this->getFolderInstance($folderName);
            $message = $folder->query()->getMessageByUid($uid);

            if ($message !== null) {
                // Delete and Expunge
                $message->delete(true);
                
                // Extra safety: manually call expunge on folder
                try {
                    $folder->expunge();
                } catch (\Throwable $e) {}

                return true;
            }

            return false;
        } catch (\Throwable $e) {
            Log::error('IMAP deleteMessage failed: ' . $e->getMessage());
            return false;
        }
    }
}

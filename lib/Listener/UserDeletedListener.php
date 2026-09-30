<?php

declare(strict_types=1);

namespace OCA\Diary\Listener;

use OCA\Diary\Db\EntryMapper;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<UserDeletedEvent>
 */
class UserDeletedListener implements IEventListener
{
    public function __construct(
        private readonly EntryMapper $mapper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(Event $event): void
    {
        if (!($event instanceof UserDeletedEvent)) {
            return;
        }

        $uid = $event->getUser()->getUID();
        try {
            $deletedEntries = $this->mapper->deleteAllEntriesForUser($uid);
            $this->logger->info("All $deletedEntries diary entries deleted for user ".$uid);
        } catch (Exception $e) {
            $this->logger->error('Could not delete diary entries for user '.$uid, ['exception' => $e]);
        }
    }
}

<?php

declare(strict_types=1);

namespace OCA\Diary\Controller;

use OCA\Diary\Db\Entry;
use OCA\Diary\Db\EntryMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\DB\Exception;
use OCP\IRequest;
use OCP\Util;
use Psr\Log\LoggerInterface;

class PageController extends Controller
{
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly ?string $userId,
        private readonly EntryMapper $mapper,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Render the main page. No admin and no CSRF check is required for it.
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): TemplateResponse
    {
        Util::addScript($this->appName, 'diary-main');

        return new TemplateResponse('diary', 'index');  // templates/index.php
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getEntry(string $date): DataResponse
    {
        try {
            $entry = $this->mapper->find((string) $this->userId, $date);
        } catch (DoesNotExistException) {
            return new DataResponse(['isEmpty' => true]);
        } catch (MultipleObjectsReturnedException|Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        return new DataResponse($entry);
    }

    /**
     * @param int $amount Number of past entries to fetch
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getLastEntries(int $amount): DataResponse
    {
        try {
            $entries = $this->mapper->findLast((string) $this->userId, $amount);
        } catch (Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
        $response = array_map(static fn (Entry $entry): array => [
            'date' => $entry->getEntryDate(),
            'excerpt' => mb_substr((string) $entry->getEntryContent(), 0, 40),
        ], $entries);

        return new DataResponse($response);
    }

    /**
     * @param string $date    ISO date as identifier
     * @param string $content Diary entry to save
     */
    #[NoAdminRequired]
    public function updateEntry(string $date, string $content): DataResponse
    {
        $userId = (string) $this->userId;

        if ('' === $content) {
            try {
                $entry = $this->mapper->find($userId, $date);
                $this->mapper->delete($entry);
            } catch (\Exception $e) {
                $this->logger->notice('Could not delete diary entry: '.$e->getMessage());
            }

            return new DataResponse(['isEmpty' => true]);
        }
        $content = strip_tags($content);
        $entry = new Entry();
        $entry->setId($userId.$date);
        $entry->setUid($userId);
        $entry->setEntryDate($date);
        $entry->setEntryContent($content);

        try {
            return new DataResponse($this->mapper->insertOrUpdate($entry));
        } catch (Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}

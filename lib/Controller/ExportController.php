<?php

declare(strict_types=1);

namespace OCA\Diary\Controller;

use OCA\Diary\Db\EntryMapper;
use OCA\Diary\Service\ConversionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\DB\Exception;
use OCP\IRequest;

/**
 * Download diary entries in multiple formats.
 */
class ExportController extends Controller
{
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly ?string $userId,
        private readonly EntryMapper $mapper,
        private readonly ConversionService $exportService,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Get all entries as one markdown file.
     *
     * @throws Exception
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getMarkdown(): DataDownloadResponse
    {
        $entries = $this->mapper->findAll((string) $this->userId);
        $markdownString = $this->exportService->entriesToMarkdown($entries);

        return new DataDownloadResponse($markdownString, 'diary.md', 'text/markdown');
    }

    /**
     * Get all entries as one PDF file.
     *
     * @throws Exception
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getPdf(): DataDownloadResponse
    {
        $entries = $this->mapper->findAll((string) $this->userId);
        $pdfString = $this->exportService->entriesToPdf($entries);

        return new DataDownloadResponse($pdfString, 'diary.pdf', 'application/pdf');
    }
}

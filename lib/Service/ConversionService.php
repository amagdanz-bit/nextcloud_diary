<?php

declare(strict_types=1);

namespace OCA\Diary\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use League\CommonMark\CommonMarkConverter;
use OCA\Diary\Db\Entry;

/**
 * Convert entries into multiple formats.
 */
class ConversionService
{
    /**
     * Convert an array of entries into one PDF encoded as string.
     *
     * @param Entry[] $entries
     */
    public function entriesToPdf(array $entries): string
    {
        // Render every entry on its own page within one single document.
        $pages = array_map(
            fn (Entry $entry): string => '<div class="entry">'.$this->markdownToHTML($this->entryToMarkdown($entry)).'</div>',
            $entries
        );

        return $this->htmlToPDF(implode("\n", $pages));
    }

    /**
     * Convert one entry into a PDF encoded as a string.
     */
    public function entryToPDF(Entry $entry): string
    {
        $data = $this->entryToMarkdown($entry);
        $data = $this->markdownToHTML($data);

        return $this->htmlToPDF($data);
    }

    /**
     * Convert an array of entries into one markdown file.
     *
     * @param Entry[] $entries
     */
    public function entriesToMarkdown(array $entries): string
    {
        return implode("\r\n\r\n", array_map($this->entryToMarkdown(...), $entries));
    }

    /**
     * Convert one entry into a markdown file.
     */
    public function entryToMarkdown(Entry $entry): string
    {
        $serializedEntry = $entry->jsonSerialize();
        $markdownString = '# '.$serializedEntry['entryDate'];
        $markdownString .= sprintf("\r\n\r\n%s", $serializedEntry['entryContent']);

        return $markdownString;
    }

    /**
     * Convert markdown into HTML.
     */
    public function markdownToHTML(string $markdown): string
    {
        $converter = new CommonMarkConverter();

        return $converter->convert($markdown)->getContent();
    }

    /**
     * Convert HTML into a PDF encoded as a string.
     */
    public function htmlToPDF(string $html): string
    {
        $options = new Options();
        // Never load remote resources (e.g. images linked in an entry) while rendering.
        $options->setIsRemoteEnabled(false);

        $pdf = new Dompdf($options);
        $pdf->setPaper('A4', 'portrait');
        $pdf->loadHtml(
            '<html><head><meta charset="utf-8"><style>.entry + .entry { page-break-before: always; }</style></head>'
            .'<body>'.$html.'</body></html>',
            'UTF-8'
        );
        $pdf->render();

        return (string) $pdf->output();
    }
}

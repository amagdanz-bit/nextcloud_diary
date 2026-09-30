<?php

declare(strict_types=1);

namespace OCA\Diary\Tests\Unit\Controller;

use OCA\Diary\Controller\PageController;
use OCA\Diary\Db\Entry;
use OCA\Diary\Db\EntryMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PageControllerTest extends TestCase
{
    /** @var PageController */
    private $controller;
    private $userId = 'john';
    /** @var EntryMapper|MockObject */
    private $mapper;

    public function setUp(): void
    {
        $request = $this->createMock(IRequest::class);
        $this->mapper = $this->getMockBuilder(EntryMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new PageController(
            'diary', $request, $this->userId, $this->mapper, $logger
        );
    }

    public function testIndex(): void
    {
        $result = $this->controller->index();

        $this->assertEquals('index', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }

    public function testGetEntry(): void
    {
        $entryDate = '2022-08-07';
        $entryContent = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam';
        $entry = $this->createMockEntry($entryDate, $this->userId, $entryContent);
        $this->mapper->expects($this->once())
            ->method('find')
            ->with($this->equalTo($this->userId),
                $this->equalTo($entryDate))
            ->willReturn($entry);
        $result = $this->controller->getEntry($entryDate);
        $this->assertEquals(Http::STATUS_OK, $result->getStatus());
        $this->assertEquals($entry, $result->getData());
    }

    public function testNotFound(): void
    {
        $entryDate = '2022-08-07';
        $this->mapper->expects($this->once())
            ->method('find')
            ->with($this->equalTo($this->userId),
                $this->equalTo($entryDate))
            ->willThrowException(new DoesNotExistException('Id not found'));
        $result = $this->controller->getEntry($entryDate);
        $this->assertEquals(Http::STATUS_OK, $result->getStatus());
        $this->assertEquals(['isEmpty' => true], $result->getData());
    }

    public function testMultipleFound(): void
    {
        $entryDate = '2022-08-07';
        $this->mapper->expects($this->once())
            ->method('find')
            ->with($this->equalTo($this->userId),
                $this->equalTo($entryDate))
            ->willThrowException(new MultipleObjectsReturnedException('Id not found'));
        $result = $this->controller->getEntry($entryDate);
        $this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $result->getStatus());
        $this->assertEquals(['error' => 'Id not found'], $result->getData());
    }

    public function testUpdateEntry(): void
    {
        $entryDate = '2022-08-07';
        $entryContent = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam';
        $entry = $this->createMockEntry($entryDate, $this->userId, $entryContent);
        $this->mapper->expects($this->once())
            ->method('insertOrUpdate')
            ->with($this->equalTo($entry))
            ->willReturn($entry);
        $result = $this->controller->updateEntry($entryDate, $entryContent);
        $this->assertEquals(Http::STATUS_OK, $result->getStatus());
        $this->assertEquals($entry, $result->getData());
    }

    public function testUpdateEntryFailure(): void
    {
        $entryDate = '2022-08-07';
        $entryContent = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam';
        $entry = $this->createMockEntry($entryDate, $this->userId, $entryContent);
        $this->mapper->expects($this->once())
            ->method('insertOrUpdate')
            ->with($this->equalTo($entry))
            ->willThrowException(new \OCP\DB\Exception('Some error while updating'));
        $result = $this->controller->updateEntry($entryDate, $entryContent);
        $this->assertEquals(Http::STATUS_INTERNAL_SERVER_ERROR, $result->getStatus());
        $this->assertEquals(['error' => 'Some error while updating'], $result->getData());
    }

    public function testUpdateEntryDeleteEmpty(): void
    {
        $entryDate = '2022-08-07';
        $entryContent = '';
        $entry = $this->createMockEntry($entryDate, $this->userId, $entryContent);
        $this->mapper->expects($this->once())
            ->method('find')
            ->with($this->userId, $entryDate)
            ->willReturn($entry);
        $this->mapper->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($entry))
            ->willReturn($entry);
        $result = $this->controller->updateEntry($entryDate, $entryContent);
        $this->assertEquals(Http::STATUS_OK, $result->getStatus());
        $this->assertEquals(['isEmpty' => true], $result->getData());
    }

    /**
     * Create an Entry element.
     */
    private function createMockEntry(string $date, string $userId, string $content): Entry
    {
        $entry = new Entry();
        $entry->setId($userId . $date);
        $entry->setUid($userId);
        $entry->setEntryDate($date);
        $entry->setEntryContent($content);

        return $entry;
    }
}

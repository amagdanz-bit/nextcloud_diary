<?php

declare(strict_types=1);

namespace OCA\Diary\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

/**
 * @method string getUid()
 * @method void   setUid(string $uid)
 * @method string getEntryDate()
 * @method void   setEntryDate(string $entryDate)
 * @method string getEntryContent()
 * @method void   setEntryContent(string $entryContent)
 */
class Entry extends Entity implements JsonSerializable
{
    protected $entryDate;
    protected $uid;
    protected $entryContent;

    public function __construct()
    {
        $this->addType('id', Types::STRING);
        $this->addType('uid', Types::STRING);
        $this->addType('entryDate', Types::STRING);
        $this->addType('entryContent', Types::STRING);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'uid' => $this->uid,
            'entryDate' => $this->entryDate,
            'entryContent' => $this->entryContent,
        ];
    }
}

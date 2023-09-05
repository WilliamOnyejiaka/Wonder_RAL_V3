<?php
declare(strict_types=1);

namespace Lib;

require __DIR__ . "/../vendor/autoload.php";

use Lib\BaseRouter;

ini_set("display_errors", 1);

class Blueprint extends BaseRouter
{

    public function __construct(string $url_prefix)
    {
        parent::__construct();
        $this->url_prefix = $url_prefix;
    }

    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        return null;
    }
}
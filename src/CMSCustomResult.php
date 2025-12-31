<?php

namespace sasky\customPageAction;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Config\Configurable;

class CMSCustomResult
{
    use Injectable;
    use Extensible;
    use Configurable;
    private $message;
    private $type = 'good'; // can be good or bad
    private $redirect = '';

    public function __construct($message, $type = 'good', $redirect = '')
    {
        $this->message = $message;
        $this->type = $type;
        $this->redirect = $redirect;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getType()
    {
        if ($this->type === 'good' || $this->type === 'bad') {
            return $this->type;
        }

        return 'good';
    }

    public function getRedirect()
    {
        return $this->redirect;
    }
}

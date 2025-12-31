<?php

namespace sasky\customPageAction;

use SilverStripe\Core\Extension;

class CustomActionsGridFieldDetailForm extends Extension
{
    public function updateItemRequestClass(&$class, $gridField, $record, $requestHandler)
    {
        $class = CustomActionsGridFieldDetailForm_ItemRequest::class;
    }
}

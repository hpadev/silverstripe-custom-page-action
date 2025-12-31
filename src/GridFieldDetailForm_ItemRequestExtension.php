<?php

namespace sasky\customPageAction;

use SilverStripe\Core\Extension;

class GridFieldDetailForm_ItemRequestExtension extends Extension
{
    private static $allowed_actions = [
        'customcmsaction'
    ];

    public function updateFormActions($actions)
    {
        if ($this->owner->record->hasMethod('getCustomCMSActions')) {
            $customActions = $this->owner->record->getCustomCMSActions()->dataFields();
            foreach ($customActions as $customAction) {
                $actions->push($customAction);
            }
        }
    }
}

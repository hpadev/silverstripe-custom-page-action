<?php

namespace sasky\customPageAction;

use SilverStripe\Versioned\VersionedGridFieldItemRequest;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse_Exception;

class CustomActionsGridFieldDetailForm_ItemRequest extends VersionedGridFieldItemRequest
{
    public function hasMethod($method)
    {
        if (strpos($method, 'CustomCMSAction_') !== false) {
            return true;
        }

        return method_exists($this, $method) || $this->getExtraMethodConfig($method);
    }

    public function __call($method, $arguments)
    {
        if (strpos($method, 'CustomCMSAction_') !== false) {
            $form = $arguments[1];
            $request = Controller::curr()->getRequest();

            if ($form->getRecord()->hasMethod($method)) {
                $result = $form->getRecord()->$method();

                if (!$result instanceof CMSCustomResult) {
                    return  parent::__call($method, $arguments);
                }

                $controller = Controller::curr();
                $response = $controller->getResponse();
                $response->addHeader('X-Status', $result->getMessage());
                if ($result->getType() === 'bad') {
                    throw new HTTPResponse_Exception($response, 400);
                }

                if ($result->getRedirect()) {
                    $response->addHeader('X-ControllerURL', $result->getRedirect());
                    $request->addHeader('X-Pjax', 'Content');
                    $response->addHeader('X-Pjax', 'Content');
                }
                $controller->setResponse($response);

                return $controller->getResponseNegotiator()->respond($request);
            }
        }
        parent::__call($method, $arguments);
    }
}

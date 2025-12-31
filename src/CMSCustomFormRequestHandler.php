<?php

namespace sasky\customPageAction;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormRequestHandler;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataObject;
use Page;

/**
 * Allows CMS forms to be decorated with additional context arguments.
 * By injecting additional IDs into the form link, LeftAndMain subclasses
 * can avoid relying on session state to record current page ID.
 * {@see CMSMain} for example usage.
 */

class CMSCustomFormRequestHandler extends FormRequestHandler
{
    private static $allowed_actions = [
        'httpSubmission',
    ];

    /**
     * @config
     * @var array
     */
    private static $url_handlers = [
        'POST ' => 'httpSubmission',
        'GET ' => 'httpSubmission',
        'HEAD ' => 'httpSubmission',
    ];
    /**
     * Extra form identifiers (e.g. ID, OtherID)
     * @var array
     */
    protected $extra = [];

    public function __construct(Form $form, $extra = [])
    {
        parent::__construct($form);
        $this->extra = $extra;
    }

    public function Link($action = null)
    {
        // Add on extra urlsegments to end of link
        $parts = $this->extra;
        if ($action) {
            $parts[] = $action;
        }

        return parent::Link(Controller::join_links($parts));
    }

    public function httpSubmission($request)
    {
        // START of stuff copied form FormRequestHandler to get the action ( function Name )
        // Strict method check
        $vars = $request->postVars();
        // Ensure we only process saveable fields (non structural, readonly, or disabled)
        $allowedFields = array_keys($this->form->Fields()->saveableFields());
        // Populate the form
        $this->form->loadDataFrom($vars, true, $allowedFields);

        // Determine the action button clicked
        $functionName = null;
        foreach ($vars as $paramName => $paramVal) {
            if (substr($paramName, 0, 7) == 'action_') {
                // Break off querystring arguments included in the action
                if (strpos($paramName, '?') !== false) {
                    list($paramName, $paramVars) = explode('?', $paramName, 2);
                    $newRequestParams = [];
                    parse_str($paramVars, $newRequestParams);
                    $vars = array_merge((array)$vars, (array)$newRequestParams);
                }

                // Cleanup action_, _x and _y from image fields
                $functionName = preg_replace(['/^action_/', '/_x$|_y$/'], '', $paramName);
                break;
            }
        }

        // END of stuff copied form FormRequestHandler to get the action ( function Name )

        if (!$functionName) {
            return parent::httpSubmission($request);
        }
        if (!is_int(strpos($functionName, 'CustomCMSAction_'))) {
            return parent::httpSubmission($request);
        }

        $recordID = $request->param('ID');

        if (!$recordID || !is_numeric($recordID)) {
            return parent::httpSubmission($request);
        }
        $record =  Page::get()->byID($recordID);

        if (!$record->hasMethod($functionName)) {
            return parent::httpSubmission($request);
        }
        $result = $record->$functionName($vars, $this->form);
        if (!$result instanceof CMSCustomResult) {
            return parent::httpSubmission($request);
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

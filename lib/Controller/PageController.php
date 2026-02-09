<?php

declare(strict_types=1);

namespace OCA\AdminPage\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller {

    public function __construct(string $appName, IRequest $request) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return TemplateResponse
     */
    public function index(): TemplateResponse {
        Util::addScript('adminpage', 'adminpage-main');
        return new TemplateResponse('adminpage', 'index');
    }
}

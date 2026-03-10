<?php

declare(strict_types=1);

namespace OCA\AdminPage\Controller;

use OCA\AdminPage\Service\PublicTokenService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PublicPageController extends Controller {

    private PublicTokenService $publicTokenService;

    public function __construct(
        string $appName,
        IRequest $request,
        PublicTokenService $publicTokenService
    ) {
        parent::__construct($appName, $request);
        $this->publicTokenService = $publicTokenService;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function index(string $token): TemplateResponse {
        $orgId = $this->publicTokenService->validateToken($token);

        if ($orgId === null) {
            return new TemplateResponse('adminpage', 'public_expired', [], 'public');
        }

        Util::addScript('adminpage', 'adminpage-public');
        return new TemplateResponse('adminpage', 'public', ['token' => $token], 'public');
    }
}

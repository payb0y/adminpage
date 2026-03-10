<?php

declare(strict_types=1);

namespace OCA\AdminPage\Controller;

use DateTime;
use OCA\AdminPage\Service\OrgOverviewService;
use OCA\AdminPage\Service\PublicTokenService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class PublicLinkAdminController extends Controller {

    private PublicTokenService $publicTokenService;
    private OrgOverviewService $orgOverviewService;
    private IUserSession $userSession;

    public function __construct(
        string $appName,
        IRequest $request,
        PublicTokenService $publicTokenService,
        OrgOverviewService $orgOverviewService,
        IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
        $this->publicTokenService = $publicTokenService;
        $this->orgOverviewService = $orgOverviewService;
        $this->userSession = $userSession;
    }

    /**
     * List all public links for the current admin's organization.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function list(): JSONResponse {
        $uid = $this->userSession->getUser()->getUID();
        $orgId = $this->orgOverviewService->resolveOrgId($uid);

        if ($orgId === null) {
            return new JSONResponse(['error' => 'No organization found'], Http::STATUS_NOT_FOUND);
        }

        $links = $this->publicTokenService->listTokens($orgId);
        return new JSONResponse($links);
    }

    /**
     * Create a new public dashboard link.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create(): JSONResponse {
        $uid = $this->userSession->getUser()->getUID();
        $orgId = $this->orgOverviewService->resolveOrgId($uid);

        if ($orgId === null) {
            return new JSONResponse(['error' => 'No organization found'], Http::STATUS_NOT_FOUND);
        }

        $label = $this->request->getParam('label', null);
        $expiresAtStr = $this->request->getParam('expiresAt', null);

        $expiresAt = null;
        if ($expiresAtStr) {
            try {
                $expiresAt = new DateTime($expiresAtStr);
            } catch (\Exception $e) {
                return new JSONResponse(['error' => 'Invalid date format'], Http::STATUS_BAD_REQUEST);
            }
        }

        $link = $this->publicTokenService->createToken($orgId, $uid, $label, $expiresAt);
        return new JSONResponse($link, Http::STATUS_CREATED);
    }

    /**
     * Revoke (disable) a public link.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function revoke(int $id): JSONResponse {
        $this->publicTokenService->revokeToken($id);
        return new JSONResponse(['status' => 'revoked']);
    }
}

<?php

declare(strict_types=1);

namespace OCA\AdminPage\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IDBConnection;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;

/**
 * Registers the navigation entry only for organization administrators.
 *
 * "Organization administrator" means a membership row with role = 'admin' —
 * the same rule OrgOverviewService::resolveOrgId() applies, which is what
 * decides whether this app returns any data at all. A member without it used to
 * see the button, open the page and be told "Admin access required"; now the
 * button is simply not there.
 *
 * Nextcloud super-admins are deliberately NOT included. This dashboard scopes
 * everything to the caller's own organization, so a super-admin who does not
 * administer one would land on that same empty state — superadminpage is the
 * cross-organization view for them.
 *
 * The membership check is a direct query rather than a call into
 * OrgOverviewService: boot() runs on every request, and that service pulls in
 * storage and geocoding collaborators this decision does not need.
 */
class Application extends App implements IBootstrap {

    public const APP_ID = 'adminpage';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
    }

    public function boot(IBootContext $context): void {
        $context->injectFn(function (
            INavigationManager $navigationManager,
            IUserSession $userSession,
            IDBConnection $db,
            IURLGenerator $urlGenerator,
            IFactory $l10nFactory,
        ): void {
            /**
            * The entry is registered unconditionally and decides for itself when the
            * navigation is actually built.
            *
            * boot() cannot do the check: on the OCS route the app menu is fetched from
            * (/ocs/v2.php/core/navigation/apps) apps are booted before the session is
            * resolved, so IUserSession::getUser() is null there and a check at boot
            * time hides the entry from everyone. A closure is evaluated later, from
            * NavigationManager::init(), by which point the user is known.
            *
            * Declining is expressed as a type other than 'link' because
            * INavigationManager::add() has no way to say "no entry" — it reads
            * $entry['id'] straight off the return value, so null would be fatal — and
            * getAll('link'), which is what builds the app menu, filters on exactly
            * that field.
            */
            $navigationManager->add(function () use ($userSession, $db, $urlGenerator, $l10nFactory): array {
                $entry = [
                    'id'    => self::APP_ID,
                    'order' => 10,
                    'href'  => $urlGenerator->linkToRoute(self::APP_ID . '.page.index'),
                    'icon'  => $urlGenerator->imagePath(self::APP_ID, 'app.svg'),
                    'name'  => $l10nFactory->get(self::APP_ID)->t('Admin Page'),
                ];
                $user = $userSession->getUser();
                if ($user === null || !$this->isOrgAdmin($db, $user->getUID())) {
                    $entry['type'] = 'hidden';
                }
                return $entry;
            });
        });
    }

    private function isOrgAdmin(IDBConnection $db, string $uid): bool {
        $sql = "SELECT organization_id FROM *PREFIX*organization_members
                 WHERE user_uid = ? AND role = ? LIMIT 1";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([$uid, 'admin']);
            return $stmt->fetch() !== false;
        } catch (\Throwable $e) {
            // The organization app may not be installed on every instance this
            // runs on. Failing closed hides a button; failing open would show
            // one that leads to an empty page.
            return false;
        }
    }
}

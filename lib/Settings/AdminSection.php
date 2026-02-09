<?php

declare(strict_types=1);

namespace OCA\AdminPage\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {

    private IL10N $l;
    private IURLGenerator $urlGenerator;

    public function __construct(IL10N $l, IURLGenerator $urlGenerator) {
        $this->l = $l;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return string unique section id
     */
    public function getID(): string {
        return 'adminpage';
    }

    /**
     * @return string display name of the section
     */
    public function getName(): string {
        return $this->l->t('Admin Page');
    }

    /**
     * @return int priority (0-100)
     */
    public function getPriority(): int {
        return 50;
    }

    /**
     * @return string icon URL for the section
     */
    public function getIcon(): string {
        return $this->urlGenerator->imagePath('adminpage', 'app-dark.svg');
    }
}

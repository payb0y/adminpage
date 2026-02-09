<?php

declare(strict_types=1);

namespace OCA\AdminPage\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {

    private IL10N $l;

    public function __construct(IL10N $l) {
        $this->l = $l;
    }

    /**
     * @return TemplateResponse
     */
    public function getForm(): TemplateResponse {
        Util::addScript('adminpage', 'adminpage-main');
        return new TemplateResponse('adminpage', 'admin');
    }

    /**
     * @return string the section ID
     */
    public function getSection(): string {
        return 'adminpage';
    }

    /**
     * @return int priority (0-100)
     */
    public function getPriority(): int {
        return 50;
    }
}

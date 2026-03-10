<?php
/**
 * Admin Page - Public Dashboard Template
 *
 * Rendered for unauthenticated visitors with a valid public token.
 * Uses Nextcloud's 'public' base template (no top bar / sidebar).
 */
?>
<div id="app-content">
    <div id="adminpage-root" data-public="true" data-token="<?php p($_['token']); ?>"></div>
</div>

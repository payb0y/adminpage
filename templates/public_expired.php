<?php
/**
 * Admin Page - Public Link Expired / Invalid Template
 *
 * Shown when a public dashboard link is expired, revoked, or invalid.
 */
?>
<div id="app-content">
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:60vh;text-align:center;padding:40px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
        <h2 style="margin:16px 0 8px;color:#1a1a2e;font-size:20px;font-weight:600;">Link Unavailable</h2>
        <p style="color:#6b7280;font-size:14px;max-width:400px;">
            This public dashboard link is invalid, has expired, or has been revoked.<br/>
            Please contact the organization administrator for a new link.
        </p>
    </div>
</div>

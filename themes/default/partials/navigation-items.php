<?php
if (!isset($villaHasAuthLink)) {
    $villaHasAuthLink = false;
}

$renderVillaNav = function (array $entries) use (&$renderVillaNav, &$villaHasAuthLink, $user, $navAllowAuth): void {
    foreach ($entries as $entry) {
        $url = (string) ($entry['url'] ?? '/');
        $label = (string) ($entry['label'] ?? '');
        $target = (string) ($entry['target'] ?? '_self');
        $normalizedUrl = '/' . trim((string) (parse_url($url, PHP_URL_PATH) ?: $url), '/');
        $role = (string) ($user['role'] ?? '');

        // Account shortcuts are role-aware: administrators must never see a customer
        // account destination in the public header.
        if (!empty($user) && in_array($normalizedUrl, ['/account', '/account/bookings'], true)) {
            if (in_array($role, ['admin', 'super_admin'], true)) {
                $url = '/admin';
                $label = \App\Core\Translator::get('nav.admin');
            } elseif ($role === 'vendor') {
                $url = '/vendor';
                $label = \App\Core\Translator::get('nav.vendor');
            } else {
                $url = '/account/bookings';
                $label = \App\Core\Translator::get('nav.account');
            }
        }

        // Login/logout links are rendered by the shared header, not the menu.
        if (in_array(rtrim($url, '/'), ['/login', '/logout'], true)) {
            continue;
        }

        $hasChildren = !empty($entry['children']);
        $href = preg_match('~^https?://~i', $url) ? $url : app_url($url);

        echo '<div class="nav-entry' . ($hasChildren ? ' has-children' : '') . '">';
        echo '<div class="nav-entry-top"><a href="' . e($href) . '"'
            . ($target === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '')
            . '>' . e($label) . '</a>';

        if ($hasChildren) {
            echo '<button class="nav-submenu-toggle" type="button" aria-expanded="false" aria-label="'
                . e($label) . ' alt menüsünü aç">⌄</button>';
        }

        echo '</div>';

        if ($hasChildren) {
            echo '<div class="nav-dropdown">';
            $renderVillaNav($entry['children']);
            echo '</div>';
        }

        echo '</div>';
    }
};

$renderVillaNav($navItems ?? $navigation ?? []);

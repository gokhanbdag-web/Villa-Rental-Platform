<?php
if(!isset($villaHasAuthLink))$villaHasAuthLink=false;
$renderVillaNav = function(array $entries) use (&$renderVillaNav, &$villaHasAuthLink, $user, $navAllowAuth): void {
    foreach ($entries as $entry) {
        $url=(string)($entry['url']??'/');$label=(string)($entry['label']??'');$target=(string)($entry['target']??'_self');
        $normalizedUrl='/' . trim((string)(parse_url($url,PHP_URL_PATH)?:$url),'/');
        $role=(string)($user['role']??'');
        // Account shortcuts are role-aware: administrators must never see a customer
        // account destination in the public header.
        if(!empty($user)&&in_array($normalizedUrl,['/account','/account/bookings'],true)){
            if(in_array($role,['admin','super_admin'],true)){$url='/admin';$label='Yönetim Paneli';}
            elseif($role==='vendor'){$url='/vendor';$label='Ev Sahibi Paneli';}
            else{$url='/account/bookings';$label='Hesabım';}
        }
        $isLogin=in_array(rtrim($url,'/'),['/login','/logout'],true);if($isLogin)continue;
        $isAuthUser=!empty($user);
        $hasChildren=!empty($entry['children']);
        if($isLogin){$villaHasAuthLink=true;$url=$isAuthUser?(($user['role']??'')==='customer'?'/account/bookings':((($user['role']??'')==='vendor')?'/vendor':'/admin')):'#login';$label=$isAuthUser?(in_array(($user['role']??''),['admin','super_admin'],true)?'Yönetim Paneli':((($user['role']??'')==='vendor')?'Ev Sahibi Paneli':'Hesabım')):'Giriş Yap';}
        $href=preg_match('~^https?://~i',$url)?$url:app_url($url);
        echo '<div class="nav-entry'.($hasChildren?' has-children':'').'">';
        echo '<div class="nav-entry-top"><a '.($isLogin?'data-auth-link="1"':'').' '.(!$isAuthUser&&$isLogin?'data-login-trigger="1"':'').' href="'.e($isLogin&&!$isAuthUser?'#login':$href).'"'.($target==='_blank'?' target="_blank" rel="noopener noreferrer"':'').'>'.e($label).'</a>';
        if($hasChildren)echo '<button class="nav-submenu-toggle" type="button" aria-expanded="false" aria-label="'.e($label).' alt menüsünü aç">⌄</button>';
        echo '</div>';
        if($hasChildren){echo '<div class="nav-dropdown">';$renderVillaNav($entry['children']);echo '</div>';}
        echo '</div>';
    }
};
$renderVillaNav($navItems??$navigation??[]);


<?php
$renderVillaNav = function(array $entries) use (&$renderVillaNav, &$villaHasAuthLink, $user, $navAllowAuth): void {
    foreach ($entries as $entry) {
        $url=(string)($entry['url']??'/');$label=(string)($entry['label']??'');$target=(string)($entry['target']??'_self');
        $isLogin=in_array(rtrim($url,'/'),['/login'],true);if($isLogin&&!$navAllowAuth)continue;
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
if(!isset($villaHasAuthLink))$villaHasAuthLink=false;
$renderVillaNav($navItems??$navigation??[]);
if((!isset($navAllowAuth)||$navAllowAuth)&&!$villaHasAuthLink){
    $role=(string)($user['role']??'');$logged=!empty($user);
    $authUrl=$logged?($role==='customer'?'/account/bookings':($role==='vendor'?'/vendor':'/admin')):'#login';
    $authLabel=$logged?(in_array($role,['admin','super_admin'],true)?'Yönetim Paneli':($role==='vendor'?'Ev Sahibi Paneli':'Hesabım')):'Giriş Yap';
    echo '<div class="nav-entry"><div class="nav-entry-top"><a data-auth-link="1" '.(!$logged?'data-login-trigger="1"':'').' href="'.e($logged?app_url($authUrl):'#login').'">'.e($authLabel).'</a></div></div>';
}

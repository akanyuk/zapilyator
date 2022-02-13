<?php
/**
 * @var array $page
 * @var string $page_title
 * @var array $top_menu
 * @var array $sidebar_right
 */
reset($page);
 
NFW::i()->registerResource('bootstrap');
NFW::i()->registerFunction('page_is');

$sidebar = NFW::i()->fetch(NFW::i()->findTemplatePath('_admin_sidebar.tpl'));
if ($sidebar) {
	NFW::i()->registerResource('bootstrap.sidebar');
}

$sidebar_right = isset($sidebar_right) && $sidebar_right ? $sidebar_right : false;

// Generate `content` replacement - help
if ($page['is_welcome']) {
	$page['content'] = NFW::i()->fetch(NFW::i()->findTemplatePath('_admin_welcome.tpl'));
}

if (!empty(NFW::i()->breadcrumb)) {
	ob_start();

	echo '<div class="breadcrumb-status-xs">'.NFW::i()->breadcrumb_status.'</div>';
	echo '<ul class="breadcrumb">';
	echo '<div class="hidden-xs"><div class="pull-right">'.NFW::i()->breadcrumb_status.'</div></div>';
	
	foreach (NFW::i()->breadcrumb as $b) { 
		if (!isset($b['url']) || !$b['url']) {
			echo '<li class="active">'.$b['desc'].'</li>';
			continue;
		}
			
		$url = strstr($b['url'], 'http://') || strstr($b['url'], 'https://') ? $b['url'] : NFW::i()->base_path.$b['url'];
		echo '<li><a href="'.$url.'">'.$b['desc'].'</a></li>';
	}

	echo '</ul>';
 	
	$page['content'] = ob_get_clean().$page['content'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo NFW::i()->lang['lang']?>"><head><title><?php echo isset($page_title) ? $page_title : $page['title']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php echo isset(NFW::i()->cfg['admin']['head_content']) ? NFW::i()->cfg['admin']['head_content'] : ''?>
<style>
body { padding-top: 60px; }
.navbar .frontend { margin-left: 10px; width: 30px; height: 50px; max-width: 30px; max-height: 50px; background-color: #333; border-left: 1px solid #080808; }
.navbar .frontend A { padding: 30px; }
.navbar .frontend A:hover { background-color: #444; border-left: 1px solid #333; }
</style>
</head>
<body>
<nav class="navbar navbar-default navbar-fixed-top navbar-inverse">
	<div class="container-fluid">
      	<div class="hidden-sm hidden-md hidden-lg">
      		<div class="pull-right">
      			<a class="navbar-brand" href="/" title="Frontend"><span class="glyphicon glyphicon-home"></span></a>
      		</div>
      		<div class="pull-right">
				<p class="navbar-text"><small><?php echo NFW::i()->lang['LoggedAs']?> <a class="navbar-link" href="<?php echo NFW::i()->base_path?>admin/profile"><strong><?php echo htmlspecialchars(NFW::i()->user['username'])?></strong></a>. <a class="navbar-link" href="?action=logout"><?php echo NFW::i()->lang['Logout']?></a>.</small></p>
			</div>
		</div>
	
		<div class="navbar-header">
			<?php if ($sidebar): ?>
			<button type="button" class="navbar-toggle toggle-left hidden-lg" data-toggle="sidebar" data-target=".sidebar-left">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<?php endif; ?>
		</div>
	
		<div class="hidden-xs">
			<ul class="nav navbar-nav">
<?php $has_active = false; foreach ($top_menu as $m) {
	if (!$has_active && page_is($m['url'])) {
		$has_active = true;
		$is_active = true;
	}
	else {
		$is_active = false;
	}
	
	echo '<li'.($is_active ? ' class="active"' : '').'><a href="'.NFW::i()->absolute_path.'/admin/'.$m['url'].'">'.$m['name'].'</a></li>';
} ?>
			</ul> 
      	</div>
      	
      	<div class="hidden-xs">
      		<div class="navbar-right frontend"><a href="/" title="Frontend"></a></div>
			<p class="navbar-text navbar-right"><?php echo NFW::i()->lang['LoggedAs']?> <a class="navbar-link" href="<?php echo NFW::i()->base_path?>admin/profile"><strong><?php echo htmlspecialchars(NFW::i()->user['username'])?></strong></a>. <a class="navbar-link" href="?action=logout"><?php echo NFW::i()->lang['Logout']?></a>.</p>
		</div>
	</div>
</nav>

<div id="global-modal-container"></div>

<div class="container-fluid" style="padding-top: 20px; padding-bottom: 20px;">
<?php if ($sidebar && $sidebar_right): ?>
	<div class="row">
		<div class="col-sm-4 col-md-3 col-lg-2 sidebar sidebar-left sidebar-animate sidebar-lg-show hidden-print">
			<?php echo $sidebar; ?>
		</div>
		
		<div class="col-sm-8 col-md-9 col-lg-7 col-lg-offset-2"><?php echo $page['content']; ?></div>
		<div class="col-sm-4 col-md-3 col-lg-3 sidebar sidebar-right sidebar-white sidebar-lg-show sidebar-md-show sidebar-sm-show"><?php echo $sidebar_right ?></div>
	</div>
<?php elseif ($sidebar): ?>
	<div class="row">
		<div class="col-sm-5 col-md-3 col-lg-2 sidebar sidebar-left sidebar-animate sidebar-lg-show hidden-print"><?php echo $sidebar ?></div>
		<div class="col-lg-10 col-lg-offset-2"><?php echo $page['content']; ?></div>
	</div>
<?php else: echo $page['content']; endif; ?>
</div>
</body></html>
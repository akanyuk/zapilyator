<?php
/**
 * @var array $lang_admin
 * @var array $admin_help
 */
$lang_admin = NFW::i()->getLang('admin');
?>
<style>
.admin-index .record { padding: 0.5em 1em; margin-top: 1em; }
.admin-index .record:nth-child(odd) { background-color: #f8f8f8; }
.admin-index IMG.icon { -webkit-filter: grayscale(80%); filter: grayscale(80%); }
.admin-index .inner { margin-left: 80px; }
.admin-index H3 { margin-top: 0px; }
.admin-index P { margin-bottom: 5px; }
</style>
<div class="admin-index">
	<h1><?php echo $lang_admin['welcome']?></h1>
	<p><?php echo $lang_admin['welcome desc']?></p>

<?php 
foreach ($admin_help as $i) { 
	if (isset($i['perm'])) {	// Check permissions
		list($module, $action) = explode(',',$i['perm']);
		if (!NFW::i()->checkPermissions($module, $action)) continue;
	}
?>
	<div class="record">
		<?php if ($i['icon']): ?>
			<div class="pull-left"><a href="<?php echo NFW::i()->absolute_path.'/admin/'.$i['url']?>"><img src="<?php echo NFW::i()->assets($i['icon'])?>" class="icon" /></a></div>
		<?php endif; ?>
		
		<div class="inner">
			<h3><a href="<?php echo NFW::i()->absolute_path.'/admin/'.$i['url']?>"><?php echo $i['name']?></a></h3>
			<?php if ($i['desc']): ?>
			<p><?php echo $i['desc']?></p>
			<?php endif; ?>
		</div>
		<div class="clearfix"></div>
	</div>
<?php 
} 
?>
</div>
<script type="text/javascript">
$(document).ready(function(){
	$('#sidebar-menu').metisMenu();
});
</script>
<nav class="sidebar-nav">
	<ul class="metismenu" id="sidebar-menu">
		<li class="active">
			<a href="#" aria-expanded="true"><span class="sidebar-nav-item">Main menu</span><span class="fa arrow"></span></a>
			<ul aria-expanded="true">
				<li><a href="/"><span class="sidebar-nav-item-icon fa fa-home"></span> Home</a></li>
				<li><a href="/admin"><span class="sidebar-nav-item-icon fa fa-cogs"></span> Control panel</a></li>
				<li><a href="/admin/profile"><span class="sidebar-nav-item-icon fa fa-user"></span> Users porfile</a></li>
				<li><a href="?logout"><span class="sidebar-nav-item-icon fa fa-sign-out"></span> Logout</a></li>
			</ul>
		</li>
	</ul>
</nav>
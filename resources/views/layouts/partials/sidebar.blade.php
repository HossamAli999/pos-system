<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">

  <!-- sidebar: style can be found in sidebar.less -->
  <section class="sidebar">

	<a href="{{route('home')}}" class="logo">
		<span class="logo-mini"><i class="fas fa-layer-group"></i></span>
		<span class="logo-lg">{{ Session::get('business.name') }}</span>
	</a>

    <!-- Sidebar Menu -->
    {!! Menu::render('admin-sidebar-menu', 'adminltecustom'); !!}

    <!-- /.sidebar-menu -->

    <a href="#" class="sidebar-collapse-toggle" data-toggle="offcanvas" role="button">
        <i class="fas fa-angle-double-left"></i>
    </a>
  </section>
  <!-- /.sidebar -->
</aside>

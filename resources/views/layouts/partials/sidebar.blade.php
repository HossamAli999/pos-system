<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">

  <!-- sidebar: style can be found in sidebar.less -->
  <section class="sidebar">

	<a href="{{route('home')}}" class="logo">
		<span class="logo-mini"><i class="fas fa-layer-group"></i></span>
		<span class="logo-lg">{{ Session::get('business.name') }}</span>
	</a>

    <div class="sidebar-search">
        <i class="fas fa-search"></i>
        <input type="text" id="sidebar_menu_search" placeholder="@lang('lang_v1.search')" autocomplete="off">
    </div>

    <!-- Sidebar Menu -->
    {!! Menu::render('admin-sidebar-menu', 'adminltecustom'); !!}

    <p class="sidebar-search__empty" id="sidebar_menu_search_empty">@lang('lang_v1.no_menu_results_found')</p>

    <!-- /.sidebar-menu -->

    <a href="#" class="sidebar-collapse-toggle" data-toggle="offcanvas" role="button">
        <i class="fas fa-angle-double-left"></i>
    </a>
  </section>
  <!-- /.sidebar -->
</aside>

<script type="text/javascript">
// Vanilla JS on purpose: the sidebar renders before jQuery/vendor.js loads
// (those load at the end of the body), so this can't depend on jQuery.
(function(){
    var search = document.getElementById('sidebar_menu_search');
    var empty = document.getElementById('sidebar_menu_search_empty');
    var items = Array.prototype.slice.call(document.querySelectorAll('.sidebar-menu > li:not(.header)'));
    if (!search) { return; }

    search.addEventListener('input', function(){
        var term = search.value.trim().toLowerCase();
        var visibleCount = 0;

        if (term === '') {
            items.forEach(function(li){
                li.classList.remove('search-hide', 'search-match');
                var children = li.querySelectorAll('.treeview-menu > li');
                children.forEach(function(child){ child.classList.remove('search-hide'); });
            });
            empty.classList.remove('in');
            return;
        }

        items.forEach(function(li){
            var topLink = li.querySelector(':scope > a');
            var topText = topLink ? topLink.textContent.toLowerCase() : '';
            var children = li.querySelectorAll('.treeview-menu > li');
            var anyChildMatch = false;

            children.forEach(function(child){
                var match = child.textContent.toLowerCase().indexOf(term) !== -1;
                child.classList.toggle('search-hide', !match);
                if (match) { anyChildMatch = true; }
            });

            var isMatch = topText.indexOf(term) !== -1 || anyChildMatch;
            li.classList.toggle('search-hide', !isMatch);
            li.classList.toggle('search-match', isMatch && anyChildMatch);
            if (isMatch) { visibleCount++; }
        });

        empty.classList.toggle('in', visibleCount === 0);
    });
})();
</script>

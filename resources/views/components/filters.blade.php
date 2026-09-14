<div class="box filter-panel" id="accordion">
  <a class="filter-panel__header" data-toggle="collapse" data-parent="#accordion" href="#collapseFilter">
    <h3 class="filter-panel__title">
      @if(!empty($icon)) {!! $icon !!} @else <i class="fa fa-filter" aria-hidden="true"></i> @endif {{$title ?? ''}}
    </h3>
    <i class="fas fa-chevron-down filter-panel__caret"></i>
  </a>
  @php
    if(isMobile()) {
      $closed = true;
    }
  @endphp
  <div id="collapseFilter" class="panel-collapse active collapse @if(empty($closed)) in @endif" aria-expanded="true">
    <div class="box-body">
      {{$slot}}
    </div>
  </div>
</div>

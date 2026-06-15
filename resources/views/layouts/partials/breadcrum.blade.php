<ol class="breadcrumb df-breadcrumbs mg-b-10">
    @if(count(Request::segments()) > 1)
        <?php $segments = ''; ?>
        @foreach(Request::segments() as $index => $segment)
            <?php $segments .= '/' . $segment; ?>
            <li class="breadcrumb-item {{Request::segments()[count(Request::segments())-1] == $segment ? 'active' : ''}}" {{Request::segments()[count(Request::segments())-1] == $segment ? 'aria-current="page"' : ''}}>
                @if(Request::segments()[count(Request::segments())-1] == $segment or $index == 0)
                    {{$segment}}
                @else
                    <a href="{{ $segments }}">{{$segment}}</a>
                @endif
            </li>
        @endforeach
    @endif
</ol>

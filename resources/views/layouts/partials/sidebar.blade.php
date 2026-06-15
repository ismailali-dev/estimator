<aside class="aside aside-fixed">
    <div class="aside-header">
        <a style="font-size: 20px; letter-spacing: 0.5px;" href="{{route('dashboard')}}" class="aside-logo">EZ&nbsp;<span>Estimator</span></a>
        <a href="" class="aside-menu-link">
            <i data-feather="menu"></i>
            <i data-feather="x"></i>
        </a>
    </div>
    <div class="aside-body">
        <div class="aside-loggedin">
            <div class="d-flex align-items-center justify-content-start">
                <a href="{{route('dashboard')}}" class="avatar">



                    <img src="{{url(asset('assets/img/favicon.png'))}}" class="rounded-circle" alt=""></a>


                <div class="aside-alert-link">
                    <!--                    <a href="" class="new" data-toggle="tooltip" title="You have 2 unread messages"><i data-feather="message-square"></i></a>
                                        <a href="" class="new" data-toggle="tooltip" title="You have 4 new notifications"><i data-feather="bell"></i></a>-->
                    {{--                    <a href="" data-toggle="tooltip" title="Sign out"><i data-feather="log-out"></i></a>--}}
                </div>
            </div>
            <div class="aside-loggedin-user">
                <a href="#loggedinMenu" class="d-flex align-items-center justify-content-between mg-b-2"
                   data-toggle="collapse">
                    <h6 class="tx-semibold mg-b-0" style="text-transform: capitalize">{{Auth::user()->full_name}}</h6>
                    {{--                    <i data-feather="chevron-down"></i>--}}
                </a>
                <p class="tx-color-03 tx-12 mg-b-0">{{Auth::user()->company_name}}</p>
            </div>
            <!--            <div class="collapse" id="loggedinMenu">
                            <ul class="nav nav-aside mg-b-0">
                                <li class="nav-item"><a href="" class="nav-link"><i data-feather="edit"></i> <span>Edit Profile</span></a></li>
                                <li class="nav-item"><a href="" class="nav-link"><i data-feather="user"></i> <span>View Profile</span></a></li>
                                <li class="nav-item"><a href="" class="nav-link"><i data-feather="settings"></i> <span>Account Settings</span></a></li>
                                <li class="nav-item"><a href="" class="nav-link"><i data-feather="help-circle"></i> <span>Help Center</span></a></li>
                                <li class="nav-item"><a href="" class="nav-link"><i data-feather="log-out"></i> <span>Sign Out</span></a></li>
                            </ul>
                        </div>-->
        </div><!-- aside-loggedin -->
        <ul class="nav nav-aside">
            @foreach(config('side-menu') as $menuItem)
                <?php
                $menuItem['isActive'] = false;
                $parentClasses = ["nav-item"];
                $routeName = \Request::route()->getName();
                $routeName = explode(".", $routeName);
                $routeName = implode(".", array_slice($routeName, 0, 3));
                if (is_array($menuItem['child'])) {
                    $parentClasses[] = "with-sub";
                    $checkSubItemsActive = array_search($routeName, array_map(function (array $value) {
                        return $value['child'];
                    }, $menuItem['child']));
                    if ($checkSubItemsActive !== false) {
                        $parentClasses[] = "active";
                        $parentClasses[] = "show";
                    }
                } elseif ($routeName == $menuItem['child']) {
                    $parentClasses[] = "active";
                    $menuItem['isActive'] = true;
                }
                ?>
                <li style="cursor: pointer" class="{{implode(" ",$parentClasses)}}">
                    <a {{!is_array($menuItem['child']) ? 'href='.route($menuItem['child']).'' : ''}} class="nav-link"><i
                            data-feather="{{$menuItem['icon']}}"></i> <span>{{$menuItem['title']}}</span></a>
                    @if(is_array($menuItem['child']))
                        <ul>
                            @foreach($menuItem['child'] as $child)
                                <li class="{{$routeName == $child['child'] ? 'active' : ''}}">
                                    @if(!empty($child['subscription']))
                                        @isSubscribed
                                        <a href="{{route($child['child'])}}">{{$child['title']}}</a>
                                        @endisSubscribed
                                        @isNotSubscribed
                                        <a href="#" data-toggle="modal" data-target="#subscriptionExpiredPopup">{{$child['title']}}</a>
                                        @endisNotSubscribed
                                    @else
                                        <a href="{{route($child['child'])}}">{{$child['title']}}</a>

                                    @endif
                                </li>
                                @if($routeName == $child['child'])
                                    @push('scripts')
                                        <script>
                                            document.title = "<?php echo $child['title'] ?> | " + document.title;
                                        </script>
                                    @endpush
                                @endif
                            @endforeach
                        </ul>
                    @elseif($menuItem['isActive'])
                        @push('scripts')
                            <script>
                                document.title = "<?php echo $menuItem['title'] ?> | " + document.title;
                            </script>
                        @endpush
                    @endif
                </li>
            @endforeach

            <li hidden class="nav-label mg-t-25">Settings</li>
            @company
            <li hidden class="nav-item"><a href="#" class="nav-link"><i
                        data-feather="hash"></i> <span>Company</span></a></li>
            @endcompany

            @admin
            <li hidden class="nav-item"><a href="#" class="nav-link"><i
                        data-feather="hash"></i> <span>Company</span></a></li>
            @endadmin

            <li hidden class="nav-item"><a href="#" class="nav-link"><i
                        data-feather="user-check"></i> <span>Profile</span></a></li>

            @admin
            <li hidden class="nav-label mg-t-25">Administrator</li>
            <li hidden class="nav-item"><a href="#" class="nav-link"><i
                        data-feather="home"></i> <span>Companies</span></a></li>
            <li hidden class="nav-item"><a href="#" class="nav-link"><i data-feather="home"></i>
                    <span>Users</span></a></li>
            @endadmin

            <li hidden class="nav-label mg-t-25">Contact Us</li>
            <li hidden class="nav-item"><a href="tel:+923171015636" class="nav-link"><i data-feather="phone-call"></i> <span>+92 317 1015636</span></a>
            </li>


        </ul>
    </div>
</aside>

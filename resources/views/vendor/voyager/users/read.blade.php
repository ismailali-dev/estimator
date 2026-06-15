@extends('voyager::master')

@section('page_title', __('voyager::generic.view').' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i> {{ __('voyager::generic.viewing') }} {{ ucfirst($dataType->getTranslatedAttribute('display_name_singular')) }} &nbsp;

        @can('edit', $dataTypeContent)
            <a href="{{ route('voyager.'.$dataType->slug.'.edit', $dataTypeContent->getKey()) }}" class="btn btn-info">
                <i class="glyphicon glyphicon-pencil"></i> <span class="hidden-xs hidden-sm">{{ __('voyager::generic.edit') }}</span>
            </a>
        @endcan
        @can('delete', $dataTypeContent)
            @if($isSoftDeleted)
                <a href="{{ route('voyager.'.$dataType->slug.'.restore', $dataTypeContent->getKey()) }}" title="{{ __('voyager::generic.restore') }}" class="btn btn-default restore" data-id="{{ $dataTypeContent->getKey() }}" id="restore-{{ $dataTypeContent->getKey() }}">
                    <i class="voyager-trash"></i> <span class="hidden-xs hidden-sm">{{ __('voyager::generic.restore') }}</span>
                </a>
            @else
                <a href="javascript:;" title="{{ __('voyager::generic.delete') }}" class="btn btn-danger delete" data-id="{{ $dataTypeContent->getKey() }}" id="delete-{{ $dataTypeContent->getKey() }}">
                    <i class="voyager-trash"></i> <span class="hidden-xs hidden-sm">{{ __('voyager::generic.delete') }}</span>
                </a>
            @endif
        @endcan
        @can('browse', $dataTypeContent)
        <a href="{{ route('voyager.'.$dataType->slug.'.index') }}" class="btn btn-warning">
            <i class="glyphicon glyphicon-list"></i> <span class="hidden-xs hidden-sm">{{ __('voyager::generic.return_to_list') }}</span>
        </a>
        @endcan
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')


<style>
 .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        .profile-card {
            padding: 10px;
        }
        .profile-card table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .profile-card table th,
        .profile-card table td {
            width: 50%;
            padding: 10px 12px;
            vertical-align: top;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .profile-card table th {
            font-size: 15px;
            font-weight: 700;
            color: #222;
        }
        .profile-card table td {
            font-size: 15px;
            font-weight: 500;
            color: #555;
        }
        .profile-card table th + th,
        .profile-card table td + td {
            padding-left: 24px;
        }
        .summary-card {
            display: flex;
            justify-content: space-between;
        }
        .summary-item {
            flex: 1;
            margin: 5px;
            text-align: center;
            padding: 10px;
            border-radius: 5px;
        }
        .summary-item:nth-child(1) { background-color: #ffeeba; }
        .summary-item:nth-child(2) { background-color: #d4edda; }
        .summary-item:nth-child(3) { background-color: #cce5ff; }
        .summary-item:nth-child(4) { background-color: #f8d7da; }
        .summary-item:nth-child(5) { background-color: #fff3cd; }
        .subscription-list, .user-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .list-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .list-item:last-child {
            border-bottom: none;
        }

    .borderless td, .borderless th {
    border: none !important;
}

.profileImage {
    text-align: center;
    padding: 42px;
}

.profileImage img {
    border-radius: 50%;
    width: 150px !important;
}

.company-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

    <div class="page-content read container-fluid">
        
   
            <!-- Profile Section -->
            <div class="col-md-4">
                <div class="card profile-card">
                    <div class="profileImage">
                         @if(isset($dataTypeContent->invoiceSetting->logo))
                                        <img src="{{ $dataTypeContent->invoiceSetting->full_logo }}" 
                                              >
                                    @else
                                    
                                    <i class="voyager-person" style=" color: #cce5ff; font-size: 140px;
"></i>
                                       
                                    @endif
                    </div>
                   
                                    
                    
                    <div class="card profile-card">
               <table class="table-borderless">
                    <tr>
                        <td><strong>First Name</strong></td>
                        <td><strong>Last Name</strong></td>
                    </tr>
                    <tr>
                        <th>{{$dataTypeContent->first_name ?? 'NA'}}</th>
                        <th>{{$dataTypeContent->last_name ?? 'NA'}}</th>
                    </tr>
                    <tr>
                        <td><strong>Email Address</strong></td>
                        <td><strong>Phone Number</strong></td>
                    </tr>
                    <tr>
                        <th>{{$dataTypeContent->email ?? 'NA'}}</th>
                        <th>{{$dataTypeContent->phone ?? 'NA'}}</th>
                    </tr>
                    <tr>
                        <td><strong>Company</strong></td>
                        <td><strong>Job Title</strong></td>
                    </tr>
                    <tr>
                        <th class="company-name">{{$dataTypeContent->company->name ?? 'NA'}}</th>
                        <th>{{$dataTypeContent->position ?? 'NA'}}</th>
                    </tr>
                    <tr>
                        <td><strong>Address</strong></td>
                        <td><strong>City</strong></td>
                    </tr>
                    <tr>
                        <th>{{$dataTypeContent->company->address ?? 'NA'}}</th>
                        <th>{{$dataTypeContent->userAddress->city ?? 'NA'}}</th>
                    </tr>
                    <tr>
                        <td><strong>State</strong></td>
                        <td><strong>Zip Code</strong></td>
                    </tr>
                    <tr>
                        <th>{{$dataTypeContent->userAddress->state_id ?? 'NA'}}</th>
                        <th>{{$dataTypeContent->userAddress->zip_code ?? 'NA'}}</th>
                    </tr>
                    <tr>
                        <td><strong>License Number</strong></td>
                        
                    </tr>
                    <tr>
                        <th>{{$dataTypeContent->company->license_no ?? 'NA'}}</th>
                       
                    </tr>
                    
                </table>
            
            </div>
                </div>
            </div>
    
            <!-- Summary Section -->
            <div class="col-md-8">
                
                <div class="row">
                    
                    <div class="col-md-12 card" style="margin-bottom: 15px;">
                        
                       <div class="summary-card">
                    <div class="summary-item">
                        
                        @php
                        $EstimatesCount = 0;
                         if ($dataTypeContent->is_admin == 1) {
            
                            if(($dataTypeContent->hasSubscription('multi_user_access')) ) {
                                $childUserIds = \App\Models\User::withTrashed()->where('parent_id', $dataTypeContent->id)->pluck('id')->toArray();
                            }
                            else{
                                $childUserIds = \App\Models\User::where('parent_id', $dataTypeContent->id)->pluck('id')->toArray();
                            }
                        
                            // Include both the current user and child users in the estimates query
                            $userIds = array_merge([$dataTypeContent->id], $childUserIds);
                            $EstimatesCount = \App\Models\Estimate::whereIn("user_id", $userIds)->count();
                        } 
        
                        @endphp
                        
                        <h4>{{ $EstimatesCount }}</h4>
                        <p>Total Estimates</p>
                    </div>
                    <div class="summary-item">
                        <h4>{{ \App\Models\Template::where('company_id', $dataTypeContent->company_id)->count() }}</h4>
                        <p>Total Templates</p>
                    </div>
                    <div class="summary-item">
                        <h4>{{ \App\Models\Code::where('company_id', $dataTypeContent->company_id)->withoutTrashed()->count() }}</h4>
                        <p>Total Codes</p>
                    </div>
                    <div class="summary-item">
                        <h4>{{ \App\Models\Customer::where('user_id', $dataTypeContent->id)->count() }}</h4>
                        <p>Total Customers</p>
                    </div>
                    <div class="summary-item">
                        <h4>{{ \App\Models\Supplier::where('user_id', $dataTypeContent->id)->count() }}</h4>
                        <p>Total Suppliers</p>
                    </div>
                </div>
                    
                    </div>
                    <!-- Subscriptions and Users -->
                    <div class="col-md-12" style="padding-right: 0px !important;
    padding-left: 6px !important;">
                        
                         <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                     <h4>Current Subscriptions</h4>
                                        <div class="subscription-list">
                                            @foreach (\App\Models\Subscription::where('user_id', $dataTypeContent->id)->latest()->take(10)->get() as $subscription)
                                            <div class="list-item">
                                                <span>{{ $subscription->title }}</span>
                                                <span>End Date: {{ \Carbon\Carbon::parse($subscription->renewable_date)->format('M d, Y') }}</span>
                                            </div>
                                            @endforeach
                                        </div>
                                </div>
                               
                            </div>
        
                            <div class="col-md-6">
                                <div class="card">
                                    <h4>Recently Added Users</h4>
                                    <div class="user-list">
                                         @foreach (\App\Models\User::where('parent_id', $dataTypeContent->id)->latest()->take(10)->get() as $user)
                    <div class="list-item">
                        <span>{{ @$user->first_name." ".@$user->last_name }}</span>
                        <span>{{ $user->created_at->format('M d, Y') }}</span>
                    </div>
                    @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                
                </div>

            </div>
    
      
        
    </div>

    {{-- Single delete modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> {{ __('voyager::generic.delete_question') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}?</h4>
                </div>
                <div class="modal-footer">
                    <form action="{{ route('voyager.'.$dataType->slug.'.index') }}" id="delete_form" method="POST">
                        {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm"
                               value="{{ __('voyager::generic.delete_confirm') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@stop

@section('javascript')
    @if ($isModelTranslatable)
        <script>
            $(document).ready(function () {
                $('.side-body').multilingual();
            });
        </script>
    @endif
    <script>
        var deleteFormAction;
        $('.delete').on('click', function (e) {
            var form = $('#delete_form')[0];

            if (!deleteFormAction) {
                // Save form action initial value
                deleteFormAction = form.action;
            }

            form.action = deleteFormAction.match(/\/[0-9]+$/)
                ? deleteFormAction.replace(/([0-9]+$)/, $(this).data('id'))
                : deleteFormAction + '/' + $(this).data('id');

            $('#delete_modal').modal('show');
        });

    </script>
@stop
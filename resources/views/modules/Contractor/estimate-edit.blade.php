@extends('layouts.app')
@push("style")
<style>
    button.fancybox-button.fancybox-close-small{
        display:none !important;
    }
    .custom-modal{
        padding: 0 !important;
        background: transparent !important;
        cursor:auto !important;
    }
</style>
@endpush
@section('content')

    <div class="card card-body">
        <div style="display: flex" class="mb-3">
            <div style="flex: 1">
                <h4 id="section1" class="mg-b-10">{{$contractor->full_name}}  Estimate</h4>
            </div>
        </div>
        @include('layouts.partials.flash_message')

        <form action="{{route('module.'.$moduleName.'.estimate.update', ["userId"=>$contractor->id, "estimateId"=>$estimate->id])}}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="put" />
            <input type="hidden" name="id" value="{{$estimate->id}}" />
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="title">Scope <span class="tx-danger">*</span></label>
                    <input type="text" name="estimate_scope" value="{{$estimate->estimate_scope}}" required class="form-control @error('estimate_scope') is-invalid @enderror" id="estimate_scope" placeholder="Please enter scope" required>
                    @error('estimate_scope')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-md-4">
                    <label for="link">Date <span class="tx-danger">*</span></label>
                    <input type="text" name="estimate_date" readonly value="{{$estimate->estimate_date}}" required class="form-control @error('estimate_date') is-invalid @enderror" id="estimate_date" placeholder="Please select date">
                    @error('estimate_date')
                    <div class="tx-danger">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group col-md-4">
                    <label for="link">Customer <span class="tx-danger" hidden>*</span></label>
                    <input type="text" readonly name="customer" value="{{$estimate->customer->full_name}}"  class="form-control id="customer">

                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-4">
                            <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>LBR</th>
                                <th>MTRL</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php($sno = 1)
                            @php($modals = [])
                            @foreach($sheet as $item)
                                <tr>
                                    <td>{{$sno}}</td>
                                    <td><a data-fancybox data-src="#hidden-content-{{$item->id}}" data-id="{{$item->Code->id}}" href="javascript:;">{{$item->Code->code_name}}</a>


                                        <div style="display: none;" class="custom-modal" id="hidden-content-{{$item->id}}">
                                            <div class="modal-dialog modal-dialog-centered" style="margin: 0; min-height: unset" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="code_title_">{{$item->Code->code_name}}</h5>

                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <textarea class="form-control" id="code_description_{{$item->id}}" data-id="{{$item->Code->id}}" style="min-width: 300px;" rows="10">{!! $item->Code->description !!}</textarea>
                                                        </div>


                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" name="btnClose" class="btn" ><i class="flaticon-cancel-12"></i> Close</button>
                                                        <button type="button" name="btnUpdate" data-id="{{$item->id}}" onclick="update_code_description({{$item->id}}, '{{route('module.'.$moduleName.'.estimate.code_update', ["userId"=>$contractor->id, 'codeId'=>$item->Code->id])}}', '{{csrf_token()}}', this)" class="btn btn-primary">Update</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @php($modals[] = $item->id)
                                    <input type="hidden" name="sheet_id[]" value="{{$item->id}}">
                                        <input type="hidden" name="code_id[]" value="{{$item->Code->id}}">
                                    </td>
                                    <td><input type="text" class="form-control two-digit" name="quantity[]" value="{{$item->quantity}}"></td>
                                    <td>{{$item->unit}}</td>
                                    <td><input type="text" class="form-control two-digit" name="labor_cost[]" value="{{$item->labor_cost}}"></td>
                                    <td><input type="text" class="form-control two-digit" name="material_cost[]" value="{{$item->material_cost}}"></td>
                                    <td>
                                        <a href="javascript:void(0);" onclick="delete_estimate_sheet_item({{$item->id}}, '{{route('module.contractor.estimate.sheet.item.delete', ["userId"=>$contractor->id, "estimateId"=>$estimate->id])}}', '{{csrf_token()}}', this)" class="text-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                @php($sno++)
                            @endforeach
                            </tbody>
                        </table>
                    </div>


                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-12">


                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <button type="reset" class="btn btn-light">Reset</button>
        </form>
    </div>
    @foreach($modals as $v)
        <div class="modal fade" id="code_modal_{{$v}}" tabindex="-1" role="dialog" aria-labelledby="code_title_{{$v}}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="code_title_{{$v}}">Vertically Aligned</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <h4 class="modal-heading mb-4 mt-2">Aligned Center</h4>
                        <p class="modal-text">In hac habitasse platea dictumst. Proin sollicitudin et lacus in tincidunt. Integer nisl ex, sollicitudin eget nulla nec, pharetra lacinia nisl. Aenean nec nunc ex. Integer varius neque at dolor scelerisque porttitor.</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                        <button type="button" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
@push("scripts")

    <script>
        $(function(){
            $( "#estimate_date" ).datepicker();
        });
    </script>
@endpush

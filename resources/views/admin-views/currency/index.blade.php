@extends('layouts.admin.app')

@section('title', translate('Currency Management'))

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{translate('Currency Management')}}</h1>
            </div>
            <div class="col-sm-auto">
                <a class="btn btn-primary" href="{{route('admin.currency.create')}}">
                    <i class="tio-add-circle"></i>
                    {{translate('Add New Currency')}}
                </a>
            </div>
        </div>
    </div>

    <!-- Card -->
    <div class="card">
        <!-- Header -->
        <div class="card-header">
            <div class="row justify-content-between align-items-center flex-grow-1">
                <div class="col-12 col-md-6 mb-3 mb-md-0">
                    <h5 class="card-header-title">
                        {{translate('Currency List')}} 
                        <span class="badge badge-soft-dark ml-2">{{$currencies->total()}}</span>
                    </h5>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive datatable-custom">
            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>{{translate('SL')}}</th>
                        <th>{{translate('Currency Name')}}</th>
                        <th>{{translate('Code')}}</th>
                        <th>{{translate('Symbol')}}</th>
                        <th>{{translate('Exchange Rate')}}</th>
                        <th>{{translate('Status')}}</th>
                        <th>{{translate('Primary')}}</th>
                        <th class="text-center">{{translate('Action')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($currencies as $key => $currency)
                    <tr>
                        <td>{{$currencies->firstitem()+$key}}</td>
                        <td>
                            <span class="d-block font-size-sm text-body">
                                {{$currency->name}}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-soft-secondary">{{$currency->code}}</span>
                        </td>
                        <td>{{$currency->symbol}}</td>
                        <td>{{number_format($currency->exchange_rate, 4)}}</td>
                        <td>
                            @if($currency->is_active)
                                <span class="badge badge-success">{{translate('Active')}}</span>
                            @else
                                <span class="badge badge-danger">{{translate('Inactive')}}</span>
                            @endif
                        </td>
                        <td>
                            @if($currency->is_primary)
                                <span class="badge badge-primary">{{translate('Primary')}}</span>
                            @else
                                <form action="{{route('admin.currency.set-primary', $currency->id)}}" method="post" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary btn-sm" 
                                            onclick="return confirm('{{translate('Set as primary currency?')}}')">
                                        {{translate('Set Primary')}}
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <div class="dropdown">
                                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="tio-more-horizontal"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="{{route('admin.currency.edit', $currency->id)}}">
                                        <i class="tio-edit"></i> {{translate('Edit')}}
                                    </a>
                                    @if(!$currency->is_primary)
                                        <form action="{{route('admin.currency.toggle-status', $currency->id)}}" method="post">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="tio-toggle"></i> 
                                                {{$currency->is_active ? translate('Deactivate') : translate('Activate')}}
                                            </button>
                                        </form>
                                        <div class="dropdown-divider"></div>
                                        <form action="{{route('admin.currency.delete', $currency->id)}}" method="post" 
                                              onsubmit="return confirm('{{translate('Are you sure you want to delete this currency?')}}')">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="tio-delete-outlined"></i> {{translate('Delete')}}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="card-footer">
            {{$currencies->links()}}
        </div>
    </div>
</div>
@endsection
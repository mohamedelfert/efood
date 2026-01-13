<table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100" id="branchOrderTable">
    <thead class="thead-light">
        <tr>
            <th>{{translate('SL')}}</th>
            <th>{{translate('Order ID')}}</th>
            <th>{{translate('Branch')}}</th>
            <th>{{translate('Customer')}}</th>
            <th>{{translate('Date')}}</th>
            <th>{{translate('Amount')}}</th>
            <th>{{translate('Payment')}}</th>
            <th>{{translate('Status')}}</th>
            <th class="text-center">{{translate('Action')}}</th>
        </tr>
    </thead>
    <tbody>
    @if(isset($orders) && count($orders) > 0)
        @foreach($orders as $key => $order)
        <tr>
            <td>{{$key + 1}}</td>
            <td>
                <a href="{{route('admin.orders.details',['id'=>$order['id']])}}" class="text-primary font-weight-bold">
                    #{{$order['id']}}
                </a>
            </td>
            <td>
                @if($order->branch)
                    <span class="badge badge-soft-info">{{$order->branch->name}}</span>
                @else
                    <span class="badge badge-soft-secondary">N/A</span>
                @endif
            </td>
            <td>
                @if($order->customer)
                    <div>
                        <div class="font-weight-semibold">{{$order->customer->f_name}} {{$order->customer->l_name}}</div>
                        <div class="text-muted small">{{$order->customer->phone}}</div>
                    </div>
                @else
                    <span class="badge badge-danger">{{translate('Invalid Customer')}}</span>
                @endif
            </td>
            <td>{{date('d M Y, h:i A', strtotime($order['created_at']))}}</td>
            <td>
                <div class="font-weight-bold">{{\App\CentralLogics\Helpers::set_symbol($order['order_amount'])}}</div>
            </td>
            <td>
                @if($order->payment_method == 'cash_on_delivery')
                    <span class="badge badge-soft-warning">{{translate('COD')}}</span>
                @else
                    <span class="badge badge-soft-success">{{translate($order->payment_method)}}</span>
                @endif
            </td>
            <td>
                @if($order->order_status == 'pending')
                    <span class="badge badge-soft-info">{{translate('pending')}}</span>
                @elseif($order->order_status == 'confirmed')
                    <span class="badge badge-soft-primary">{{translate('confirmed')}}</span>
                @elseif($order->order_status == 'processing')
                    <span class="badge badge-soft-warning">{{translate('processing')}}</span>
                @elseif($order->order_status == 'out_for_delivery')
                    <span class="badge badge-soft-info">{{translate('out for delivery')}}</span>
                @elseif($order->order_status == 'delivered')
                    <span class="badge badge-soft-success">{{translate('delivered')}}</span>
                @elseif($order->order_status == 'returned')
                    <span class="badge badge-soft-danger">{{translate('returned')}}</span>
                @elseif($order->order_status == 'failed')
                    <span class="badge badge-soft-danger">{{translate('failed')}}</span>
                @elseif($order->order_status == 'canceled')
                    <span class="badge badge-soft-dark">{{translate('canceled')}}</span>
                @endif
            </td>
            <td class="text-center">
                <a class="btn btn-sm btn-outline-primary square-btn" href="{{route('admin.orders.details',['id'=>$order['id']])}}">
                    <i class="tio-visible"></i>
                </a>
            </td>
        </tr>
        @endforeach
    @else
        <tr>
            <td colspan="9">
                <div class="text-center p-4">
                    <img class="mb-3" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="No data" style="width: 7rem;">
                    <p class="mb-0">{{translate('No data to show')}}</p>
                </div>
            </td>
        </tr>
    @endif
    </tbody>
</table>

<script>
    $(document).ready(function() {
        $('#branchOrderTable').DataTable({
            "pageLength": 25,
            "ordering": true,
            "searching": true,
            "order": [[0, "desc"]],
            "language": {
                "emptyTable": "{{translate('no_data_available')}}",
                "info": "{{translate('showing')}} _START_ {{translate('to')}} _END_ {{translate('of')}} _TOTAL_ {{translate('entries')}}",
                "infoEmpty": "{{translate('showing')}} 0 {{translate('to')}} 0 {{translate('of')}} 0 {{translate('entries')}}",
                "infoFiltered": "({{translate('filtered_from')}} _MAX_ {{translate('total_entries')}})",
                "lengthMenu": "{{translate('show')}} _MENU_ {{translate('entries')}}",
                "loadingRecords": "{{translate('loading')}}...",
                "processing": "{{translate('processing')}}...",
                "search": "{{translate('search')}}:",
                "zeroRecords": '<div class="text-center p-4"><img class="mb-3" src="{{asset("public/assets/admin")}}/svg/illustrations/sorry.svg" style="width: 7rem;"><p class="mb-0">{{translate("No data to show")}}</p></div>',
                "paginate": {
                    "first": "{{translate('first')}}",
                    "last": "{{translate('last')}}",
                    "next": "{{translate('next')}}",
                    "previous": "{{translate('previous')}}"
                }
            }
        });
    });
</script>
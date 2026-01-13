<link rel="stylesheet" href="https://cdn.datatables.net/1.11.1/css/jquery.dataTables.min.css">

<table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 mt-3" id="datatable">
    <thead class="thead-light">
        <tr>
            <th>{{translate('SL')}} </th>
            <th>{{translate('order')}}</th>
            <th>{{translate('date')}}</th>
            <th>{{translate('qty')}}</th>
            <th>{{translate('amount')}}</th>
        </tr>
    </thead>
    <tbody>
    @foreach($data as $key=>$row)
        <tr>
            <td>{{$key+1}}</td>
            <td>
                <a href="{{route('admin.orders.details',['id'=>$row['order_id']])}}">{{$row['order_id']}}</a>
            </td>
            <td>{{date('d M Y',strtotime($row['date']))}}</td>
            <td>{{$row['quantity']}}</td>
            <td>{{ \App\CentralLogics\Helpers::set_symbol($row['price']) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<script type="text/javascript">
    $(document).ready(function () {
        $('input').addClass('form-control');
        
        var datatable = $('#datatable').DataTable({
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
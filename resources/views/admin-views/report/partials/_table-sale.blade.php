<table class="table" id="datatable">
    <thead class="thead-light">
    <tr>
        <th>{{translate('#')}} </th>
        <th>{{translate('order')}}</th>
        <th>{{translate('date')}}</th>
        <th>{{translate('qty')}}</th>
        <th>{{translate('amount')}}</th>
    </tr>
    </thead>
    <tbody>
    @if(isset($data))
        @foreach($data as $key=>$row)
            <tr>
                <td>{{$key+1}}</td>
                <td class="table-column-pl-0">
                    <a href="{{route('admin.orders.details',['id'=>$row['order_id']])}}">{{$row['order_id']}}</a>
                </td>
                <td>{{date('d M Y',strtotime($row['date']))}}</td>
                <td>{{$row['quantity']}}</td>
                <td>{{ \App\CentralLogics\Helpers::set_symbol($row['price']) }}</td>
            </tr>
        @endforeach
    @else
        <tr>
            <td colspan="5">
                <div class="text-center p-4">
                    <img class="mb-3" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="Image Description" style="width: 7rem;">
                    <p class="mb-0">{{translate('No data to show')}}</p>
                </div>
            </td>
        </tr>
    @endif
    </tbody>
</table>

<script type="text/javascript">
    $(document).ready(function () {
        $('input').addClass('form-control');
    });

    var datatable = $.HSCore.components.HSDatatables.init($('#datatable'), {
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                className: 'd-none'
            },
            {
                extend: 'excel',
                className: 'd-none'
            },
            {
                extend: 'csv',
                className: 'd-none'
            },
            {
                extend: 'pdf',
                className: 'd-none'
            },
            {
                extend: 'print',
                className: 'd-none'
            },
        ],
        select: {
            style: 'multi',
            selector: 'td:first-child input[type="checkbox"]',
            classMap: {
                checkAll: '#datatableCheckAll',
                counter: '#datatableCounter',
                counterInfo: '#datatableCounterInfo'
            }
        },
        language: {
            "emptyTable": "{{translate('no_data_available')}}",
            "info": "{{translate('showing')}} _START_ {{translate('to')}} _END_ {{translate('of')}} _TOTAL_ {{translate('entries')}}",
            "infoEmpty": "{{translate('showing')}} 0 {{translate('to')}} 0 {{translate('of')}} 0 {{translate('entries')}}",
            "infoFiltered": "({{translate('filtered_from')}} _MAX_ {{translate('total_entries')}})",
            "lengthMenu": "{{translate('show')}} _MENU_ {{translate('entries')}}",
            "loadingRecords": "{{translate('loading')}}...",
            "processing": "{{translate('processing')}}...",
            "search": "{{translate('search')}}:",
            "zeroRecords": '<div class="text-center p-4"><img class="mb-3" src="{{asset("public/assets/admin")}}/svg/illustrations/sorry.svg" alt="Image Description" style="width: 7rem;"><p class="mb-0">{{translate("No data to show")}}</p></div>',
            "paginate": {
                "first": "{{translate('first')}}",
                "last": "{{translate('last')}}",
                "next": "{{translate('next')}}",
                "previous": "{{translate('previous')}}"
            }
        }
    });

    $('#export-copy').click(function () {
        datatable.button('.buttons-copy').trigger()
    });

    $('#export-excel').click(function () {
        datatable.button('.buttons-excel').trigger()
    });

    $('#export-csv').click(function () {
        datatable.button('.buttons-csv').trigger()
    });

    $('#export-pdf').click(function () {
        datatable.button('.buttons-pdf').trigger()
    });

    $('#export-print').click(function () {
        datatable.button('.buttons-print').trigger()
    });

    $('.js-datatable-filter').on('change', function () {
        var $this = $(this),
            elVal = $this.val(),
            targetColumnIndex = $this.data('target-column-index');

        datatable.column(targetColumnIndex).search(elVal).draw();
    });

    $('#datatableSearch').on('search', function () {
        datatable.search('').draw();
    });
</script>
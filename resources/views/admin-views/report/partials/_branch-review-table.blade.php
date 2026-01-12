@if(isset($reviews) && count($reviews) > 0)
<table class="table table-bordered table-hover table-striped">
    <thead class="thead-light">
        <tr>
            <th>{{translate('SL')}}</th>
            <th>{{translate('Review ID')}}</th>
            <th>{{translate('Customer')}}</th>
            <th>{{translate('Branch')}}</th>
            <th>{{translate('Order ID')}}</th>
            <th>{{translate('Rating')}}</th>
            <th>{{translate('Comment')}}</th>
            <th>{{translate('Images')}}</th>
            <th>{{translate('Date')}}</th>
        </tr>
    </thead>
    <tbody>
    @foreach($reviews as $k => $review)
        <tr>
            <td>{{ $k + 1 }}</td>
            <td>{{ $review->id }}</td>
            <td>
                @if($review->customer)
                    {{ $review->customer->f_name }} {{ $review->customer->l_name }}
                    <br>
                    <small class="text-muted">{{ $review->customer->phone }}</small>
                @else
                    <span class="badge badge-soft-danger">{{translate('Customer Deleted')}}</span>
                @endif
            </td>
            <td>
                @if($review->branch)
                    {{ $review->branch->name }}
                @else
                    <span class="text-muted">N/A</span>
                @endif
            </td>
            <td>
                <a href="{{ route('admin.orders.details', $review->order_id) }}" target="_blank">
                    #{{ $review->order_id }}
                </a>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <span class="badge badge-soft-warning">
                        {{ number_format($review->rating, 1) }} <i class="tio-star"></i>
                    </span>
                </div>
            </td>
            <td>
                @if($review->comment)
                    <div style="max-width: 300px;">
                        {{ Str::limit($review->comment, 100) }}
                    </div>
                @else
                    <span class="text-muted">{{translate('No comment')}}</span>
                @endif
            </td>
            <td>
                @if($review->attachment && count($review->attachment) > 0)
                    <div class="d-flex gap-1">
                        @foreach($review->attachment as $img)
                            <img src="{{ asset('storage/app/public/review/' . $img) }}" 
                                 alt="review" 
                                 class="rounded" 
                                 style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;"
                                 onclick="showImageModal('{{ asset('storage/app/public/review/' . $img) }}')">
                        @endforeach
                    </div>
                @else
                    <span class="text-muted">{{translate('No images')}}</span>
                @endif
            </td>
            <td>
                {{ $review->created_at->format('d M Y') }}
                <br>
                <small class="text-muted">{{ $review->created_at->format('h:i A') }}</small>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="text-center py-5">
    <img src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="no data" class="mb-3" style="width: 100px;">
    <p class="mb-0">{{translate('No reviews found')}}</p>
</div>
@endif

{{-- Image Modal --}}
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{translate('Review Image')}}</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="review" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<script>
function showImageModal(imageSrc) {
    $('#modalImage').attr('src', imageSrc);
    $('#imageModal').modal('show');
}
</script>
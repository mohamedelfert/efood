<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ translate('service_review_report') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Arial Unicode MS', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            direction: rtl;
            text-align: right;
            unicode-bidi: bidi-override;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin: 20px 0;
        }
        .stat-box {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            border-radius: 4px;
        }
        .stat-box h3 {
            margin: 0 0 5px 0;
            font-size: 20px;
            color: #333;
        }
        .stat-box p {
            margin: 0;
            font-size: 11px;
            color: #666;
        }
        .aspect-ratings {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
        }
        .aspect-ratings h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .aspect-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }
        .aspect-row:last-child {
            border-bottom: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            direction: rtl;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .rating-badge {
            background-color: #ffc107;
            color: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .service-badge {
            background-color: #17a2b8;
            color: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ translate('service_review_report') }}</h1>
        <p><strong>{{ translate('service_type') }}:</strong> 
            @php
                $serviceTypeTranslated = match($serviceType) {
                    'All Services' => translate('all_services'),
                    'Delivery' => translate('delivery'),
                    'Packaging' => translate('packaging'),
                    'Customer_service' => translate('customer_service'),
                    'Food_quality' => translate('food_quality'),
                    default => $serviceType
                };
            @endphp
            {{ $serviceTypeTranslated }}
        </p>
        <p><strong>{{ translate('date_range') }}:</strong> {{ $dateRange }}</p>
        <p><strong>{{ translate('generated') }}:</strong> {{ date('d M Y, h:i A') }}</p>
    </div>

    @if(isset($data['stats']))
    <div class="stats-grid">
        <div class="stat-box">
            <h3>{{ $data['stats']['total'] }}</h3>
            <p>{{ translate('total_reviews') }}</p>
        </div>
        <div class="stat-box">
            <h3>{{ $data['stats']['average_rating'] }}</h3>
            <p>{{ translate('average_rating') }}</p>
        </div>
        <div class="stat-box">
            <h3>{{ $data['stats']['rating_distribution']['5_star'] }}</h3>
            <p>{{ translate('5_star_reviews') }}</p>
        </div>
    </div>

    @if(isset($data['stats']['aspect_ratings']) && count($data['stats']['aspect_ratings']) > 0)
    <div class="aspect-ratings">
        <h3>{{ translate('service_aspect_ratings') }}</h3>
        @foreach($data['stats']['aspect_ratings'] as $aspect => $rating)
        <div class="aspect-row">
            <span><strong>
                @php
                    $aspectTranslated = match($aspect) {
                        'delivery_speed' => translate('delivery_speed'),
                        'food_temperature' => translate('food_temperature'),
                        'packaging_quality' => translate('packaging_quality'),
                        'customer_service' => translate('customer_service'),
                        'food_quality' => translate('food_quality'),
                        default => ucfirst(str_replace('_', ' ', $aspect))
                    };
                @endphp
                {{ $aspectTranslated }}
            </strong></span>
            <span class="rating-badge">{{ $rating }} ★</span>
        </div>
        @endforeach
    </div>
    @endif
    @endif

    @if(isset($data['reviews']) && count($data['reviews']) > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">{{ translate('serial') }}</th>
                <th style="width: 15%;">{{ translate('customer') }}</th>
                <th style="width: 12%;">{{ translate('service_type') }}</th>
                <th style="width: 8%;">{{ translate('order_number') }}</th>
                <th style="width: 8%;">{{ translate('rating') }}</th>
                <th style="width: 42%;">{{ translate('comment') }}</th>
                <th style="width: 10%;">{{ translate('date') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['reviews'] as $k => $review)
            <tr>
                <td>{{ $k + 1 }}</td>
                <td>
                    @if($review->customer)
                        {{ $review->customer->f_name }} {{ $review->customer->l_name }}
                    @else
                        {{ translate('deleted') }}
                    @endif
                </td>
                <td>
                    <span class="service-badge">
                        @php
                            $serviceTypeText = match($review->service_type) {
                                'delivery' => translate('delivery'),
                                'packaging' => translate('packaging'),
                                'customer_service' => translate('customer_service'),
                                'food_quality' => translate('food_quality'),
                                default => ucfirst(str_replace('_', ' ', $review->service_type))
                            };
                        @endphp
                        {{ $serviceTypeText }}
                    </span>
                </td>
                <td>#{{ $review->order_id }}</td>
                <td>
                    <span class="rating-badge">{{ number_format($review->rating, 1) }} ★</span>
                </td>
                <td>{{ $review->comment ? Str::limit($review->comment, 120) : translate('no_comment') }}</td>
                <td>{{ $review->created_at->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="text-align: center; padding: 20px;">{{ translate('no_reviews_found') }}</p>
    @endif

    <div class="footer">
        <p>{{ translate('system_generated_report') }} {{ date('d M Y, h:i A') }}</p>
    </div>
</body>
</html>
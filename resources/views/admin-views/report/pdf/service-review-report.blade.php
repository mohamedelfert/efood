<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportType }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
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
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
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
        <h1>{{ $reportType }}</h1>
        <p><strong>Service Type:</strong> {{ $serviceType }}</p>
        <p><strong>Date Range:</strong> {{ $dateRange }}</p>
        <p><strong>Generated:</strong> {{ date('d M Y, h:i A') }}</p>
    </div>

    @if(isset($data['stats']))
    <div class="stats-grid">
        <div class="stat-box">
            <h3>{{ $data['stats']['total'] }}</h3>
            <p>Total Reviews</p>
        </div>
        <div class="stat-box">
            <h3>{{ $data['stats']['average_rating'] }}</h3>
            <p>Average Rating</p>
        </div>
        <div class="stat-box">
            <h3>{{ $data['stats']['rating_distribution']['5_star'] }}</h3>
            <p>5 Star Reviews</p>
        </div>
    </div>

    @if(isset($data['stats']['aspect_ratings']) && count($data['stats']['aspect_ratings']) > 0)
    <div class="aspect-ratings">
        <h3>Service Aspect Ratings</h3>
        @foreach($data['stats']['aspect_ratings'] as $aspect => $rating)
        <div class="aspect-row">
            <span><strong>{{ ucfirst(str_replace('_', ' ', $aspect)) }}</strong></span>
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
                <th style="width: 5%;">SL</th>
                <th style="width: 15%;">Customer</th>
                <th style="width: 12%;">Service</th>
                <th style="width: 8%;">Order</th>
                <th style="width: 8%;">Rating</th>
                <th style="width: 42%;">Comment</th>
                <th style="width: 10%;">Date</th>
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
                        Deleted
                    @endif
                </td>
                <td>
                    <span class="service-badge">{{ ucfirst(str_replace('_', ' ', $review->service_type)) }}</span>
                </td>
                <td>#{{ $review->order_id }}</td>
                <td>
                    <span class="rating-badge">{{ number_format($review->rating, 1) }} ★</span>
                </td>
                <td>{{ $review->comment ? Str::limit($review->comment, 120) : 'No comment' }}</td>
                <td>{{ $review->created_at->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="text-align: center; padding: 20px;">No reviews found for the selected criteria.</p>
    @endif

    <div class="footer">
        <p>This is a system-generated report. Generated on {{ date('d M Y, h:i A') }}</p>
    </div>
</body>
</html>
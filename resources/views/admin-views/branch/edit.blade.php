@extends('layouts.admin.app')

@section('title', translate('Edit Branch'))

@push('css_or_js')
    <style>
        .schedule-day-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        .schedule-slot {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }
        .day-header {
            font-weight: 600;
            color: #334257;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .time-24hrs-badge {
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
        }
        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
        }
        .map-search-box {
            margin-bottom: 15px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" src="{{asset('public/assets/admin/img/icons/branch.png')}}" alt="">
                <span>{{translate('Edit Branch')}}</span>
            </h2>
        </div>

        <form action="{{route('admin.branch.update', $branch->id)}}" method="post" enctype="multipart/form-data" id="branch-form">
            @csrf
            <div class="row">
                <!-- Branch Information Card -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="tio-info"></i>
                                {{translate('Branch Information')}}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="input-label">{{translate('Name')}} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{$branch->name}}" required>
                            </div>

                            <div class="form-group">
                                <label class="input-label">{{translate('Email')}} <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="{{$branch->email}}" required>
                            </div>

                            <div class="form-group">
                                <label class="input-label">{{translate('Phone')}}</label>
                                <input type="text" name="phone" class="form-control" value="{{$branch->phone}}">
                            </div>

                            <div class="form-group">
                                <label class="input-label">{{translate('Password')}}</label>
                                <input type="password" name="password" class="form-control" placeholder="{{translate('Leave blank to keep current password')}}">
                                <small class="text-muted">{{translate('Leave blank if you don\'t want to change')}}</small>
                            </div>

                            <div class="form-group">
                                <label class="input-label">{{translate('Preparation Time (Minutes)')}}) <span class="text-danger">*</span></label>
                                <input type="number" name="preparation_time" class="form-control" value="{{$branch->preparation_time}}" min="1" required>
                            </div>

                            <div class="form-group">
                                <label class="input-label">{{translate('Address')}}</label>
                                <textarea name="address" class="form-control" rows="3">{{$branch->address}}</textarea>
                            </div>

                            <!-- Map Section -->
                            <div class="form-group">
                                <label class="input-label">{{translate('Location on Map')}}</label>
                                <div class="map-search-box">
                                    <input type="text" id="pac-input" class="form-control" placeholder="{{translate('Search for a location...')}}">
                                </div>
                                <div id="map"></div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Latitude')}}</label>
                                        <input type="text" name="latitude" id="latitude" class="form-control" value="{{$branch->latitude}}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Longitude')}}</label>
                                        <input type="text" name="longitude" id="longitude" class="form-control" value="{{$branch->longitude}}" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="input-label">{{translate('Coverage (KM)')}}</label>
                                <input type="number" name="coverage" class="form-control" value="{{$branch->coverage}}" min="1">
                            </div>

                            <div class="form-group">
                                <label class="input-label">{{translate('Branch Image')}}</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">{{translate('Image ratio 1:1')}}</small>
                                @if($branch->image)
                                    <div class="mt-2">
                                        <img src="{{$branch->image_full_path}}" alt="Branch" style="max-width: 100px;">
                                    </div>
                                @endif
                            </div>

                            <div class="form-group">
                                <label class="input-label">{{translate('Branch Cover Image')}}</label>
                                <input type="file" name="cover_image" class="form-control" accept="image/*">
                                <small class="text-muted">{{translate('Image ratio 2:1')}}</small>
                                @if($branch->cover_image)
                                    <div class="mt-2">
                                        <img src="{{$branch->cover_image_full_path}}" alt="Cover" style="max-width: 200px;">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Time Schedule Card -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="tio-calendar"></i>
                                {{translate('Opening Hours Schedule')}}
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Schedule Container -->
                            <div id="schedule-container" style="max-height: 600px; overflow-y: auto;">
                                @php
                                    $days = [
                                        0 => translate('Sunday'),
                                        1 => translate('Monday'),
                                        2 => translate('Tuesday'),
                                        3 => translate('Wednesday'),
                                        4 => translate('Thursday'),
                                        5 => translate('Friday'),
                                        6 => translate('Saturday'),
                                    ];
                                @endphp

                                @foreach($days as $dayNum => $dayName)
                                    @php
                                        $daySchedules = $schedulesByDay->get($dayNum, collect());
                                        $is24Hours = $daySchedules->first() && $daySchedules->first()->is_24_hours;
                                    @endphp
                                    
                                    <div class="schedule-day-card" data-day="{{$dayNum}}">
                                        <div class="day-header">
                                            <span>{{$dayName}}</span>
                                            <div>
                                                <div class="custom-control custom-checkbox d-inline-block mr-2">
                                                    <input type="checkbox" 
                                                           class="custom-control-input day-24hrs-check" 
                                                           id="day_24hrs_{{$dayNum}}"
                                                           {{$is24Hours ? 'checked' : ''}}
                                                           onchange="toggle24Hours({{$dayNum}})">
                                                    <label class="custom-control-label" for="day_24hrs_{{$dayNum}}">
                                                        {{translate('24 Hours')}}
                                                    </label>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTimeSlot({{$dayNum}})">
                                                    <i class="tio-add"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="schedule-slots" id="slots_{{$dayNum}}">
                                            @if($daySchedules->isEmpty())
                                                <!-- Default time slot -->
                                                <div class="schedule-slot" data-slot="0">
                                                    <input type="hidden" name="schedule[{{$dayNum}}][0][is_24_hours]" value="0" class="is-24hrs-input">
                                                    <input type="time" name="schedule[{{$dayNum}}][0][start_time]" class="form-control start-time" value="09:00" required>
                                                    <span>{{translate('to')}}</span>
                                                    <input type="time" name="schedule[{{$dayNum}}][0][end_time]" class="form-control end-time" value="21:00" required>
                                                </div>
                                            @else
                                                @foreach($daySchedules as $index => $schedule)
                                                    <div class="schedule-slot" data-slot="{{$index}}">
                                                        <input type="hidden" name="schedule[{{$dayNum}}][{{$index}}][is_24_hours]" value="{{$schedule->is_24_hours ? 1 : 0}}" class="is-24hrs-input">
                                                        @if($schedule->is_24_hours)
                                                            <span class="time-24hrs-badge">{{translate('Open 24 Hours')}}</span>
                                                            <input type="hidden" name="schedule[{{$dayNum}}][{{$index}}][start_time]" value="00:00">
                                                            <input type="hidden" name="schedule[{{$dayNum}}][{{$index}}][end_time]" value="23:59">
                                                        @else
                                                            <input type="time" name="schedule[{{$dayNum}}][{{$index}}][start_time]" class="form-control start-time" value="{{$schedule->opening_time}}" required>
                                                            <span>{{translate('to')}}</span>
                                                            <input type="time" name="schedule[{{$dayNum}}][{{$index}}][end_time]" class="form-control end-time" value="{{$schedule->closing_time}}" required>
                                                            @if($daySchedules->count() > 1)
                                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeTimeSlot(this)">
                                                                    <i class="tio-delete"></i>
                                                                </button>
                                                            @endif
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="alert alert-warning mt-3">
                                <small>
                                    <i class="tio-info"></i>
                                    {{translate('You can add multiple time slots per day for split shifts')}}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="btn-group float-right">
                        <a href="{{route('admin.branch.list')}}" class="btn btn-secondary">{{translate('Back')}}</a>
                        <button type="submit" class="btn btn-primary">{{translate('Update Branch')}}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('script_2')
    <script src="https://maps.googleapis.com/maps/api/js?key={{\App\CentralLogics\Helpers::get_business_settings('map_api_key')}}&libraries=places&callback=initMap" async defer></script>
    
    <script>
        let map;
        let marker;
        let slotCounters = {
            0: {{$schedulesByDay->get(0, collect())->count() ?: 1}},
            1: {{$schedulesByDay->get(1, collect())->count() ?: 1}},
            2: {{$schedulesByDay->get(2, collect())->count() ?: 1}},
            3: {{$schedulesByDay->get(3, collect())->count() ?: 1}},
            4: {{$schedulesByDay->get(4, collect())->count() ?: 1}},
            5: {{$schedulesByDay->get(5, collect())->count() ?: 1}},
            6: {{$schedulesByDay->get(6, collect())->count() ?: 1}}
        };

        // Initialize Map
        function initMap() {
            const branchLocation = { 
                lat: parseFloat('{{$branch->latitude}}') || 23.777176, 
                lng: parseFloat('{{$branch->longitude}}') || 90.399452 
            };
            
            map = new google.maps.Map(document.getElementById('map'), {
                center: branchLocation,
                zoom: 15,
                mapTypeControl: true
            });

            marker = new google.maps.Marker({
                position: branchLocation,
                map: map,
                draggable: true
            });

            // Update coordinates on marker drag
            google.maps.event.addListener(marker, 'dragend', function() {
                updateCoordinates(marker.getPosition());
            });

            // Click on map to place marker
            google.maps.event.addListener(map, 'click', function(event) {
                marker.setPosition(event.latLng);
                updateCoordinates(event.latLng);
            });

            // Search box
            const input = document.getElementById('pac-input');
            const searchBox = new google.maps.places.SearchBox(input);

            map.addListener('bounds_changed', function() {
                searchBox.setBounds(map.getBounds());
            });

            searchBox.addListener('places_changed', function() {
                const places = searchBox.getPlaces();

                if (places.length == 0) {
                    return;
                }

                const place = places[0];

                if (!place.geometry) {
                    return;
                }

                map.setCenter(place.geometry.location);
                marker.setPosition(place.geometry.location);
                updateCoordinates(place.geometry.location);
            });
        }

        function updateCoordinates(location) {
            document.getElementById('latitude').value = location.lat();
            document.getElementById('longitude').value = location.lng();
        }

        // Schedule Management Functions (same as add view)
        function addTimeSlot(day, startTime = '09:00', endTime = '21:00', is24Hours = false) {
            const container = document.getElementById('slots_' + day);
            const slotIndex = slotCounters[day]++;
            const showDelete = container.children.length > 0;

            const slotHtml = `
                <div class="schedule-slot" data-slot="${slotIndex}">
                    <input type="hidden" name="schedule[${day}][${slotIndex}][is_24_hours]" value="${is24Hours ? 1 : 0}" class="is-24hrs-input">
                    <input type="time" name="schedule[${day}][${slotIndex}][start_time]" class="form-control start-time" value="${startTime}" ${is24Hours ? 'disabled' : 'required'}>
                    <span>${'{{translate("to")}}'}</span>
                    <input type="time" name="schedule[${day}][${slotIndex}][end_time]" class="form-control end-time" value="${endTime}" ${is24Hours ? 'disabled' : 'required'}>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeTimeSlot(this)" style="display: ${showDelete ? 'block' : 'none'}">
                        <i class="tio-delete"></i>
                    </button>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', slotHtml);
            updateDeleteButtons(day);
        }

        function removeTimeSlot(button) {
            const slot = button.closest('.schedule-slot');
            const day = slot.closest('.schedule-day-card').dataset.day;
            slot.remove();
            updateDeleteButtons(day);
        }

        function updateDeleteButtons(day) {
            const container = document.getElementById('slots_' + day);
            const slots = container.querySelectorAll('.schedule-slot');
            
            slots.forEach((slot, index) => {
                const deleteBtn = slot.querySelector('button');
                if (deleteBtn) {
                    deleteBtn.style.display = slots.length > 1 ? 'block' : 'none';
                }
            });
        }

        function toggle24Hours(day) {
            const checkbox = document.getElementById('day_24hrs_' + day);
            const isChecked = checkbox.checked;
            const container = document.getElementById('slots_' + day);

            if (isChecked) {
                container.innerHTML = '';
                slotCounters[day] = 0;
                
                const slotIndex = slotCounters[day]++;
                const slotHtml = `
                    <div class="schedule-slot" data-slot="${slotIndex}">
                        <input type="hidden" name="schedule[${day}][${slotIndex}][is_24_hours]" value="1" class="is-24hrs-input">
                        <span class="time-24hrs-badge">${'{{translate("Open 24 Hours")}}'}</span>
                        <input type="hidden" name="schedule[${day}][${slotIndex}][start_time]" value="00:00">
                        <input type="hidden" name="schedule[${day}][${slotIndex}][end_time]" value="23:59">
                    </div>
                `;
                container.innerHTML = slotHtml;
            } else {
                container.innerHTML = '';
                slotCounters[day] = 0;
                addTimeSlot(day, '09:00', '21:00', false);
            }
        }

        // Form validation
        document.getElementById('branch-form').addEventListener('submit', function(e) {
            let isValid = true;
            
            for (let day = 0; day <= 6; day++) {
                const dayContainer = document.getElementById('slots_' + day);
                const slots = dayContainer.querySelectorAll('.schedule-slot');
                
                if (slots.length === 0) {
                    isValid = false;
                    toastr.error('{{translate("Please add at least one time slot for each day")}}');
                    break;
                }

                slots.forEach(slot => {
                    const is24Hours = slot.querySelector('.is-24hrs-input').value == '1';
                    if (!is24Hours) {
                        const startTime = slot.querySelector('.start-time').value;
                        const endTime = slot.querySelector('.end-time').value;
                        
                        if (startTime && endTime && startTime >= endTime) {
                            isValid = false;
                            toastr.error('{{translate("End time must be after start time")}}');
                        }
                    }
                });
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Initialize delete buttons on page load
        document.addEventListener('DOMContentLoaded', function() {
            for (let day = 0; day <= 6; day++) {
                updateDeleteButtons(day);
            }
        });
    </script>
@endpush
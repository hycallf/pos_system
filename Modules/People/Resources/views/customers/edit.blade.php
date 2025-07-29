@extends('layouts.app')

@section('title', 'Edit Customer')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <form action="{{ route('customers.update', $customer) }}" method="POST">
            @csrf
            @method('patch')
            <div class="row">
                <div class="col-lg-12">
                    @include('utils.alerts')
                    <div class="form-group">
                        <button class="btn btn-primary">Update Customer <i class="bi bi-check"></i></button>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="customer_name">Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="customer_name"
                                            value="{{ $customer->customer_name }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="customer_email">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="customer_email"
                                            value="{{ $customer->customer_email }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="customer_phone">Phone <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="customer_phone"
                                            value="{{ $customer->customer_phone }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="country">Country</label>
                                        <select name="country" id="country" class="form-control" required
                                            data-selected="{{ $customer->country }}">
                                            <option value="" disabled>Select Country</option>
                                            <!-- Options diisi lewat JS -->
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="state">State / Province</label>
                                        <select name="state" id="state" class="form-control" required
                                            data-selected="{{ $customer->state }}">
                                            <option value="" disabled>Select State</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <select name="city" id="city" class="form-control" required
                                            data-selected="{{ $customer->city }}">
                                            <option value="" disabled>Select City</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" name="address" rows="4">{{ $customer->address }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('page_scripts')
    <script>
        $(document).ready(function() {
            const API_URL = "https://api.countrystatecity.in/v1/";
            const API_KEY = "{{ env('COUNTRYSTATECITY_API_KEY') }}";

            const selectedCountry = "{{ $customer->country ?? '' }}";
            const selectedState = "{{ $customer->state ?? '' }}";
            const selectedCity = "{{ $customer->city ?? '' }}";

            const headers = new Headers();
            headers.append("X-CSCAPI-KEY", API_KEY);
            const requestOptions = {
                method: 'GET',
                headers: headers,
                redirect: 'follow'
            };

            function loadCountries() {
                fetch(API_URL + "countries", requestOptions)
                    .then(response => response.json())
                    .then(data => {
                        let options = "<option value='' disabled>Select Country</option>";
                        data.forEach(country => {
                            const selected = country.iso2 === selectedCountry ? 'selected' : '';
                            options +=
                                `<option value="${country.iso2}" ${selected}>${country.name}</option>`;
                        });
                        $('#country').html(options).prop('disabled', false);

                        if (selectedCountry) {
                            loadStates(selectedCountry);
                        }
                    })
                    .catch(error => console.log('error', error));
            }

            function loadStates(countryCode) {
                $('#state').html("<option value='' selected disabled>Loading...</option>").prop('disabled', false);
                fetch(`${API_URL}countries/${countryCode}/states`, requestOptions)
                    .then(response => response.json())
                    .then(data => {
                        let options = "<option value='' disabled>Select State</option>";
                        data.forEach(state => {
                            const selected = state.iso2 === selectedState ? 'selected' : '';
                            options +=
                                `<option value="${state.iso2}" ${selected}>${state.name}</option>`;
                        });
                        $('#state').html(options).prop('disabled', false);

                        if (selectedState) {
                            loadCities(countryCode, selectedState);
                        }
                    })
                    .catch(error => console.log('error', error));
            }

            function loadCities(countryCode, stateCode) {
                $('#city').html("<option value='' selected disabled>Loading...</option>").prop('disabled', false);
                fetch(`${API_URL}countries/${countryCode}/states/${stateCode}/cities`, requestOptions)
                    .then(response => response.json())
                    .then(data => {
                        let options = "<option value='' disabled>Select City</option>";
                        data.forEach(city => {
                            const selected = city.name === selectedCity ? 'selected' : '';
                            options += `<option value="${city.name}" ${selected}>${city.name}</option>`;
                        });
                        $('#city').html(options).prop('disabled', false);
                    })
                    .catch(error => console.log('error', error));
            }

            // Event listeners
            $('#country').on('change', function() {
                const countryCode = $(this).val();
                $('#state').prop('disabled', true);
                $('#city').prop('disabled', true);
                loadStates(countryCode);
            });

            $('#state').on('change', function() {
                const stateCode = $(this).val();
                const countryCode = $('#country').val();
                $('#city').prop('disabled', true);
                loadCities(countryCode, stateCode);
            });

            // Init on page load
            loadCountries();
        });
    </script>
@endpush

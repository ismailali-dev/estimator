@extends('voyager::master')

@section('content')


<style>

    .dashboard-card {
        background: #fff;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 20px;
    }
    .dashboard-header {
        font-size: 20px;
        font-weight: bold;
        margin:0px;
    }

    .text-primary {
        color: #007bff;
    }
    .status-badge {
        border-radius: 12px;
        padding: 5px 10px;
        font-size: 12px;
    }
    .status-subscribed {
        background-color: #28a745;
        color: #fff;
    }
    .status-unsubscribed {
        background-color: #dc3545;
        color: #fff;
    }
    .table > tbody > tr > td {
        vertical-align: middle;
    }

    .container-fluid.pt-3 {
        padding-top: 30px;
    }
    .totalFigure {
        font-size: 32px;
        color: #000;
        font-weight: 600;
        margin-bottom: 0px;
    }
    canvas#subscriptionChart,canvas#contractorsChart {
        margin: 0 auto;
    }
    .voyager .table>tbody>tr>td, .voyager .table>tbody>tr>th, .voyager .table>tfoot>tr>td, .voyager .table>tfoot>tr>th {
        padding: 15px 15px;
    }
    .table>caption+thead>tr:first-child>td, .table>caption+thead>tr:first-child>th, .table>colgroup+thead>tr:first-child>td, .table>colgroup+thead>tr:first-child>th, .table>thead:first-child>tr:first-child>td, .table>thead:first-child>tr:first-child>th{
        background: #EEF4BD;
        font-size: 12px;
    }

    .text-right.dropdownpostion {
        /*position: absolute;*/
        /*right: 35px;*/
        /*top: 24px;*/
        min-width: 110px;
    }

    #legend {
        display: flex;
        flex-direction: column;
        font-family: Arial, sans-serif;
        margin-top: 20px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        margin-bottom: 13px;
        justify-content: space-between;
    }

    .legend-color {
        width: 12px;
        height: 12px;
        margin-right: 10px;
        border-radius: 50%;
    }
    .legendInnerItem{
        display: flex;
    }
    .faded-row {
        opacity: 0.5;
        position: relative; /* Needed for button positioning */
    }

    .faded-btn-row{
        position: relative;
        min-height: 65px;
        height: 65px;
    }
    .view-more-btn {
        position: absolute; /* Button on top of the row */
        top: 50%; /* Center vertically */
        left: 50%; /* Center horizontally */
        transform: translate(-50%, -50%); /* Adjust for exact centering */
        z-index: 99; /* Ensure the button is above the faded row */
        text-decoration: none !important; /* Remove any default text decoration */
    }

    .faded-row td {

    }
    .voyager .btn.btn-success {
        background: #c9da2b;
    }
    
    canvas#revenueChart {
    	height: 320px;
    }
    .status-badge {
    	    width: 80px;
	    display: inline-block;
	    text-align: center;
	    padding: 3px 5px;
    }
    
    .applycutomRange{
        padding: 0px 20px;
            height: 33.5px;
            margin-left: 0px;
            margin-top: 0px !important;
    }
    
      /* Contact Number in one line */
    .table td[data-title="Contact Number"] {
        white-space: nowrap;
        min-width: 140px;
    }
    
    
</style>
<link rel="stylesheet" href="https://ez-estimator.wellnesslife360.com//public/admin/assets/css/bootstrap.css">

@php

use Carbon\Carbon;
use App\Models\User;

use Illuminate\Support\Facades\DB;
$users = App\Models\User::with('company')->latest()->take(7)->get();
$totalContractors = App\Models\User::count();

$totalAmount = DB::table('subscriptions')->sum('amount');

$formattedTotalAmount = number_format($totalAmount, 2);


$weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Fetch subscription data grouped by day
$subscriptionData = DB::table('subscriptions')
->select(DB::raw('DAYNAME(created_at) as day'), DB::raw('SUM(amount) as revenue'))
->groupBy('day')
->get()
->pluck('revenue', 'day'); // Create key-value pairs of day => revenue

// Map the database data to the static week days
$revenueData = array_map(function($day) use ($subscriptionData) {
return $subscriptionData->get($day, 0); // Use 0 if no revenue for the day
}, $weekDays);



$totalSubscriptions = DB::table('subscriptions')->count();


$currentDate = Carbon::now();
$startOfWeek = $currentDate->copy()->startOfWeek();
$endOfWeek = $currentDate;

// Get users who registered this week
$usersInWeek = User::whereBetween('created_at', [$startOfWeek, $endOfWeek])->pluck('id');

// Get users who subscribed this week from those users
$paidUserIds = DB::table('subscriptions')
    ->whereIn('user_id', $usersInWeek)
    ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
    ->pluck('user_id')
    ->unique();

// Count
$paidCount = $paidUserIds->count();
$unpaidCount = $usersInWeek->count() - $paidCount;
$totalContractors = $usersInWeek->count();


$baseUrl = config('app.url');

@endphp

<div class="container-fluid pt-3">
    <div class="row">
        <div class="col-md-9">
            <div class="row">

                <!-- Revenue and Chart -->
                <div class="col-md-12">
                    <div class="dashboard-card">
                        <div class="row">
                            <div class="col-sm-12">
                            	<div class="d-flex align-items-center justify-content-between border-bottom pb-3">
		                    <h4 class="dashboard-header">Revenue</h4>
		                    <div class="filterrevenue">
		                        <div class="form-control-select">
		                        <div class="text-right dropdownpostion">
		                                <select id="periodDropdownforRevenue" class="form-control">
                                            <option value="today">Today</option>
                                            <option value="yesterday">Yesterday</option>
                                            <option value="last_7_days">Last 7 Days</option>
                                            <option value="last_14_days">Last 14 Days</option>
                                            <option value="last_28_days">Last 28 Days</option>
                                            <option value="last_30_days">Last 30 Days</option>
                                            <option value="this_week">This Week</option>
                                            <option value="last_week">Last Week</option>
                                            <option value="this_month">This Month</option>
                                            <option value="last_month">Last Month</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                     
		                            </div>
		                    </div>
	                       <div id="customDateInputs" class="mt-2" style="display: none;">
                                <input type="date" id="customStartDate" class="form-control mb-1 me-2" />
                                <input type="date" id="customEndDate" class="form-control" />
                                <button class="btn btn-primary ms-2 applycutomRange applyCustomRange"  >Apply</button>
                            </div>
		                    </div>
		                    
		                </div>
		                <div class="d-flex align-items-center justify-content-between border-bottom">
		                    <p class="mb-0">Total Revenue</p>
		                    <p class="totalFigure" id="totalRevenue">${{$formattedTotalAmount}}</p>
		                </div>
                                <div class="chart-container bar-chart" >
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                            
                        </div>

                    </div>
                </div>
                
                

                <div class="col-md-4">
	        	
	                
                    <div class="dashboard-card">
                    	<div class="d-flex align-items-center justify-content-between border-bottom pb-3">
	                    <h4 class="dashboard-header">Contractors</h4>
	                    <div class="form-control-select">
                        <select id="periodDropdownforContractorsCount" class="form-control">
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="this_year">This Year</option>
                        </select>
                    </div>
	                </div>
	                <div class="d-flex align-items-center justify-content-between border-bottom mb-4">
	                    <p class="mb-0">Total Contractors</p>
	                    <p class="totalFigure" id="totalcontratcorcount">{{$totalContractors}}</p>
	                </div>
	           
                        <div class="chart-container">
                            <canvas id="contractorsChart"></canvas>
                        </div>
                    </div>

                </div>
                <div class="col-md-8">
                    <div class="dashboard-card">
                    	<div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
	                    <h4 class="dashboard-header">Recent Contractors</h4>
	                    <div class="form-control-select">
	                        <div class="text-right dropdownpostion">
                                <select id="SubscriptionStatusDropdown" class="form-control">
                                    <option value="all">All</option>
                                    <option value="paid">Paid</option>
                                    <option value="unpaid">Unpaid</option>
                                </select>
	                        </div>
	                    </div>
	                </div>
                        <table id="userTable" class="table">
                            <thead>
                            <tr>
                                <th>Contractor</th>
                                
                                <th>Company</th>
                                <th>Contact Number</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <!-- Dynamically populated rows -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3" >
            <div class="dashboard-card">
                <div class="d-flex align-items-center justify-content-between border-bottom pb-3">
                    <h4 class="dashboard-header">Subscription</h4>
                    <div class="form-control-select">
                        <select id="periodDropdownforSubcriptionCount" class="form-control">
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="this_year">This Year</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between border-bottom mb-4">
                    <p class="mb-0">Total Subscriptions</p>
                    <p class="totalFigure" id="totalSubscriptions">{{$totalSubscriptions}}</p>
                </div>
                <div class="chart-container">
                            <canvas id="subscriptionChart"></canvas>
                            <div id="legend"></div>
                        </div>
            </div>
        </div>
    </div>


    @stop



    @section('javascript')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>



        $(document).ready(function() {
            
            
            $('.applyCustomRange').on('click', function () {
        const startDate = $('#customStartDate').val();
        const endDate = $('#customEndDate').val();

        if (!startDate || !endDate) {
            alert('Please select both start and end dates.');
            return;
        }

        updateRevenueChart('custom', startDate, endDate);
    });

            var base_url = 'https://admin.ezestimater.com';

            // Get the data from Blade variables
            const labels = []; // Static week days
            const dataRevenueChart = []; // Revenue data with 0 for missing days
            const totalRevenue = []; // Initial total revenue value

            // Initialize the chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            const revenueChart = new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: dataRevenueChart,
                        backgroundColor: 'rgba(201, 218, 43, 0.7)',
                        borderColor: 'rgba(201, 218, 43)',
                        borderWidth: 1,
                        barThickness: 32, // Set bar width to 10px
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Allows dynamic height adjustment
                    scales: {
                        x: {
                            grid: {
                                display: true // Hide the vertical grid lines
                            }
                        },
                        y: {
                            beginAtZero: true, // Start at 0
                            ticks: {
                                stepSize: 5000, // Increment by 5000
                                callback: function(value) {
                                    return value / 1000 + 'k'; // Display values as 0k, 5k, etc.
                                }
                            }
                        }
                    }
                }
            });

            // Function to update the total revenue display
            function updateTotalRevenue(totalRevenue) {
                // Format the total revenue to match the display format
                const formattedRevenue = '$' + totalRevenue.toLocaleString();
                $('#totalRevenue').text(formattedRevenue); // Update the total revenue in the HTML
                console.log(formattedRevenue)
            }

            // Define the function to fetch revenue data and update the chart
            function updateRevenueChart(period, startDate = '', endDate = '') {
    $.ajax({
        url: base_url + '/get-revenue-data',
        type: 'GET',
        data: {
            period: period,
            start_date: startDate,
            end_date: endDate
        },
        success: function (response) {
            var labels = response.labels;
            var revenueData = response.data;
            var totalRevenue = response.totalRevenue;

            revenueChart.data.labels = labels;
            revenueChart.data.datasets[0].data = revenueData;
            revenueChart.update();
            updateTotalRevenue(totalRevenue);
        },
        error: function (error) {
            console.error('Error fetching revenue data:', error);
        }
    });
}

    
                
            $('#periodDropdownforRevenue').on('change', function () {
                    const selectedPeriod = $(this).val();
                    if (selectedPeriod === 'custom') {
                        $('#customDateInputs').addClass('d-flex').show();
                    } else {
                        $('#customDateInputs').removeClass('d-flex').hide();
                        updateRevenueChart(selectedPeriod);
                    }
               
                
                
            });
                
            
            // Initially, load the chart data and total revenue for the default period
            updateRevenueChart('this_week');
            fetchSubscriptionData('this_week');

            $('#periodDropdownforSubcriptionCount').change(function() {
                const selectedPeriod = $(this).val();
                fetchSubscriptionData(selectedPeriod);
            });

            function fetchSubscriptionData(period) {
                $.ajax({
                    url: base_url + '/get-subscription-count', // Controller route
                    method: 'GET',
                    data: { period: period }, // Send selected period
                    success: function(response) {
                        // Update chart with new data
                        updateChart(response.labels, response.data, response.colors);
                        const totalSubscriptions = document.getElementById('totalSubscriptions');
                        totalSubscriptions.innerText = response.totalSubscriptions; // Clear previous legend

                    }
                });
            }

            function updateChart(labels, data, colors) {
                const chartData = {
                    labels: labels,
                    datasets: [{
                        label: 'Subscriptions',
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 1
                    }]
                };

                const config = {
                    type: 'pie',
                    data: chartData,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false // Disable built-in legend
                            }
                        }
                    }
                };

                // Clear the previous chart before re-rendering
                const ctx = document.getElementById('subscriptionChart').getContext('2d');
                if (window.myChart) {
                    window.myChart.destroy(); // Destroy existing chart
                }
                window.myChart = new Chart(ctx, config);

                // Update custom legend
                const legendContainer = document.getElementById('legend');
                legendContainer.innerHTML = ''; // Clear previous legend
                labels.forEach((label, index) => {
                    const legendItem = document.createElement('div');
                    legendItem.classList.add('legend-item');
                    legendItem.innerHTML = `
                    <div class="legendInnerItem">
                        <div class="legend-color" style="background-color: ${colors[index]}"></div>
                        ${label}
                    </div>
                    <div>${data[index]}</div>
                `;
                    legendContainer.appendChild(legendItem);
                });
            }



            let paidCount = {{ $paidCount }};
            let unpaidCount = {{ $unpaidCount }};

            // Contractors Chart
            const contractorsCtx = document.getElementById('contractorsChart').getContext('2d');
            const contractorsChart = new Chart(contractorsCtx, {
                type: 'pie',
                data: {
                    labels: ['Paid', 'Unpaid'],
                    datasets: [{
                        backgroundColor: ['rgba(201, 218, 43)', '#E9E9E9'],
                        data: [paidCount, unpaidCount]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false // Ensures the chart adjusts height dynamically
                }
            });
            
            
            document.getElementById('periodDropdownforContractorsCount').addEventListener('change', function () {
            let period = this.value;
        
            fetch(base_url + `/contractors/filterchart?period=${period}`)
                .then(response => response.json())
                .then(data => {
                    // Update chart data
                    contractorsChart.data.datasets[0].data = [data.paid, data.unpaid];
                    contractorsChart.update();
        
                    // Update total contractor count
                    const total = data.total || 0;

                    $('#totalcontratcorcount').text(`${total}`);
                    
                })
                .catch(error => {
                    console.error('Error fetching contractor data:', error);
                });
        });



            function fetchUsers(status) {
                $.ajax({
                    url: base_url + '/contractors/filter',
                    method: 'GET',
                    data: { status: status }, // Pass status as a query parameter
                    success: function (response) {
                        const usersTable = $('.table tbody');
                        usersTable.empty();
                        response.users.forEach((user, index) => {
                            const fadedClass = (response.users.length >= 7 && index === response.users.length - 1)
                                ? 'faded-row'
                                : '';
                            let statusBadge = '';
                            let actionButtons = '';

                            if (user.status === 'Paid') {
                                statusBadge = '<span class="status-badge status-subscribed rounded-pill">Paid</span>';
                                actionButtons = '<button class="btn btn-success btn-sm rounded-pill">Paid</button>';
                            } else {
                                statusBadge = '<span class="status-badge status-unsubscribed rounded-pill">Unpaid</span>';
                                actionButtons = '<button class="btn btn-danger btn-sm rounded-pill">Unpaid</button>';
                            }

                            // If status is 'all', show both Active and Unpaid buttons
                            if (status === 'all') {
                                actionButtons = `
                            <button class="btn btn-success btn-sm">Paid</button>
                            <button class="btn btn-danger btn-sm">Unpaid</button>
                        `;
                            }

                            usersTable.append(`
                        <tr class="${fadedClass}">
                            <td data-title="Contractor">${user.name}</td>
                          
                            <td data-title="Company">${user.company}</td>
                            <td data-title="Contact Number">${user.phone}</td>
                            <td data-title="Status" class="text-lg-center">${statusBadge}</td>
                        </tr>
                    `);
                        });

                        if (response.users.length === 7) {
                            usersTable.append(`
                        <tr class="faded-btn-row">
                            <td colspan="5" class="text-center">
                                <a href="/dashboard/users" class="btn btn-primary view-more-btn">
                                    View More
                                </a>
                            </td>
                        </tr>
                    `);
                        }

                        // Update paid/unpaid counts
                        $('#SubscriptionStatusDropdown [value="paid"]').text(`Paid (${response.paid})`);
                        $('#SubscriptionStatusDropdown [value="unpaid"]').text(`Unpaid (${response.unpaid})`);

                        paidCount = response.paid;
                        unpaidCount = response.unpaid;

                        contractorsChart.data.datasets[0].data = [paidCount, unpaidCount];
                        contractorsChart.update();
                    },
                    error: function (xhr, status, error) {
                        console.error('Failed to fetch users:', error);
                    }
                });
            }

            // Handle dropdown change
            $('#SubscriptionStatusDropdown').on('change', function () {
                const selectedStatus = $(this).val();
                fetchUsers(selectedStatus);
            });

            // Fetch all users on page load
            fetchUsers('all');



        });


    </script>
    @stop


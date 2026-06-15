<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Expenses;
use App\Models\PurchaseOrders;
use App\Models\SaleOrders;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Subscription; // Make sure to import the Subscription model
use Illuminate\Support\Facades\DB;
use App\Models\User;

class HomeController extends AuthenticatedController
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index():View
    {
        
        $secondBox = []; //$this->secondBox();
        $topBox = []; //$this->topBox();
        $thirdBox = []; //$this->thirdBox();
        return view('dashboard', ['topBox' => $topBox,'secondBox'=>$secondBox,'thirdBox'=>$thirdBox]);

    }
    private function secondBox(): array
    {
       $dates_month = Helper::rangeMonth();
        $sale_purchase_comp = [];
       foreach ($dates_month as $key => $value){
           $sale_purchase_comp[date('M',strtotime($value))] = [];
           $sale_purchase_comp[date('M',strtotime($value))]['so'] = SaleOrders::where('is_archive', 0)->where('status', 1)->where('company_id',Auth::user()->company_id)->whereMonth('order_date',Carbon::parse($value)->month)->count();
           $sale_purchase_comp[date('M',strtotime($value))]['po'] = PurchaseOrders::where('is_archive', 0)->where('status', 1)->where('company_id',Auth::user()->company_id)->whereMonth('order_date',Carbon::parse($value)->month)->count();
       }

//       dd($sale_purchase_comp);

        $sale_month_pie = SaleOrders::where('is_archive', 0)->where('status', 1)->where('company_id',Auth::user()->company_id)->whereMonth('order_date',Carbon::parse(date('Y-m-d'))->month)->sum('grand_total');
        $purchase_month_pie = PurchaseOrders::where('is_archive', 0)->where('status', 1)->where('company_id',Auth::user()->company_id)->whereMonth('order_date',Carbon::parse(date('Y-m-d'))->month)->sum('grand_total');
        $exp_month_pie = Expenses::where('is_archive', 0)->where('status', 1)->where('company_id',Auth::user()->company_id)->whereMonth('expense_date',Carbon::parse(date('Y-m-d'))->month)->sum('amount');

        $data =
           [
               'sale_month_pie'=>$sale_month_pie,
               'purchase_month_pie'=>$purchase_month_pie,
               'exp_month_pie'=>$exp_month_pie,
               'sale_purchase_comp'=>json_encode($sale_purchase_comp),
           ];
        return $data;

    }

    private function thirdBox(): array
    {
        $saleSumToday = SaleOrders::where('is_archive', 0)->where('status', 1)->where('company_id', Auth::user()->company_id)->where('order_date',date('Y-m-d'))->sum('grand_total');
        $saleSumWeek = SaleOrders::where('is_archive', 0)->where('status', 1)->where('company_id', Auth::user()->company_id)->whereBetween('order_date',[Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->sum('grand_total');
        $saleSumMonth = SaleOrders::where('is_archive', 0)->where('status', 1)->where('company_id', Auth::user()->company_id)->whereMonth('order_date',Carbon::parse(date('Y-m-d'))->month)->sum('grand_total');
        $saleSumYear = SaleOrders::where('is_archive', 0)->where('status', 1)->where('company_id', Auth::user()->company_id)->whereYear('order_date',Carbon::parse(date('Y-m-d'))->year)->sum('grand_total');

        $previous_week = strtotime("-1 week +1 day");
        $start_week = strtotime("last sunday midnight",$previous_week);
        $end_week = strtotime("next saturday",$start_week);
        $start_week = date("Y-m-d",$start_week);
        $end_week = date("Y-m-d",$end_week);
        $saleSumTodayLast = SaleOrders::where('is_archive', 0)->where('status', 1)->where('company_id', Auth::user()->company_id)->where('order_date',date('Y-m-d',strtotime("-1 days")))->sum('grand_total');
        $saleSumWeekLast = SaleOrders::where('is_archive', 0)->where('status', 1)->where('company_id', Auth::user()->company_id)->whereBetween('order_date', [$start_week, $end_week])->sum('grand_total');
        $saleSumMonthLast = SaleOrders::where('is_archive', 0)->where('status', 1)->where('company_id', Auth::user()->company_id)->whereMonth('order_date',Carbon::now()->subMonth()->month)->sum('grand_total');
        $saleSumYearLast = SaleOrders::where('is_archive', 0)->where('status', 1)->where('company_id', Auth::user()->company_id)->whereYear('order_date',date('Y', strtotime('-1 year')))->sum('grand_total');

        $data =
            [
                'saleSumToday'=>$saleSumToday,
                'saleSumWeek'=>$saleSumWeek,
                'saleSumMonth'=>$saleSumMonth,
                'saleSumYear'=>$saleSumYear,
                'saleSumTodayLast'=>$saleSumTodayLast,
                'saleSumWeekLast'=>$saleSumWeekLast,
                'saleSumMonthLast'=>$saleSumMonthLast,
                'saleSumYearLast'=>$saleSumYearLast,
                'todayComp'=>($saleSumToday > $saleSumTodayLast ? 1 : 0),
                'weekComp'=>($saleSumWeek > $saleSumWeekLast ? 1 : 0),
                'monthComp'=>($saleSumMonth > $saleSumMonthLast ? 1 : 0),
                'yearComp'=>($saleSumYear > $saleSumYearLast ? 1 : 0),
            ];
        return $data;
    }

    private function topBox(): array
    {
        $purchaseSum = PurchaseOrders::where('is_archive', 0)->where('status', 1)->where('company_id', Auth::user()->company_id);
        $saleSum = SaleOrders::where('is_archive', 0)->where('status', 1)->where('company_id', Auth::user()->company_id);
        $saleItem = SaleOrders::where('is_archive', 0)->where('status', 1)->where('company_id', Auth::user()->company_id);
        $expenseSum = Expenses::where('is_archive', 0)->where('company_id', Auth::user()->company_id);
        $saleSumGraph = json_encode($saleSum->get(['grand_total'])->toArray());
        $purchaseSumGraph = json_encode($purchaseSum->get(['grand_total'])->toArray());

        $expSumGraph = json_encode($expenseSum->get(['amount'])->toArray());
//        dd($saleCountGraph);
        $purchase = $purchaseSum->sum('grand_total');
        $sale = $saleSum->sum('grand_total');
        $saleI = $saleItem->sum('count');
        $exp = $expenseSum->sum('amount');
        $data = [
            'purchaseSum' => $purchase,
            'saleSum' => $sale,
            'saleItem' => $saleI,
            'expenseSum' => $exp,
            'grossProfit' => $sale - $purchase,
            'netProfit' => ($sale - $purchase) - $exp,
            'saleSumGraph' => $saleSumGraph,
            'purchaseSumGraph' => $purchaseSumGraph,
            'expSumGraph' => $expSumGraph
        ];

        return $data;
    }
    
    
public function getRevenueData(Request $request)
{
    $period = $request->input('period');
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    $labels = [];
    $data = [];
    $totalRevenue = 0;
    $currentDate = Carbon::now();

    switch ($period) {
        case 'today':
            $start = $currentDate->copy()->startOfDay();
            $end = $currentDate->copy()->endOfDay();
            $labels = ['Today'];
            break;

        case 'yesterday':
            $start = $currentDate->copy()->subDay()->startOfDay();
            $end = $currentDate->copy()->subDay()->endOfDay();
            $labels = ['Yesterday'];
            break;

        case 'last_7_days':
            $start = $currentDate->copy()->subDays(6)->startOfDay(); // includes today
            $end = $currentDate->copy()->endOfDay();
            $labels = collect(range(0, 6))->map(fn($i) => $currentDate->copy()->subDays(6 - $i)->format('d M'))->toArray();
            break;

        case 'last_14_days':
            $start = $currentDate->copy()->subDays(13)->startOfDay();
            $end = $currentDate->copy()->endOfDay();
            $labels = collect(range(0, 13))->map(fn($i) => $currentDate->copy()->subDays(13 - $i)->format('d M'))->toArray();
            break;

        case 'last_28_days':
            $start = $currentDate->copy()->subDays(27)->startOfDay();
            $end = $currentDate->copy()->endOfDay();
            $labels = collect(range(0, 27))->map(fn($i) => $currentDate->copy()->subDays(27 - $i)->format('d M'))->toArray();
            break;

        case 'last_30_days':
            $start = $currentDate->copy()->subDays(29)->startOfDay();
            $end = $currentDate->copy()->endOfDay();
            $labels = collect(range(0, 29))->map(fn($i) => $currentDate->copy()->subDays(29 - $i)->format('d M'))->toArray();
            break;

        case 'this_week':
            $start = $currentDate->copy()->startOfWeek();
            $end = $currentDate->copy()->endOfWeek();
            $labels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            break;

        case 'last_week':
            $start = $currentDate->copy()->subWeek()->startOfWeek();
            $end = $currentDate->copy()->subWeek()->endOfWeek();
            $labels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            break;

        case 'this_month':
            $start = $currentDate->copy()->startOfMonth();
            $end = $currentDate->copy()->endOfMonth();
            $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
            break;

        case 'last_month':
            $start = $currentDate->copy()->subMonth()->startOfMonth();
            $end = $currentDate->copy()->subMonth()->endOfMonth();
            $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
            break;

        case 'custom':
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $days = $start->diffInDays($end);
            $labels = collect(range(0, $days))->map(fn($i) => $start->copy()->addDays($i)->format('d M'))->toArray();
            break;

        default:
            return response()->json(['labels' => [], 'data' => [], 'totalRevenue' => 0]);
    }

    $subscriptions = DB::table('subscriptions')
        ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as revenue'))
        ->whereBetween('created_at', [$start, $end])
        ->groupBy(DB::raw('DATE(created_at)'))
        ->get()
        ->pluck('revenue', 'date');

    $data = [];
    foreach ($labels as $label) {
        if (in_array($period, ['this_week', 'last_week'])) {
            // For weekday labels
            $day = $label;
            $date = $start->copy()->startOfWeek()->addDays(array_search($day, $labels))->format('Y-m-d');
        } elseif (in_array($period, ['this_month', 'last_month'])) {
            // Group by week
            $data = array_fill(0, 5, 0);
            foreach ($subscriptions as $date => $revenue) {
                $week = ceil(Carbon::parse($date)->day / 7);
                $data[$week - 1] += $revenue;
            }
            break;
        } else {
            // Daily labels
            $date = Carbon::createFromFormat('d M', $label)->format('Y-m-d');
        }

        $data[] = $subscriptions->get($date, 0);
    }

    $totalRevenue = array_sum($data);

    return response()->json([
        'labels' => $labels,
        'data' => $data,
        'totalRevenue' => number_format($totalRevenue, 2)
    ]);
}
    
  
public function getSubscriptionCount(Request $request)
{
    // Get selected period from request
    
    
    // Membership titles for mapping slugs to readable labels
    $membershipTitles = [
        'template' => '3+ Code Templates',
        'invoice' => 'Branding',
        'multi_user_access' => 'Multiple User Access',
        'credit_card_support' => 'Credit Card Processing',
        'code_book_support' => 'Code Book',
        'home_depot_support' => 'Built In Supplier',
        'global_setting_support' => 'AI Profit',
    ];
    $period = $request->input('period', 'this_week');
        $currentDate = Carbon::now();
    
        // Define the date range for filtering
        $startDate = null;
        $endDate = $currentDate;
    
        switch ($period) {
            case 'this_week':
                $startDate = $currentDate->copy()->startOfWeek();
                break;
    
            case 'this_month':
                $startDate = $currentDate->copy()->startOfMonth();
                break;
    
            case 'this_year':
                $startDate = $currentDate->copy()->startOfYear();
                break;
    
            default:
                // Handle invalid period gracefully
                return response()->json(['error' => 'Invalid period'], 400);
        }
    
        // Format the dates for the query
        $startDateFormatted = $startDate->format('Y-m-d H:i:s');
        $endDateFormatted = $endDate->format('Y-m-d H:i:s');
    
        // Query for total subscriptions within the date range
        $query = DB::table('subscriptions')->whereBetween('created_at', [$startDateFormatted, $endDateFormatted]);

    // Count the total subscriptions
    $totalSubscriptions = $query->count();

    // Query to get subscription counts grouped by slug
    $subscriptionData = $query
        ->select('slug', DB::raw('count(*) as total'))
        ->groupBy('slug')
        ->get();

    // Prepare the chart data with all possible slugs, including those with 0 counts
    $chartLabels = [];
    $chartData = [];
    $backgroundColors = ['#20c997', '#007bff', '#ffc107', '#fd7e14', '#6f42c1', '#e83e8c', '#28a745'];

    foreach ($membershipTitles as $slug => $title) {
        // Find the subscription count for the current slug
        $subscriptionCount = $subscriptionData->firstWhere('slug', $slug)->total ?? 0;

        $chartLabels[] = $title;
        $chartData[] = $subscriptionCount;
    }

    // Return the response as JSON
    return response()->json([
        'totalSubscriptions' => $totalSubscriptions,
        'labels' => $chartLabels,
        'data' => $chartData,
        'colors' => $backgroundColors,
    ]);
}

public function getContractorsChart(Request $request)
{
    $period = $request->input('period', 'this_week');
    $currentDate = Carbon::now();

    $startDate = null;
    $endDate = $currentDate;

    switch ($period) {
        case 'this_week':
            $startDate = $currentDate->copy()->startOfWeek();
            break;
        case 'this_month':
            $startDate = $currentDate->copy()->startOfMonth();
            break;
        case 'this_year':
            $startDate = $currentDate->copy()->startOfYear();
            break;
        default:
            return response()->json(['error' => 'Invalid period'], 400);
    }

    // Filtered users within period
    $usersInPeriod = User::whereBetween('created_at', [$startDate, $endDate])->pluck('id');

    // Subscriptions for users in period
    $paidUserIds = DB::table('subscriptions')
        ->whereIn('user_id', $usersInPeriod)
        ->whereBetween('created_at', [$startDate, $endDate])
        ->pluck('user_id')
        ->unique();

    $paidCount = $paidUserIds->count();
    $unpaidCount = $usersInPeriod->count() - $paidCount;

    return response()->json([
        'paid' => $paidCount,
        'unpaid' => $unpaidCount,
        'total' => $usersInPeriod->count()
    ]);
}

public function getContractors(Request $request)
{
    // Check if a status filter is applied (e.g., 'paid', 'unpaid')
    $status = $request->get('status', 'all');

    // Base query for users
    $query = User::query();

    // Apply filter based on status
    if ($status === 'paid') {
        $query->whereHas('subscriptions');
    } elseif ($status === 'unpaid') {
        $query->whereDoesntHave('subscriptions');
    }

    // Get the first 7 users
    $users = $query->where('is_admin',1)->latest('id')->take(7)->get();
    $totalUsers = $query->count();

    // Add faded row class to the last record in the current result
    $users = $users->map(function ($user, $index) use ($users) {
        $isLastRecord = $index === $users->count() - 1; // Last record in current batch
        return [
            'name' => $user->first_name." ".$user->last_name,
            'email' => $user->email,
            'company' => $user->company ? $user->company->name : 'N/A',
            'phone' => $user->phone ?? 'N/A',
            'created_at' => $user->created_at->format('M d, Y'),
            'status' => $user->subscriptions->isNotEmpty() ? 'Paid' : 'Unpaid',
            'is_faded_row' => $isLastRecord, // Add fade-row class to the last record
        ];
    });

    // Count totals for both paid and unpaid users
    $paidCount = User::whereHas('subscriptions')->count();
    $unpaidCount = User::whereDoesntHave('subscriptions')->count();

    // Return data as JSON response
    return response()->json([
        'users' => $users,
        'paid' => $paidCount,
        'unpaid' => $unpaidCount,
    ]);
}




}

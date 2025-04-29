<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseType;
use App\Models\Budget;
use App\Models\ExpenseLabel;
use App\Models\Payment;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function WorkHours(Request $request)
    {
        $from = $request->from ?? Carbon::create(now()->year, now()->month, 1)->toDateString();
        $to = $request->to ?? now()->toDateString();

        $invoice_sub_query = DB::table('invoices');
        $invoice_sub_query->leftJoin('invoice_days', 'invoice_days.invoice_id', '=', 'invoices.id');
        $invoice_sub_query->select([
            'invoices.id as invoice_id',
            DB::raw('SUM(invoice_days.hours) as hours'),
            DB::raw('SUM(invoice_days.trailers) as trailers'),
            DB::raw('COUNT(invoice_days.id) as work_days'),
        ]);
        $invoice_sub_query->groupBy([
            'invoices.id',
        ]);

        $query = DB::table('invoices');
        $query->leftJoinSub($invoice_sub_query, 'invoice_days', function ($join) {
            $join->on('invoice_days.invoice_id', '=', 'invoices.id');
        });

        $query->where('invoices.user_id', auth('sanctum')->id());

        $query->whereDate('invoices.invoice_date', '>=', $from);
        $query->whereDate('invoices.invoice_date', '<=', $to);

        $query->select([
            DB::raw('DATE(invoices.invoice_date) as date'),
            DB::raw('SUM(invoice_days.hours) as hours'),
            DB::raw('SUM(invoices.total) as total'),
        ]);

        $query->groupBy([
            'invoices.invoice_date'
        ]);

        $data = $query->get();

        return response()->json($data);
    }

    public function UnpaidInvoices(Request $request)
    {
        $from = $request->from ?? Carbon::create(now()->year, now()->month, 1)->toDateString();
        $to = $request->to ?? now()->toDateString();

        $sub_query = DB::table('invoices');
        $sub_query->leftJoin('payments', 'payments.invoice_id', '=', 'invoices.id');
        $sub_query->where('invoices.user_id', auth('sanctum')->id());
        $sub_query->whereDate('invoices.invoice_date', '>=', $from);
        $sub_query->whereDate('invoices.invoice_date', '<=', $to);
        $sub_query->select([
            'invoices.id',
            DB::raw('SUM(payments.amount) as paid'),
        ]);
        $sub_query->groupBy('invoices.id');

        $query = DB::table('invoices');
        $query->leftJoin('payments', 'payments.invoice_id', '=', 'invoices.id');
        $query->joinSub($sub_query, 'invoices_payments', function ($join) {
            $join->on('invoices_payments.id', '=', 'invoices.id');
        });

        $query->where('invoices.user_id', auth('sanctum')->id());

        $query->whereDate('invoices.invoice_date', '>=', $from);
        $query->whereDate('invoices.invoice_date', '<=', $to);

        $query->select([
            DB::raw('DATE(invoices.invoice_date) as "date"'),
            DB::raw('SUM(invoices.total - invoices_payments.paid) as unpaid_amount'),
        ]);

        $query->groupBy([
            'invoices.invoice_date',
            'invoices.total'
        ]);

        $query->havingRaw('SUM(invoices.total - invoices_payments.paid) > 0');

        $data = $query->get();

        return response()->json($data);
    }

    public function LatestPayments(Request $request)
    {
        $query = Payment::query();

        $query->with([
            'Invoice.Client',
        ]);

        $query->whereHas('Invoice', function ($query) {
            $query->where('user_id', auth('sanctum')->id());
        });

        $query->latest();

        $query->take(4);

        $data = $query->get()->map(function ($payment) {
            return [
                'client' => $payment->Invoice->Client->first_name . ' ' . $payment->Invoice->Client->last_name,
                'date' => $payment->created_at,
                'amount' => $payment->amount
            ];
        });

        return response()->json($data);
    }

    public function LatestUnpaidClients()
    {
        $sub_query = DB::table('invoices');
        $sub_query->leftJoin('payments', 'payments.invoice_id', '=', 'invoices.id');
        $sub_query->where('invoices.user_id', auth('sanctum')->id());
        $sub_query->select([
            'invoices.id',
            DB::raw('SUM(payments.amount) as paid'),
        ]);
        $sub_query->groupBy('invoices.id');

        $hours_sub_query = DB::table('invoices');
        $hours_sub_query->leftJoin('invoice_days', 'invoice_days.invoice_id', '=', 'invoices.id');
        $hours_sub_query->where('invoices.user_id', auth('sanctum')->id());
        $hours_sub_query->select([
            'invoices.id',
            DB::raw('SUM(invoice_days.hours) as hours'),
        ]);
        $hours_sub_query->groupBy('invoices.id');

        $query = DB::table('invoices');
        $query->join('clients', 'clients.id', '=', 'invoices.client_id');
        $query->join('activities', 'activities.id', '=', 'invoices.activity_id');
        $query->joinSub($sub_query, 'invoices_payments', function ($join) {
            $join->on('invoices_payments.id', '=', 'invoices.id');
        });
        $query->joinSub($hours_sub_query, 'invoices_hours', function ($join) {
            $join->on('invoices_hours.id', '=', 'invoices.id');
        });

        $query->where('invoices.user_id', auth('sanctum')->id());

        $query->select([
            DB::raw('CONCAT(clients.first_name, " ", clients.last_name) as client_name'),
            DB::raw('invoices.total - IFNULL(invoices_payments.paid,0) as unpaid_amount'),
            DB::raw('DATE(invoices.invoice_date) as date'),
            'invoices_hours.hours',
            'activities.name as activity',
            'invoices.id as invoice_id',
        ]);

        $query->whereRaw('invoices.total > IFNULL(invoices_payments.paid,0)');

        $query->orderBy('clients.first_name');
        $query->orderBy('unpaid_amount', 'desc');

        // $data = $query->take(5)->get();
        $data = $query->get();

        return response()->json($data);
    }

    public function Clients(Request $request)
    {
        $from = $request->from;
        $to = $request->to;

        $query = DB::table('clients');

        $query->where('clients.user_id', auth('sanctum')->id());

        if ($from && $to) {
            $query->whereDate('clients.created_at', '>=', $from);
            $query->whereDate('clients.created_at', '<=', $to);
        }

        $query->select([
            DB::raw('DATE(clients.created_at) as "date"'),
            DB::raw('COUNT(clients.id) as count'),
        ]);

        $query->groupBy([
            DB::raw('DATE(clients.created_at)')
        ]);

        return response()->json($query->get());
    }

    public function Invoices(Request $request)
    {
        $from = $request->from ?? Carbon::create(now()->year, now()->month, 1)->toDateString();
        $to = $request->to ?? now()->toDateString();

        $query = DB::table('invoices');

        $query->where('invoices.user_id', auth('sanctum')->id());

        $query->whereDate('invoices.invoice_date', '>=', $from);
        $query->whereDate('invoices.invoice_date', '<=', $to);

        $query->select([
            DB::raw('DATE(invoices.invoice_date) as date'),
            DB::raw('SUM(invoices.total) as total'),
        ]);

        $query->groupBy([
            DB::raw('DATE(invoices.invoice_date)'),
        ]);

        $query->orderBy('date', 'asc');

        return response()->json($query->get());
    }

    private function weeks_by_month($year)
    {
        $return = [];
        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::create($year, $month, 1);
            $weeks = 0;
            while ($date->month == $month) {
                $weeks++;
                $date = $date->addWeeks(1);
            }
            $return[$month] = $weeks;
        }
        return $return;
    }

    public function Graph(Request $request)
    {
        $year = $request->year ?? now()->year;
        $budget_id = $request->budget;

        $invoice_total_sub_query = DB::table('invoices');
        $invoice_total_sub_query->leftJoin('invoice_days', 'invoice_days.invoice_id', '=', 'invoices.id');
        $invoice_total_sub_query->select([
            'invoices.id as invoice_id',
            DB::raw('SUM(invoice_days.hours) as hours'),
            DB::raw('SUM(invoice_days.trailers) as trailers'),
            DB::raw('COUNT(invoice_days.id) as work_days'),
        ]);
        $invoice_total_sub_query->groupBy([
            'invoices.id',
        ]);

        $invoice_sub_query = DB::table('invoices');
        $invoice_sub_query->where('invoices.user_id', auth('sanctum')->id());
        $invoice_sub_query->whereYear('invoices.invoice_date', $year);

        $invoice_sub_query->select([
            DB::raw('MONTH(invoices.invoice_date) as month'),
            DB::raw('SUM(invoices.total) as total'),
        ]);

        $invoice_sub_query->groupBy([
            'invoices.invoice_date'
        ]);

        $budget = Budget::find($budget_id);

        $budget_sub_query = DB::table('invoices');
        $budget_sub_query->where('invoices.user_id', auth('sanctum')->id());
        $budget_sub_query->whereYear('invoices.invoice_date', $year);

        $budget_sub_query->select([
            DB::raw('MONTH(invoices.invoice_date) as month'),
            DB::raw('SUM(invoices.total) as total'),
            DB::raw($budget->amount / 12 . ' as budget'),
            DB::raw('SUM(invoices.total) -' . $budget->amount / 12 . ' as diffrence'),
        ]);
        $budget_sub_query->groupBy([
            'invoices.invoice_date'
        ]);

        $activities_query = DB::table('invoices');
        $activities_query->join('activities', 'activities.id', '=', 'invoices.activity_id');
        $activities_query->leftJoinSub($invoice_total_sub_query, 'invoice_days', function ($join) {
            $join->on('invoice_days.invoice_id', '=', 'invoices.id');
        });
        $activities_query->where('invoices.user_id', auth('sanctum')->id());
        $activities_query->whereYear('invoices.invoice_date', $year);
        $activities_query->select([
            'activities.name',
            DB::raw('COUNT(invoices.id) as count_invoices'),
            DB::raw('SUM(invoice_days.hours) as total_hours_invoices'),
            DB::raw('SUM(invoices.total) as total_invoices'),
            DB::raw('SUM(invoices.total) / SUM(invoice_days.hours) as ratio'),
        ]);
        $activities_query->groupBy([
            'activities.name'
        ]);

        $month_working_days_sub_query = DB::table('invoices');
        $month_working_days_sub_query->where('invoices.user_id', auth('sanctum')->id());
        $month_working_days_sub_query->whereYear('invoices.invoice_date', $year);
        $month_working_days_sub_query->select([
            DB::raw('MONTH(invoices.invoice_date) as month'),
            DB::raw('COUNT(DISTINCT DATE(invoices.invoice_date)) as days'),
        ]);
        $month_working_days_sub_query->groupBy([
            'invoices.invoice_date'
        ]);

        /////////////////////////////


        $month_total_query = DB::table('invoices');
        $month_total_query->where('invoices.user_id', auth('sanctum')->id());
        $month_total_query->whereYear('invoices.invoice_date', $year);
        $month_total_query->leftJoinSub($invoice_total_sub_query, 'invoice_days', function ($join) {
            $join->on('invoice_days.invoice_id', '=', 'invoices.id');
        });
        $month_total_query->select([
            DB::raw('MONTH(invoices.invoice_date) as month'),
            DB::raw('SUM(invoices.total) as total'),
            DB::raw('SUM(invoice_days.hours) as hours'),
        ]);
        $month_total_query->groupBy([
            'invoices.invoice_date',
        ]);

        $month_ratio_query = DB::query();
        $month_ratio_query->fromSub($month_total_query, 'month_total');
        $month_ratio_query->leftJoinSub($month_working_days_sub_query, 'month_working_days', function ($join) {
            $join->on('month_working_days.month', '=', 'month_total.month');
        });
        $month_ratio_query->select([
            'month_total.month',
            'month_working_days.days',
            'month_total.hours',
            DB::raw('month_total.total / month_working_days.days as ratio_euro_per_day'),
            DB::raw('month_total.total / month_total.hours as ratio_euro_per_hour'),
        ]);

        $remorque_query = DB::table('invoices');
        $remorque_query->where('invoices.user_id', auth('sanctum')->id());
        $remorque_query->leftJoinSub($invoice_total_sub_query, 'invoice_days', function ($join) {
            $join->on('invoice_days.invoice_id', '=', 'invoices.id');
        });
        $remorque_query->where(function ($q) {
            $q->whereNotNull('invoice_days.trailers');
            $q->orWhere('invoice_days.trailers', '!=', 0);
        });
        $remorque_query->whereYear('invoices.invoice_date', $year);


        return response()->json([
            'budget_realisation_comparison' => $budget_sub_query->get()->toArray(),
            'activities_data' => $activities_query->get()->toArray(),
            'month_ratio' => $month_ratio_query->get()->toArray(),
            'trailers' => $remorque_query->sum('invoice_days.trailers'),
        ]);
    }

    public function WeeksComparison(Request $request)
    {
        $year = $request->year ?? now()->year;
        $budget_id = $request->budget;

        $budget = Budget::find($budget_id);
        $year_weeks = (new DateTime('December 28th ' . $year))->format('W');

        $invoices_query = DB::table('invoices');
        $invoices_query->where('invoices.user_id', auth('sanctum')->id());
        $invoices_query->whereYear('invoices.invoice_date', $year);
        $invoices_query->select([
            DB::raw('MONTH(invoices.invoice_date) as invoice_month'),
            DB::raw('WEEKOFYEAR(invoices.invoice_date) as invoice_week'),
            DB::raw('SUM(invoices.total) as invoice_total'),
            DB::raw($budget->amount / $year_weeks . ' as budget'),
        ]);
        $invoices_query->orderBy('invoice_week');
        $invoices_query->groupBy([
            'invoices.invoice_date',
        ]);

        return response()->json($invoices_query->get());
    }

    public function Accounts(Request $request)
    {
        $from = $request->from ?? Carbon::create(now()->year, now()->month, 1)->toDateString();
        $to = $request->to ?? now()->toDateString();

        $invest_expenses = ExpenseLabel::whereHas('Expenses', function ($q) use ($from, $to) {
            $q->where('user_id', auth('sanctum')->id());
            $q->whereDate('created_at', '>=', $from);
            $q->whereDate('created_at', '<=', $to);
        })
            ->whereHas('ExpenseCategory', function ($q) {
                $q->where('type', ExpenseType::INVEST->value);
            })
            ->get()
            ->pluck('name')->toArray();

        $invest_query = DB::table('expenses');
        $invest_query->join('expense_labels', 'expense_labels.id', '=', 'expenses.expense_label_id');
        $invest_query->join('expense_categories', 'expense_categories.id', '=', 'expense_labels.expense_category_id');

        $invest_query->where('expense_categories.type', ExpenseType::INVEST->value);
        $invest_query->where('expenses.user_id', auth('sanctum')->id());

        $select = [
            DB::raw('DATE(expenses.created_at) as expense_date'),
        ];
        foreach ($invest_expenses as $key => $value) {
            $select[] = DB::raw('SUM(CASE WHEN expense_labels.name = "' . $value . '" THEN expenses.amount ELSE 0 END) as ' . $value);
        }
        $select[] = DB::raw('SUM(expenses.amount) as total');

        $invest_query->select($select);

        $invest_query->groupBy([
            DB::raw('DATE(expenses.created_at)'),
        ]);

        return response()->json($invest_query->get());
    }
}

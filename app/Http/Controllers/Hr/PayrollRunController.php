<?php

namespace App\Http\Controllers\Hr;

use App\Account;
use App\BusinessLocation;
use App\Http\Controllers\Controller;
use App\PayrollRun;
use App\Payslip;
use App\Utils\PayrollUtil;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PayrollRunController extends Controller
{
    protected $payrollUtil;

    public function __construct(PayrollUtil $payrollUtil)
    {
        $this->payrollUtil = $payrollUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('payroll.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $runs = PayrollRun::where('payroll_runs.business_id', $business_id)
                ->leftjoin('business_locations as bl', 'payroll_runs.location_id', '=', 'bl.id')
                ->select([
                    'payroll_runs.id', 'payroll_runs.pay_period_start', 'payroll_runs.pay_period_end',
                    'payroll_runs.pay_date', 'payroll_runs.status', 'payroll_runs.total_net_pay',
                    'bl.name as location_name',
                ]);

            return DataTables::of($runs)
                ->editColumn('pay_period_start', '{{@format_date($pay_period_start)}}')
                ->editColumn('pay_period_end', '{{@format_date($pay_period_end)}}')
                ->editColumn('pay_date', '{{@format_date($pay_date)}}')
                ->editColumn('total_net_pay', '{{@num_format($total_net_pay)}}')
                ->editColumn('status', function ($row) {
                    $labels = ['draft' => 'bg-yellow', 'approved' => 'bg-info', 'paid' => 'bg-green', 'cancelled' => 'bg-red'];

                    return '<span class="label '.($labels[$row->status] ?? 'bg-gray').'">'.__('hr.payroll_status_'.$row->status).'</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="'.action([\App\Http\Controllers\Hr\PayrollRunController::class, 'show'], [$row->id]).'" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> '.__('messages.view').'</a>';
                })
                ->removeColumn('id')
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return view('hr.payroll_runs.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('payroll.manage')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('hr.payroll_runs.create')->with(compact('business_locations'));
    }

    /**
     * Store a newly created resource in storage — generates the draft run and its payslips.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('payroll.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $payroll_run = $this->payrollUtil->generatePayrollRun(
                $business_id,
                $request->input('location_id'),
                $request->input('pay_period_start'),
                $request->input('pay_period_end'),
                $request->input('pay_date'),
                $user_id
            );

            $output = ['success' => true, 'msg' => __('hr.payroll_run_generated_success')];

            return redirect()->action([\App\Http\Controllers\Hr\PayrollRunController::class, 'show'], [$payroll_run->id])->with('status', $output);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];

            return redirect()->action([\App\Http\Controllers\Hr\PayrollRunController::class, 'create'])->with('status', $output);
        }
    }

    /**
     * Display the specified resource — the run's payslips.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('payroll.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $payroll_run = PayrollRun::where('business_id', $business_id)
            ->with(['location', 'payslips.employee'])
            ->findOrFail($id);

        $accounts = Account::forDropdown($business_id, false);

        return view('hr.payroll_runs.show')->with(compact('payroll_run', 'accounts'));
    }

    /**
     * Approves the draft payroll run, locking its amounts in ahead of posting to the ledger.
     */
    public function approve(Request $request, $id)
    {
        if (! auth()->user()->can('payroll.approve')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $this->payrollUtil->approvePayrollRun($id, $business_id, auth()->user()->id);

            $output = ['success' => true, 'msg' => __('hr.payroll_run_approved_success')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => $e->getMessage()];
        }

        return redirect()->action([\App\Http\Controllers\Hr\PayrollRunController::class, 'show'], [$id])->with('status', $output);
    }

    /**
     * Posts the run's net pay to the existing cash/bank ledger via a 'payroll' transaction.
     */
    public function postToLedger(Request $request, $id)
    {
        if (! auth()->user()->can('payroll.post_to_ledger')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $this->payrollUtil->postPayrollRunToLedger($id, $business_id, $request->input('account_id'), auth()->user()->id);

            $output = ['success' => true, 'msg' => __('hr.payroll_posted_success')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => $e->getMessage()];
        }

        return redirect()->action([\App\Http\Controllers\Hr\PayrollRunController::class, 'show'], [$id])->with('status', $output);
    }

    /**
     * Cancels a payroll run that hasn't been posted to the ledger yet.
     */
    public function cancel(Request $request, $id)
    {
        if (! auth()->user()->can('payroll.manage')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $this->payrollUtil->cancelPayrollRun($id, $business_id);

            $output = ['success' => true, 'msg' => __('hr.payroll_run_cancelled_success')];
        } catch (\Exception $e) {
            $output = ['success' => false, 'msg' => $e->getMessage()];
        }

        return redirect()->action([\App\Http\Controllers\Hr\PayrollRunController::class, 'show'], [$id])->with('status', $output);
    }

    /**
     * Streams a single employee's payslip as a PDF.
     *
     * @param  int  $payslip_id
     */
    public function payslipPdf($payslip_id)
    {
        if (! auth()->user()->can('payroll.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $payslip = Payslip::whereHas('payroll_run', function ($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })->with(['employee', 'lines', 'payroll_run'])->findOrFail($payslip_id);

        $business_name = request()->session()->get('business.name');

        $body = view('hr.payroll_runs.partials.payslip_pdf')
            ->with(compact('payslip', 'business_name'))
            ->render();

        $mpdf = $this->getMpdf();
        $mpdf->SetTitle('Payslip-'.$payslip->employee->employee_code.'.pdf');
        $mpdf->WriteHTML($body);
        $mpdf->Output('Payslip-'.$payslip->employee->employee_code.'.pdf', 'I');
    }
}

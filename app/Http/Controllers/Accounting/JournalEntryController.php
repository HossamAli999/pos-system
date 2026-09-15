<?php

namespace App\Http\Controllers\Accounting;

use App\ChartOfAccount;
use App\Http\Controllers\Controller;
use App\JournalEntry;
use App\Utils\JournalUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class JournalEntryController extends Controller
{
    protected $journalUtil;

    protected $commonUtil;

    public function __construct(JournalUtil $journalUtil, Util $commonUtil)
    {
        $this->journalUtil = $journalUtil;
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('journal_entry.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $entries = JournalEntry::where('business_id', $business_id)
                ->select(['id', 'entry_date', 'entry_type', 'reference_number', 'narration', 'is_posted']);

            return DataTables::of($entries)
                ->editColumn('entry_date', '{{@format_date($entry_date)}}')
                ->editColumn('entry_type', function ($row) {
                    return __('gl.type_'.$row->entry_type);
                })
                ->addColumn('action', function ($row) {
                    return '<a href="'.action([\App\Http\Controllers\Accounting\JournalEntryController::class, 'show'], [$row->id]).'" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> '.__('messages.view').'</a>';
                })
                ->removeColumn('id')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('accounting.journal_entries.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('journal_entry.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $accounts = ChartOfAccount::forDropdown($business_id);

        return view('accounting.journal_entries.create')->with(compact('accounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('journal_entry.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');

            $lines = [];
            foreach ($request->input('lines', []) as $line) {
                if (empty($line['chart_of_account_id'])) {
                    continue;
                }

                $debit = ! empty($line['debit']) ? $this->commonUtil->num_uf($line['debit']) : 0;
                $credit = ! empty($line['credit']) ? $this->commonUtil->num_uf($line['credit']) : 0;

                if (empty($debit) && empty($credit)) {
                    continue;
                }

                $lines[] = [
                    'chart_of_account_id' => $line['chart_of_account_id'],
                    'debit' => $debit,
                    'credit' => $credit,
                    'memo' => $line['memo'] ?? null,
                ];
            }

            $this->journalUtil->postEntry(
                $business_id,
                $lines,
                $request->input('entry_date'),
                'manual',
                null,
                $request->input('narration'),
                $user_id
            );

            $output = ['success' => true, 'msg' => __('gl.entry_added_success')];

            return redirect()->action([\App\Http\Controllers\Accounting\JournalEntryController::class, 'index'])->with('status', $output);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => $e->getMessage() ?: __('messages.something_went_wrong')];

            return redirect()->action([\App\Http\Controllers\Accounting\JournalEntryController::class, 'create'])->with('status', $output);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('journal_entry.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $entry = JournalEntry::where('business_id', $business_id)->with('lines.chart_of_account', 'created_by_user')->findOrFail($id);

        return view('accounting.journal_entries.show')->with(compact('entry'));
    }

    /**
     * Posts a reversing entry for the given entry.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reverse(Request $request, $id)
    {
        if (! auth()->user()->can('journal_entry.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $entry = JournalEntry::where('business_id', $business_id)->findOrFail($id);

            $this->journalUtil->reverseEntry($entry->id, null, auth()->user()->id);

            $output = ['success' => true, 'msg' => __('gl.entry_reversed_success')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()->action([\App\Http\Controllers\Accounting\JournalEntryController::class, 'show'], [$id])->with('status', $output);
    }
}

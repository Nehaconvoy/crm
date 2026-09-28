<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Department;
use App\Models\Manger;
use App\Models\Office;
use App\Models\StatusType;
use App\Models\ShipmentType;
use App\Models\EquipmentType;
use App\Models\Role;
use App\Models\Permission;
use App\Models\TeamLeader;
use App\Models\Load;
use App\Models\Customer;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\External;
use App\Models\Shipper;
use App\Models\Consignee;
use App\Models\IpConfig;
use App\Models\Log as activity_log;
use App\Models\CustomerApprovalForm;
use App\Models\ItHardware;
use Dompdf\Dompdf;
use Dompdf\Options;
use PDF;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet; 
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\CreditService;


class AdminController extends Controller
{
    protected CreditService $creditService;

    public function __construct(CreditService $creditService)
    {
        $this->creditService = $creditService;
    }

   public function correct_data(){
	   $allload = Load::get('load_mc_no');
	   foreach($allload as $load){
		   $mcno = $load->load_mc_no;
		   // $carrier = External::where('carrier_mc_ff_input', $mcno)->first();
		   // $data = array(
				// 'load_mc' => $mcno,
				// 'carrir_mc' => $carrier->carrier_mc_ff_input
		   // );
		   //print_r($data);
	   }
   }
   
   public function getByOffice($officeId)
	{
		$managers = Manger::where('office', $officeId)->get(['id', 'manager']);
		$teamLeaders = TeamLeader::where('office', $officeId)->get(['id', 'tl']);
		$agents = User::where('office', $officeId)->where('status', 'active')->where('role_id', 21)->get(['id', 'name']);

		return response()->json([
			'managers' => $managers,
			'team_leaders' => $teamLeaders,
			'agents' => $agents,
		]);
	}

	public function getByManager($managerId)
	{
		$teamLeaders = TeamLeader::where('leader_manager', $managerId)->get(['id', 'tl']);
		$agents = User::where('manager', $managerId)->where('status', 'active')->where('role_id', 21)->get(['id', 'name']);

		return response()->json([
			'team_leaders' => $teamLeaders,
			'agents' => $agents,
		]);
	}

	public function getByTeamLeader($tlId)
	{
		$agents = User::where('team_lead', $tlId)->where('status', 'active')->where('role_id', 21)->get(['id', 'name']);

		return response()->json([
			'agents' => $agents,
		]);
	}

   
  public function search_by_filter(Request $request)
{
    $filters = [
        'office'   => $request->input('office'),
        'manager'  => $request->input('manager'),
        'teamlead' => $request->input('teamLeader'),
        'agent'    => $request->input('agent'),
    ];

    // Resolve the current page from the relevant page parameter for the active tab.
    if ($request->hasAny(['page', 'all_load', 'open', 'delivered', 'completed', 'invoiced', 'invoiced_paid'])) {
        $activeTab = $request->input('tab');
        $pageParam = 'page';

        if ($activeTab === '#all_load') {
            $pageParam = 'all_load';
        } elseif ($activeTab === '#open') {
            $pageParam = 'open';
        } elseif ($activeTab === '#delivered') {
            $pageParam = 'delivered';
        } elseif ($activeTab === '#completed') {
            $pageParam = 'completed';
        } elseif ($activeTab === '#invoiced') {
            $pageParam = 'invoiced';
        } elseif ($activeTab === '#invoiced_paid') {
            $pageParam = 'invoiced_paid';
        }

        Paginator::currentPageResolver(function ($pageName = null) use ($request, $pageParam) {
            $pageKey = $pageName ?: $pageParam;
            $pageValue = $request->input($pageKey);

            if ($pageValue !== null) {
                return (int) $pageValue;
            }

            foreach (['page', 'all_load', 'open', 'delivered', 'completed', 'invoiced', 'invoiced_paid'] as $fallbackKey) {
                if ($request->filled($fallbackKey)) {
                    return (int) $request->input($fallbackKey);
                }
            }

            return 1;
        });
    }

    $broker_status = $this->filteredLoadsQuery(Load::with('user'), $filters)
        ->orderBy('id', 'desc')
            ->paginate(50, ['*'], 'all_load');

    $open = $this->filteredLoadsQuery(
            Load::with('user')->where('load_status', 'Open'),
            $filters)
        ->orderBy('id', 'desc')
            ->paginate(50, ['*'], 'open');

    $deliverd = $this->filteredLoadsQuery(
            Load::with('user')->where('load_status', 'Delivered'),
            $filters)
        ->orderBy('id', 'desc')
            ->paginate(50, ['*'], 'delivered');

    $complete = $this->filteredLoadsQuery(
            Load::with(['user', 'customer', 'carrier'])
                ->where('load_status', 'Completed')
                ->where(function ($query) {
                    $query->whereNull('invoice_status')->orWhere('invoice_status', '');
                }),
            $filters)
        ->orderBy('loads.id', 'desc')
            ->paginate(50, ['*'], 'completed');

    $invoice_paid = $this->filteredLoadsQuery(
            Load::with('user')->where('invoice_status', 'Paid'),
            $filters)
        ->orderBy('id', 'desc')
            ->paginate(50, ['*'], 'invoiced');

    $paid_record = $this->filteredLoadsQuery(
            Load::with('user')->where('invoice_status', 'Paid Record'),
            $filters)
        ->orderBy('id', 'desc')
            ->paginate(50, ['*'], 'invoiced_paid');

    $allagent = User::where('status', 'active')->pluck('name');
    $manager  = Manger::get();
    $teamlead = TeamLeader::get();
    $office   = Office::get();

    if ($request->ajax()) {

        // Tab ke hisaab se view aur uska paginator map karo
        $tabConfig = [
            '#all_load'      => ['view' => 'admin.home.all_load',      'paginator' => $broker_status],
            '#open'          => ['view' => 'admin.home.open_load',     'paginator' => $open],
            '#delivered'     => ['view' => 'admin.home.delivered',     'paginator' => $deliverd],
            '#completed'     => ['view' => 'admin.home.completed',     'paginator' => $complete],
            '#invoiced'      => ['view' => 'admin.home.invoiced',      'paginator' => $invoice_paid],
            '#invoiced_paid' => ['view' => 'admin.home.invoiced_paid', 'paginator' => $paid_record],
        ];

        $activeTab = $request->input('tab');
        $config = $tabConfig[$activeTab] ?? $tabConfig['#all_load'];

        $html = view($config['view'], compact(
            'broker_status', 'allagent', 'open', 'deliverd',
            'complete', 'invoice_paid', 'paid_record',
            'manager', 'teamlead', 'office'
        ))->render();

        $pagination = render_pagination_links($config['paginator']);

        return response()->json([
            'html'       => $html,
            'pagination' => $pagination,
        ]);
    }

    return view('admin.home', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office'));
}
	
	protected function filteredLoadsQuery($baseQuery, $filters)
	{
		return $baseQuery
			->when($filters['office'], function ($query) use ($filters) {
				$query->whereHas('user', function ($q) use ($filters) {
					$q->where('users.office', $filters['office']);
				});
			})
			->when($filters['manager'], function ($query) use ($filters) {
				$query->whereHas('user', function ($q) use ($filters) {
					$q->where('users.manager', $filters['manager']);
				});
			})
			->when($filters['teamlead'], function ($query) use ($filters) {
				$query->whereHas('user', function ($q) use ($filters) {
					$q->where('users.team_lead', $filters['teamlead']);
				});
			})
			->when($filters['agent'], function ($query) use ($filters) {
				$query->whereHas('user', function ($q) use ($filters) {
					$q->where('users.id', $filters['agent']); // assumes agent is user_id
				});
			});
	}

    public function home(Request $request){
        $broker_status = Load::with('user')->orderBy("id", "desc")->paginate(50, ['*'], 'all_load');
        $allagent = User::pluck('name');
        $open = Load::with('user')->where('load_status', 'Open')->orderBy("id", "desc")->paginate(50, ['*'], 'open');
        $deliverd = Load::with('user')->where('load_status', 'Delivered')->orderBy("id", "desc")->paginate(50, ['*'], 'delivered');
        $complete = Load::where('load_status', 'Completed')
                    ->where(function ($query) {
                        $query->where('invoice_status', '')
                            ->orWhereNull('invoice_status');
                    })
                    ->with(['user', 'customer', 'carrier'])
                    ->orderBy("loads.id", "desc")
                    ->paginate(50, ['*'], 'completed');
        $invoice_paid = Load::with('user')->where('invoice_status', 'Paid')->orderBy("id", "desc")->paginate(50, ['*'], 'invoiced');
        $paid_record = Load::with('user')->where('invoice_status', 'Paid Record')->orderBy("id", "desc")->paginate(50, ['*'], 'invoiced_paid');
        $manager = Manger::get();
        $teamlead = TeamLeader::get();
        $office = Office::get();
		$agent = User::where('role_id', 21)->get();
		
		if ($request->ajax()) {
			
			if($request->input('tab') == '#all_load'){
				return view('admin.home.all_load', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'))->render();
			}else if($request->input('tab') == '#open'){
				return view('admin.home.open_load', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'))->render();
			}else if($request->input('tab') == '#delivered'){
				return view('admin.home.delivered', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'))->render();
			}else if($request->input('tab') == '#completed'){
				return view('admin.home.completed', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'))->render();
			}else if($request->input('tab') == '#invoiced'){
				return view('admin.home.invoiced', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'))->render();
			}else if($request->input('tab') == '#invoiced_paid'){
				return view('admin.home.invoiced_paid', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'))->render();
			}
				
		}

         return view('admin.home', compact('broker_status', 'allagent', 'open', 'deliverd', 'complete', 'invoice_paid', 'paid_record', 'manager', 'teamlead', 'office','agent'));

    }

    public function all_data(Request $request){
        $activeTab = match (true) {
            $request->filled('carrier') => 'carrier',
            $request->filled('consignee') => 'consignee',
            $request->filled('shipper') => 'shipper',
            $request->filled('loads') => 'load',
            default => 'customer',
        };
		
			if ($request->has('loads')) {
				Paginator::currentPageResolver(function () use ($request) {
					return $request->input('loads');
				});
			}else if ($request->has('shipper')) {
				Paginator::currentPageResolver(function () use ($request) {
					return $request->input('shipper');
				});
			}else if ($request->has('consignee')) {
				Paginator::currentPageResolver(function () use ($request) {
					return $request->input('consignee');
				});
			}else if ($request->has('carrier')) {
				Paginator::currentPageResolver(function () use ($request) {
					return $request->input('carrier');
				});
			}else if ($request->has('customer')) {
				Paginator::currentPageResolver(function () use ($request) {
					return $request->input('customer');
				});
			}

            $customers = Customer::orderBy('id', 'DESC')->get();

            $countries = Country::orderByRaw('CASE WHEN id = 233 THEN 0 WHEN id = 39 THEN 1 ELSE 2 END')
                                ->orderBy('name')
                                ->get();

            $states = State::orderBy('name')->get();

            $cities = City::all();  

            $users = User::get();

            $approvedCustomers = $customers->where('status', 'Approved');

            $external = External::orderBy("id", "desc")->paginate(50, ['*'], 'carrier');

            $shipper = Shipper::select(
                            'shippers.*', 
                            'users.name as user_name',
                            'users.manager',
                            'users.team_lead'
                        )
                        ->join('users', 'shippers.user_id', '=', 'users.id')
                        ->orderBy('shippers.id', 'DESC')
                        ->paginate(50, ['*'], 'shipper');

            $consignee = Consignee::orderBy("id", "desc")->paginate(50, ['*'], 'consignee');

            $loads = Load::orderBy("id", "desc")->paginate(50, ['*'], 'loads');

            $manager = Manger::get();

            $teamlead = TeamLeader::get();
            

            $office = Office::get();

            $sortedCustomers = Customer::orderByRaw("CASE WHEN status = 'Not Approved' THEN 0 ELSE 1 END")
                        ->orderBy('id', 'DESC')  // Optional: further ordering by ID
                        ->paginate(100, ['*'], 'customer');


			if ($request->ajax()) {
			
                $tabConfig = [
                    '#customer' => ['view' => 'admin.all_data.customer', 'paginator' => $sortedCustomers],
                    '#carrier' => ['view' => 'admin.all_data.carrier', 'paginator' => $external],
                    '#consignee' => ['view' => 'admin.all_data.consignee', 'paginator' => $consignee],
                    '#shipper' => ['view' => 'admin.all_data.shipper', 'paginator' => $shipper],
                    '#load' => ['view' => 'admin.all_data.loads', 'paginator' => $loads],
                ];
                $config = $tabConfig[$request->input('tab')] ?? $tabConfig['#customer'];
                $html = view($config['view'], compact('sortedCustomers', 'countries', 'states', 'cities', 'customers', 'approvedCustomers', 'users', 'external', 'shipper', 'consignee', 'loads','manager','teamlead','office'))->render();
                return response()->json(['html' => $html, 'pagination' => render_pagination_links($config['paginator'])]);
			}						

         return view('admin.all_data', compact('sortedCustomers', 'countries', 'states', 'cities', 'customers', 'approvedCustomers', 'users', 'external', 'shipper', 'consignee', 'loads','manager','teamlead','office', 'activeTab'));

    }
     /**
     * Display a listing of the resource.
     */
    
    public function users(Request $request)
    {
        $brokers = User::with('role', 'department', 'managers', 'teamleader', 'office')->where('department', 3)->get();
        $roles = Role::all();
        $offices = Office::all();
        $departments = Department::all();
        $managers = Manger::all();
        $teamleaders = TeamLeader::all();

        return view('admin.broker_users', compact('brokers','roles', 'offices', 'departments', 'managers', 'teamleaders'));
    }
 
    public function account_users(Request $request)
    {
        $accounts = User::with('role', 'department', 'managers', 'teamleader', 'office')->where('department', 2)->paginate(10);
        return view('admin.account_users', compact('accounts')); 
    }

    public function admin_users(Request $request)
    {
        $admins = User::with('role', 'department', 'managers', 'teamleader', 'office')->whereIn('department', [1, 6, 7])->paginate(10);
        return view('admin.admin_users', compact('admins'));
    } 
	
	public function broker_users_search(Request $request)
    {

        $q = $request->input('query');
        if (!empty($q)) {
            
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); 
            });

            if (count($searchTerms) > 0) {
				$brokers = User::with('role', 'department', 'managers', 'teamleader', 'office')->where('department', 3)->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('name', 'like', "%$term%");
                        }
                    })->get();
                
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $brokers = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
           $brokers = User::with('role', 'department', 'managers', 'teamleader', 'office')->where('department', 3)->paginate(10); 
        }
        return view('admin.users.brokers', compact('brokers'))->render();
    }
	
	public function account_users_search(Request $request)
    {

        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
				$accounts = User::with('role', 'department', 'managers', 'teamleader', 'office')->where('department', 2)->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('name', 'like', "%$term%");
                        }
                    })->get();
                
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $accounts = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
           $accounts = User::with('role', 'department', 'managers', 'teamleader', 'office')->where('department', 2)->paginate(10); 
        }
        return view('admin.users.accounts', compact('accounts'))->render();
    }
	
	public function admins_users_search(Request $request)
    {

        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
				$admins = User::with('role', 'department', 'managers', 'teamleader', 'office')->whereIn('department', [1, 6, 7])->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('name', 'like', "%$term%");
                        }
                    })->get();
                
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $admins = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
           $admins = User::with('role', 'department', 'managers', 'teamleader', 'office')->whereIn('department', [1, 6, 7])->paginate(10); 
        }
        return view('admin.users.admins', compact('admins'))->render();
    }

    public function add_user(Request $request)
    {
        $allDepartment = Department::get();
        $allOffice = Office::get();
        $allmanger = Manger::get();
        $allteamleader = TeamLeader::get();
        $allroles = Role::get();
        return view('admin.add_new_user',compact('allDepartment', 'allOffice', 'allmanger', 'allteamleader', 'allroles'));
    }
    
	public function create_load(Request $request)
    {
		$customer = Customer::where('status', 'Approved')->get();
		$equipmentType = EquipmentType::all();
        $shipmentType = ShipmentType::all();
         $users = User::where('role_id', 21)->get();
        return view('admin.create_load', compact('customer','equipmentType','shipmentType','users'));
    } 

    public function createuser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'FullName' => 'required|string|max:255',
            'EmailAddress' => 'required|email|unique:users,email',
            'ConfirmEmailAddress' => 'required|same:EmailAddress',
            'Password' => 'required|string|min:6',
            'ConfirmPassword' => 'required|same:Password',
            'EmployeeCode' => 'required|string|max:255',
            'FullAddress' => 'required|string|max:255',
            'modalOfficeName' => 'required|exists:offices,id',
            'modaldepartmentName' => 'required|exists:departments,id',
            //'modalmangerName' => 'required|exists:users,id',
            //'modalleaderName' => 'required|exists:users,id',
            'Role' => 'required|exists:roles,id',
            'EmergencyContact' => 'required|string|max:15',
            'ContactNumber' => 'nullable|string|max:30',
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        $user = new User();
        $user->name = $request->FullName;
        $user->email = $request->EmailAddress;
        $user->password = Hash::make($request->Password);
        $user->emp_code = $request->EmployeeCode;
        $user->address = $request->FullAddress;
        $user->office = $request->modalOfficeName;
        $user->department = $request->modaldepartmentName;
        $user->manager = $request->modalmangerName;
        $user->team_lead = $request->modalleaderName;
        $user->role_id = $request->Role;
        $user->emergency_contact = $request->EmergencyContact;
        $user->contact_number = $request->ContactNumber;
        $user->save();

        $subject = "Create the User, userid:-".$user->id;
        addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');
    
        return redirect()->back()->with('success', 'User created successfully.');
    }

    

    public function delete_user(Request $request, $id)
    {
       
        $user = Department::find($id);
    
        if ($user) {
            $user->delete();
            $subject = "Delete the User, Departmentid:- $user->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');
            return redirect()->back()->with('success', 'User deleted successfully.');
        } else {
            return redirect()->back()->with('error', 'User not found.');
        }
    }

    public function updatestatus(Request $request){
        $admin = User::find($request->id);

        if ($admin) {
            $admin->status = $request->status; // 'active' or 'inactive'

            $subject = "Update the User stataus, userid:- $admin->id user status:- $admin->status to $request->status";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

            $admin->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 400);
    }

    public function get_manager_by_departmentid(Request $request, $id)
    {
        
       $getmanager = Manger::Where('department', $id)->get();
   
        $html = "<option value=''>Select Manager user</option>";

       foreach($getmanager as $managerrole){

            $html .= "<option value='".$managerrole->id."'>".$managerrole->manager."</option>";

       }

        return $html;
    }

    public function get_tl_by_managerid(Request $request, $id)
    {
        $gettls = TeamLeader::Where('leader_manager', $id)->get();

        $html = "<option value=''>Select TL user</option>";

       foreach($gettls as $tlrole){
           
            $html .= "<option value='".$tlrole->id."'>".$tlrole->tl."</option>";
       }

        return $html;
    }


    public function edituser(Request $request)
    {
        //
    }


    /**
     * Display a Department.
     */
    public function department(Request $request)
    {

        $alldepartment = Department::paginate(10);
        return view('admin.department', compact('alldepartment'));
    }


    public function store_department(Request $request)
    {
        $request->validate([
            'department_name' => 'required|string|max:255',
        ]);
    
        // Create a new department with the validated input
        $department = new Department();
        $department->department_name = $request->input('department_name');
        $department->status = "Active";
        // Save the new department to the database
        $department->save();

        $subject = "create the Department, Departmentid:- $department->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');
    
        // Redirect back with a success message
        return redirect()->back()->with('success', 'Department created successfully.');
    }

    public function update_department(Request $request, $id)
    {
        $request->validate([
            'department_name' => 'required|string|max:255',
        ]);
    
        // Find the department by its ID
        $department = Department::find($id);
    
        // Check if the department exists
        if ($department) {
            // Update the department's name
            $department->department_name = $request->input('department_name');
            
            // Save the updated department
            $department->save();

            $subject = "Update the Department, Departmentid:- $department->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

            return redirect()->back()->with('success', 'Department updated successfully..');
           
        } else {
            return redirect()->back()->with('error', 'Department not found');
           
        }
    }

    public function delete_department(Request $request)
    {
        $id = $request->id;
    
        $office = Department::find($id);
    
        if ($office) {
            $office->delete();
            $subject = "Delete the Department, Departmentid:- $office->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');
            return redirect()->back()->with('success', 'Department deleted successfully.');
        } else {
            return redirect()->back()->with('error', 'Department not found.');
        }
    }

    /**
     * Display a office.
     */
    public function office()
    {
        $alloffice= Office::with('department')->paginate(10);
        // $departments = Department::all();
        return view('admin.office', compact('alloffice'));
    }


    public function store_office(Request $request)
    {
        $request->validate([
            'office_name' => 'required|string|max:255'
        ]);
    
        // Create a new department with the validated input
        $Office = new Office();
        $Office->office_name = $request->input('office_name');
        $Office->status = "Active";
        // Save the new department to the database
        $Office->save();
    
        $subject = "Create the Office, officeid:- $office->id";
        addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');
        // Redirect back with a success message
        return redirect()->back()->with('success', 'Office created successfully.');
    }

    public function update_office(Request $request, $id)
    {
        $request->validate([
            'office_name' => 'required|string|max:255',
        ]);
    
        // Find the department by its ID
        $Office = Office::find($id);
    
        // Check if the department exists
        if ($Office) {
            // Update the department's name
            $Office->office_name = $request->input('office_name');            
            // Save the updated department
            $Office->save();

             $subject = "Update the Office, officeid:- $office->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

            return redirect()->back()->with('success', 'office updated successfully..');
           
        } else {
            return redirect()->back()->with('error', 'office not found');
           
        }
    }

    public function delete_office(Request $request)
    {
        $id = $request->id;
    
        $office = Office::find($id);
    
        if ($office) {
            $office->delete();

            $subject = "Delete the Office, officeid:- $office->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

            return redirect()->back()->with('success', 'Office deleted successfully.');
        } else {
            return redirect()->back()->with('error', 'Office not found.');
        }
    }
    


    /**
     * Display a Manager.
     */
    public function manager()
    {
        $alldepartment = Department::get();
        $alloffice = Office::get();
        $alluser = User::get();
        $managers = DB::table('mangers')
        ->join('offices', 'mangers.office', '=', 'offices.id')
        ->join('departments', 'mangers.department', '=', 'departments.id')
        ->join('users', 'mangers.user_id', '=', 'users.id')
        ->select('users.name as manager_name', 'offices.office_name', 'departments.department_name')
        ->paginate(10);

        return view('admin.manager',compact('alldepartment', 'alloffice', 'alluser','managers'));
    }


    public function store_manager(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'modalOfficeName' => 'required|exists:offices,id',  // Ensure office exists
            'modaldepartmentName' => 'required|exists:departments,id',  // Ensure department exists
            'modalmangerName' => 'required|exists:users,id',  // Ensure user exists
        ]);
    
        // Create a new Manager record
        $manager = new Manger();
        $manager->office = $validatedData['modalOfficeName'];
        $manager->department = $validatedData['modaldepartmentName'];
        $manager->user_id = $validatedData['modalmangerName']; // Store the user ID
    
        // Retrieve the user name from the users table and store it in the 'manager' field
        $user = User::find($validatedData['modalmangerName']);
        $manager->manager = $user ? $user->name : 'Unknown'; // In case user not found, fallback to 'Unknown'
    
        $manager->save();

        $subject = "Create the manager, managerid:- $manager->id";
        addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');
    
        return redirect()->back()->with('success', 'Manager created successfully!');
    }
    
    
    
    

    public function update_manager(Request $request, $id)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'modalOfficeName' => 'required|exists:offices,id',  // Ensure office exists
            'modaldepartmentName' => 'required|exists:departments,id',  // Ensure department exists
            'modalmangerName' => 'required|exists:users,id',  // Ensure user exists
        ]);
     
        $manager = Manger::with('office', 'department', 'user')->findOrFail($id);
    
        // Check if there are users available for assignment
        $allusers = User::all();
        if ($allusers->isEmpty()) {
            return redirect()->back()->with('error', 'No users available to assign as manager.');
        }

        $manager->office_id = $validatedData['modalOfficeName'];
        $manager->department_id = $validatedData['modaldepartmentName'];
        $manager->user_id = $validatedData['modalmangerName'];

        $user = User::find($validatedData['modalmangerName']);
        $manager->manager = $user->name;

        $manager->save();
        if($manager){

            $subject = "Update the manager, managerid:- $manager->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

            return redirect()->back()->with('success', 'Manager updated successfully.');
        }else{
            return redirect()->back()->with('error', 'Failed to update manager.');
        }
    }
    

    public function delete_manager(Request $request)
    {
        //
    }

     /**
     * Display a team Leader.
     */
    public function teamleader()
    {
        $alloffice = Office::get(); 
        $alldepartment = Department::get();
        $allmanager = Manger::get();
        $alluser = User::get();
        $teamleaders = DB::table('team_leaders')
        ->join('offices', 'team_leaders.office', '=', 'offices.id')
        ->join('departments', 'team_leaders.department', '=', 'departments.id')
        ->join('users as managers', 'team_leaders.user_id', '=', 'managers.id') // for manager
        ->join('users as leaders', 'team_leaders.leader_email', '=', 'leaders.email') // or use ID if available
        ->select(
            'leaders.name as leader_name',
            'departments.department_name',
            'managers.name as manager_name',
            'offices.office_name'
        )
        ->paginate(10);
        return view('admin.team_leader',compact('alldepartment', 'alloffice', 'allmanager', 'alluser','teamleaders'));
    }


    public function store_teamleader(Request $request)
    {
        $teamleader = new TeamLeader();
        $teamleader->office     = $request->modalOfficeName;
        $teamleader->department = $request->modaldepartmentName;
        $teamleader->leader_manager    = $request->modalmangerName;
        $teamleader->tl   = $request->modalleaderName;
        $teamleader->user_id   = $request->user_id;
        $teamleader->leader_email   = $request->user_email;
        $teamleader->save();
        $subject = "Created Team Leader, teamleader id: $teamleader->id";
        addToLog($customerId = '', $id = '', $subject, $oldData = '', $newData = '');

        return redirect()->back()->with('success', 'Team Leader created successfully.');
    }


    
    
    

    public function update_teamleader(Request $request)
    {
        //
    }

    public function delete_teamleader(Request $request)
    {
       
    }


    /**
     * Display a Status Type.
     */
    public function statustype()
    {
        $allstatus = StatusType::paginate(10);
        return view('admin.status_type', compact('allstatus'));
    }


    public function store_statustype(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
    
        // Create a new department with the validated input
        $StatusType = new StatusType();
        $StatusType->name = $request->input('name');
        $StatusType->value = strtolower($request->input('name'));
        // Save the new department to the database
        $StatusType->save();

        $subject = "create the Status Type, statustypeid:- $StatusType->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');
    
        // Redirect back with a success message
        return redirect()->back()->with('success', 'Status Type created successfully.');
    }

    public function update_statustype(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
    
        // Find the department by its ID
        $StatusType = StatusType::find($id);
    
        // Check if the department exists
        if ($StatusType) {
            // Update the department's name
            $StatusType->name = $request->input('name');
            $StatusType->value = strtolower($request->input('name'));
            
            // Save the updated department
            $StatusType->save();

            $subject = "Update the Status Type, statustypeid:- $StatusType->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

            return redirect()->back()->with('success', 'Status Type updated successfully..');
           
        } else {
            return redirect()->back()->with('error', 'Status Type not found');
           
        }
    }

    public function delete_statustype(Request $request)
    {
        //
    }


    /**
     * Display a Shipment Type.
     */
    public function shipmenttype()
    {
        $allShipment = ShipmentType::paginate(10);
        return view('admin.shipment_type', compact('allShipment'));
    }


    public function store_shipmenttype(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
    
        // Create a new department with the validated input
        $ShipmentType = new ShipmentType();
        $ShipmentType->name = $request->input('name');
        $ShipmentType->value = strtolower($request->input('name'));
        // Save the new department to the database
        $ShipmentType->save();

        $subject = "Create the Shipment Type, shipmenttypeid:- $ShipmentType->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');
    
        // Redirect back with a success message
        return redirect()->back()->with('success', 'Shipment Type created successfully.');
    }

    public function update_shipmenttype(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
    
        // Find the department by its ID
        $ShipmentType = ShipmentType::find($id);
    
        // Check if the department exists
        if ($ShipmentType) {
            // Update the department's name
            $ShipmentType->name = $request->input('name');
            $ShipmentType->value = strtolower($request->input('name'));
            
            // Save the updated department
            $ShipmentType->save();

            $subject = "Update the Shipment Type, shipmenttypeid:- $ShipmentType->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

            return redirect()->back()->with('success', 'Shipment Type updated successfully..');
           
        } else {
            return redirect()->back()->with('error', 'Shipment Type not found');
           
        }
    }

    public function delete_shipmenttype(Request $request)
    {
        //
    }


    /**
     * Display a Equipment Type.
     */
    public function equipmenttype()
    {
        $allequipment = EquipmentType::paginate(10);
        return view('admin.equipment_type', compact('allequipment'));
    }


    public function store_equipmenttype(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
    
        // Create a new department with the validated input
        $equipmentType = new EquipmentType();
        $equipmentType->name = $request->input('name');
        $equipmentType->value = strtolower($request->input('name'));
        // Save the new department to the database
        $equipmentType->save();

        $subject = "Create the Equipment Type, equipmenttypeid:- $equipmentType->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');
    
        // Redirect back with a success message
        return redirect()->back()->with('success', 'Equipment Type created successfully.');
    }

    public function update_equipmenttype(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
    
        // Find the department by its ID
        $EquipmentType = EquipmentType::find($id);
    
        // Check if the department exists
        if ($EquipmentType) {
            // Update the department's name
            $EquipmentType->name = $request->input('name');
            $EquipmentType->value = strtolower($request->input('name'));
            
            // Save the updated department
            $EquipmentType->save();

             $subject = "Update the Equipment Type, equipmenttypeid:- $equipmentType->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

            return redirect()->back()->with('success', 'Equipment Type updated successfully..');
           
        } else {
            return redirect()->back()->with('error', 'Equipment Type not found');
           
        }
    }

    public function delete_equipmenttype(Request $request)
    {
        //
    }


    /**
     * Display a permissions.
     */
    public function permissions()
    {
        $departments = Department::all();
        $permissions = Permission::with('department')->paginate(50);
        return view('admin.permissions', compact('departments', 'permissions'));
    }

    public function create_permissions(Request $request){
       
        $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required',
            
        ]);
    
        $Permission = new Permission();
        $Permission->name = $request->input('name');
        $Permission->department_id = strtolower($request->input('department_id'));
        $Permission->save();

        $subject = "create the permission, permissionid:- $Permission->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');
  
        return redirect()->back()->with('success', 'Permission created successfully.');
    }


     /**
     * Display a Roles.
     */
    public function roles()
    {
        $departments = Department::all();
        $permissions = Permission::with('department')->get();
        $roles = Role::with(['department', 'parentUser'])->paginate(50);
        return view('admin.roles', compact('departments', 'roles', 'permissions'));
    }
  

    public function role_create(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'required',
            
        ]);
    
        $Role = new Role();
        $Role->name = $request->input('name');
        $Role->department_id = strtolower($request->input('department'));
        $Role->type = strtolower($request->input('type'));
        $Role->parent_role = strtolower($request->input('parent_role'));
        $Role->save();

         $subject = "Create the Role, roleid:- $Role->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

        return redirect()->back()->with('success', 'Role created successfully.');
       
    }


    public function profile(){
        $user = Auth::user();
    
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to access your profile.');
        }
    
        $profile = DB::table('team_leaders')
            ->join('offices', 'team_leaders.office', '=', 'offices.id')
            ->join('departments', 'team_leaders.department', '=', 'departments.id')
            ->join('users as managers', 'team_leaders.user_id', '=', 'managers.id')
            ->join('users as leaders', 'team_leaders.leader_email', '=', 'leaders.email')
            ->join('roles', 'leaders.role_id', '=', 'roles.id')
            ->where('team_leaders.leader_email', $user->email)
            ->select(
                'leaders.name as leader_name',
                'departments.department_name',
                'managers.name as manager_name',
                'offices.office_name',
                'roles.name as role_name'
            )
            ->first();
    
        return view('admin.profile', compact('user', 'profile'));
    }
    
    public function update_profile(Request $request)
    {
        $user = Auth::user();
    
        $request->validate([
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
    
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = $user->name . '_' . time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('images/profile-image');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0775, true);
            }
    
            $file->move($destinationPath, $filename);

            $user->profile_picture = 'images/profile-image/' . $filename;
            $user->save();

              $subject = "Update the profile, profileid:- $user->id";
            addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');
    
            return response()->json(['success' => true, 'path' => asset($user->profile_picture)]);
        }
    
        return response()->json(['success' => false]);
    }
    

    public function change_password()
    {
        return view('admin.change_password');
    }
    
public function update_password(Request $request)
{
    $user = Auth::user();

    // Validation
    $request->validate([
        'current_password' => 'required|string',
        'new_password' => 'required|string|min:8|confirmed',
    ]);

    // Check old password
    if (!Hash::check($request->current_password, $user->password)) {
        return back()->with('error', 'Current password is incorrect.');
    }

    // Update password
    $user->password = Hash::make($request->new_password);
    $user->save();

    // Log event (optional)
    $subject = "Password updated for user ID: {$user->id}";
    addToLog('', $user->id, $subject, '', '');

    return back()->with('success', 'Password updated successfully.');
}


    public function admin_update_password(Request $request,  int $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'password' => 'required|min:8',
        ]);

        if ($id !== $user->id) {
            return redirect()->back()->with('error', 'User not match with User record.');
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        $subject = "Update the user password , user-id:- $user->id";
        addToLog($customerId ='', $loadid ='', $subject, $oldData='', $newData='');
    
        return redirect()->back()->with('success', 'Password updated successfully.');
    }


    public function country()
    {
    $country = Country::orderBy('name', 'asc')->paginate(10);
        return view('admin.country',compact('country'));
    }


    public function countryCreate(Request $request)
    {
        $request->validate([
            'country_name' => 'required|string|max:255',
            'flag' => 'required|string|max:255',
        ]);

        $country = Country::create([
            'name' => $request->country_name,
            'flag' => $request->flag,
        ]);

        $subject = "Create the Country, countryid:- $country->id";
        addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

        return redirect()->back()->with('success', 'Country created successfully.');
    }
    

    public function deleteCountry($id)
    {
        $country = Country::findOrFail($id);
        $country->delete();

         $subject = "Delete the Country, countryid:- $country->id";
        addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

        return redirect()->back()->with('success', 'Country deleted successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'country_name' => 'required|string|max:255',
            'flag' => 'required|string|max:255',
        ]);

        $country = Country::findOrFail($id);
        $country->update([
            'name' => $request->country_name,
            'flag' => $request->flag,
        ]);

         $subject = "Update the Country, countryid:- $country->id";
        addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

        return redirect()->back()->with('success', 'Country updated successfully.');
    }


    public function state(){
        $states = State::with('country')->orderBy('name', 'asc')->paginate(10);
        $countries = Country::all();
        return view('admin.state', compact('states', 'countries'));
    }

    public function statestore(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'country_id' => 'required|exists:countries,id'
        ]);

        $State = State::create($request->all());

         $subject = "Create the State, Stateid:- $State->id";
        addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

        return redirect()->back()->with('success', 'State created successfully!');
    }

    public function stateupdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'country_id' => 'required|exists:countries,id'
        ]);

        $state = State::findOrFail($id);
        $state->update($request->all());

        $subject = "Update the State, Stateid:- $state->id";
        addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

        return redirect()->back()->with('success', 'State updated successfully!');
    }

    public function statedestroy($id)
    {
        $state = State::findOrFail($id);
        $state->delete();

        $subject = "Delete the State, Stateid:- $state->id";
        addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

        return redirect()->back()->with('success', 'State deleted successfully!');
    }


    public function adminRcDownload($id)
    {
        // Fetch the load based on the provided id
        $load = Load::find($id);
    
        // Check if $load is found
        if (!$load) {
            abort(404, 'Load not found.');
        }
    
        // Consolidate consignee data
        $consigneeData = [
            'load_consignee' => $load->load_consignee,
            'load_consignee_location' => $load->load_consignee_location,
            'load_consignee_date' => $load->load_consignee_date,
            'load_consignee_discription' => $load->load_consignee_discription,
            'load_consignee_type' => $load->load_consignee_type,
            'load_consignee_qty' => $load->load_consignee_qty,
            'load_consignee_weight' => $load->load_consignee_weight,
            'load_consignee_commodity' => $load->load_consignee_commodity,
            'load_consignee_value' => $load->load_consignee_value,
            'load_consignee_delivery_notes' => $load->load_consignee_delivery_notes,
            'load_consignee_po_numbers' => $load->load_consignee_po_numbers,
            'load_consignee_appointment' => $load->load_consignee_appointment
        ];
    
        // Prepare shipper data
        $shipperData = [
            'load_shipperr' => $load->load_shipperr,
            'load_shipper_location' => $load->load_shipper_location,
            'load_shipper_date' => $load->load_shipper_date,
            'load_shipper_discription' => $load->load_shipper_discription,
            'load_shipper_commodity_type' => $load->load_shipper_commodity_type,
            'load_shipper_qty' => $load->load_shipper_qty,
            'load_shipper_weight' => $load->load_shipper_weight,
            'load_shipper_commodity' => $load->load_shipper_commodity,
            'load_shipper_value' => $load->load_shipper_value,
            'load_shipper_shipping_notes' => $load->load_shipper_shipping_notes,
            'load_shipper_po_numbers' => $load->load_shipper_po_numbers,
            'load_shipper_contact' => $load->load_shipper_contact,
            'load_shipper_appointment' => $load->load_shipper_appointment
        ];
    
        $pdf = new Dompdf();
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $pdf->setOptions($options);
    
        // Pass title and other data to the view
        $view = view('broker.invoice_html', compact('load', 'consigneeData', 'shipperData'))->render();
    
        $pdf->loadHtml($view);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
    
        // Get the load number for the filename
        $filename = 'Carrier RC Load No - ' . $load->load_number .'.pdf';
        // Stream the PDF to the browser for preview
        return $pdf->stream($filename, ['Attachment' => false]);
    }

    public function adminShipperRcDownload($id)
    {
        // Fetch the load based on the provided id
        $load = Load::with('equipmentType')->find($id);
    
        // Check if $load is found
        if (!$load) {
            abort(404, 'Load not found.');
        }
    
        // Consolidate consignee data
        $consigneeData = [
            'load_consignee' => $load->load_consignee,
            'load_consignee_location' => $load->load_consignee_location,
            'load_consignee_date' => $load->load_consignee_date,
            'load_consignee_discription' => $load->load_consignee_discription,
            'load_consignee_type' => $load->load_consignee_type,
            'load_consignee_qty' => $load->load_consignee_qty,
            'load_consignee_weight' => $load->load_consignee_weight,
            'load_consignee_commodity' => $load->load_consignee_commodity,
            'load_consignee_value' => $load->load_consignee_value,
            'load_consignee_delivery_notes' => $load->load_consignee_delivery_notes,
            'load_consignee_po_numbers' => $load->load_consignee_po_numbers,
            'load_consignee_appointment' => $load->load_consignee_appointment
        ];
    
        // Prepare shipper data
        $shipperData = [
            'load_shipperr' => $load->load_shipperr,
            'load_shipper_location' => $load->load_shipper_location,
            'load_shipper_date' => $load->load_shipper_date,
            'load_shipper_discription' => $load->load_shipper_discription,
            'load_shipper_commodity_type' => $load->load_shipper_commodity_type,
            'load_shipper_qty' => $load->load_shipper_qty,
            'load_shipper_weight' => $load->load_shipper_weight,
            'load_shipper_commodity' => $load->load_shipper_commodity,
            'load_shipper_value' => $load->load_shipper_value,
            'load_shipper_shipping_notes' => $load->load_shipper_shipping_notes,
            'load_shipper_po_numbers' => $load->load_shipper_po_numbers,
            'load_shipper_contact' => $load->load_shipper_contact,
            'load_shipper_appointment' => $load->load_shipper_appointment
        ];
    
        $pdf = new Dompdf();
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $pdf->setOptions($options);
    
        // Pass title and other data to the view
        $view = view('broker.shipper_rc', compact('load', 'consigneeData', 'shipperData'))->render();
    
        $pdf->loadHtml($view);
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
    
        // Get the load number for the filename
        $filename = 'Customer RC Load No - ' . $load->load_number .  '.pdf';
        // Stream the PDF to the browser for preview
        return $pdf->stream($filename, ['Attachment' => false]);
    }

    /******************** Home *********************/

    // public function all_search(Request $request)
    // {
    //     $q = $request->input('query');
    //     if (!empty($q)) {
    //         // Split the query by commas to get multiple terms
    //         $searchTerms = array_filter(explode(',', $q), function($term) {
    //             return !empty(trim($term)); // Only keep non-empty terms
    //         });

    //         if (count($searchTerms) > 0) {
    //             // Search for non-empty terms with 'orWhere'
    //             $broker_status = Load::with(['user'])
    //                 ->where(function($query) use ($searchTerms) {
    //                     foreach ($searchTerms as $term) {
    //                         $query->orWhere('load_number', 'like', "%$term%");
    //                         // $query->orwhere('load_workorder', 'like', "%$term%");
    //                         // $query->orwhere('customer_refrence_number', 'like', "%$term%");
    //                         // $query->orwhere('load_bill_to', 'like', "%$term%");
    //                         // $query->orwhere('load_dispatcher', 'like', "%$term%");
    //                         // $query->orwhere('invoice_number', 'like', "%$term%");
    //                         // $query->orWhere('load_shipper_po_numbers->shipping_po_numbers', 'like', "%$term%");
    //                         // $query->orWhere('load_shipper_po_numbers->po_number', 'like', "%$term%");
    //                         // $query->orWhere('load_consigneer_notes->consignee_po_number', 'like', "%$term%");
    //                     }
    //                 })
    //                 ->orderBy('id', 'desc')
    //                 ->paginate(100);
    //         } else {
    //             // If no valid terms, return an empty collection or handle accordingly
    //             $broker_status = collect();
    //         }
    //     } else {
    //         // If query is empty, return a paginated result without any filter
    //         $broker_status = Load::with('user')->orderBy("id", "desc")->paginate(50); 
    //     }
        
    //     return view('admin.home.all_load', compact('broker_status'))->render();
    
    // }
public function all_search(Request $request)
{
    $q = $request->input('query');

    if (!empty($q)) {
        // Split the query by commas to get multiple terms
        $searchTerms = array_filter(explode(',', $q), function ($term) {
            return !empty(trim($term)); // Only keep non-empty terms
        });

        if (count($searchTerms) > 0) {
            $broker_status = Load::with(['user'])
                ->where(function ($query) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $query->orWhere('load_number', 'like', "%$term%");
                        $query->orWhereHas('customer', function ($customerQuery) use ($term) {
                            $customerQuery->where('customer_name', 'like', "%$term%");
                        });
                        $query->orWhereHas('user', function ($userQuery) use ($term) {
                            $userQuery->where('users.name', 'like', "%$term%");
                        });
                        // $query->orwhere('load_workorder', 'like', "%$term%");
                        // ...baaki commented fields same rahenge
                    }
                })
                ->orderBy('id', 'desc')
                ->paginate(100)
                ->withQueryString();
        } else {
            // Koi valid term nahi -> empty paginator banao (crash na ho)
            $broker_status = Load::with('user')->whereRaw('1 = 0')->paginate(100);
        }
    } else {
        // Query empty -> normal paginated result
        $broker_status = Load::with('user')
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->withQueryString();
    }

    $html = view('admin.home.all_load', compact('broker_status'))->render();
    $pagination = $broker_status->setPageName('all_load')
        ->links('pagination::bootstrap-5')
        ->render();

    return response()->json([
        'html' => $html,
        'pagination' => $pagination,
    ]);
}
     public function open_search(Request $request)
    {

        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $open = Load::with(['user'])->where('load_status', 'Open')
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "%$term%");
                            $query->orwhere('load_workorder', 'like', "%$term%");
                            $query->orwhere('customer_refrence_number', 'like', "%$term%");
                            $query->orWhereHas('customer', function ($customerQuery) use ($term) {
                                $customerQuery->where('customer_name', 'like', "%$term%");
                            });
                            $query->orWhereHas('user', function ($userQuery) use ($term) {
                                $userQuery->where('users.name', 'like', "%$term%");
                            });
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $open = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
           $open = Load::with('user')->where('load_status', 'Open')->orderBy("id", "desc")->paginate(50); 
        }
        
        return view('admin.home.open_load', compact('open'))->render();
       
    }

     public function delivered_search(Request $request)
    {
        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $deliverd = Load::with(['user'])->where('load_status', 'Delivered')
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "%$term%");
                            $query->orwhere('load_workorder', 'like', "%$term%");
                            $query->orwhere('customer_refrence_number', 'like', "%$term%");
                            $query->orWhereHas('customer', function ($customerQuery) use ($term) {
                                $customerQuery->where('customer_name', 'like', "%$term%");
                            });
                            $query->orWhereHas('user', function ($userQuery) use ($term) {
                                $userQuery->where('users.name', 'like', "%$term%");
                            });
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $deliverd = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
           $deliverd = Load::with('user')->where('load_status', 'Delivered')->orderBy("id", "desc")->paginate(50); 
        }
        
        return view('admin.home.delivered', compact('deliverd'))->render();
       
    }

     public function complete_search(Request $request)
    {
       $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $complete = Load::with(['user'])->where('load_status', 'Completed')
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "%$term%");
                            $query->orwhere('load_workorder', 'like', "%$term%");
                            $query->orwhere('customer_refrence_number', 'like', "%$term%");
                            $query->orWhereHas('customer', function ($customerQuery) use ($term) {
                                $customerQuery->where('customer_name', 'like', "%$term%");
                            });
                            $query->orWhereHas('user', function ($userQuery) use ($term) {
                                $userQuery->where('users.name', 'like', "%$term%");
                            });
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $complete = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
           $complete = Load::with('user')->where('load_status', 'Completed')->orderBy("id", "desc")->paginate(50);
        }
        
        return view('admin.home.completed', compact('complete'))->render();
    }

     public function invoice_search(Request $request)
    {
       $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $invoice_paid = Load::with(['user'])->where('invoice_status', 'Paid')
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "%$term%");
                            $query->orwhere('load_workorder', 'like', "%$term%");
                            $query->orwhere('customer_refrence_number', 'like', "%$term%");
                            $query->orWhereHas('customer', function ($customerQuery) use ($term) {
                                $customerQuery->where('customer_name', 'like', "%$term%");
                            });
                            $query->orWhereHas('user', function ($userQuery) use ($term) {
                                $userQuery->where('users.name', 'like', "%$term%");
                            });
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $invoice_paid = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
           $invoice_paid = Load::with('user')->where('invoice_status', 'Paid')->orderBy("id", "desc")->paginate(50); 
        }
        
        return view('admin.home.invoiced', compact('invoice_paid'))->render();
    }

    public function invoice_paid_search(Request $request)
    {

        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $paid_record = Load::with(['user'])->where('invoice_status', 'Paid Record')
                    ->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "%$term%");
                            $query->orwhere('load_workorder', 'like', "%$term%");
                            $query->orwhere('customer_refrence_number', 'like', "%$term%");
                            $query->orWhereHas('customer', function ($customerQuery) use ($term) {
                                $customerQuery->where('customer_name', 'like', "%$term%");
                            });
                            $query->orWhereHas('user', function ($userQuery) use ($term) {
                                $userQuery->where('users.name', 'like', "%$term%");
                            });
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $paid_record = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
          $paid_record = Load::with('user')->where('invoice_status', 'Paid Record')->orderBy("id", "desc")->paginate(50); 
        }
        
        return view('admin.home.invoiced_paid', compact('paid_record'))->render();
       
    }


    /******************** All data *********************/

    public function customer_search(Request $request)
    {
        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

        if (count($searchTerms) > 0) {
        $sortedCustomers = Customer::query()
            ->join('users', 'users.id', '=', 'customers.user_id')
            ->select('customers.*', 'users.name as user_name')
            ->orderByRaw("CASE WHEN customers.status = 'Not Approved' THEN 0 ELSE 1 END")
            ->where(function ($query) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $query->orWhere('customers.customer_name', 'like', "%$term%")
                        ->orWhere('users.name', 'like', "%$term%");
                }
            })
            ->orderBy('customers.id', 'desc')
            ->get();
    }else {
                $sortedCustomers = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
          
            $sortedCustomers = Customer::orderByRaw("CASE WHEN status = 'Not Approved' THEN 0 ELSE 1 END")
                    ->orderBy('id', 'DESC')  // Optional: further ordering by ID
                    ->paginate(100);
        }
        
        return view('admin.all_data.customer', compact('sortedCustomers'))->render();
       
    }

    public function carrier_search(Request $request)
    {

        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $external = External::where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('carrier_name', 'like', "%$term%");
                            $query->orwhere('carrier_mc_ff_input', 'like', "%$term%");
                            $query->orwhere('carrier_dot', 'like', "%$term%");
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $external = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
          $external = External::orderBy("id", "desc")->paginate(50);
        }
        
        return view('admin.all_data.carrier', compact('external'))->render();
       
    }

    public function consignee_search(Request $request)
    {
        $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $consignee = Consignee::where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('consignee_name', 'like', "%$term%");
                            $query->orwhere('consignee_address', 'like', "%$term%");
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $consignee = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
          $consignee = Consignee::orderBy("id", "desc")->paginate(50);
        }
        
        return view('admin.all_data.consignee', compact('consignee'))->render();
       
    }

    public function shipper_search(Request $request)
    {
       $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $shipper = Shipper::select(
                            'shippers.*', 
                            'users.name as user_name',
                            'users.manager',
                            'users.team_lead'
                    )
                    ->join('users', 'shippers.user_id', '=', 'users.id')
                    ->orderBy('shippers.id', 'DESC')->where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('shipper_name', 'like', "%$term%");
                            $query->orwhere('shipper_address', 'like', "%$term%");
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $shipper = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
          $shipper = Shipper::orderBy("id", "desc")->paginate(50);
        }
        
        return view('admin.all_data.shipper', compact('shipper'))->render();
    }

    public function load_search(Request $request)
    {
       $q = $request->input('query');
        if (!empty($q)) {
            // Split the query by commas to get multiple terms
            $searchTerms = array_filter(explode(',', $q), function($term) {
                return !empty(trim($term)); // Only keep non-empty terms
            });

            if (count($searchTerms) > 0) {
                // Search for non-empty terms with 'orWhere'
                $loads = Load::where(function($query) use ($searchTerms) {
                        foreach ($searchTerms as $term) {
                            $query->orWhere('load_number', 'like', "%$term%");
                            $query->orwhere('invoice_number', 'like', "%$term%");
                            $query->orwhere('load_bill_to', 'like', "%$term%");
                            $query->orwhere('load_dispatcher', 'like', "%$term%");
                            $query->orwhere('load_workorder', 'like', "%$term%");
                            $query->orwhere('customer_refrence_number', 'like', "%$term%");
                        }
                    })
                    ->orderBy('id', 'desc')
                    ->get();
            } else {
                // If no valid terms, return an empty collection or handle accordingly
                $loads = collect();
            }
        } else {
            // If query is empty, return a paginated result without any filter
          $loads = Load::orderBy("id", "desc")->paginate(50);
        }
        
        return view('admin.all_data.loads', compact('loads'))->render();
    }


    public function ipconfigcreate(){
        $ipconfig = IpConfig::paginate(50);
        return view('admin.ipconfig', compact('ipconfig'));
    }

    public function ipStore(Request $request)
    {
            $request->validate([
            'ip_address' => 'required|unique:ip_configs',
        ]);

        // Save IP
        $ip = IpConfig::create([
            'ip_address' => $request->ip_address,
        ]);

        $subject = "Create the IP, IP-id:- $ip->id";
        addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

        return redirect()->back()->with('success', 'IP Address added successfully.');
    }

    public function ipUpdate(Request $request, $id)
    {
           $request->validate([
        'ip_address' => 'required|unique:ip_configs',
    ]);

        $ip = IpConfig::findOrFail($id);
        $ip->update(['ip_address' => $request->ip_address]);

        $subject = "Update the IP, IP-id:- $ip->id";
        addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

        return redirect()->back()->with('success', 'IP Address updated successfully.');
    }

    public function ipDelete($id)
    {
        $ip = IpConfig::findOrFail($id);
        $ip->delete();

         $subject = "Delete the IP, IP-id:- $ip->id";
        addToLog($customerId ='', $id='', $subject, $oldData ='', $newData ='');

        return redirect()->back()->with('success', 'IP Address deleted successfully.');
    }

    public function loadEdit($id)
    {


        $post = Load::with('customer')->findOrFail($id);
        $customer = Customer::where('status', 'Approved')->get();
        $equipmentType = EquipmentType::all();
        $shipmentType = ShipmentType::all();
        $shipperdata = Shipper::orderBy('shipper_name', 'asc')->get();
        $consigneedata = Consignee::orderBy('consignee_name', 'asc')->get();
        $allowedAuthIds = [227, 226, 218, 312, 221, 222];
        $alllogs = activity_log::where('load_id', $id)
            ->whereIn('user_id', $allowedAuthIds)
            ->orderBy('updated_at', 'desc')
            ->get();
        $allcustomer = Customer::where('status', 'Approved')->get();
        $users = User::with('role', 'department', 'managers', 'teamleader', 'office')->where('department', 3)->get();
        $customerAvailableCredits = get_customers_available_credit_limits($allcustomer);

        $currentCustomerName = trim((string) ($post->load_bill_to ?? '')) ?: trim((string) ($post->customer?->customer_name ?? ''));
        $post->load_bill_to = $currentCustomerName ?: ($post->load_bill_to ?? '');
        $post->customer_id = $post->customer_id ?: ($post->customer?->id ?? null);
    //   echo "<pre>"; print_r($alllogs); die;
        return view('admin.load_edit', compact('allcustomer', 'customerAvailableCredits', 'post', 'shipperdata', 'consigneedata', 'shipmentType', 'equipmentType', 'users', 'alllogs'));
    }

    public function loadUpdate(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {

        // dd($request->all());
        $load = Load::findOrFail($id);

        if(empty($request->input('shipper_load_final_rate'))){
            return redirect()->back()->with('error', 'please enter the Customer Final Rate');
        }
   
        // ✅ VALIDATION: Check if total load creation amount would exceed the customer's effective credit limit
        $newFinalRate = (float) $request->input('shipper_load_final_rate');
        $oldFinalRate = (float) $load->shipper_load_final_rate;
        $rateDifference = $newFinalRate - $oldFinalRate;
        
        $requestedCustomerId = (int) $request->input('customer_id');
        $customerIsChanging = $requestedCustomerId > 0 && $requestedCustomerId !== (int) $load->customer_id;

        // Only run credit check when the customer is NOT changing.
        // When customer changes, transferLoadCreditBetweenCustomers handles the validation.
        if (!$customerIsChanging) {
            $customer = Customer::find($load->customer_id);
            if ($customer && $rateDifference > 0) {
                $eligibleLoadAmount = (float) Load::where('customer_id', $load->customer_id)
                    ->where('load_status', '!=', 'Cancelled')
                    ->where('id', '!=', $load->id)
                    ->where(function ($query) {
                        $query->where('invoice_status', '!=', 'Paid Record')
                            ->orWhereNull('invoice_status');
                    })
                    ->sum('shipper_load_final_rate');

                $availableLimit = (float) get_customer_available_credit_limit($customer);
                $effectiveAssignedLimit = $eligibleLoadAmount + $availableLimit;
                $newTotalLoadAmount = $eligibleLoadAmount + $newFinalRate;

                if ($newTotalLoadAmount > $effectiveAssignedLimit) {
                    $availableCredit = $effectiveAssignedLimit - $eligibleLoadAmount;
                    return back()->with('error', "Cannot update load. Effective credit limit is {$effectiveAssignedLimit}. Load amount already counted: {$eligibleLoadAmount}. Available credit: {$availableCredit}. New load amount: {$newFinalRate}.");
                }
            }
        }

        $originalCustomerId = (int) ($load->customer_id ?? 0);
        $requestedCustomerId = $request->filled('customer_id') ? (int) $request->input('customer_id') : 0;

        if ($requestedCustomerId <= 0 && !empty($request->input('load_bill_to'))) {
            $customerName = trim((string) $request->input('load_bill_to'));
            if ($customerName !== '') {
                $requestedCustomerId = (int) Customer::whereRaw('LOWER(TRIM(customer_name)) = ?', [mb_strtolower($customerName)])->value('id');
            }
        }

        $request->merge(['customer_id' => $requestedCustomerId > 0 ? $requestedCustomerId : $request->input('customer_id')]);

        $newCustomerId = (int) ($request->input('customer_id') ?? 0);
        $targetCustomer = $newCustomerId > 0 ? Customer::find($newCustomerId) : null;

        if ($originalCustomerId > 0 && $newCustomerId > 0 && $originalCustomerId !== $newCustomerId && $targetCustomer) {
            // Calculate invoice amount from shipper_load_other_charge
            $invoiceAmount = 0;
            $shipperCharges = json_decode($load->shipper_load_other_charge, true);
            if (is_array($shipperCharges)) {
                foreach ($shipperCharges as $charge) {
                    if (isset($charge['for_invoice']) && $charge['for_invoice'] === 'on') {
                        $invoiceAmount += (float) ($charge['amount'] ?? 0);
                    }
                }
            }
            
            $transferResult = $this->creditService->transferLoadCreditBetweenCustomers(
                Customer::find($originalCustomerId),
                $targetCustomer,
                (float) ($request->input('shipper_load_final_rate') ?? $load->shipper_load_final_rate ?? 0),
                $invoiceAmount
            );

            if (!$transferResult['allowed']) {
                return back()->with('error', $transferResult['message']);
            }
        }
   
        $exsistcarrier = External::where('carrier_name', $request->input('load_carrier'))
        ->where('carrier_mc_ff_input', $request->input('load_mc_no'))
        ->first();
        if(empty($exsistcarrier)){
          return redirect()->back()->with('error', 'Carrier Not Found');
        }

        $oldData = json_encode($load);
        $originalLoad = clone $load;
        $newStatus = $request->input('load_status');

        // $exsistcarrier = External::where('carrier_name', $request->input('load_carrier'))
            // ->where('carrier_mc_ff_input', $request->input('load_mc_no'))
            // ->first();
        // if (empty($exsistcarrier)) {
            // return redirect()->back()->with('error', 'Carrier Not Found');
        // }

        // if ($request->input('load_final_carrier_fee') > $request->input('shipper_load_final_rate')) {
            // return redirect()->back()->with('error', 'Carrier rate cannot exceed the Customer Final Rate');
        // }

        $shipper_name = [];
        $shipper_location = [];
        $shipper_description = [];
        $shipper_appointment = [];
        $shipper_type = [];
        $shipper_commodity_name = [];
        $shipper_qty = [];
        $shipper_weight = [];
        $shipper_value = [];
        $shipper_note = [];
        $shipper_po_number = [];
        $shipper_contact = [];
        $shipper_delivery_note = [];

        for ($i = 1; $i <= 15; $i++) { // Assuming a maximum of 15 shippers
            if ($request->has("load_shipper_{$i}")) {
                
				$name = $request->input("load_shipper_{$i}") ?? ' ';
				
				$shipper_name[] = [
					'name' => $name
				];
            }

            if ($request->has("load_shipper_location_{$i}")) {
                
				$location = $request->input("load_shipper_location_{$i}") ?? ' ';
				
				$shipper_location[] = [
					'location' => $location
				];
            }

            if ($request->has("load_shipper_appointment_{$i}")) {
                
				$appointment = $request->input("load_shipper_appointment_{$i}") ?? null;
				
				$shipper_appointment[] = [
					'appointment' => $appointment
				];
            }
            if ($request->has("load_shipper_type_{$i}")) {
                
				
				$type = $request->input("load_shipper_type_{$i}") ?? ' ';
				
				$shipper_type[] = [
					'type' => $type
				];
            }

            if ($request->has("load_shipper_description_{$i}")) {
                
				$description = $request->input("load_shipper_description_{$i}") ?? ' ';
				
				$shipper_description[] = [
					'description' => $description
				];
				
            }

            if ($request->has("load_shipper_commodity_{$i}")) {
                
				$commodity = $request->input("load_shipper_commodity_{$i}") ?? ' ';
				
				$shipper_commodity_name[] = [
					'commodity' => $commodity
				];
            }

            if ($request->has("load_shipper_qty_{$i}")) {
                
				$qty = $request->input("load_shipper_qty_{$i}") ?? ' ';
				
				$shipper_qty[] = [
					'qty' => $qty
				];
            }

            if ($request->has("load_shipper_weight_{$i}")) {
               
				$weight = $request->input("load_shipper_weight_{$i}") ?? ' ';
				
				$shipper_weight[] = [
					'weight' => $weight
				];
            }

            if ($request->has("load_shipper_value_{$i}")) {
                
				$value = $request->input("load_shipper_value_{$i}") ?? ' ';
				
				$shipper_value[] = [
					'value' => $value
				];
            }

            if ($request->has("load_shipper_delivery_notes_{$i}")) {
                
				$delivery_notes = $request->input("load_shipper_delivery_notes_{$i}") ?? ' ';
				
				$shipper_delivery_note[] = [
					'delivery_notes' => $delivery_notes
				];
            }

            if ($request->has("load_shipper_po_numbers_{$i}")) {
                
				$po_number = $request->input("load_shipper_po_numbers_{$i}") ?? ' ';
				
				$shipper_po_number[] = [
					'po_number' => $po_number
				];
            }

            if ($request->has("load_shipper_contact_{$i}")) {
                
				$contact = $request->input("load_shipper_contact_{$i}") ?? ' ';
				
				$shipper_contact[] = [
					'contact' => $contact
				];
            }

			if ($request->has("load_shipper_shipping_notes_{$i}")) {
				$note = $request->input("load_shipper_shipping_notes_{$i}") ?? ' ';
				
				$shipper_note[] = [
					'shipping_notes' => $note
				];
			}
		
        }

        // Loop through the request to extract shipper data
        foreach ($request->all() as $key => $value) {
            // Assuming shipper fields are structured similarly to consignee fields
            if (preg_match('/^load_shipper(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_name[$index]['name'] = $value;
            } elseif (preg_match('/^load_shipper_location(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_location[$index]['location'] = $value;
            } elseif (preg_match('/^load_shipper_description(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_description[$index]['description'] = $value;
            } elseif (preg_match('/^load_shipper_appointment(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_appointment[$index]['appointment'] = $value;
            } elseif (preg_match('/^load_shipper_type(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_type[$index]['shipper_type'] = $value;
            } elseif (preg_match('/^load_shipper_commodity(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_commodity_name[$index]['shipper_commodity'] = $value;
            } elseif (preg_match('/^load_shipper_qty(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_qty[$index]['shipper_qty'] = $value;
            } elseif (preg_match('/^load_shipper_weight(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_weight[$index]['shipper_weight'] = $value;
            } elseif (preg_match('/^load_shipper_value(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_value[$index]['shipper_value'] = $value;
            } elseif (preg_match('/^load_shipper_delivery_notes(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_delivery_note[$index]['shipper_delivery_notes'] = $value;
            } elseif (preg_match('/^load_shipper_po_numbers(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_po_number[$index]['shipper_po_number'] = $value;
            } elseif (preg_match('/^load_shipper_contact(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_contact[$index]['shipper_contact'] = $value;
            } elseif (preg_match('/^load_shipper_notes(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $shipper_note[$index]['shipper_notes'] = $value;
            }


            elseif (preg_match('/^load_consignee(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $consignee_name[$index]['name'] = $value;
            } elseif (preg_match('/^load_consignee_location(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $consignee_location[$index]['location'] = $value;
            } elseif (preg_match('/^load_consignee_description(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $consignee_description[$index]['description'] = $value;
            } elseif (preg_match('/^load_consignee_appointment(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $load_consignee_appointment[$index]['appointment'] = $value;
            } elseif (preg_match('/^load_consignee_type(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $consignee_commodity_type[$index]['consignee_type'] = $value;
            } elseif (preg_match('/^load_consignee_commodity(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $consignee_commodity_name[$index]['consignee_commodity'] = $value;
            } elseif (preg_match('/^load_consignee_qty(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $consignee_qty[$index]['consignee_qty'] = $value;
            } elseif (preg_match('/^load_consignee_weight(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $consignee_weight[$index]['consignee_weight'] = $value;
            } elseif (preg_match('/^load_consignee_value(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $consignee_value[$index]['consignee_value'] = $value;
            } elseif (preg_match('/^load_consignee_delivery_notes(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $consignee_delivery_note[$index]['consignee_delivery_notes'] = $value;
            } elseif (preg_match('/^load_consignee_po_numbers(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $consignee_po_number[$index]['consignee_po_number'] = $value;
            } elseif (preg_match('/^load_consignee_contact(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $load_consigneer_contact[$index]['consignee_contact'] = $value;
            } elseif (preg_match('/^load_consignee_notes(\d*)$/', $key, $matches)) {
                $index = $matches[1] ?: 0;
                $load_consignee_notes[$index]['load_consignee_notes'] = $value;
            }
      
        }


        // Handle consignee data
        $consignee_name = [];
        $consignee_location = [];
        $load_consignee_appointment = [];
        $consignee_description = [];
        $load_consignee_type = [];
        $consignee_commodity_name = [];
        $consignee_qty = [];
        $consignee_weight = [];
        $consignee_value = [];
        $consignee_note = [];
        $consignee_po_number = [];
        $consignee_contact = [];
        $consignee_delivery_note = [];
        $load_consignee_commodity = [];
        $load_consigneer_contact = [];


        for ($i = 1; $i <= 15; $i++) { // Assuming there are up to 2 consignees based on your form
            if ($request->has("load_consignee_{$i}")) {
                
				$name = $request->input("load_consignee_{$i}") ?? ' ';
				
				$consignee_name[] = [
					'name' => $name
				];
            }
        }

        for ($i = 1; $i <= 15; $i++) { // Assuming there are up to 2 consignees based on your form
            if ($request->has("load_consignee_location_{$i}")) {
                
				$location = $request->input("load_consignee_location_{$i}") ?? ' ';
				
				$consignee_location[] = [
					'location' => $location
				];
            }
        }

        for ($i = 1; $i <= 15; $i++) { // Assuming there are up to 2 consignees based on your form
            if ($request->has("load_consignee_appointment_{$i}")) {
                
				$appointment = $request->input("load_consignee_appointment_{$i}") ?? null;
				
				$load_consignee_appointment[] = [
					'appointment' => $appointment
				];
            }
        }

        for ($i = 1; $i <= 15; $i++) { // Assuming there are up to 2 consignees based on your form
            if ($request->has("load_consignee_discription_{$i}")) {
                
				$description = $request->input("load_consignee_discription_{$i}") ?? ' ';
				
				$load_consignee_appointment[] = [
					'description' => $description
				];
            }
        }

        for ($i = 1; $i <= 15; $i++) { // Assuming there are up to 2 consignees based on your form
            if ($request->has("load_consignee_commodity_{$i}")) {
                
				$consignee_commodity = $request->input("load_consignee_commodity_{$i}") ?? ' ';
				
				$load_consignee_commodity[] = [
					'consignee_commodity' => $consignee_commodity
				];
            }
        }

        for ($i = 1; $i <= 15; $i++) { // Assuming there are up to 2 consignees based on your form
            if ($request->has("load_consignee_type_{$i}")) {
                
				$consignee_type = $request->input("load_consignee_type_{$i}") ?? ' ';
				
				$load_consignee_type[] = [
					'consignee_type' => $consignee_type
				];
            }
        }


        for ($i = 1; $i <= 15; $i++) { // Assuming there are up to 2 consignees based on your form
            if ($request->has("load_consignee_qty_{$i}")) {
                
				$consignee_qtys = $request->input("load_consignee_qty_{$i}") ?? ' ';
				
				$consignee_qty[] = [
					'consignee_qty' => $consignee_qtys
				];
            }
        }


        for ($i = 1; $i <= 15; $i++) { // Assuming there are up to 2 consignees based on your form
            if ($request->has("load_consignee_weight_{$i}")) {
                
				$consignee_weights = $request->input("load_consignee_weight_{$i}") ?? ' ';
				
				$consignee_weight[] = [
					'consignee_weight' => $consignee_weights
				];
            }
        }

        for ($i = 1; $i <= 15; $i++) { // Assuming there are up to 2 consignees based on your form
            if ($request->has("load_consignee_value_{$i}")) {
                
				$consignee_values = $request->input("load_consignee_value_{$i}") ?? ' ';
				
				$consignee_value[] = [
					'consignee_value' => $consignee_values
				];
            }
        }

        for ($i = 1; $i <= 15; $i++) {
			if ($request->has("load_consignee_notes_{$i}")) {
				$note = $request->input("load_consignee_notes_{$i}") ?? ' ';
				
				$consignee_note[] = [
					'load_consignee_notes' => $note
				];
			}
		}

        for ($i = 1; $i <= 15; $i++) { // Assuming there are up to 2 consignees based on your form
            if ($request->has("load_consignee_po_numbers_{$i}")) {
                
				$consignee_po_numbers = $request->input("load_consignee_po_numbers_{$i}") ?? ' ';
				
				$consignee_po_number[] = [
					'consignee_po_number' => $consignee_po_numbers
				];
            }
        }

        for ($i = 1; $i <= 15; $i++) { // Assuming there are up to 2 consignees based on your form
            if ($request->has("load_consigneer_contact_{$i}")) {
               
				$consignee_contact = $request->input("load_consigneer_contact_{$i}") ?? ' ';
				
				$load_consigneer_contact[] = [
					'consignee_contact' => $consignee_contact
				];
            }
        }

        for ($i = 1; $i <= 15; $i++) { // Assuming there are up to 2 consignees based on your form
            if ($request->has("load_consignee_delivery_notes_{$i}")) {
                
				$consignee_delivery_notes = $request->input("load_consignee_delivery_notes_{$i}") ?? ' ';
				
				$consignee_delivery_note[] = [
					'consignee_delivery_notes' => $consignee_delivery_notes
				];
            }
        }

        for ($i = 1; $i <= 15; $i++) {
            // Check if the form input for consignee note exists
            if ($request->has("load_consignee_notes_{$i}")) {

				$load_consignee_notes = $request->input("load_consignee_notes_{$i}") ?? ' ';
				
				$consignee_note[] = [
					'load_consignee_notes' => $load_consignee_notes
				];
            }
        }
        $load->carrier_id = $request->input('carrier_id') ?? '';
        $load->load_shipperr = json_encode($shipper_name);
        $load->load_shipper_location = json_encode($shipper_location);
        $load->load_shipper_appointment = json_encode($shipper_appointment);
        $load->load_shipper_discription = json_encode($shipper_description);
        $load->load_shipper_commodity_type = json_encode($shipper_type);
        $load->load_shipper_commodity = json_encode($shipper_commodity_name);
        $load->load_shipper_qty = json_encode($shipper_qty);
        $load->load_shipper_weight = json_encode($shipper_weight);
        $load->load_shipper_value = json_encode($shipper_value);
        //$load->load_shipper_shipping_notes = json_encode($shipper_delivery_note);
        $load->load_shipper_po_numbers = json_encode($shipper_po_number);
        $load->load_shipper_contact = json_encode($shipper_contact);
        $load->load_shipper_shipping_notes = json_encode($shipper_note);
        $load->load_consignee = json_encode($consignee_name);
        $load->load_consignee_location = json_encode($consignee_location);
        $load->load_consignee_appointment = json_encode($load_consignee_appointment);
        $load->load_consignee_discription = json_encode($consignee_description);
        $load->load_consignee_type = json_encode($load_consignee_type);
        $load->load_consignee_commodity = json_encode($load_consignee_commodity);
        $load->load_consignee_qty = $consignee_qty ? json_encode($consignee_qty) : '0';
        $load->load_consignee_weight = json_encode($consignee_weight);
        $load->load_consignee_value = json_encode($consignee_value);
        $load->load_consigneer_notes = json_encode($consignee_note);
        $load->load_consignee_po_numbers = json_encode($consignee_po_number);
        $load->load_consigneer_contact = json_encode($load_consigneer_contact);
        $load->load_consignee_delivery_notes = json_encode($consignee_delivery_note);
        $load->load_consignee_appointment = json_encode($load_consignee_appointment);
        $load->load_bill_to = $request->input('load_bill_to') ?? '';
        $load->load_dispatcher = $request->input('load_dispatcher') ?? '';
        $load->load_workorder = $request->input('load_workorder') ?? '';
        $load->load_payment_type = $request->input('load_payment_type') ?? '';
        $load->load_type = $request->input('load_type') ?? '';
        $load->load_shipper_rate = $request->input('load_shipper_rate') ?? '';
        $load->load_pds = $request->input('load_pds') ?? '';
        $load->load_fsc_rate = $request->input('load_fsc_rate') ?? '';
        $load->load_telephone = $request->input('load_telephone') ?? '';
        $load->load_carrier = $request->input('load_carrier') ?? '';
        $load->load_carrier_phone = $request->input('load_carrier_phone') ?? '';
        $load->load_advance_payment = $request->input('load_advance_payment') ?? '';
        $load->load_type_two = $request->input('load_type_two') ?? '';
        $load->load_billing_type = $request->input('load_billing_type') ?? '';
        $load->load_mc_no = $request->input('load_mc_no') ?? '';
        $load->load_equipment_type = $request->input('load_equipment_type') ?? '';
        $load->load_carrier_fee = $request->input('load_carrier_fee') ?? '';
        $load->load_currency = $request->input('load_currency') ?? '';
        $load->load_pds_two = $request->input('load_pds_two') ?? '';
        $load->load_billing_fsc_rate = $request->input('load_billing_fsc_rate') ?? '';
        $load->load_final_carrier_fee = $request->input('load_final_carrier_fee') ?? '';
        $load->load_final_rate = $request->input('shipper_load_final_rate') ?? '';
        $load->load_other_charge = $request->input('load_other_charge') ?? '';
        $load->shipper_load_final_rate = $request->input('shipper_load_final_rate') ?? '';
        $load->customer_id = $request->input('customer_id') ?? '';
        $load->comment = $request->input('comment') ?? '';
        $load->customer_refrence_number = $request->input('customer_refrence_number') ?? '';
        $load->carrier_dot = $request->input('carrier_dot') ?? '';
        $load->invoicing_payment_terms = $request->input('invoicing_payment_terms') ?? '';
        $load->invoice_number = $request->input('invoice_number') ?? '';
        $load->invoice_date = $request->input('invoice_date') ?? '';
		$load->pre_advance = $request->input('pre_advance') ?? '';
        $load->paper_work_date = !empty($request->paper_work_date) ? $request->paper_work_date : null;
        $load->payment_receiving_date = $request->filled('payment_receiving_date') ? $request->payment_receiving_date : null;
        $load->invoice_status_date = $request->filled('invoice_status_date') ? $request->invoice_status_date : null;
        // $load->load_carrier_due_date_on = $request->input('load_carrier_due_date_on') ?? '';
        $status = $request->input('carrier_mark_as_paid', 'Not Paid');

        $load->carrier_mark_as_paid = $status;

        if ($status === 'Paid') {
            $load->load_carrier_due_date_on = $request->input('load_carrier_due_date_on') ?? ''; // or now()->format('d-m-Y') if TEXT column
        } else {
            $load->load_carrier_due_date_on = null;
        }
        $currentDateTime = Carbon::now();  // Get the current timestamp
        
        if ($newStatus === 'Delivered') {
            // When the load status is 'Delivered', add the actual delivery date
            $data = [
                'load_status' => $newStatus,
                'load_actual_delivery_date' => $currentDateTime,  // Current timestamp
            ];
        } else {
            // Otherwise, just update the load status
            $data = [
                'load_status' => $newStatus,
                // Include other fields if necessary
            ];
        }
        
        // Apply the data update
        $load->update($data);

        // Initialize shipperCharges array
       $shipperCharges = [];

        if ($request->has('shipperchargeType') && $request->has('shipperchargeAmount')) {
            foreach ($request->shipperchargeType as $index => $chargeType) {
                $chargeAmount = $request->shipperchargeAmount[$index] ?? null;
                if ($chargeAmount !== null) {
                    $shipperCharges[] = [
                        'type' => $chargeType,
                        'amount' => $chargeAmount,
                    ];
                }
            }
        }
        
        // Initialize carrierCharges array
        $carrierCharges = [];
        if ($request->has('shipper_type_charge') && $request->has('shipper_other_charge')) {
            foreach ($request->shipper_type_charge as $index => $carrierchargeType) {
                $carrierchargeAmount = $request->shipper_other_charge[$index] ?? null;
                if ($carrierchargeAmount !== null) {
                    $carrierCharges[] = [
                        'type' => $carrierchargeType,
                        'amount' => $carrierchargeAmount,
                    ];
                }
            }
        }
        
        $load->carrier_load_other_charge = json_encode($carrierCharges);
        $load->shipper_load_other_charge = json_encode($shipperCharges);

        if ($request->hasFile('load_delivery_do_file')) {
            $files = [];
			
            foreach ($request->file('load_delivery_do_file') as $file) {
                if ($file->isValid()) {
                   $filename = $file->getClientOriginalName();
                    $destinationPath = public_path('uploads/delivery-order/'.$id.'/');
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0775, true);
                    }
				
                    $file->move($destinationPath, $filename);
                    $files[] = 'uploads/delivery-order/'.$id.'/' . $filename;
                } else {
					return back()->withErrors(['load_delivery_do_file' => 'Uploaded file is not valid.']);
				}
            }
            $load->load_delivery_do_file = json_encode($files); // Save as JSON
        }
        
        $customerId = $request->customer_id;
        $newShipperLoadFinalRate = (float) ($request->shipper_load_final_rate ?? 0);
        $oldShipperLoadFinalRate = (float) ($load->shipper_load_final_rate ?? 0);

        // Calculate the difference between old and new rates
        $rateDifference = $newShipperLoadFinalRate - $oldShipperLoadFinalRate;

        $customer = Customer::where('id', $customerId)->lockForUpdate()->first();

        if ($customer) {
            $loadCreationAmount = (float) Load::where('customer_id', $customer->id)
                ->where('load_status', '!=', 'Cancelled')
                ->where(function ($query) {
                    $query->where('invoice_status', '!=', 'Paid Record')
                        ->orWhereNull('invoice_status');
                })
                ->sum('shipper_load_final_rate');

            $paymentReceivedAmount = (float) Load::where('customer_id', $customer->id)
                ->where('invoice_status', 'Paid Record')
                ->sum('receiving_amount');

            $creditSummary = app(CreditService::class)->calculateCustomerCreditSummary(
                $customer,
                (float) ($customer->adv_customer_credit_limit ?? 0),
                $loadCreationAmount,
                $paymentReceivedAmount
            );

            $customer->remaining_credit = $creditSummary['remaining_credit'];
            $customer->remaining_credit_amount = $creditSummary['remaining_credit'];
            $customer->save();
        }
        
        if (strcasecmp((string) $newStatus, 'Cancelled') === 0 && strcasecmp((string) $originalLoad->load_status, 'Cancelled') !== 0) {
            $this->applyCancelledLoadAccounting($originalLoad, $load);
        }

        $load->save();

        $newData = json_encode($load);
        $subject = "update the load, load-id:- $load->id";
        addToLog($customerId ='', $load->id, $subject, $oldData, $newData);


        return back()->with('success', 'Load updated successfully');
        
    }); // end DB::transaction
    }
	
	
    protected function applyCancelledLoadAccounting(Load $originalLoad, Load $load, ?Customer $customer = null): void
    {
        $wasAlreadyCancelled = strcasecmp((string) $originalLoad->load_status, 'Cancelled') === 0;

        if (!$wasAlreadyCancelled) {
            $customer = $customer ?? Customer::find($originalLoad->customer_id);

            if ($customer) {
                $release = app(CreditService::class)->splitCreditRelease(
                    $this->moneyValue($originalLoad->shipper_load_final_rate),
                    $this->invoiceCreditAmount($originalLoad),
                    $this->moneyValue($originalLoad->invoice_credit_overflow)
                );

                $customer->remaining_credit = $this->moneyValue($customer->remaining_credit) + $release['to_remaining'];
                $customer->remaining_credit_amount = $customer->remaining_credit; // Update remaining credit amount after refund
                $customer->invoice_credit_limit = $this->moneyValue($customer->invoice_credit_limit) + $release['to_invoice_limit'];
                $customer->save();
            }
        }

        $load->invoice_credit_overflow = 0;
        $load->load_status = 'Cancelled';
        $load->invoice_status = null;
        $load->load_shipper_rate = 0;
        $load->load_fsc_rate = 0;
        $load->load_billing_fsc_rate = 0;
        $load->shipper_load_other_charge = json_encode([]);
        $load->carrier_load_other_charge = json_encode([]);
        $load->shipper_load_final_rate = 0;
        $load->load_final_rate = 0;
        $load->load_carrier_fee = 0;
        $load->load_final_carrier_fee = 0;
        $load->receiving_amount = 0;
        $load->remaining_amount = 0;
        $load->load_advance_rec_amount = 0;
        $load->invoice_number = '';
        $load->invoice_date = null;
        $load->paper_work_date = null;
        $load->payment_receiving_date = null;
        $load->invoice_status_date = null;
        $load->load_actual_delivery_date = null;
        $load->load_carrier_due_date = null;
        $load->load_carrier_due_date_on = null;
    }

    protected function invoiceCreditAmount(Load $load): float
    {
        $charges = json_decode($load->shipper_load_other_charge, true);

        if (!is_array($charges)) {
            return 0.0;
        }

        return array_reduce($charges, function ($total, $charge) {
            if (($charge['for_invoice'] ?? 'off') !== 'on') {
                return $total;
            }

            return $total + $this->moneyValue($charge['amount'] ?? 0);
        }, 0.0);
    }

    protected function moneyValue($value): float
    {
        return (float) preg_replace('/[^0-9.\-]/', '', (string) ($value ?? 0));
    }

	public function allLogs(Request $request)
    {
        $alllogs = activity_log::orderBy('created_at', 'desc')->paginate(50);
        return view('admin.activity_logs', compact('alllogs'));
    }

    public function updateusers(Request $request, $id)
    {
        // User being updated
        $user = User::findOrFail($id);

        // ✅ Capture OLD data (before update)
        $oldData = $user->getOriginal();

        // Update user
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'office' => $request->office_id,
            'manager' => $request->manager_id,
            'team_lead' => $request->team_lead,
            'address' => $request->address,
            'emergency_contact' => $request->emergency_contact,
            'emp_code' => $request->emp_code,
        ]);

        // ✅ Capture NEW data (only changed fields)
        $newData = $user->getChanges();

        // Logged-in user (who performed the action)
        $authUser = Auth::user();

        $subject = "Customer Approval Form Created By {$authUser->name}";

        // ✅ Log everything
        addToLog(
            $customerId = '',
            $id = $user->id,
            $subject,
            json_encode($oldData),
            json_encode($newData)
        );

        return redirect()->back()->with('success', 'User updated successfully!');
    }

public function it_hardware()
{
        $tickets_open = ItHardware::with('user')->where('status', 'Open')->orderBy('created_at', 'desc')->get();
        $tickets_hold = ItHardware::where('status', 'Hold')->orderBy('created_at', 'desc')->get();
        $tickets_completed = ItHardware::where('status', 'Completed')->orderBy('created_at', 'desc')->get();
    return view('admin.it_hardware', compact('tickets_open', 'tickets_hold', 'tickets_completed'));
}

public function updateTicketStatus(Request $request)
{
    $request->validate([
        'ticket_id' => 'required|exists:it_hardwares,id',
        'status' => 'required',
        'remark' => 'nullable|string'
    ]);

    $ticket = ItHardware::findOrFail($request->ticket_id);
    $ticket->status = $request->status;
    $ticket->remark = $request->remark;
    $ticket->save();

    return response()->json([
        'success' => true
    ]);
}

public function ticketCount()
{
    $count = ItHardware::where('status', 'Open')->count();

    return response()->json([
        'count' => $count
    ]);
}






}

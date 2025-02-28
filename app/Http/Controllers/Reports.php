<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\GoalModel;
use App\SettingModel;
use App\Http\Controllers\TraitSettings;
use DB;
use Auth;
use App;

class Reports extends Controller
{

	use TraitSettings;

	public function __construct() {
		$data = $this->getapplications();
		// $lang = $data->language;
		// App::setLocale($lang);
        $this->middleware('auth');
	}

	public function assetactivity() {
        return view( 'reports.assetactivitys' );
	}

	public function componentactivity() {
        return view( 'reports.componentactivity' );
	}

	public function maintenance() {
        return view( 'reports.maintenance' );
	}

	public function bytype() {
        return view( 'reports.bytype' );
	}

	public function bysupplier() {
        return view( 'reports.bysupplier' );
	}

	public function bylocation() {
        return view( 'reports.bylocation' );
	}

	public function bystatus() {
        return view( 'reports.bystatus' );
	}

	//show all report report view
	public function allreports(){
		return view('reports.reports');
	}


	/**
	 * get data asset from database
	 * @return object
	 */
    public function getassetactivityreport(){
        $data = DB::table('asset_history')
        ->select('asset_history.*')
        ->leftJoin('assets', 'assets.id', '=', 'asset_history.assetid')
        // ->leftJoin('asset_type', 'assets.typeid', '=', 'asset_type.id') 
        // ->leftJoin('employees', 'employees.id', '=', 'asset_history.employeeid')
        // ->leftJoin('location1', 'location.id', '=', 'assets.locationid')
		->orderBy('asset_history.updated_at', 'desc')
		->orderBy('asset_history.created_at', 'desc')  
		->get();
        return Datatables::of($data)
        
        ->addColumn('asset_detail',function($single){

            $asset_detail="";
            $asset_data = DB::table('assets')->where("assetid",$single->assetid)->get();
            // if(!empty($asset_data)){
            if(count($asset_data)){
                $asset_detail=$asset_data[0]->name;
            }

			return $asset_detail;
        })
        ->addColumn('emp_detail',function($single){
            $emp_detail="";
            if($single->employeeid!=""){
                $emp_data = DB::table('employees')->where("emp_id",$single->employeeid)->get();
                
                if (count($emp_data) === 0) {

                }else{
                    $emp_detail=$emp_data[0]->fullname.'<br>'.$emp_data[0]->emp_id;
                }
               
            }

			return $emp_detail;
        })
        ->addColumn('location_detail',function($single){

            $location_detail="";
            if($single->location!=""){
                $location_data = DB::table('location')->where("id",$single->location)->get();

                // if(!empty($location_data)){
                if(count($location_data)){
                    $location_detail=$location_data[0]->name;
                }
            }
            
			return $location_detail;
        })
        ->addColumn('date_div',function($single){

            $setting = DB::table('settings')->where('id', '1')->first();

            $date_div="";
            if($single->type=="Allocation"){
                $date_div.="Allocated Date : ";
                if($single->allocated_date!=null && $single->allocated_date!="0000-00-00"){
                    $date_div.=date($setting->formatdate, strtotime($single->allocated_date));
                }
                $date_div.="<br>";
                $date_div.="Get Back Date : ";

                if($single->get_back_date!=null && $single->get_back_date!= "0000-00-00"){
                    $date_div.=date($setting->formatdate, strtotime($single->get_back_date));
                }
            }
            else if($single->type=="Retiral"){
                $date_div.="Retiral Date : ";
                if($single->retiraldate!=null && $single->retiraldate!= "0000-00-00"){
                    $date_div.=date($setting->formatdate, strtotime($single->retiraldate));
                    $date_div.="<br>";
                    $date_div.=$single->reason;
                }
            }

            return $date_div;
        })
        ->rawColumns(['asset_detail','emp_detail','location_detail','date_div'])
        ->make(true);		
    }

    /**
	 * get data  component from database
	 * @return object
	 */
    public function getcomponentactivityreport(){
        $data = DB::table('component_assets')
        ->select('component_assets.*', 'component.name as component','assets.name as asset', 'asset_type.name as type', 'location.name as location')
        ->leftJoin('assets', 'assets.id', '=', 'component_assets.assetid')
        ->leftJoin('asset_type', 'assets.typeid', '=', 'asset_type.id')
        ->leftJoin('location', 'location.id', '=', 'assets.locationid')
        ->leftJoin('component', 'component.id', '=', 'component_assets.componentid')
        ->offset(0)->limit(10)
		->orderBy('component_assets.updated_at', 'desc')
		->orderBy('component_assets.created_at', 'desc')
		->get();

        return Datatables::of($data)
        
        ->addColumn( 'status', function ( $accountsingle ) {
           

            if($accountsingle->status==2){
                    
                    $status = '<span class="badge badge-data text-white background-blue">'.trans('lang.checkin').'</span>';
                
            }else{
                    $status = '<span class="badge badge-data text-white background-yellow">'.trans('lang.checkout').'</span>';
            }

            return  $status;
           
        } )->rawColumns(['status'])
        ->make(true);		
    }


    /**
	 * get asset data by type from database
	 * @return object
	 */
    public function getdatabytypereport(Request $request){
    	$data = DB::table('assets')
		->leftJoin('brand', 'assets.brandid', '=', 'brand.id')
		// ->leftJoin('supplier', 'assets.supplierid', '=', 'supplier.id')
		->leftjoin('asset_type', 'assets.a_type_id', '=', 'asset_type.id')
		->leftJoin('location', 'assets.locationid', '=', 'location.id')
		->select(['assets.*','brand.name as brand','brand.id as brandid', 'asset_type.name as type' , 'location.name as location']);
		
       return Datatables::of($data)
        ->addColumn('pictures',function($single){
			return '<img src="'.url('/').'/../upload/assets/'.$single->picture.'" style="width:90px"/>';
        })
        ->filter(function ($query) use ($request) {
				if ($request->has('assettype')) {

					$query->where('assets.a_type_id', 'like', "%{$request->get('assettype')}%");
				} 
			})
        ->rawColumns(['pictures'])
        ->make(true);		
    }


    /**
	 * get asset data by status from database
	 * @return object
	 */
    public function getdatabystatusreport(Request $request){
    	$data = DB::table('assets')
		->leftJoin('brand', 'assets.brandid', '=', 'brand.id')
		->leftJoin('supplier', 'assets.supplierid', '=', 'supplier.id')
		->leftjoin('asset_type', 'assets.typeid', '=', 'asset_type.id')
		->leftJoin('location', 'assets.locationid', '=', 'location.id')
		->select(['assets.*', 'supplier.name as supplier', 'brand.name as brand','brand.id as brandid', 'asset_type.name as type' , 'location.name as location']);
		

       return Datatables::of($data)
        ->addColumn('pictures',function($single){
			return '<img src="'.url('/').'/../upload/assets/'.$single->picture.'" style="width:90px"/>';
        })
        ->addColumn('status2', function($single){
        	//set status
            if($single->status=='1'){
                $status = trans('lang.readytodeploy');
            }
            if($single->status=='2'){
                $status = trans('lang.pending');
            }
            if($single->status=='3'){
                $status = trans('lang.archived');
            }
            if($single->status=='4'){
                $status = trans('lang.broken');
            }
            if($single->status=='5'){
                $status = trans('lang.lost');
            }
            if($single->status=='6'){
                $status = trans('lang.outofrepair');
            }

            return $status;
        })
        ->filter(function ($query) use ($request) {
				if ($request->has('statustype')) {

					$query->where('assets.status', 'like', "%{$request->get('statustype')}%");
				} 
			})
        ->rawColumns(['pictures','status2'])
        ->make(true);		
    }


    /**
	 * get asset data by supplier from database
	 * @return object
	 */
    public function getdatabysupplierreport(Request $request){
    	$data = DB::table('assets')
		->leftJoin('brand', 'assets.brandid', '=', 'brand.id')
		->leftJoin('supplier', 'assets.supplierid', '=', 'supplier.id')
		->leftjoin('asset_type', 'assets.typeid', '=', 'asset_type.id')
		->leftJoin('location', 'assets.locationid', '=', 'location.id')
		->select(['assets.*', 'supplier.name as supplier', 'brand.name as brand','brand.id as brandid', 'asset_type.name as type' , 'location.name as location']);
		
       return Datatables::of($data)
        ->addColumn('pictures',function($single){
			return '<img src="'.url('/').'/../upload/assets/'.$single->picture.'" style="width:90px"/>';
        })
        ->filter(function ($query) use ($request) {
				if ($request->has('supplierid')) {

					$query->where('assets.supplierid', 'like', "%{$request->get('supplierid')}%");
				} 
			})
        ->rawColumns(['pictures'])
        ->make(true);		
    }


    /**
	 * get asset data by location from database
	 * @return object
	 */
    public function getdatabylocationreport(Request $request){
    	$data = DB::table('assets')
		->leftJoin('brand', 'assets.brandid', '=', 'brand.id')
		// ->leftJoin('supplier', 'assets.supplierid', '=', 'supplier.id')
		// ->leftjoin('asset_type', 'assets.typeid', '=', 'asset_type.id')
		->leftJoin('location', 'assets.locationid', '=', 'location.id')
		->select(['assets.*', 'brand.name as brand','brand.id as brandid', 'location.name as location']);
		
       return Datatables::of($data)
        ->addColumn('pictures',function($single){
			return '<img src="'.url('/').'/../upload/assets/'.$single->picture.'" style="width:90px"/>';
        })
        ->filter(function ($query) use ($request) {
				if ($request->has('locationid')) {

					$query->where('assets.locationid', 'like', "%{$request->get('locationid')}%");
				} 
			})
        ->rawColumns(['pictures'])
        ->make(true);		
    }




}

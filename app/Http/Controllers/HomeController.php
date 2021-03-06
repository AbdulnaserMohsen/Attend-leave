<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\AttendLeave;
use App\Weekends;
use App\AttendLeaveSetting;
use Carbon\Carbon;
use App\Calender;
use App\UserStatus;
use App\Job;
use Auth;
use App\User;
use Cache;
use App;
use Illuminate\Support\Facades\Validator;
use App\rules\Email_Username;
use App\About;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    protected function init($current_date)
    {
        $dayname = Carbon::now()->locale('en')->isoFormat('dddd'); //get day name
        $newday = new Calender();
        $newday->date = $current_date;
        $weekends = Weekends::pluck('day_en')->toArray();
        //dd($weekends , $dayname);
        if(in_array($dayname,$weekends))//if weekend
        {
            $newday->day_status_id = 2; 
        }
        else if($newday)
        {
            $newday->day_status_id = 1;
        }
        $newday->save();

    }
    protected function all_absence($newday , $attend_leave_setting)
    {
        
        $users = User::whereNotIn('id',[1,2,3])->pluck('id')->toArray();
        $exists_users = AttendLeave::where('calender_id', $newday->id)->whereIn('user_id', $users)->pluck('user_id')->toArray();
        if(count($exists_users) == count($users)) 
        {
            return;
        }

        $not_exists_ueser = array_diff($users, $exists_users);
        $absence_all =[];
        foreach ($not_exists_ueser as $key => $value) 
        {
            $absence_all[$key] = array( 'user_id'=>$value , 'calender_id'=>$newday->id , 'setting_id'=>$attend_leave_setting->id , 'attend_user_status_id'=>3 , 'leave_user_status_id'=>3 , 'created_at' => date('Y-m-d H:i:s') , 'updated_at' => date('Y-m-d H:i:s') );
        }
        AttendLeave::insert($absence_all);

    }

    public function index()
    {
        $current_date = Carbon::now()->format('Y-m-d');
        if(Calender::where('date',$current_date)->first() == null)
        {
            $this->init($current_date);
        }
        $newday =  Calender::where('date',$current_date)->first();
        if($newday->day_status_id != 1)
        {
            //dd("no attend");
            return view("no_attend",compact("newday")); 
        }
        else
        {
            //dd("attend");
            $user_statuses = UserStatus::whereIn('id',[1,2])->get();
            $attend_leave_setting =AttendLeaveSetting::latest()->first();//get last record
            $this->all_absence($newday , $attend_leave_setting);

            if(Carbon::now()->diffInSeconds(Carbon::parse($attend_leave_setting->attend_time_from), false)>0)
            {
                $attend_timer = 0;// the time to save attend not start
            }
            else
            {
               $attend_timer = Carbon::now()->diffInSeconds(Carbon::parse($attend_leave_setting->attend_time_to), false);
               
               if($attend_timer<0) {$attend_timer=-1;}//the time to save attend finished 
            }

            if(Carbon::now()->diffInSeconds(Carbon::parse($attend_leave_setting->leave_time_from), false)>0)
            {
                $leave_timer = 0;// the time to save leave not start
            }
            else
            {
               $leave_timer = Carbon::now()->diffInSeconds(Carbon::parse($attend_leave_setting->leave_time_to), false);
               
                if($leave_timer<0) {$leave_timer=-1;}//the time to save leave finished 
            }
            
            $user_attend_leave = AttendLeave::where('calender_id',$newday->id)->where('user_id', Auth::id())->first();
            if(in_array(Auth::id(), [1,2,3]) ){$user_attend_leave = AttendLeave::where('calender_id',$newday->id)->where('user_id', 4)->first();}
            //dd($attend_timer,$leave_timer);
            $home =  About::first();
            return view("user_attend",compact("user_statuses","attend_timer","leave_timer","attend_leave_setting","user_attend_leave","home"));
        } 
        

    }

    public function user_attend(Request $request)
    {
        $current_date = Carbon::now()->format('Y-m-d');
        $current_day =  Calender::where('date',$current_date)->first();
        $attend_leave_setting =AttendLeaveSetting::latest()->first();

        if( ($current_day->day_status_id == 1) && ( (Carbon::now()->diffInSeconds(Carbon::parse($attend_leave_setting->attend_time_from), false)<0) && (Carbon::now()->diffInSeconds(Carbon::parse($attend_leave_setting->attend_time_to), false)>0 ) ||  $attend_leave_setting->enable_after_attend ) )
        {
            //dd("aaaaae");
            $attend = AttendLeave::where('calender_id',$current_day->id)->where('user_id', Auth::id())->first();
            $attend->setting_id = AttendLeaveSetting::latest()->first()->id;
            $attend->attend_user_status_id = $request->get('slct');
            $attend->attend_time =  Carbon::now()->toTimeString();
            $attend->attend_by_user = Carbon::now()->toTimeString();

            if( Carbon::now()->diffInSeconds(Carbon::parse($attend_leave_setting->attend_time_to), false)<0 &&  $attend_leave_setting->enable_after_attend != 0)
            {
                $attend->attend_hint = Carbon::now()->diff(Carbon::parse($attend_leave_setting->attend_time_to))->format('%H:%i');
            }
            
            $attend->save();
            return response()->json(['success'=>__('admin.saved')]);
        }

        //dd("ddddeeeeee");

    }

    public function user_leave(Request $request)
    {
        $current_date = Carbon::now()->format('Y-m-d');
        $current_day =  Calender::where('date',$current_date)->first();
        $attend_leave_setting =AttendLeaveSetting::latest()->first();

        if( ($current_day->day_status_id == 1) && ( (Carbon::now()->diffInSeconds(Carbon::parse($attend_leave_setting->leave_time_from), false)<0) && (Carbon::now()->diffInSeconds(Carbon::parse($attend_leave_setting->leave_time_to), false)>0 ) ||  $attend_leave_setting->enable_before_leave ) )
        {
            //dd("aaaaae");
            $attend = AttendLeave::where('calender_id',$current_day->id)->where('user_id', Auth::id())->first();
            $attend->setting_id = AttendLeaveSetting::latest()->first()->id;
            $attend->leave_user_status_id = $request->get('slct');
            $attend->leave_time =  Carbon::now()->toTimeString();
            $attend->leave_by_user = Carbon::now()->toTimeString();

            if( Carbon::now()->diffInSeconds(Carbon::parse($attend_leave_setting->leave_time_from), false)>0 &&  $attend_leave_setting->enable_before_leave )
            {
                $attend->leave_hint = Carbon::now()->diff(Carbon::parse($attend_leave_setting->leave_time_from))->format('%H:%i');
            }
            
            $attend->save();
            return response()->json(['success'=>__('admin.saved')]);
        }

        //dd("ddddeeeeee");
    }

    public function profile()
    {
        $jobs = Job::whereNotIn('id',[1000,2000,3000])->get();
        $user= User::find(Auth::id());
        $home =  About::first();
        return view("auth.profile",compact("jobs","user","home"));
    }

    protected function validator(array $data)
    {
       return Validator::make($data, [
            'name_ar' => ['required', 'string', 'max:255' , 'regex:/^[\p{Arabic} ]+$/u'],
            'name_en' => ['required', 'string', 'max:255' , 'regex:/^[a-zA-Z ]+$/u'],
            'slct' => ['required','exists:jobs,id'],
            'user_name' => ['required', 'string', 'max:255', 'unique:users,user_name,'.Auth::id().',id', new Email_Username ],
        ]);
    }

    public function update_profile(Request $request)
    {
        $this->validator($request->all())->validate();

        $user= User::find(Auth::id());
        $user->name_ar = $request->get('name_ar');
        $user->name_en = $request->get('name_en');
        $user->job_id = $request->get('slct');
        $user->user_name = $request->get('user_name');
        $user->save();
        return response()->json(['success'=>__('admin.saved')]);
    }

    public function user_year_months_statistics( $year, Request $request)
    {
        
        if(in_array(Auth::id(), [1,2,3])){$user_id = 4;}
        else{$user_id = Auth::id();}
        
        $years = Calender::whereHas('attend_leave', function($q) use($user_id)
                    {
                        $q->where('user_id', $user_id);
                    })->pluck('date')->countBy(function($val) 
                    {
                        return Carbon::parse($val)->format('Y') ;
                    }) ;

        $months = Calender::whereHas('attend_leave', function($q) use($user_id)
                        {
                            $q->where('user_id', $user_id);
                        })->whereYear('date',$year)->pluck('date') ->countBy(function($val) 
                        {
                            return Carbon::parse($val)->monthName ;
                        });

        $home =  About::first();
        return view('year_month_statistics',compact('year','years','months','home'));
        

    }


    public function user_statictics(Request $request , $year , $month , $table_paginate)
    {
        
        if ($table_paginate <= 0) 
        {
            $table_paginate = Cache::get('table_paginate', function() { return 5; });
        }
        else
        {
            Cache::forever('table_paginate', $table_paginate);
        }

        if(in_array(Auth::id(), [1,2,3])){$user_id = 4;}
        else{$user_id = Auth::id();}

        $attend_leaves = AttendLeave::where('user_id',$user_id)->whereHas('calendar', function($q) use($year,$month)
            {
                $q->whereYear('date',$year)->whereMonth('date',$month);
            })->orderBy('user_id')->get();
        
        $workers = $attend_leaves->groupBy('user_id');
        //$workers = $workers->paginate($worker_paginate);
        $statistics_of_users =collect();
        foreach ($workers as $key => $worker) 
        {
            if(App::isLocale('ar')){$name = $worker->first()->user->name_ar;}
            else {$name = $worker->first()->user->name_en;}
            
            $absence_counter = $worker->filter( function($worker) 
                {
                    return strstr($worker->attend_user_status_id, "3") ||
                           strstr($worker->leave_user_status_id, "3");
                })->count();

            $attend_leave_counter = $worker->where("attend_user_status_id",1)->where("leave_user_status_id",2)->count();

            $other_attend = $worker->whereNotIn("attend_user_status_id",[1,3])->whereNotIn("leave_user_status_id",[3])->countBy("attend_user_status_id")->keyBy(function ($item, $key)
                {
                    if(App::isLocale('ar')){$key = UserStatus::where('id',$key)->first()->name_ar;}
                    else {$key = UserStatus::where('id',$key)->first()->name_en;}
                    return $key;
                }); 

            $other_leave = $worker->whereNotIn("leave_user_status_id",[2,3])->whereNotIn("attend_user_status_id",[3])->countBy('leave_user_status_id')->keyBy(function ($item, $key)
                {
                    if(App::isLocale('ar')){$key = UserStatus::where('id',$key)->first()->name_ar;}
                    else {$key = UserStatus::where('id',$key)->first()->name_en;}
                    return $key;
                }); 

            $ordinary = AttendLeave::whereHas('calendar', function($q) use($year,$month)
            {
                $q->whereYear('date', $year)->whereMonth('date',$month);
            })->where('user_id',$worker->first()->user_id)->paginate($table_paginate,['*'],'table');
            
            //dd($other_leave);
            
            $statistics_of_users[$key] = (["name" => $name , "attend_leave_counter" => $attend_leave_counter ,"absence_counter" => $absence_counter , "other_attend" => $other_attend ,"other_leave" => $other_leave,"ordinary"=>$ordinary]);
        
        }

        //dd($attend_leaves,$statistics_of_users,$workers );
        $home =  About::first();
        return view('statistics',compact('statistics_of_users','workers','year','month','table_paginate','home'));

    }

}

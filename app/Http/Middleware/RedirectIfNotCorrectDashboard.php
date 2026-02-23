<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Position; 
use App\Models\Resort;
use App\Models\ResortPosition;
class RedirectIfNotCorrectDashboard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('resort-admin')->check()) {
            $Resort = Auth::guard('resort-admin')->user();
            $employee = $Resort->GetEmployee;
            $employeeRank = $employee->rank ?? null;
            $rankConfig = config('settings.Position_Rank');
            $availableRank = array_key_exists($employeeRank, $rankConfig) ? $rankConfig[$employeeRank] : '';
            $currentRoute = $request->route()->getName();

            Log::info("Redirect Middleware: User Rank - $availableRank | Current Route - $currentRoute");

            $allowedRoutesForMasterAdmin = [
                'resort.workforceplan.resortadmindashboard',
                'resort.recruitement.admindashboard',
                'resort.timeandattendance.admindashboard',
                'leave.admindashboard',
                'resort.Accommodation.admindashboard',
                'payroll.admindashboard',
                'PeopleRelation.Admindashboard', 
                'Survey.Admindashboard',          
                'FileManagment.Admindashboard',
                'incident.admin.dashboard',
                'people.admin.dashboard'
            ];

            // Master Admin Redirection Logic
            if (empty($availableRank) && $Resort->is_master_admin == 1) 
            {
                // if (in_array($currentRoute, $allowedRoutesForMasterAdmin)) {
                //     Log::info("Master Admin is on an allowed route, skipping redirection.");
                //     return $next($request);
                // } else {

                    // // Check if current route is related to workforce plan or recruitment
                    // if (str_contains($currentRoute, 'workforceplan') && !$request->routeIs('resort.workforceplan.resortadmindashboard')) {
                    //     Log::info("Redirecting Admin to Workforceplan Admin Dashboard");
                    //     return redirect()->route('resort.workforceplan.resortadmindashboard');
                    // }
                    // elseif (str_contains($currentRoute, 'recruitement') && !$request->routeIs('resort.recruitement.admindashboard')) {
                    //     Log::info("Redirecting Admin to Recruitment Admin Dashboard");
                    //     return redirect()->route('resort.recruitement.admindashboard');
                    // }
                    // elseif (str_contains($currentRoute, 'timeandattendance') && !$request->routeIs('resort.timeandattendance.admindashboard')) {
                    //     Log::info("Redirecting Admin to Time and attendance Admin Dashboard");
                    //     return redirect()->route('resort.timeandattendance.admindashboard');
                    // }
                    // elseif (str_contains($currentRoute, 'accommodation') && !$request->routeIs('resort.accommodation.admindashboard')) {
                    //     Log::info("Redirecting Admin to Accommodation Admin Dashboard");
                    //     return redirect()->route('resort.accommodation.admindashboard');
                    // }
                    // elseif (str_contains($currentRoute, 'leave') && !$request->routeIs('leave.admindashboard')) {
                    //     Log::info("Redirecting Admin to Leave Admin Dashboard");
                    //     return redirect()->route('leave.admindashboard');
                    // }
                    // elseif (str_contains($currentRoute, 'payroll') && !$request->routeIs('payroll.admindashboard')) {
                    //     Log::info("Redirecting Admin to Payroll Admin Dashboard");
                    //     return redirect()->route('payroll.admindashboard');
                    // }
                    // elseif (str_contains($currentRoute, 'PeopleRelation') && !$request->routeIs('PeopleRelation.Admindashboard'))
                    //  {
                    //     Log::info("Redirecting Admin to PeopleRelation Admin Dashboard");
                    //     return redirect()->route('PeopleRelation.Admindashboard');
                    // }
                    // elseif (str_contains($currentRoute, 'learning') && !$request->routeIs('learning.admin.dashboard'))
                    // {
                    //     Log::info("Redirecting Admin to Learning Admin Dashboard");
                    //     return redirect()->route('learning.admin.dashboard');
                    // }
                    // elseif (str_contains($currentRoute, 'GrievanceAndDisciplinery') && !$request->routeIs('GrievanceAndDisciplinery.Admindashboard'))
                    // {
                    //     Log::info("Redirecting Admin to Grievance And Disciplinery Admin Dashboard");
                    //     return redirect()->route('GrievanceAndDisciplinery.Admindashboard');
                    // }
                    // elseif (str_contains($currentRoute, 'Survey') && !$request->routeIs('Survey.Admindashboard'))
                    // {
                    //     Log::info("Redirecting Admin to Survey Admin Dashboard");
                    //     return redirect()->route('Survey.Admindashboard');
                    // }
                    // elseif (str_contains($currentRoute, 'FileManagment') && !$request->routeIs('FileManagment.Admindashboard'))
                    // {
                    //     Log::info("Redirecting Admin to File Managment Admin Dashboard");
                    //     return redirect()->route('FileManagment.Admindashboard');
                    // }
                    // elseif (str_contains($currentRoute, 'incident') && !$request->routeIs('incident.admin.dashboard'))
                    // {
                    //     Log::info("Redirecting Admin to Incident Admin Dashboard");
                    //     return redirect()->route('incident.admin.dashboard');
                    // }
                    // elseif (str_contains($currentRoute, 'people') && !$request->routeIs('people.admin.dashboard'))
                    // {
                    //     Log::info("Redirecting Admin to People Admin Dashboard");
                    //     return redirect()->route('people.admin.dashboard');
                    // }
                    // elseif(str_contains($currentRoute, 'master') && !$request->routeIs('resort.master.admin_dashboard')) {
                    //     Log::info("Redirecting Admin to Master Dashboard");
                    //     return redirect()->route('resort.master.admin_dashboard');
                    // }

                        return redirect()->route('resort.Page.Permission');


                    
                // }

                // dd(str_contains($currentRoute, 'leave'), !$request->routeIs('leave.admindashboard'));
            }

            // HR (rank 3), Finance (rank 7), and GM (rank 8) should always get HR-level dashboard access
            $isHrOrFinance = in_array($employeeRank, [3, 7, 8]);

            // Also check if user is in Finance/Accounting/HR department or has related position title
            if (!$isHrOrFinance && $employee) {
                $empDeptName = $employee->department->name ?? '';
                $empPositionTitle = $employee->position->position_title ?? '';
                if (stripos($empDeptName, 'Accounting') !== false || stripos($empDeptName, 'Finance') !== false
                    || stripos($empPositionTitle, 'Finance') !== false
                    || stripos($empDeptName, 'Human Resources') !== false || stripos($empPositionTitle, 'Human Resources') !== false) {
                    $isHrOrFinance = true;
                }
            }

                $position_name = $employee->position->position_title ?? null;
                $position_access = $Resort->resort->Position_access ?? null;
                $Access_position = Position::where('status', 'Active')->where('id', $position_access)->first();
                if($Access_position != null)
                {
                    if ($isHrOrFinance || $Access_position->position_title == $position_name)
                    {
                            if (str_contains($currentRoute, 'workforceplan') && !$request->routeIs('resort.workforceplan.dashboard')) 
                            {
                                Log::info("Redirecting MGR to Workforceplan Dashboard");
                                return redirect()->route('resort.workforceplan.dashboard');
                            }
                            elseif (str_contains($currentRoute, 'recruitement') && !$request->routeIs('resort.recruitement.hrdashboard')) {
                                Log::info("Redirecting MGR to Recruitment HR Dashboard");
                                return redirect()->route('resort.recruitement.hrdashboard');
                            }
                            elseif (str_contains($currentRoute, 'timeandattendance') && !$request->routeIs('resort.timeandattendance.dashboard')) {
                                Log::info("Redirecting MGR to Recruitment HR Dashboard");
                                return redirect()->route('resort.timeandattendance.dashboard');
                            }
                            elseif (str_contains($currentRoute, 'leave') && !$request->routeIs('leave.dashboard')) {
                                Log::info("Redirecting MGR to Recruitment HR Dashboard");
                                return redirect()->route('leave.dashboard');
                            }
                            elseif (str_contains($currentRoute, 'accommodation') && !$request->routeIs('resort.accommodation.dashboard')) {
                                Log::info("Redirecting HOD to Accommodation HR Dashboard");
                                return redirect()->route('resort.accommodation.dashboard');
                            }
                            elseif (str_contains($currentRoute, 'payroll') && !$request->routeIs('payroll.dashboard')) {
                                Log::info("Redirecting HOD to Payroll HR Dashboard");
                                return redirect()->route('payroll.dashboard');
                            }
                            elseif (str_contains($currentRoute, 'PeopleRelation') && !$request->routeIs('PeopleRelation.Hrdashboard')) {
                                Log::info("Redirecting HOD to PeopleRelation HR Dashboard");
                                return redirect()->route('PeopleRelation.Hrdashboard');
                            }
                            elseif (str_contains($currentRoute, 'learning') && !$request->routeIs('learning.hr.dashboard')) {
                                Log::info("Redirecting HR to Learning HR Dashboard");
                                return redirect()->route('learning.hr.dashboard');
                            }
                            elseif (str_contains($currentRoute, 'GrievanceAndDisciplinery') && !$request->routeIs('GrievanceAndDisciplinery.Hrdashboard')) {
                                Log::info("Redirecting HR to GrievancevAnd Disciplinery HR Dashboard");
                                return redirect()->route('GrievanceAndDisciplinery.Hrdashboard');
                            }
                            elseif (str_contains($currentRoute, 'Survey') && !$request->routeIs('Survey.hr.dashboard')) {
                                Log::info("Redirecting HR to Survey HR Dashboard");
                                return redirect()->route('Survey.hr.dashboard');
                            }
                            elseif (str_contains($currentRoute, 'FileManagment') && !$request->routeIs('FileManagment.hr.dashboard')) 
                            {
                                Log::info("Redirecting HR to File Managment HR Dashboard");
                                return redirect()->route('FileManagment.hr.dashboard');
                            }
                            elseif (str_contains($currentRoute, 'incident') && !$request->routeIs('incident.hr.dashboard'))
                            {
                                Log::info("Redirecting Admin to Incident HR Dashboard");
                                return redirect()->route('incident.hr.dashboard');
                            }
                            elseif (str_contains($currentRoute, 'people') && !$request->routeIs('people.hr.dashboard'))
                            {
                                Log::info("Redirecting HR to People HR Dashboard");
                                return redirect()->route('people.hr.dashboard');
                            }
                            elseif(str_contains($currentRoute, 'master') && !$request->routeIs('resort.master.hr_dashboard')) {
                                Log::info("Redirecting HR to Master Dashboard");
                                return redirect()->route('resort.master.hr_dashboard');
                            }


                    }
                    else
                    {
                            if (str_contains($currentRoute, 'workforceplan') && !$request->routeIs('resort.workforceplan.hoddashboard')) 
                            {
                                Log::info("Redirecting HOD to Workforceplan HOD Dashboard");
                                return redirect()->route('resort.workforceplan.hoddashboard');
                            } elseif (str_contains($currentRoute, 'recruitement') && !$request->routeIs('resort.recruitement.hoddashboard')) {
                                Log::info("Redirecting HOD to Recruitment HOD Dashboard");
                                return redirect()->route('resort.recruitement.hoddashboard');
                            }
                            elseif (str_contains($currentRoute, 'timeandattendance') && !$request->routeIs('resort.timeandattendance.hoddashboard')) {
                                Log::info("Redirecting HOD to timeandattendance HOD Dashboard");
                                return redirect()->route('resort.timeandattendance.hoddashboard');
                            }
                            elseif (str_contains($currentRoute, 'leave') && !$request->routeIs('leave.hoddashboard')) {
                                Log::info("Redirecting HOD to leave HOD Dashboard");
                                return redirect()->route('leave.hoddashboard');
                            }
                            elseif (str_contains($currentRoute, 'accommodation') && !$request->routeIs('resort.accommodation.hoddashboard')) {
                                Log::info("Redirecting HOD to Accommodation HOD Dashboard");


                                // dd($currentRoute,str_contains($currentRoute, 'accommodation'),$request->routeIs('resort.accommodation.hoddashboard'));

                                return redirect()->route('resort.accommodation.hoddashboard');
                            }
                            // elseif (str_contains($currentRoute, 'learning') && !$request->routeIs('learning.hod.dashboard')) {
                            //     Log::info("Redirecting HOD to Learning HOD Dashboard");
                            //     return redirect()->route('learning.hod.dashboard');
                            // }
                            elseif (str_contains($currentRoute, 'incident') && !$request->routeIs('incident.hod.dashboard'))
                            {
                                Log::info("Redirecting Admin to Incident HOD Dashboard");
                                return redirect()->route('incident.hod.dashboard');
                            }
                            elseif (str_contains($currentRoute, 'learning') && !$request->routeIs('learning.manager.dashboard')) {
                                Log::info("Redirecting Manager to Learning Manager Dashboard");
                                return redirect()->route('learning.manager.dashboard');
                            }
                            elseif(str_contains($currentRoute, 'master') && !$request->routeIs('resort.master.hod_dashboard')) {
                                Log::info("Redirecting HOD to Master Dashboard");
                                return redirect()->route('resort.master.hod_dashboard');
                            }
                    }
                }
                elseif($isHrOrFinance)
                {
                    // HR/Finance users should get HR-level dashboards even when Position_access is not configured
                    if (str_contains($currentRoute, 'recruitement') && !$request->routeIs('resort.recruitement.hrdashboard')) {
                        Log::info("Redirecting HR/Finance to Recruitment HR Dashboard");
                        return redirect()->route('resort.recruitement.hrdashboard');
                    }
                    elseif (str_contains($currentRoute, 'workforceplan') && !$request->routeIs('resort.workforceplan.dashboard')) {
                        Log::info("Redirecting HR/Finance to Workforceplan Dashboard");
                        return redirect()->route('resort.workforceplan.dashboard');
                    }
                    elseif (str_contains($currentRoute, 'timeandattendance') && !$request->routeIs('resort.timeandattendance.dashboard')) {
                        Log::info("Redirecting HR/Finance to Time and Attendance Dashboard");
                        return redirect()->route('resort.timeandattendance.dashboard');
                    }
                    elseif (str_contains($currentRoute, 'leave') && !$request->routeIs('leave.dashboard')) {
                        Log::info("Redirecting HR/Finance to Leave Dashboard");
                        return redirect()->route('leave.dashboard');
                    }
                    elseif (str_contains($currentRoute, 'accommodation') && !$request->routeIs('resort.accommodation.dashboard')) {
                        Log::info("Redirecting HR/Finance to Accommodation Dashboard");
                        return redirect()->route('resort.accommodation.dashboard');
                    }
                    elseif (str_contains($currentRoute, 'payroll') && !$request->routeIs('payroll.dashboard')) {
                        Log::info("Redirecting HR/Finance to Payroll Dashboard");
                        return redirect()->route('payroll.dashboard');
                    }
                    elseif(str_contains($currentRoute, 'master') && !$request->routeIs('resort.master.hr_dashboard')) {
                        Log::info("Redirecting HR/Finance to Master Dashboard");
                        return redirect()->route('resort.master.hr_dashboard');
                    }
                }

            // if ( ($availableRank == 'HOD' || $availableRank == 'MGR' || $availableRank == 'EXCOM' ) && $Resort->is_master_admin == 0)
            // {

         
                           
            //     if (str_contains($currentRoute, 'workforceplan') && !$request->routeIs('resort.workforceplan.hoddashboard')) {
            //         Log::info("Redirecting HOD to Workforceplan HOD Dashboard");
            //         return redirect()->route('resort.workforceplan.hoddashboard');
            //     } elseif (str_contains($currentRoute, 'recruitement') && !$request->routeIs('resort.recruitement.hoddashboard')) {
            //         Log::info("Redirecting HOD to Recruitment HOD Dashboard");
            //         return redirect()->route('resort.recruitement.hoddashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'timeandattendance') && !$request->routeIs('resort.timeandattendance.hoddashboard')) {
            //         Log::info("Redirecting HOD to timeandattendance HOD Dashboard");
            //         return redirect()->route('resort.timeandattendance.hoddashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'leave') && !$request->routeIs('leave.hoddashboard')) {
            //         Log::info("Redirecting HOD to leave HOD Dashboard");
            //         return redirect()->route('leave.hoddashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'accommodation') && !$request->routeIs('resort.accommodation.hoddashboard')) {
            //         Log::info("Redirecting HOD to Accommodation HOD Dashboard");


            //         // dd($currentRoute,str_contains($currentRoute, 'accommodation'),$request->routeIs('resort.accommodation.hoddashboard'));

            //         return redirect()->route('resort.accommodation.hoddashboard');
            //     }
            //     // elseif (str_contains($currentRoute, 'learning') && !$request->routeIs('learning.hod.dashboard')) {
            //     //     Log::info("Redirecting HOD to Learning HOD Dashboard");
            //     //     return redirect()->route('learning.hod.dashboard');
            //     // }
            //     elseif (str_contains($currentRoute, 'incident') && !$request->routeIs('incident.hod.dashboard'))
            //     {
            //         Log::info("Redirecting Admin to Incident HOD Dashboard");
            //         return redirect()->route('incident.hod.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'learning') && !$request->routeIs('learning.manager.dashboard')) {
            //         Log::info("Redirecting Manager to Learning Manager Dashboard");
            //         return redirect()->route('learning.manager.dashboard');
            //     }
            //     elseif(str_contains($currentRoute, 'master') && !$request->routeIs('resort.master.hod_dashboard')) {
            //         Log::info("Redirecting HOD to Master Dashboard");
            //         return redirect()->route('resort.master.hod_dashboard');
            //     }


            }

            // dd($availableRank,$Resort->is_master_admin);
            // MGR Redirection Logic
            // if ($availableRank == 'HR' && $Resort->is_master_admin == 0) {
            //     if (str_contains($currentRoute, 'workforceplan') && !$request->routeIs('resort.workforceplan.dashboard')) {
            //         Log::info("Redirecting MGR to Workforceplan Dashboard");
            //         return redirect()->route('resort.workforceplan.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'recruitement') && !$request->routeIs('resort.recruitement.hrdashboard')) {
            //         Log::info("Redirecting MGR to Recruitment HR Dashboard");
            //         return redirect()->route('resort.recruitement.hrdashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'timeandattendance') && !$request->routeIs('resort.timeandattendance.dashboard')) {
            //         Log::info("Redirecting MGR to Recruitment HR Dashboard");
            //         return redirect()->route('resort.timeandattendance.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'leave') && !$request->routeIs('leave.dashboard')) {
            //         Log::info("Redirecting MGR to Recruitment HR Dashboard");
            //         return redirect()->route('leave.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'accommodation') && !$request->routeIs('resort.accommodation.dashboard')) {
            //         Log::info("Redirecting HOD to Accommodation HR Dashboard");
            //         return redirect()->route('resort.accommodation.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'payroll') && !$request->routeIs('payroll.dashboard')) {
            //         Log::info("Redirecting HOD to Payroll HR Dashboard");
            //         return redirect()->route('payroll.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'PeopleRelation') && !$request->routeIs('PeopleRelation.Hrdashboard')) {
            //         Log::info("Redirecting HOD to PeopleRelation HR Dashboard");
            //         return redirect()->route('PeopleRelation.Hrdashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'learning') && !$request->routeIs('learning.hr.dashboard')) {
            //         Log::info("Redirecting HR to Learning HR Dashboard");
            //         return redirect()->route('learning.hr.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'GrievanceAndDisciplinery') && !$request->routeIs('GrievanceAndDisciplinery.Hrdashboard')) {
            //         Log::info("Redirecting HR to GrievancevAnd Disciplinery HR Dashboard");
            //         return redirect()->route('GrievanceAndDisciplinery.Hrdashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'Survey') && !$request->routeIs('Survey.hr.dashboard')) {
            //         Log::info("Redirecting HR to Survey HR Dashboard");
            //         return redirect()->route('Survey.hr.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'FileManagment') && !$request->routeIs('FileManagment.hr.dashboard')) 
            //     {
            //         Log::info("Redirecting HR to File Managment HR Dashboard");
            //         return redirect()->route('FileManagment.hr.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'incident') && !$request->routeIs('incident.hr.dashboard'))
            //     {
            //         Log::info("Redirecting Admin to Incident HR Dashboard");
            //         return redirect()->route('incident.hr.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'people') && !$request->routeIs('people.hr.dashboard'))
            //     {
            //         Log::info("Redirecting HR to People HR Dashboard");
            //         return redirect()->route('people.hr.dashboard');
            //     }
            //      elseif(str_contains($currentRoute, 'master') && !$request->routeIs('resort.master.hr_dashboard')) {
            //         Log::info("Redirecting HR to Master Dashboard");
            //         return redirect()->route('resort.master.hr_dashboard');
            //     }

            // }

            // // GM Redirection Logic
            // if ($availableRank == 'GM' && $Resort->is_master_admin == 0) 
            // {
            //     if (str_contains($currentRoute, 'workforceplan') && !$request->routeIs('resort.workforceplan.dashboard')) {
            //         Log::info("Redirecting GM to Workforceplan Dashboard");
            //         return redirect()->route('resort.workforceplan.dashboard');
            //     } elseif (str_contains($currentRoute, 'recruitment') && !$request->routeIs('resort.recruitement.hrdashboard')) {
            //         Log::info("Redirecting GM to Recruitment HR Dashboard");
            //         return redirect()->route('resort.recruitement.hrdashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'timeandattendance') && !$request->routeIs('resort.timeandattendance.dashboard')) {
            //         Log::info("Redirecting GM to Recruitment HR Dashboard");
            //         return redirect()->route('resort.timeandattendance.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'leave') && !$request->routeIs('leave.dashboard')) {
            //         Log::info("Redirecting GM to Leave GM Dashboard");
            //         return redirect()->route('leave.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'PeopleRelation') && !$request->routeIs('PeopleRelation.Hrdashboard')) {
            //         Log::info("Redirecting HOD to PeopleRelation HR Dashboard");
            //         return redirect()->route('PeopleRelation.Hrdashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'learning') && !$request->routeIs('learning.manager.dashboard')) {
            //         Log::info("Redirecting Manager to Learning Manager Dashboard");
            //         return redirect()->route('learning.manager.dashboard');
            //     }
            //     elseif (str_contains($currentRoute, 'incident') && !$request->routeIs('incident.hr.dashboard'))
            //     {
            //         Log::info("Redirecting Admin to Incident HR Dashboard");
            //         return redirect()->route('incident.hr.dashboard');
            //     }
            //     elseif(str_contains($currentRoute, 'master') && !$request->routeIs('resort.master.gm_dashboard')) {
            //         Log::info("Redirecting GM to Master Dashboard");
            //         return redirect()->route('resort.master.gm_dashboard');
            //     }
            // }

            // if ($availableRank == 'EXCOM' && $Resort->is_master_admin == 0) {
            //     if (str_contains($currentRoute, 'learning') && !$request->routeIs('learning.manager.dashboard')) {
            //         Log::info("Redirecting Manager to Learning Manager Dashboard");
            //         return redirect()->route('learning.manager.dashboard');
            //     }
            // }
        // }

        // Proceed with the request if no redirection is needed
        return $next($request);
    }
}
<?php

namespace App\Http\Controllers;

use App\AcctReq;
use App\Comment;
use App\Helpers\AppMailer;
use App\Helpers\Logger;
use App\Helpers\TradeSync;
use App\Investments;
use App\MainAccount;
use App\Referral;
use App\Ticket;
use App\Transaction;
use App\User;
use App\UserInv;
use App\Utility;
use App\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Translation\Exception\InvalidResourceException;

class AdminController extends Controller
{
    private function getId($id)
    {
        return $id/(8009 * 8009);
    }

    private function getLogger()
    {
        return new Logger();
    }

    //User View.
    public function Dashboard()
    {
        return view('Admin.dashboard',['title' => 'Dashboard', 'users' => User::where(['role_id' => 3])->orderByDesc('created_at')->get()]);
    }
    public function UserView($id)
    {
        $u = User::find(decrypt($id));
        return view('Admin.user_view',['title' => 'User View','user' =>  $u]);
    }
    public function UserEdit(Request $request, $id)
    {
        $this->validate($request,[
            'password' => 'required'
        ]);
        try{
            $u = User::find($this->getId($id));
            $old = $u;
            if(Hash::check($request->password, Auth::user()->password)) {
                $u->acc_id = $request->acc_id != null ? $request->acc_id : $u->acc_id;
                if ($u->class_id == $request->class_id) {
                    $u->class_id = $request->class_id;
                } else {
                    $u->class_id = $request->class_id;
                    $u->r_mark = 0;
                }
                //$u->class_id = $request->class_id == null ? 0 : $request->class_id;
                $u->is_active = (bool)$request->is_active;
                $u->activated = (bool)$request->activated;
                if ($u->activated) {
                   // dd('am here');
                    if (Referral::FindByUserAndReferrer($u->id, $u->referrer) == null) {
                        User::MultiGenRef($u->id);
                    }
                    $u->start_date = Carbon::now();
                }
                $u->save();
                Session::flash('success', 'User Profile Updated Succuessfully');
                Log::info('User Profile Updated', ['old_user' => $old, 'new_user' => $u]);
            }
            else{
                Session::flash('error','Incorrect Password');
            }
        }
        catch(\Exception $ex)
        {
            Session::flash('error','Unable to Update User Profile');
            $this->getLogger()->LogError('Admin: User Profile Update','Unable to Update User Profile',$ex,['old_user' => $old,'new_user' => $u]);
        }
        return redirect()->back();
    }
    public function UserAction($id,$aid)
    {
        $op = decrypt($aid);
        $user = User::find(decrypt($id));
        if($op == 1)
        {

        }
        if($op == 2)
        {
            //dd($user->trans()->orderByDesc('created_at'));
            return view('Admin.trans',['title' => 'Transactions','trans' => $user->trans()->orderByDesc('created_at')->get()]);
        }
        if($op == 3)
        {
            return view('Admin.withdrawal',['title'=>'Withdrawal Request','with' => $user->withd()->orderByDesc('created_at')->get()]);
        }

        if($op == 4)
        {
            try{
                $t = new TradeSync();
                $t->Sync();
            }
            catch (\Exception $exception){
                $this->getLogger()->LogError('Error Occured when Syncing Trade', $exception,null);
            }
            return view('Admin.tradings',['title' => 'Tradings','trades' => $user->Trade()->orderByDesc('created_at')->get()]);

        }

        if($op == 5)
        {
            return view('Admin.account',['title' => 'Accounts','main' => $user->bal()->orderBy('created_at','DESC')->get()]);
        }
    }

    //Admin
    public function Admin()
    {
        return view('Admin.admin',['title'=>'Admin','admin' =>  User::where('role_id','=' ,2)->get()]);
    }
    public function AdminPost(Request $request)
    {

        $this->validate($request, [
            'fullname' => 'required',
            'email' => 'required|unique:users,email',
            'password' => 'required',
            'conf_password' => 'required|same:password'
        ],['conf_password.same' => 'Passwords Mismatch']);
        //dd($request->all());
        $user = new User();
        $user->fullname = $request->fullname;;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
       // $user->phone_number = 'empty';
        $user->role_id = 2;
        $user->is_active = true;
        try{
            $user->save();
            Session::flash('success','Administrator Added Successfully');
            Log::info('Add Admin: Admin has been added',['Admin' => $user, 'by' => Auth::user()]);
            return redirect()->back();
        }
        catch(\Exception $ex)
        {
            $this->getLogger()->LogError(' Admin Registration Error: Unable to register Admin',$ex,['User' => $user]);
            Session::flash('error','Oops, An Error Occurred and We Could Not Complete the request, Please Try Again');
            return redirect()->back();
        }
    }
    public function AdminDelete($id)
    {
        $id = decrypt($id);
        if(Auth::user()->role_id == 1)
        {

            try{
                $adm = User::where(['role_id' => 2, 'id' => $id])->first();
                $adm->is_active = false;
                $adm->save();
                Session::flash('success','Operation was successfully carried out');
                Log::info('Delete Admin: Admin Successfully Deleted. ', ['admin' => $adm,'Super_admin' => Auth::id()]);
            }
            catch(\Exception $ex)
            {
                $this->Logger()->LogError('Delete Admin: Unable to delete admin',$ex,['admin' => $adm]);
                Session::flash('error','Unable to carry out operation. Please check log files');
            }
        }
        else{
            Session::flash('error','You don\'t have the necessary permission to carry out this operation.');
            Log::error('Tyring to Delete an admin with out proper access', ['Admin' => $id, 'by' => Auth::id()]);
        }
        return redirect()->back();
    }

    //Mail
    public function Mail()
    {
        return view('Admin.mail',['title' => 'Mail','user' => null]);
    }
    public function MailAll()
    {
        $user = User::pluck('email');
        //dd($user);
        $a = [];
        $s = null;
        foreach ($user as $m)
        {
            if($s == null)
            {
                $s = $m;
            }
            else{
                $s = $s . ',' . $m;
            }
        }
        //dd([$s][0]);
        $user = $s;
        return view('Admin.mail',['title' => 'Mail','user' => $user]);
    }
    public function MailSingle($email)
    {
        $user = User::where('email',decrypt($email))->first();
        return view('Admin.mail',['title' => 'Mail','user' => $user->email]);
    }
    public function MailSend(Request $request)
    {
        $this->validate($request,[
            'to' => 'required',
            'msg' => ' required'
        ]);

        Log::info('startt');

        try{
            $email = $request->to[0];
            $msg = $request->msg;
            $sub = $request->subject;
            var_dump(10);
            $mail = new AppMailer();
            if($mail->send($email,$msg,$sub))
                Session::flash('success','Mail Sent. ');
            else
                Session::flash('error','Mail Not Sent.');

        }
        catch(\Exception $ex){
            Session::flash('error','an Error Occured, Please Try Again');
            $this->Logger()->LogError('Unable To send mail',$ex,['Emails' => $email,'message' => $msg,'subject' => $sub]);
        }
        return redirect()->back()->withInput();
    }

    //Trading
    public function Trade()
    {
        try{
            $t = new TradeSync();
            $t->Sync();
        }
        catch (\Exception $exception){
            $this->getLogger()->LogError('Error Occured when Syncing Trade', $exception,null);
        }
        return view('Admin.tradings',['title' => 'Tradings','trades' => Investments::orderBy('created_at','DESC')->get()]);
    }
    public function TradeAction($id, $a_id)
    {

        try{
            $a = decrypt($a_id);
            $i = Investments::FindbyInvD(decrypt($id));
            $old = $i;
            if($a == 1)
            {
                $i->ts_id = 6;
                $i->start_date = Carbon::now();
                $i->save();
                $t = new Transaction();
                $t->t_id = Transaction::GenerateTID();
                $t->user_id = $i->user_id;
                $t->amount = $i->amount;
                $t->descn = 'Trade-' . $i->inv_id;
                $t->tn_id = 1;
                $t->t_type = 2;
                $t->ts_id = 1;
                $t->save();

                try {
                    if(!UserInv::findById($i->user_id))
                    {
                        Investments::ReferralBonusTeacher($i, $i->amount);
                        $m = UserInv::dataS($i->user_id, true);
                    }
                }
                catch(\Exception $ex) {
                    $this->getLogger()->LogError('an Error Occurred When Giving Inv Bonus',$ex,['inv' => $i]);
                }
                Log::info('Transaction saved',['Trans' => $t]);
                //Put In Transaction
            }

            if($a == 2)
            {
                $i->ts_id = 5;
                $i->save();

            }
            $i->save();
            Log::info('Operation completed successfully',['old' =>$old,'i' => $i,'action' => $a,'by' =>Auth::id()]);
            Session::flash('success','Operation Completed Successfully');
        }
        catch(\Exception $ex){
            $this->getLogger()->LogError('Trade Action: An Error Occurred',$ex,['old' =>$old,'i' => $i,'action' => $a,'by' =>Auth::id()]);
            Session::flash('error','Oops An Error Occured, Please Try Again');
        }
        return redirect()->back();

    }

    //Transaction
    public function Transaction()
    {
        return view('Admin.trans',['title' => 'Transactions','trans' => Transaction::orderBy('created_at', 'DESC')->get()]);
    }


    //Account
    public function Account()
    {
        return view('Admin.account',['title' => 'Accounts','main' => MainAccount::orderBy('created_at','DESC')->get()]);
    }
    public function AccountUpdate($id)
    {
        return view('Admin.account_update',['title' => 'Update Account','Main' => MainAccount::find(decrypt($id))]);
    }
    public function AccountUpdatePost(Request $request, $id)
    {
        $this->validate($request,[
           'amount' => 'required|numeric',
           'acct_type' => 'required',
            'password' => 'required'
        ],[
            'acct_type.required' => 'Please Select An Account Type To Update.'
        ]);

        $main = MainAccount::find(decrypt($id));
        if($request->acct_type == 1)
        {
            $main->trade_bal = $main->trade_bal + $request->amount;
        }

        if($request->acct_type == 2)
        {
            $main->ref_bal = $main->ref_bal + $request->amount;
        }

        try{
           if(Hash::check($request->password, Auth::user()->password))
           {
               $main->save();
               Session::flash('success','Account Updated Successfully');
               return redirect()->action('AdminController@Account');
           }
           else{
               Session::flash('error','Incorrect Password. Please Try Again');
               return redirect()->back();
           }
        }
        catch(\Exception $ex)
        {
            $this->getLogger()->LogError('An error Occurred When Updating Account', $ex,['Main'=>$main,'req' => $request->all()]);
            Session::flash('error','An Error Occurred. Please Try Again');
            return redirect()->back();
        }

    }

    public function Withdrawal()
    {
        return view('Admin.withdrawal',['title'=>'Withdrawal Request','with' => Withdrawal::orderBy('created_at','DESC')->get()]);
    }
    public function WithAction($id,$a_id)
    {
        try{
            $a = decrypt($a_id);
            $i = Withdrawal::find(decrypt($id));
            $old = $i;
            if(decrypt($a_id) == 1)
            {
                //dd($a, $i);
                $i->ts_id = 1;
                $i->save();
                $t = new Transaction();
                $t->t_id = Transaction::GenerateTID();
                $t->user_id = $i->user_id;
                $t->amount = $i->trans->amount;
                $t->descn = 'Trade-' . $i->w_id;
                $t->tn_id = 2;
                $t->t_type = 2;
                $t->ts_id = 1;
                $t->save();
                Log::info('Transaction saved',['Trans' => $t]);
                //Put In Transaction
            }

            if($a == 2)
            {
                $i->ts_id = 5;
                $i->save();
            }
            $i->save();
            Log::info('Operation completed successfully',['old' =>$old,'i' => $i,'action' => $a,'by' =>Auth::id()]);
            Session::flash('success','Operation Completed Successfully');
        }
        catch(\Exception $ex){
            $this->getLogger()->LogError('Trade Action: An Error Occurred',$ex,['old' =>$old,'i' => $i,'action' => $a,'by' =>Auth::id()]);
            Session::flash('error','Oops An Error Occured, Please Try Again');
        }

        return redirect()->back();
    }


    //Referrals
    public function Referrals()
    {
        return view('Admin.referrals',['title' => 'Referrals','ref' => Referral::orderByDesc('created_at')->get()]);
    }


    //Tickets
    public function Ticket()
    {
        return view('Admin.ticket',['title'=>'Tickets','tick' => Ticket::orderByDesc('created_at')->get()]);
    }
    public function TicketComment($id)
    {
        $ticket = Ticket::find(decrypt($id));
        return view('Admin.ticket_comment',['title'=>'Tickets','ticket' => $ticket,'comments' => $ticket->comments()->orderBy('created_at','ASC')->get()]);
    }
    public function TicketCommentPost(Request $request)
    {
        $this->validate($request,[
            'message' => 'required'
        ]);
        //dd($request->all());
        try{
            $c = new Comment();
            $c->user_id = Auth::id();
            $c->ticket_id = $request->ticket_id;
            $c->comment = $request->message;
            $c->save();
            Session::flash('success','Message Successfully saved.');
            return redirect()->back();
        }
        catch(\Exception $ex)
        {
            //dd($ex);
            $this->getLogger()->LogError('An Error Occured When Sending Message',$ex,['comment' =>  $c]);
            Session::flash('error','An Error Occured, Please Try Again');
            return redirect()->back();
        }
    }


    //Request
    public function Request()
    {
        return view('Admin.acct_req',['title' => 'Requests','req' => AcctReq::orderByDesc('created_at')->get()]);
    }
    public function ReqRes($id)
    {
        //dd(decrypt($id));
        try{
            $a = AcctReq::find(decrypt($id));
            $a->resolved = true;
            $a->save();
            Session::flash('success','Request Resolved Successfully');
            Log::info('request resolved', ['by' => Auth::id(), 'Req' => $a]);
        }
        catch(\Exception $ex)
        {
            Session::flash('error','Unable to resolve request.');
            $this->getLogger()->LogError('Error Occured When trying to resolve request.', $ex, ['by' => Auth::id(), 'Req' => $a]);

        }

        return redirect()->back();
    }

    //Utility
    public function Util()
    {
        return view('Admin.utils',['title' => 'Utility','util' => Utility::all()]);
    }
    public function UtilPost(Request $request)
    {
        //dd($request->all());
        try{
            $u = Utility::find($request->id);
            $u->value = $request->new_value;
            $u->save();
            Session::flash('success','Utilities Updated Successfully');
        }
        catch(\Exception $ex)
        {
            $this->getLogger()->LogError('An error Occurred When updating utility',$ex,['u'=>$u]);
            Session::flash('error','An Error Occurred When Updating Utilities');
        }
        return redirect()->back();

    }

}
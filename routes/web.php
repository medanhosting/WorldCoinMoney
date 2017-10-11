<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


//Utility
Route::get('/clear', function()
{
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('make:model test');
});
Route::get('/','UtilityController@Home')->name('home');
Route::get('/about','UtilityController@About')->name('about');
Route::get('/contact','UtilityController@Contact')->name('contact');


//Account
Route::get('/register','AccountController@Register')->name('register');
Route::get('/register/referral/{r_link}','AccountController@RegisterRef')->name('register_referrals');
Route::post('/register','AccountController@RegisterPost')->name('register_post');
Route::get('/login','AccountController@Login')->name('login');
Route::post('/login','AccountController@LoginPost')->name('login_post');
Route::get('/logout','AccountController@Logout')->name('logout');
Route::get('/forgot-password','AccountController@ForgotPassword')->name('forgot_password');
Route::post('/forgot-password/post','AccountController@ForgotPasswordPost')->name('forgot_password_post');
Route::get('/reset-password/{token}','AccountController@ResetLink')->name('reset_link');
Route::post('/reset-password/{token}','AccountController@RecoverPassword')->name('change_password');



Route::group(['prefix' => '/user/','middleware' => ['auth','AuthUserCheck','UserActivated']], function ()
{
    Route::get('dashboard','UserController@Dashboard')->name('user_dashboard');
    Route::get('profile','UserController@Profile')->name('user_profile');
    Route::get('accounts','UserController@Account')->name('user_account');
    Route::get('invest','UserController@Invest')->name('user_invest');
    Route::get('withdrawals','UserController@Withdrawals')->name('user_withdrawals');
    Route::get('transactions','UserController@Transactions')->name('user_transaction');
    Route::get('referrals','UserController@Referrals')->name('user_referrals');
    Route::get('referrals/{id}','UserController@RefOthers')->name('user_referrals_id');
    Route::get('support','UserController@Support')->name('user_support');
    Route::get('withdrawals/{id}','UserController@WithPost')->name('user_with_trans');
    Route::get('tickets','TicketController@UserTickets')->name('user_tickets');
    Route::get('tickets/{t_id}','TicketController@Show')->name('user_ticket_show');
    Route::get('ticket/create','TicketController@Create')->name('user_ticket_create');
    Route::post('ticket/create','TicketController@Store')->name('user_ticket_post');
    Route::post('ticket/comment','TicketController@PostComment')->name('user_comment_post');
    Route::get('ticket/close/{t_id}','TicketController@CloseTicket')->name('user_ticket_close');

    //Posts
    Route::post('profile/edit','UserController@ProfileEdit')->name('user_profile_post');
    Route::post('profile/password/change','UserController@ProfileEditPassword')->name('user_password_edit');
    Route::post('invest/add','UserController@InvestPost')->name('user_invest_post');
    Route::post('profile/account/upgrade','UserController@AccountUpgrade')->name('user_acct_upgrade');
});



Route::group(['prefix' => '/admin/','middleware' => ['auth','AuthAdminCheck']],function ()
{
    //User
    Route::get('dashboard','AdminController@Dashboard')->name('admin_dashboard');
    Route::get('user/view/{id}','Admincontroller@UserView')->name('admin_user_view');
    Route::post('user/profile/edit/{id}','AdminController@UserEdit')->name('admin_user_edit');
    Route::get('user/action/{id}/{aid}','AdminController@UserAction')->name('admin_user_action');


    //Admin
    Route::get('admin','AdminController@Admin')->name('admin_admin');
    Route::get('admin/delete/{id}','AdminController@AdminDelete')->name('admin_delete');
    Route::post('admin/post','AdminController@AdminPost')->name('admin_post');

    //Trade
    Route::get('trade','AdminController@Trade')->name('admin_trade');
    Route::get('trade/action/{id}/{a_id}','AdminController@TradeAction')->name('admin_trade_action');

    //Mails
    Route::get('mail','AdminController@Mail')->name('mail');
    Route::get('mail/send/{email}','AdminController@MailSingle')->name('mail.single');
    Route::get('mail/all','AdminController@MailAll')->name('mail.all');
    Route::post('mail/send','AdminController@MailSend')->name('mail_send');


    //Utilities
    Route::get('utility','AdminController@Util')->name('admin_utils');
    Route::post('util/post/update','AdminController@UtilPost')->name('admin_update_util');


    //Transaction
    Route::get('transaction','AdminController@Transaction')->name('admin_trans');


    //Account
    Route::get('account','AdminController@Account')->name('admin_account');
    Route::get('account/update/{id}','AdminController@AccountUpdate')->name('admin_acc_up');
    Route::post('account/update/{id}/update','AdminController@AccountUpdatePost')->name('admin_acc_up_post');

    //Withdrawals
    Route::get('withdrawal','AdminController@Withdrawal')->name('admin_with');
    Route::get('with/action/{id}/{a_id}','AdminController@WithAction')->name('admin_with_action');

    //Referrals
    Route::get('referrals','AdminController@Referrals')->name('admin_referrals');

    //Tickets
    Route::get('ticket','AdminController@Ticket')->name('admin_ticket');
    Route::get('ticket/{id}','AdminController@TicketComment')->name('admin_ticket_comment');
    Route::post('ticket/comment','AdminController@TicketCommentPost')->name('admin_ticket_comment_post');


    //Request
    Route::get('request','AdminController@Request')->name('admin_req');
    Route::get('request/resolved/{id}','AdminController@ReqRes')->name('admin_req_post');



});


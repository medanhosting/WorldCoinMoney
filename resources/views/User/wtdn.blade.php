@extends('master')
@section('body')
    <div class="container " style="margin-top:50px;margin-bottom:100px;">
        <div class="col-lg-12">
            @include('Partials._message')
        </div>
            @if(isset($i) && $i != null)
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <h4 style="margin-top:5px;text-align:center; margin-bottom:10px;font-size:29px;">WHAT TO DO NEXT</h4>
                        <hr/>
                        <br/>
                        Thank you for applying to trade with us. Kindly Follow the below step to pay for your trading.
                        <br/><br/><br/>
                        <ul class="list-group">
                            <li class="list-group-item">Kindly Check Your Email For Your Chosen Payment Type Details either <i class="fa fa-bitcoin"></i> <strong>Bitcoin or</strong> <i class="fa fa-money"></i> <strong>Bank Transfer</strong>. Check Below For Your Trading Details:</li>
                            <?php $pay = \App\Utility::all();?>
                            <li class="list-group-item">{{"Trading Amount: $i->amount"}}</li>
                            <li class="list-group-item">{{"Trading Duration: $i->duration"}}</li>
                            <li class="list-group-item"><h4>AFTER PAYMENT: </h4></li>
                            <li class="list-group-item">For Bitcoin <i class="fa fa-bitcoin"></i>: Please Click Here To Upload Your POP and HASH ID. <a href="{{route('user_EOP',['token' =>encrypt(1)])}}" class="btn btn-primary" data-toggle="tooltip" title="click me to upload"><i class="fa fa-upload"></i> Upload </a> </li>
                            <li class="list-group-item">For Bank Transfer <i class="fa fa-money"></i>: Please Click Here To Upload Your Evidence Of Payment(Deposit Slip, Bank Transaction Alert e.t.c).  <a href="{{route('user_EOP',['token' =>encrypt(2)])}}" class="btn btn-info" data-toggle="tooltip" title="click me to upload"><i class="fa fa-upload"></i> Upload </a> </li>
                            <li class="list-group-item">For More Information: <a href="#Twakto" class="btn btn-success" data-toggle="tooltip" title="click me to chat"> Click here to <i class="fa fa-comment"></i> </a> or send us a mail at info@worldcoinmoney.com. Thank You. </li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
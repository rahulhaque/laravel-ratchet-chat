@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Chat Room <span id="total_client" class="float-right"></span></div>
                    <div class="card-body">
                        <div id="chat_output" class="pre-scrollable" style="height: 600px">
                            @foreach($messages as $message)
                                @if($message->user_id == auth()->id())
                                    <span class="text-success"><b>{{$message->user_id}}. {{$message->name}}
                                            :</b> {{$message->message}} <span
                                                class="text-warning float-right">{{date('Y-m-d h:i a', strtotime($message->created_at))}}</span></span>
                                    <br><br>
                                @else
                                    <span class="text-info"><b>{{$message->user_id}}. {{$message->name}}
                                            :</b> {{$message->message}} <span
                                                class="text-warning float-right">{{date('Y-m-d h:i a', strtotime($message->created_at))}}</span></span>
                                    <br><br>
                                @endif
                            @endforeach
                        </div>
                        <input id="chat_input" class="form-control" placeholder="Write Message and Press Enter"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{asset('dist/jquery.js')}}"></script>
    <script type="text/javascript">

        $('document').ready(function () {
            $("#chat_output").animate({scrollTop: $('#chat_output').prop("scrollHeight")}, 1000); // Scroll the chat output div
        });

        // Websocket
        let ws = new WebSocket("ws://localhost:8090");
        ws.onopen = function (e) {
            // Connect to websocket
            console.log('Connected to websocket');
            ws.send(
                JSON.stringify({
                    'type': 'socket',
                    'user_id': '{{auth()->id()}}'
                })
            );

            // Bind onkeyup event after connection
            $('#chat_input').on('keyup', function (e) {
                if (e.keyCode === 13 && !e.shiftKey) {
                    let chat_msg = $(this).val();
                    ws.send(
                        JSON.stringify({
                            'type': 'chat',
                            'user_id': '{{auth()->id()}}',
                            'user_name': '{{auth()->user()->name}}',
                            'chat_msg': chat_msg
                        })
                    );
                    $(this).val('');
                    console.log('{{auth()->id()}} sent ' + chat_msg);
                }
            });
        };
        ws.onerror = function (e) {
            // Error handling
            console.log(e);
            alert('Check if WebSocket server is running!');
        };
        ws.onmessage = function (e) {
            let json = JSON.parse(e.data);
            switch (json.type) {
                case 'chat':
                    $('#chat_output').append(json.msg); // Append the new message received
                    $("#chat_output").animate({scrollTop: $('#chat_output').prop("scrollHeight")}, 1000); // Scroll the chat output div
                    console.log("Received " + json.msg);
                    break;

                case 'socket':
                    $('#total_client').html(json.msg);
                    console.log("Received " + json.msg);
                    break;
            }
        };
    </script>
@endsection

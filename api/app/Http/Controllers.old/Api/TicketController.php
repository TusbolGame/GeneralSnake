<?php

namespace App\Http\Controllers\Api;

use App\Constants\TicketStatus;
use App\Constants\UserType;
use App\Entities\Profile;
use App\Entities\Ticket;
use App\Entities\TicketDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function getTicket(Request $request)
    {
        $user = Auth::user();
        $ticket = Ticket::where('user_id', $user->id)->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $ticket
        ]);
    }

    public function getDetail(Request $request)
    {
        $input = $request->input();

        $id = $input['ticket_id'];

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'error' => 'Ticket Not Found'
            ]);
        }

        $detail = TicketDetail::where('ticket_id', $id)->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $detail,
            'ticket' => $ticket
        ]);
    }

    public function postTicket(Request $request)
    {
        $input = $request->input();

        $user = Auth::user();

        if (!isset($input['title']) || $input['title'] == '') {
            return response()->json([
                'success' => false,
                'error' => 'You have to input title.'
            ]);
        }
        if (!isset($input['message']) || $input['message'] == '') {
            return response()->json([
                'success' => false,
                'error' => 'You have to input message.'
            ]);
        }
        $ticket = new Ticket();
        $ticket->user_id = $user->id;
        $ticket->title = $input['title'];
        $ticket->status = TicketStatus::OPEN;
        $ticket->last_user = $user->name;
        $ticket->save();

        $detail = new TicketDetail();
        $detail->ticket_id = $ticket->id;
        $detail->message = $input['message'];
        $detail->username = $user->name;
        if (isset($input['attach_url'])) {
            $detail->attach_url = $input['attach_url'];
            $detail->attach_name = $input['attach_name'];
        }
        $detail->user_type = UserType::USER;
        $detail->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function replyTicket(Request $request)
    {
        $input = $request->input();

        $user = Auth::user();

        if (!isset($input['message']) || $input['message'] == '') {
            return response()->json([
                'success' => false,
                'error' => 'You have to input message.'
            ]);
        }

        $ticket_id = isset($input['id']) ? $input['id'] : 0;
        $ticket = Ticket::find($ticket_id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'error' => 'Ticket Not Found.'
            ]);
        }
        $ticket->last_user = ($user->role == 'admin') ? 'Admin' : $user->name;
        if ($user->role == 'admin') {
            $ticket->status = TicketStatus::PENDING;
        }
        $ticket->updated_at = Carbon::now();
        $ticket->save();

        $detail = new TicketDetail();
        $detail->ticket_id = $ticket->id;
        $detail->message = $input['message'];
        $detail->username = ($user->role == 'admin') ? 'Admin' : $user->name;
        if (isset($input['attach_url'])) {
            $detail->attach_url = $input['attach_url'];
            $detail->attach_name = $input['attach_name'];
        }
        $detail->user_type = ($user->role == 'admin') ? UserType::ADMIN : UserType::USER;
        $detail->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function getAllTicket(Request $request) {
        $input = $request->input();
        $ticket = Ticket::query();

        if (isset($input['open']) && $input['open']) {
            $ticket->where('status', '<>', TicketStatus::CLOSED);
        }
        if (isset($input['close']) && $input['close']) {
            $ticket->where('status', TicketStatus::CLOSED);
        }

        $data = $ticket->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function closeTicket(Request $request) {
        $input = $request->input();

        $id = $input['id'];
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'error' => 'Ticket Not Found.'
            ]);
        }

        $ticket->status = TicketStatus::CLOSED;
        $ticket->updated_at = Carbon::now();
        $ticket->save();

        return response()->json([
            'success' => true
        ]);

    }
}

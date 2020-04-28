<?php

namespace App\Http\Controllers\Admin\TicketUser;

use App\Http\Controllers\AdminUsersBaseController;

use App\Models\EventGuestListModel;
use Illuminate\Http\Request;
use AppHelper;
use App\Models\Event;
use App\Models\TransactionModel;
use Anam\PhantomMagick\Converter;
use Illuminate\Support\Facades\DB;
use Laracasts\Flash\Flash;

class TicketController extends AdminUsersBaseController
{
    /**
     * @var view location path
     */
    protected $view_path = 'admin.ticket_user.tickets';

    /**
     * @var translation array path
     */
    protected $trans_path;

    public function __construct()
    {
        parent:: __construct();

        $this->base_route = 'admin.ticket-user';
        $this->middleware('switch-lang');

        // Generate Translation Dir path
        $this->trans_path = AppHelper::getTransPathFromViewPath($this->view_path);
    }

    public function index()
    {
        $data = [];
        $data['page_title'] = trans($this->trans_path . 'general.page.event-list');

        $data['transaction'] = TransactionModel::ByStatus()->whereEmail(auth()->user()->email)->get();
        $event_ids =[];
        $data['ticket_bought'] = [];
        foreach ($data['transaction'] as $tran) {
            $getEvent = Event::find($tran->event_id);
            array_push($event_ids, $getEvent->event_code);
            $getTicketNumber = count($tran->ticketOrder);
            if (isset($data['ticket_bought'][$getEvent->event_code]))
                $data['ticket_bought'][$getEvent->event_code] = $data['ticket_bought'][$getEvent->event_code] + count($tran->ticketOrder);
            else
                $data['ticket_bought'] += [$getEvent->event_code => $getTicketNumber];
        }

        $data['event_guest'] = EventGuestListModel::select(
            \DB::raw('id, event_id, ticket_section_id, 
            SUM(Case When can_delete = 1 Then ticket_request_quantity Else 0 End) as total_ticket, 
            GROUP_CONCAT(if(can_delete, guest_code, null) SEPARATOR ", ") AS guest_code, ticket_id')
        )
            ->ByStatus()
            ->where('email', auth()->user()->email)
            ->groupBy('event_id')
            ->get();

        $event_guest_ids =[];
        $data['guest_ticket'] = [];
        foreach ($data['event_guest'] as $guest) {
            if ($guest->total_ticket > 0) {
                $getEvent = Event::find($guest->event_id);
                array_push($event_guest_ids, $getEvent->event_code);
                $getGuestTicketNumber = $guest->total_ticket;
                $data['guest_ticket'] += [
                    $getEvent->event_code => [
                        'ticket_number' => $getGuestTicketNumber,
                        'guest_code' => $guest->guest_code
                    ]
                ];
            }
        }

        $data['events'] = Event::select(
            \DB::raw('ts.title, MIN(tickets.price) as min_price, MAX(tickets.price) as max_price, 
            events.id, events.short_desc, events.long_desc, events.status, events.event_code, events.event_date, 
            events.event_end_date, events.start_time, events.end_time, events.name, events.address, events.created_at,
             u.first_name, u.middle_name, u.last_name, u.profile_image, media.content as event_image, 
            (select CONCAT_WS(" ", su.first_name, su.middle_name, su.last_name) as creator_name from users as su where su.id = events.created_by) as creator_name')
        )
            ->join('role_users_details as u', 'u.id', '=', 'events.promoter_id')
            ->join('event_media as em', 'em.event_id', '=', 'events.id')
            ->join('media', 'media.id', '=', 'em.media_id')
            ->leftJoin('ticket_section as ts', 'ts.event_id', '=', 'events.id')
            ->leftJoin('tickets', 'tickets.ticket_section_id', '=', 'ts.id')
            ->where('em.used_for', 'banner_image')
            ->whereIn('events.event_code', $event_ids)
            ->orWhere(function ($query) use ($event_guest_ids) {
                $query->whereIn('events.event_code', $event_guest_ids);
            })
            ->eventComplete()
            ->published()
            ->groupBy('events.id')
            ->orderBy('events.event_date', 'desc')
            ->get();

        return view(parent::loadDefaultVars($this->view_path.'.index', [
            'show_ticket_user_menu' => true,
        ]), compact('data'));
    }

    public function tickets($event_code)
    {
        $event_code = AppHelper::getEventCodeFromSeoUrl($event_code);
        $email = auth()->user()->email;

        $data['event'] = Event::where('event_code', $event_code)->NotExpired()->first();

        if (!$data['event']) {
            Flash::warning(trans($this->trans_path . 'general.error.no-ticket'));
            return redirect()->back();
        }

        $transaction = TransactionModel::where('email', $email)
            ->ByStatus()
            ->where('event_id', $data['event']->id)
            ->get();


        // check If ticket exist
        if (count($data['event']) < 1 || count($transaction) < 1) {
            Flash::warning(trans($this->trans_path . 'general.error.no-ticket'));
            return redirect()->back();
        }

        $options = [
            'format' => 'A4',
            'orientation' => 'portrait',
            'quality' => 100
        ];

        $conv = new Converter();

        $view = '';
        $count = 0;

        foreach($transaction as $trans) {
            $data['ticket_detail'] = $trans->ticket;
            $data['transaction'] = $trans;
            $data['transaction_fee'] = unserialize($trans->service_fee);
            
            foreach ($trans->ticketOrder as $ticket) {
                $view = $view . view($this->loadDefaultVars($this->view_path . '.partials.ticket2'), compact('data', 'ticket'))->render();
                // add 4 tickets to each pdf page.
                if (++$count == 4) {
                    $conv->addPage($view);
                    $view = '';
                    $count = 0;
                }
            }
        }

        // check if tickets exist to display
        if ($view != '')
            $conv->addPage($view);

        return $conv->toPdf($options)->serve();
    }

}

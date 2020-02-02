<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use App\Events\Event;

class OrderEvent implements ShouldBroadcastNow, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use Queueable;

    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PresenceChannel('public');
    }

    public function broadcastAs()
    {
        return 'market_order';
    }

    public function broadcastWith()
    {
        return [
            'data' => $this->data
        ];
    }
}

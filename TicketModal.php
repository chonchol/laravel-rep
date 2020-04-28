<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketModal extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'event_id', 'ticket_code', 'name', 'price', 'quantity_available', 'quantity_sold', 'sales_start_date', 'sales_end_date', 'valid_from_date', 'valid_to_date',
        'created_by', 'updated_by', 'created_at', 'updated_at', 'message', 'status', 'max_per_order', 'absorb_fees', 'sell_in_pos', 'ticket_section_id', 'sort_order'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function getGroupIdAttribute()
    {
        return $this->event_id.'-'.$this->id;
    }

    public function ticketSection()
    {
        return $this->belongsTo('App\Models\TicketSectionModal', 'ticket_section_id');
    }
    public function transaction()
    {
        return $this->hasMany('App\Models\TransactionModel', 'ticket_id');
    }

    public function guest()
    {
        return $this->hasMany('App\Models\EventGuestListModel', 'ticket_id');
    }

    public function discount()
    {
        return $this->hasOne('App\Models\DiscountDetail', 'ticket_id');
    }

    public function event()
    {
        return $this->belongsTo('App\Models\Event', 'event_id');
    }

    public function setTicketSectionIdAttribute($value)
    {
        $this->attributes['ticket_section_id'] = (int) $value;
    }

    public function setPriceAttribute($value)
    {
        $this->attributes['price'] = floatval($value);
    }

    public function setSalesStartDateAttribute($value)
    {

        $this->attributes['sales_start_date'] = strtotime($value);
    }
    public function getSalesStartDateAttribute($value)
    {
        return date("m/d/Y h:i A", $value);
    }

    public function setSalesEndDateAttribute($value)
    {
        $this->attributes['sales_end_date'] = strtotime($value);
    }

    public function getSalesEndDateAttribute($value)
    {
        return date("m/d/Y h:i A", $value);
    }

    public function setValidFromDateAttribute($value)
    {

        $this->attributes['valid_from_date'] = strtotime($value);
    }
    public function getValidFromDateAttribute($value)
    {
        return date("m/d/Y h:i A", $value);
    }

    public function setValidToDateAttribute($value)
    {
        $this->attributes['valid_to_date'] = strtotime($value);
    }

    public function getValidToDateAttribute($value)
    {
        return date("m/d/Y h:i A", $value);
    }

    public function isActive()
    {
        return $this->status == 1 ? true : false;
    }

}
